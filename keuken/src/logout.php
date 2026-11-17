<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$db = recipeDb();
requireLogin($db);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrfToken()) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Invalid logout request.';
    exit;
}

$_SESSION = array();
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', array(
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
    ));
}
session_destroy();
header('Location: /src/login.php', true, 303);
