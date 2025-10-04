<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

use MaintenancePro\Application\Event\EventDispatcher;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\AnalyticsService;
use MaintenancePro\Application\Service\AnalyticsServiceInterface;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Application\Service\SecurityService;
use MaintenancePro\Application\Service\SecurityServiceInterface;
use MaintenancePro\Domain\Repository\RepositoryInterface;
use MaintenancePro\Domain\Strategy\DefaultMaintenanceStrategy;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Infrastructure\Cache\CacheInterface;
use MaintenancePro\Infrastructure\Cache\FileSystemCache;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;
use MaintenancePro\Infrastructure\Config\JsonConfigurationManager;
use MaintenancePro\Infrastructure\Logger\FileLogger;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;
use MaintenancePro\Infrastructure\Repository\AnalyticsEventRepository;
use MaintenancePro\Infrastructure\Service\MetricsService;
use MaintenancePro\Application\Service\MetricsServiceInterface;
use MaintenancePro\Presentation\Template\BasicTemplateRenderer;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;

class Kernel
{
    private ServiceContainer $container;
    private ConfigurationManagerInterface $config;
    private LoggerInterface $logger;

    public function __construct(string $rootPath)
    {
        $this->container = new ServiceContainer();
        $this->setupPaths($rootPath);
        $this->registerServices();
        $this->initialize();
    }

    private function setupPaths(string $rootPath): void
    {
        $paths = [
            'root' => $rootPath,
            'config' => $rootPath . '/config',
            'cache' => $rootPath . '/var/cache',
            'logs' => $rootPath . '/var/log',
            'templates' => $rootPath . '/templates',
            'storage' => $rootPath . '/var/storage',
        ];

        foreach (['cache', 'logs', 'storage'] as $dir) {
            $path = $paths[$dir];
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        $this->container->instance('paths', $paths);
    }

    private function registerServices(): void
    {
        $paths = $this->container->get('paths');

        $this->container->singleton(ConfigurationManagerInterface::class, function($c) use ($paths) {
            $configPath = $paths['config'] . '/config.json';
            $config = new JsonConfigurationManager($configPath);
            $config->set('system.config_path', $configPath);
            $config->set('system.paths', $paths);
            return $config;
        });

        $this->container->singleton(LoggerInterface::class, function($c) use ($paths) {
            return new FileLogger($paths['logs'] . '/app.log');
        });

        $this->container->singleton(CacheInterface::class, function($c) use ($paths) {
            return new FileSystemCache(
                $paths['cache'],
                $c->get(MetricsServiceInterface::class)
            );
        });

        $this->container->singleton(EventDispatcherInterface::class, function($c) {
            return new EventDispatcher($c->get(LoggerInterface::class));
        });

        $this->container->singleton(TemplateRendererInterface::class, function($c) use ($paths) {
            return new BasicTemplateRenderer($paths['templates']);
        });

        $this->container->singleton(\PDO::class, function($c) use ($paths) {
            $storagePath = $paths['storage'] . '/database.sqlite';
            $pdo = new \PDO('sqlite:' . $storagePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        });

        $this->container->singleton(RepositoryInterface::class, function($c) {
            return new AnalyticsEventRepository($c->get(\PDO::class), $c->get(LoggerInterface::class));
        });

        $this->container->singleton(AccessControlService::class, function($c) {
            return new AccessControlService(
                $c->get(ConfigurationManagerInterface::class),
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class)
            );
        });

        $this->container->singleton(AnalyticsServiceInterface::class, function($c) {
            return new AnalyticsService(
                $c->get(RepositoryInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(CacheInterface::class)
            );
        });

        $this->container->singleton(SecurityServiceInterface::class, function($c) {
            return new SecurityService(
                $c->get(ConfigurationManagerInterface::class),
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(EventDispatcherInterface::class)
            );
        });

        $this->container->singleton(MaintenanceStrategyInterface::class, function($c) {
            $config = $c->get(ConfigurationManagerInterface::class);
            if ($config->get('maintenance.strategy') === 'intelligent') {
                return new IntelligentMaintenanceStrategy(
                    $config,
                    $c->get(AccessControlService::class),
                    $c->get(MetricsServiceInterface::class)
                );
            }

            return new DefaultMaintenanceStrategy(
                $config,
                $c->get(AccessControlService::class)
            );
        });

        $this->container->singleton(MaintenanceService::class, function($c) {
            return new MaintenanceService(
                $c->get(ConfigurationManagerInterface::class),
                $c->get(EventDispatcherInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(MaintenanceStrategyInterface::class)
            );
        });

        $this->container->singleton(MetricsServiceInterface::class, function($c) {
            return new MetricsService($c->get(\PDO::class));
        });
    }

    private function initialize(): void
    {
        $this->config = $this->container->get(ConfigurationManagerInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);

        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function run(): void
    {
        $startTime = microtime(true);
        $metrics = $this->container->get(MetricsServiceInterface::class);
        $metrics->increment('request.count');

        $this->logger->info('Application started.');

        $maintenanceService = $this->container->get(MaintenanceService::class);
        $context = [
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'access_key' => $_GET['access_key'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        if ($maintenanceService->shouldBlock($context)) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 3600');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            $renderer = $this->container->get(TemplateRendererInterface::class);
            $config = $this->container->get(ConfigurationManagerInterface::class);

            echo $renderer->render('maintenance.phtml', [
                'title' => $config->get('maintenance.title', 'Site Under Maintenance'),
                'message' => $config->get('maintenance.message', 'We are currently performing scheduled maintenance. We should be back online shortly.')
            ]);
            $metrics->timing('request.time', (microtime(true) - $startTime) * 1000);
            exit;
        }

        echo "Application is running.";
        $this->logger->info('Application finished.');
        $metrics->timing('request.time', (microtime(true) - $startTime) * 1000);
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (!(error_reporting() & $errno)) {
            return;
        }
        $this->logger->error("Error: [$errno] $errstr in $errfile on line $errline");
    }

    public function handleException(\Throwable $e): void
    {
        $this->logger->critical(
            "Uncaught Exception: " . $e->getMessage(),
            ['exception' => $e]
        );
        http_response_code(500);
        echo "<h1>Internal Server Error</h1>";
        exit;
    }

    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }
}