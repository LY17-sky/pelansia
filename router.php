<?php
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Serve existing files directly (PHP, static assets)
$filePath = __DIR__ . $path;
if (is_file($filePath)) {
    return false;
}

// API routing (for virtual API paths)
if (preg_match('#^/api(?:/|$)#', $path)) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Root path — serve via index.php (routes to login.php)
if ($path === '' || $path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// SPA fallback
$spaFile = __DIR__ . '/dist/index.html';
if (is_file($spaFile)) {
    readfile($spaFile);
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
