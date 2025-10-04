<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

use MaintenancePro\Application\Event\EventDispatcher;
use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\Service\AccessControlService;
use MaintenancePro\Application\Service\MaintenanceService;
use MaintenancePro\Application\Service\SecurityService;
use MaintenancePro\Application\Service\SecurityServiceInterface;
use MaintenancePro\Domain\Strategy\DefaultMaintenanceStrategy;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Infrastructure\Cache\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Infrastructure\Cache\FileSystemCache;
use MaintenancePro\Infrastructure\Configuration\JsonConfiguration;
use MaintenancePro\Infrastructure\Logger\MonologLogger;
use MaintenancePro\Infrastructure\Metrics\BufferedMetricsService;
use MaintenancePro\Infrastructure\Repository\AnalyticsEventRepository;
use MaintenancePro\Presentation\Template\BasicTemplateRenderer;
use MaintenancePro\Presentation\Template\TemplateRendererInterface;
use MaintenancePro\Presentation\Web\Controller\AdminController;
use MaintenancePro\Presentation\Web\Router;
use MaintenancePro\Infrastructure\CircuitBreaker\CircuitBreakerInterface;
use MaintenancePro\Infrastructure\CircuitBreaker\CacheableCircuitBreaker;
use MaintenancePro\Infrastructure\Health\HealthCheckAggregator;
use MaintenancePro\Infrastructure\Health\DatabaseHealthCheck;
use MaintenancePro\Infrastructure\Health\CacheHealthCheck;
use MaintenancePro\Infrastructure\Health\DiskSpaceHealthCheck;
use MaintenancePro\Infrastructure\Service\Mock\MockExternalService;

class Kernel
{
    private ServiceContainer $container;
    private ConfigurationInterface $config;
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

        $this->container->singleton(ConfigurationInterface::class, function ($c) use ($paths) {
            $configPath = $paths['config'] . '/config.json';
            $schema = [
                'maintenance.enabled' => ['type' => 'boolean', 'required' => true],
                'security.rate_limiting.max_requests' => ['type' => 'integer']
            ];
            return new JsonConfiguration($configPath, $schema);
        });

        $this->container->singleton(LoggerInterface::class, function($c) use ($paths) {
            return new MonologLogger($paths['logs'] . '/app.log');
        });

        $this->container->singleton(CacheInterface::class, function($c) use ($paths) {
            return new FileSystemCache(
                $paths['cache'],
                $c->get(MetricsInterface::class)
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

        $this->container->singleton(AccessControlService::class, function($c) {
            return new AccessControlService(
                $c->get(ConfigurationInterface::class),
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class)
            );
        });

        $this->container->singleton(SecurityServiceInterface::class, function($c) {
            return new SecurityService(
                $c->get(ConfigurationInterface::class),
                $c->get(CacheInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(EventDispatcherInterface::class)
            );
        });

        $this->container->singleton(MaintenanceStrategyInterface::class, function($c) {
            $config = $c->get(ConfigurationInterface::class);
            if ($config->get('maintenance.strategy') === 'intelligent') {
                return new IntelligentMaintenanceStrategy(
                    $config,
                    $c->get(AccessControlService::class),
                    $c->get(MetricsInterface::class)
                );
            }

            return new DefaultMaintenanceStrategy(
                $config,
                $c->get(AccessControlService::class)
            );
        });

        $this->container->singleton(MaintenanceService::class, function($c) {
            return new MaintenanceService(
                $c->get(ConfigurationInterface::class),
                $c->get(EventDispatcherInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(MaintenanceStrategyInterface::class)
            );
        });

        $this->container->singleton(MetricsInterface::class, function ($c) {
            return new BufferedMetricsService($c->get(CacheInterface::class));
        });

        $this->container->singleton(AdminController::class, function($c) {
            return new AdminController(
                $c->get(TemplateRendererInterface::class),
                $c->get(MaintenanceService::class),
                $c->get(AccessControlService::class),
                $c->get(MetricsInterface::class),
                $c->get(ConfigurationInterface::class),
                $c->get(HealthCheckAggregator::class),
                $c->get(CircuitBreakerInterface::class)
            );
        });

        $this->container->singleton(CircuitBreakerInterface::class, function ($c) {
            return new CacheableCircuitBreaker($c->get(CacheInterface::class));
        });

        $this->container->singleton(HealthCheckAggregator::class, function ($c) use ($paths) {
            $aggregator = new HealthCheckAggregator();
            $aggregator->addCheck(new DatabaseHealthCheck($c->get(\PDO::class)));
            $aggregator->addCheck(new CacheHealthCheck($c->get(CacheInterface::class)));
            $aggregator->addCheck(new DiskSpaceHealthCheck($paths['storage']));
            return $aggregator;
        });

        $this->container->singleton(MockExternalService::class, function ($c) use ($paths) {
            return new MockExternalService($paths['storage']);
        });
    }

    private function initialize(): void
    {
        $this->config = $this->container->get(ConfigurationInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);

        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function run(): void
    {
        $startTime = microtime(true);
        /** @var MetricsInterface $metrics */
        $metrics = $this->container->get(MetricsInterface::class);
        $metrics->increment('request.count');

        $this->logger->info('Application started.');

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if (str_starts_with($requestPath, '/admin')) {
            $this->runAdmin();
        } else {
            $this->runPublic();
        }

        $metrics->timing('request.time', (microtime(true) - $startTime) * 1000);
    }

    private function runPublic(): void
    {
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
            return;
        }

        echo "Application is running.";
        $this->logger->info('Application finished.');
    }

    private function runAdmin(): void
    {
        $router = new Router($this->container);

        $router->add('GET', '/admin', [AdminController::class, 'index']);
        $router->add('POST', '/admin/maintenance/enable', [AdminController::class, 'enableMaintenance']);
        $router->add('POST', '/admin/maintenance/disable', [AdminController::class, 'disableMaintenance']);
        $router->add('POST', '/admin/whitelist/add', [AdminController::class, 'addWhitelistIp']);
        $router->add('POST', '/admin/whitelist/remove', [AdminController::class, 'removeWhitelistIp']);

        $router->dispatch();
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