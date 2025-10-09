<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Web;

use MaintenancePro\Application\ServiceContainer;

/**
 * A simple router for handling HTTP requests.
 *
 * This router matches the request method and path to a registered handler,
 * supporting dynamic URL parameters (e.g., /users/:id). It dispatches the
 * request to the appropriate controller method, passing extracted parameters
 * as arguments.
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
     * It matches the request against registered routes, extracts URL parameters,
     * and calls the corresponding controller method with those parameters.
     *
     * @return mixed The response from the handler, or a string for a 404 error.
     */
    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        foreach ($this->routes as $route) {
            // Convert route path to a regex pattern
            $routePattern = preg_replace('/:(\w+)/', '(?<$1>[^/]+)', $route['path']);
            $regex = '~^' . $routePattern . '$~';

            // Check if the route matches the request
            if ($route['method'] === $requestMethod && preg_match($regex, $requestPath, $matches)) {
                // Filter out numeric keys to get only named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                [$controllerClass, $method] = $route['handler'];

                $controller = $this->container->get($controllerClass);

                // Call the handler with the extracted parameters
                return $controller->$method(...array_values($params));
            }
        }

        // No route matched
        return null;
    }
}