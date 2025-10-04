<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Web;

use MaintenancePro\Application\ServiceContainer;

class Router
{
    private array $routes = [];
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

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