<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Web;

use MaintenancePro\Application\ServiceContainer;

/**
 * A simple router for handling HTTP requests.
 *
 * This router matches the request method and path to a registered handler
 * and dispatches the request to the appropriate controller method.
 */
class Router
{
    private array $routes = [];
    private ServiceContainer $container;

    /**
     * Router constructor.
     *
     * @param ServiceContainer $container The service container for resolving controller dependencies.
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Adds a new route to the routing table.
     *
     * @param string $method  The HTTP method (e.g., 'GET', 'POST').
     * @param string $path    The request path.
     * @param array  $handler An array containing the controller class and method name.
     */
    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    /**
     * Dispatches the current request to the appropriate handler.
     *
     * If no route matches, it sends a 404 Not Found response.
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['path'] === $requestPath) {
                $controllerClass = $route['handler'][0];
                $method = $route['handler'][1];

                $controller = $this->container->get($controllerClass);
                $response = $controller->$method();

                if (is_string($response)) {
                    echo $response;
                }
                return;
            }
        }

        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
    }
}