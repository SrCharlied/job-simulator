<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/ResourceController.php';

function dispatchApiRequest(string $method, string $uri): void
{
    $controller = new ResourceController();
    $path = normalizeRequestPath($uri);
    $allowedMethods = [];

    $routes = [
        [
            'methods' => ['GET', 'POST'],
            'pattern' => '#^/resources$#',
            'handler' => static function () use ($controller, $method): void {
                $controller->handle($method);
            },
        ],
        [
            'methods' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'pattern' => '#^/resources/(?P<id>\d+)$#',
            'handler' => static function (array $matches) use ($controller, $method): void {
                $controller->handle($method, (int) $matches['id']);
            },
        ],
    ];

    foreach ($routes as $route) {
        if (!preg_match($route['pattern'], $path, $matches)) {
            continue;
        }

        $allowedMethods = array_merge($allowedMethods, $route['methods']);

        if (!in_array($method, $route['methods'], true)) {
            continue;
        }

        $route['handler']($matches);
        return;
    }

    if ($allowedMethods !== []) {
        header('Allow: ' . implode(', ', array_unique($allowedMethods)));
        jsonResponse(['error' => 'Method not allowed.'], 405);
        return;
    }

    jsonResponse(['error' => 'Route not found.'], 404);
}

function normalizeRequestPath(string $uri): string
{
    $path = parse_url($uri, PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return '/';
    }

    if (str_starts_with($path, '/index.php')) {
        $path = substr($path, strlen('/index.php')) ?: '/';
    }

    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return $path === '' ? '/' : $path;
}

function jsonResponse(array $payload, int $statusCode): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
