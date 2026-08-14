<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Regex-free router with {param} placeholders and per-route middleware.
 * Middleware forms: 'auth', 'guest', 'can:permission.slug', 'role:admin|supervisor'
 */
final class Router
{
    /** @var array<string,array<int,array{pattern:string,handler:array,middleware:array}>> */
    private array $routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => []];

    public function get(string $p, array $h, array $m = []): void    { $this->add('GET', $p, $h, $m); }
    public function post(string $p, array $h, array $m = []): void   { $this->add('POST', $p, $h, $m); }
    public function put(string $p, array $h, array $m = []): void    { $this->add('PUT', $p, $h, $m); }
    public function delete(string $p, array $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $this->routes[$method][] = [
            'pattern'    => '/' . trim($pattern, '/'),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path   = Request::path();

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }
            if ($method !== 'GET') {
                Csrf::verify();
            }
            foreach ($route['middleware'] as $rule) {
                Middleware::apply($rule);
            }
            [$class, $action] = $route['handler'];
            $fqcn = 'App\\Controllers\\' . $class;
            if (!class_exists($fqcn) || !method_exists($fqcn, $action)) {
                $this->fail(500, 'Route handler ' . $class . '::' . $action . ' does not exist.');
                return;
            }
            (new $fqcn())->{$action}(...array_values($params));
            return;
        }

        $this->fail(404, 'The page you requested could not be found.');
    }

    /** @return array<string,string>|null */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }
        $pSeg = explode('/', trim($pattern, '/'));
        $uSeg = explode('/', trim($path, '/'));
        if (count($pSeg) !== count($uSeg)) {
            return null;
        }
        $params = [];
        foreach ($pSeg as $i => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                if ($uSeg[$i] === '') {
                    return null;
                }
                $params[trim($segment, '{}')] = $uSeg[$i];
                continue;
            }
            if ($segment !== $uSeg[$i]) {
                return null;
            }
        }
        return $params;
    }

    private function fail(int $code, string $message): void
    {
        http_response_code($code);
        if (Request::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
            return;
        }
        View::render('errors/error', ['code' => $code, 'message' => $message], 'public');
    }
}
