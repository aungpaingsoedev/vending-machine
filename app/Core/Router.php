<?php

declare (strict_types = 1);

namespace App\Core;

use App\Attributes\Route;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(
        string $method,
        string $path,
        callable $handler
    ): void {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $requestPath = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                static fn(array $matches): string =>
                '(?P<' . $matches[1] . '>[^/]+)',
                $route['path']
            );

            $pattern = '#^' . $pattern . '$#';

            if (! preg_match($pattern, $requestPath, $matches)) {
                continue;
            }

            $parameters = array_filter(
                $matches,
                static fn(string | int $key): bool =>
                is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $parameters = array_map(
                'urldecode',
                $parameters
            );

            $route['handler'](...$parameters);

            return;
        }

        http_response_code(404);

        echo '404 - Page not found';
    }

    public function registerController(
        object $controller
    ): void {
        $reflection = new ReflectionClass($controller);

        $methods = $reflection->getMethods(
            ReflectionMethod::IS_PUBLIC
        );

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(
                Route::class
            );

            foreach ($attributes as $attribute) {
                $route = $attribute->newInstance();

                foreach ($route->methods as $httpMethod) {
                    $httpMethod = strtoupper($httpMethod);

                    if (! in_array(
                        $httpMethod,
                        ['GET', 'POST', 'PUT', 'DELETE'],
                        true
                    )) {
                        throw new InvalidArgumentException(
                            "Unsupported HTTP method: {$httpMethod}"
                        );
                    }

                    $this->addRoute(
                        $httpMethod,
                        $route->path,
                        [
                            $controller,
                            $method->getName(),
                        ]
                    );
                }
            }
        }
    }
}
