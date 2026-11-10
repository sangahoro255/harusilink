<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = str_replace('{token}', '([a-f0-9]{32})', $path);
        $pattern = str_replace('{slug}', '([a-z0-9][a-z0-9\-]{2,80})', $pattern);
        $pattern = preg_replace('#\{[a-z_]+\}#', '(\d+)', $pattern);
        $this->routes[$method][] = ['#^' . $pattern . '$#', $handler];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = request_path();

        foreach ($this->routes[$method] ?? [] as [$regex, $handler]) {
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $handler;
                (new $class())->$action(...$matches);
                return;
            }
        }

        http_response_code(404);
        echo e(t('err.not_found'));
    }
}