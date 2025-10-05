<?php
declare(strict_types=1);

namespace MaintenancePro\Application;

use MaintenancePro\Application\Event\EventDispatcherInterface;
use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Application\Provider\AppServiceProvider;
use MaintenancePro\Application\Provider\CacheServiceProvider;
use MaintenancePro\Application\Provider\CircuitBreakerServiceProvider;
use MaintenancePro\Application\Provider\ConfigurationServiceProvider;
use MaintenancePro\Application\Provider\ControllerServiceProvider;
use MaintenancePro\Application\Provider\DatabaseServiceProvider;
use MaintenancePro\Application\Provider\EventServiceProvider;
use MaintenancePro\Application\Provider\HealthCheckServiceProvider;
use MaintenancePro\Application\Provider\LogServiceProvider;
use MaintenancePro\Application\Provider\MetricsServiceProvider;
use MaintenancePro\Application\Provider\TemplateServiceProvider;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Presentation\Web\Controller\AdminController;
use MaintenancePro\Presentation\Web\Router;

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
     * @var array<class-string>
     */
    protected array $serviceProviders = [
        LogServiceProvider::class,
        ConfigurationServiceProvider::class,
        CacheServiceProvider::class,
        EventServiceProvider::class,
        TemplateServiceProvider::class,
        DatabaseServiceProvider::class,
        AppServiceProvider::class,
        MetricsServiceProvider::class,
        ControllerServiceProvider::class,
        HealthCheckServiceProvider::class,
        CircuitBreakerServiceProvider::class,
    ];

    /**
     * @param string $rootPath The absolute path to the project root.
     */
    public function __construct(string $rootPath)
    {
        $this->container = new ServiceContainer();
        $this->setupPaths($rootPath);
        $this->registerServiceProviders();
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
    private function registerServiceProviders(): void
    {
        foreach ($this->serviceProviders as $providerClass) {
            /** @var \MaintenancePro\Application\Provider\ServiceProviderInterface $provider */
            $provider = new $providerClass();
            $provider->register($this->container);
        }
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

        if ($this->config->get('app.debug', false)) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            $this->renderDebugException($e);
        } else {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            $renderer = $this->container->get(TemplateRendererInterface::class);
            echo $renderer->render('error.phtml', [
                'title' => 'Internal Server Error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ]);
        }

        exit;
    }

    private function renderDebugException(\Throwable $e): void
    {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Unhandled Exception</title>";
        echo "<style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 20px; } h1 { color: #b90000; } .trace { background-color: #f0f0f0; padding: 10px; border-radius: 5px; }</style>";
        echo "</head><body>";
        echo "<h1>Unhandled Exception</h1>";
        echo "<h2>" . htmlspecialchars($e->getMessage()) . "</h2>";
        echo "<p><strong>Type:</strong> " . get_class($e) . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre class='trace'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</body></html>";
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