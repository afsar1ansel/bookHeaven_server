<?php

namespace Core;

class Router {
    private array $routes = [];

    public function get(string $path, array|callable $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array|callable $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array|callable $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array|callable $handler): void {
        // Simple regex conversion for parameters like {id}
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle subdirectory if any (strips project folder from URI)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
            $uri = substr($uri, strlen($scriptName));
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$controllerName, $action] = $route['handler'];
                
                if (is_callable($route['handler'])) {
                    call_user_func($route['handler'], ...$params);
                } else {
                    $controller = new $controllerName();
                    $controller->$action(...$params);
                }
                return;
            }
        }

        Response::error("Route not found: " . $method . " " . $uri, 404);
    }
}
