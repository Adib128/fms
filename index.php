<?php
$__requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . $__requestedPath);

    if ($file !== false && is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/app/bootstrap.php';