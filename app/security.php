<?php
// Ensure the guard runs only once per request
if (defined('AUTH_GUARD_LOADED')) {
    return;
}

define('AUTH_GUARD_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Allow unauthenticated access to these paths
$publicPaths = ['/', '/login', '/deconnexion'];

$isAuthenticated = !empty($_SESSION['login']);

if (!$isAuthenticated) {
    if (!in_array($requestPath, $publicPaths, true)) {
        header('Location: /?redirect=' . urlencode($requestUri));
        exit();
    }
    return;
}

// Check session timeout (30 minutes)
$inactive = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    header('Location: /?timeout=1&redirect=' . urlencode($requestUri));
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

