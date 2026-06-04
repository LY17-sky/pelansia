<?php
// Router for PHP pages and React SPA
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path if present (supports both /pelaporanlansia/ and /)
if ($base_path && $base_path !== '/' && strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
}

// Serve existing PHP files directly (built-in server may not for subdirs)
$file_path = __DIR__ . $path;
if (strpos($path, '.php') !== false && file_exists($file_path)) {
    require $file_path;
    exit;
}

// API routing
if (preg_match('#^/api(?:/|$)#', $path)) {
    header('Content-Type: application/json');
    $_SERVER['PATH_INFO'] = $path;
    require __DIR__ . '/api/index.php';
    exit;
}

// Serve static files first
if (strpos($path, '/dist/') === 0 || strpos($path, '/assets/') === 0) {
    $file = __DIR__ . $path;
    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'map' => 'application/json',
            'html' => 'text/html'
        ];
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'text/plain'));
        readfile($file);
        exit;
    }
}

// Root path - serve login
if ($path === '' || $path === '/') {
    include __DIR__ . '/login.php';
    exit;
}

// PHP page routing
$phpPages = [
    '/login.php' => 'login.php',
    '/dashboard.php' => 'dashboard.php',
    '/lansia.php' => 'lansia.php',
    '/kunjungan.php' => 'kunjungan.php',
    '/laporan.php' => 'laporan.php',
    '/logout.php' => 'logout.php',
    '/setup.php' => 'setup.php'
];

if (isset($phpPages[$path]) && file_exists(__DIR__ . '/' . $phpPages[$path])) {
    include __DIR__ . '/' . $phpPages[$path];
    exit;
}

// Default - serve React SPA (no-cache to prevent stale HTML)
header('Cache-Control: no-cache, must-revalidate');
readfile(__DIR__ . '/dist/index.html');
?>