<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Router.php';

$routes = require __DIR__ . '/routes.php';

$basePath = dirname(__DIR__);
$frontController = $basePath . DIRECTORY_SEPARATOR . 'index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$router = new Router(
    $routes,
    $basePath,
    $frontController,
    [
        '/',
        '/deconnexion',
    ]
);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
