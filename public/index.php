<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/routes/api.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    dispatchApiRequest($method, $uri);
} catch (Throwable) {
    jsonResponse(['error' => 'Internal server error.'], 500);
}
