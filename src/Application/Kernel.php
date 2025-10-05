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
use MaintenancePro\Domain\Strategy\IntelligentMaintenanceStrategy;
use MaintenancePro\Domain\Strategy\MaintenanceStrategyInterface;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Infrastructure\Cache\AdaptiveCache;
use MaintenancePro\Infrastructure\Cache\FileCache;
use MaintenancePro\Infrastructure\Configuration\JsonConfiguration;
use MaintenancePro\Infrastructure\Logger\MonologLogger;
use MaintenancePro\Infrastructure\Metrics\BufferedMetricsService;
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

/**
 * The core of the application.
 *
 * The Kernel is responsible for bootstrapping the application, setting up paths,
 * registering services in the dependency injection container, and handling the
 * incoming request by dispatching it to the appropriate controller or service.
 */
class Kernel
{
    /** @var ServiceContainer The dependency injection container. */
    private ServiceContainer $container;

    /** @var ConfigurationInterface The application's configuration manager. */
    private ConfigurationInterface $config;

    /** @var LoggerInterface The application's logger. */
    private LoggerInterface $logger;

    /**
     * @param string $rootPath The absolute path to the project root.
     */
    public function __construct(string $rootPath)
    {
        $this->container = new ServiceContainer();
        $this->setupPaths($rootPath);
        $this->registerServices();
        $this->initialize();
    }

    /**
     * Sets up the essential directory paths for the application.
     *
     * @param string $rootPath The project root path.
     */
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

        foreach (['config', 'cache', 'logs', 'storage'] as $dir) {
            $path = $paths[$dir];
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        $this->container->instance('paths', $paths);
    }

    /**
     * Registers all application services in the dependency injection container.
     */
    private function registerServices(): void
    {
        $paths = $this->container->get('paths');

        // Logger must be registered first so it's available for other services.
        $this->container->singleton(LoggerInterface::class, function($c) use ($paths) {
            return new MonologLogger($paths['logs'] . '/app.log');
        });

        $this->container->singleton(ConfigurationInterface::class, function ($c) use ($paths) {
            $configPath = $paths['config'] . '/config.json';
            if (!file_exists($configPath)) {
                $defaultConfig = [
                    'maintenance.enabled' => false,
                    'maintenance.title' => 'Site Under Maintenance',
                    'maintenance.message' => 'We are currently performing scheduled maintenance. We should be back online shortly.',
                    'maintenance.allowed_ips' => [],
                    'maintenance.strategy' => 'default',
                    'maintenance.intelligent' => [
                        'error_rate_threshold' => 5.0,
                        'response_time_threshold' => 1000,
                    ],
                    'app.timezone' => 'UTC',
                    'security.rate_limiting.max_requests' => 100,
                    'security.rate_limiting.time_window' => 60,
                ];
                file_put_contents($configPath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
            }
            $schema = [
                'maintenance.enabled' => ['type' => 'boolean', 'required' => true],
                'security.rate_limiting.max_requests' => ['type' => 'integer']
            ];

            try {
                return new JsonConfiguration($configPath, $schema);
            } catch (\Exception $e) {
                /** @var LoggerInterface $logger */
                $logger = $c->get(LoggerInterface::class);
                $logger->critical('Failed to load or validate configuration.', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Application could not be initialized due to a configuration error: ' . $e->getMessage(), 0, $e);
            }
        });

        $this->container->singleton(CacheInterface::class, function($c) use ($paths) {
            $fileCache = new FileCache($paths['cache']);
            return new AdaptiveCache($fileCache);
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

        $this->container->singleton(MaintenanceStrategyInterface::class, function ($c) {
            $config = $c->get(ConfigurationInterface::class);
            if ($config->get('maintenance.strategy') === 'intelligent') {
                return new IntelligentMaintenanceStrategy(
                    $config,
                    $c->get(MetricsInterface::class),
                    $c->get(HealthCheckAggregator::class)
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
    }

    /**
     * Initializes core application settings after services are registered.
     */
    private function initialize(): void
    {
        $this->config = $this->container->get(ConfigurationInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);

        date_default_timezone_set($this->config->get('app.timezone', 'UTC'));
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * The main entry point for the application.
     * Handles the incoming request and dispatches it.
     */
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
        $metrics->flush();
    }

    /**
     * Handles a public-facing request.
     */
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
            $config = $this->container->get(ConfigurationInterface::class);

            echo $renderer->render('maintenance.phtml', [
                'title' => $config->get('maintenance.title', 'Site Under Maintenance'),
                'message' => $config->get('maintenance.message', 'We are currently performing scheduled maintenance. We should be back online shortly.')
            ]);
            return;
        }

        echo "Application is running.";
        $this->logger->info('Application finished.');
    }

    /**
     * Handles an admin-facing request.
     */
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

    /**
     * Custom error handler.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (!(error_reporting() & $errno)) {
            return;
        }
        $this->logger->error("Error: [$errno] $errstr in $errfile on line $errline");
    }

    /**
     * Custom exception handler.
     */
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

    /**
     * Gets the service container.
     *
     * @return ServiceContainer
     */
    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    /**
     * Gets the configuration service.
     *
     * @return ConfigurationInterface
     */
    public function getConfig(): ConfigurationInterface
    {
        return $this->container->get(ConfigurationInterface::class);
    }

    /**
     * Gets the cache service.
     *
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->container->get(CacheInterface::class);
    }

    /**
     * Gets the metrics service.
     *
     * @return MetricsInterface
     */
    public function getMetrics(): MetricsInterface
    {
        return $this->container->get(MetricsInterface::class);
    }
}