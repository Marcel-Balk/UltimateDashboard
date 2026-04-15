<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

startAppSession();

// Auto-login via remember-me token
if (!isLoggedIn()) {
    checkRememberToken();
}
pruneTokens();

// Resolve route from REQUEST_URI
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = trim($uri, '/');

// Special: API
if ($path === 'api') {
    require __DIR__ . '/api.php';
    exit;
}

// Auth gates
$publicRoutes = ['login'];
if (!isLoggedIn() && !in_array($path, $publicRoutes, true)) {
    header('Location: /login');
    exit;
}
if (isLoggedIn() && $path === 'login') {
    header('Location: /');
    exit;
}

// Route dispatch
switch ($path) {
    case '':
    case 'dashboard':
        require __DIR__ . '/dashboard.php';
        break;
    case 'login':
        require __DIR__ . '/login.php';
        break;
    case 'logout':
        require __DIR__ . '/logout.php';
        break;
    case 'settings':
        require __DIR__ . '/settings.php';
        break;
    default:
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>404</title></head><body style="background:#04080f;color:#f1f5f9;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><div style="text-align:center"><h1 style="color:#00d4ff;font-size:4rem;margin:0">404</h1><p>Page not found. <a href="/" style="color:#00d4ff">Go home</a></p></div></body></html>';
}
