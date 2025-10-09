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
use MaintenancePro\Application\Provider\ApiServiceProvider;
use MaintenancePro\Application\Provider\MachineLearningServiceProvider;
use MaintenancePro\Application\Provider\MetricsServiceProvider;
use MaintenancePro\Application\Provider\TemplateServiceProvider;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\Contracts\MaintenanceServiceInterface;
use MaintenancePro\Domain\Contracts\MetricsInterface;
use MaintenancePro\Domain\Exceptions\ValidationException;
use MaintenancePro\Presentation\Api\Controller\ApiController;
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
        MachineLearningServiceProvider::class,
        ApiServiceProvider::class,
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

        if (str_starts_with($requestPath, '/api')) {
            $this->runApi();
        } elseif (str_starts_with($requestPath, '/admin')) {
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
        $maintenanceService = $this->container->get(MaintenanceServiceInterface::class);
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

            $renderer = $this->container->get(\MaintenancePro\Presentation\Template\TemplateRendererInterface::class);
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

        $router->add('GET', '/admin/login', [AdminController::class, 'showLoginForm']);
        $router->add('POST', '/admin/login', [AdminController::class, 'login']);
        $router->add('GET', '/admin/logout', [AdminController::class, 'logout']);
        $router->add('GET', '/admin/2fa', [AdminController::class, 'showTwoFactorForm']);
        $router->add('POST', '/admin/2fa', [AdminController::class, 'verifyTwoFactor']);

        $router->add('GET', '/admin', [AdminController::class, 'index']);
        $router->add('POST', '/admin/maintenance/enable', [AdminController::class, 'enableMaintenance']);
        $router->add('POST', '/admin/maintenance/disable', [AdminController::class, 'disableMaintenance']);
        $router->add('POST', '/admin/whitelist/add', [AdminController::class, 'addWhitelistIp']);
        $router->add('POST', '/admin/whitelist/remove', [AdminController::class, 'removeWhitelistIp']);

        $response = $router->dispatch();
        if ($response === null) {
            http_response_code(404);
            echo "<h1>404 Not Found</h1>";
        } elseif (is_string($response)) {
            echo $response;
        }
    }

    /**
     * Handles an API-facing request.
     */
    private function runApi(): void
    {
        // Basic CORS and security headers
        header("Access-Control-Allow-Origin: *"); // In production, lock this down to the frontend URL
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");
        header("Content-Security-Policy: default-src 'self'");
        header("X-Content-Type-Options: nosniff");

        // Handle pre-flight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // TODO: Implement API Key authentication
        // $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        // if (!$this->config->get('app.api_key') || $apiKey !== $this->config->get('app.api_key')) {
        //     $this->sendJsonResponse(['error' => 'Unauthorized'], 401);
        //     return;
        // }

        $router = new Router($this->container);

        // Maintenance
        $router->add('GET', '/api/maintenance/status', [ApiController::class, 'getMaintenanceStatus']);
        $router->add('POST', '/api/maintenance/enable', [ApiController::class, 'enableMaintenance']);
        $router->add('POST', '/api/maintenance/disable', [ApiController::class, 'disableMaintenance']);

        // IP Whitelist
        $router->add('POST', '/api/whitelist/add', [ApiController::class, 'addIpToWhitelist']);
        $router->add('POST', '/api/whitelist/remove', [ApiController::class, 'removeIpFromWhitelist']);

        // Health Checks
        $router->add('GET', '/api/health/check', [ApiController::class, 'getHealthCheck']);

        // Circuit Breakers
        $router->add('GET', '/api/circuit-breakers', [ApiController::class, 'getCircuitBreakers']);
        $router->add('POST', '/api/circuit-breakers/:service/reset', [ApiController::class, 'resetCircuitBreaker']);

        // Metrics
        $router->add('GET', '/api/metrics', [ApiController::class, 'getMetrics']);
        $router->add('GET', '/api/metrics/report', [ApiController::class, 'getMetricsReport']);

        try {
            $response = $router->dispatch();
            if ($response === null) {
                $this->sendJsonResponse(['error' => 'Not Found'], 404);
            } else {
                $this->sendJsonResponse($response);
            }
        } catch (ValidationException $e) {
            $this->sendJsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            // If in testing environment, re-throw to get a full stack trace in PHPUnit
            if (getenv('APP_ENV') === 'testing') {
                throw $e;
            }

            // Log the detailed error for debugging
            $this->logger->critical("API Exception: " . $e->getMessage(), ['exception' => $e]);

            // Send a generic error to the client
            $this->sendJsonResponse(['error' => 'An internal server error occurred.'], 500);
        }
    }

    /**
     * Sends a response in JSON format.
     *
     * @param mixed $data The data to encode.
     * @param int $statusCode The HTTP status code.
     */
    private function sendJsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
    }

    /**
     * Custom error handler. Converts PHP errors into log messages.
     *
     * This method respects the `error_reporting` level.
     *
     * @param int    $errno   The level of the error raised.
     * @param string $errstr  The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int    $errline The line number the error was raised at.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if (!(error_reporting() & $errno)) {
            return;
        }
        $this->logger->error("Error: [$errno] $errstr in $errfile on line $errline");
    }

    /**
     * Custom exception handler. Logs the exception and displays an error page.
     *
     * In debug mode, it shows a detailed exception page. Otherwise, it shows
     * a generic error page.
     *
     * @param \Throwable $e The exception that was thrown.
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
            $renderer = $this->container->get(\MaintenancePro\Presentation\Template\TemplateRendererInterface::class);
            echo $renderer->render('error.phtml', [
                'title' => 'Internal Server Error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ]);
        }

        exit;
    }

    /**
     * Renders a simple but detailed HTML page for debugging an exception.
     *
     * @param \Throwable $e The exception to render.
     */
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