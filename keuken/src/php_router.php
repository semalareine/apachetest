<?php

$path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';
$normalizedPath = strtolower(str_replace('\\', '/', $path));

if ($normalizedPath === '/data' || strpos($normalizedPath, '/data/') === 0) {
    http_response_code(404);
    exit;
}

if (preg_match('#^/src/(recipes|recipe|add_recipe|login|register|logout|session|profile|recipe_delete)\.php$#', $path)
    && !extension_loaded('pdo_sqlite')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'The PHP PDO SQLite extension is required to use the recipe book.';
    exit;
}

return false;
