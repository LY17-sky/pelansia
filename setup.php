<?php
require_once __DIR__ . '/inc/functions.php';
if (!isSuperAdmin()) {
    die('Akses ditolak');
}
$action = $_GET['action'] ?? '';

if ($action === 'createdb') {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS sistemlansia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo json_encode(['success' => true, 'message' => 'Database sistemlansia created/verified.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'import') {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=sistemlansia;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);
        
        $sql = file_get_contents(__DIR__ . '/database.sql');
        $statements = explode(';', $sql);
        $count = 0;
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt) && stripos($stmt, 'USE ') !== 0 && stripos($stmt, 'NOTE:') === false && stripos($stmt, '--') !== 0) {
                $stmt = preg_replace('/^-- .*/m', '', $stmt);
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    try {
                        $pdo->exec($stmt);
                        $count++;
                    } catch (Exception $e) {
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'message' => "Import completed. $count statements executed."]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'checkdb') {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $stmt = $pdo->query("SHOW DATABASES LIKE 'sistemlansia'");
        $exists = $stmt->fetch() ? true : false;
        
        $tables = [];
        if ($exists) {
            $pdo->exec("USE sistemlansia");
            $result = $pdo->query("SHOW TABLES");
            $tables = $result->fetchAll(PDO::FETCH_COLUMN);
        }
        
        echo json_encode([
            'success' => true,
            'database_exists' => $exists,
            'tables' => $tables,
            'mysql_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'initsqlite') {
    try {
        require_once __DIR__ . '/config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        if ($conn) {
            echo json_encode(['success' => true, 'message' => 'SQLite database initialized successfully.', 'driver' => 'sqlite']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to connect to SQLite database.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'checksqlite') {
    try {
        $dbFile = __DIR__ . '/data/lansia.db';
        $exists = file_exists($dbFile);
        $info = ['exists' => $exists, 'path' => $dbFile];
        if ($exists) {
            $conn = new PDO("sqlite:$dbFile");
            $tables = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
            $info['tables'] = $tables;
            $info['size'] = filesize($dbFile) . ' bytes';
        }
        echo json_encode(['success' => true, 'data' => $info]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - Pelaporan Lansia</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        h1 { color: #333; }
        .card { background: #f5f5f5; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        button { padding: 0.6rem 1.2rem; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; margin: 0.3rem; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #16a34a; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        pre { background: #e5e5e5; padding: 0.5rem; border-radius: 4px; overflow-x: auto; }
        .success { color: #16a34a; }
        .error { color: #dc2626; }
        #result { margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>Setup Database Pelaporan Lansia</h1>
    
    <h2>MySQL (XAMPP)</h2>
    <div class="card">
        <button class="btn-primary" onclick="checkDb()">Check DB</button>
        <button class="btn-success" onclick="createDb()">Create DB</button>
        <button class="btn-danger" onclick="importDb()">Import SQL</button>
    </div>

    <h2>SQLite (tanpa XAMPP)</h2>
    <div class="card">
        <button class="btn-primary" onclick="checkSqlite()">Check SQLite</button>
        <button class="btn-success" onclick="initSqlite()">Init SQLite</button>
    </div>

    <div id="result">Click a button to start...</div>

    <script>
        async function checkDb() {
            const res = await fetch('?action=checkdb');
            const data = await res.json();
            document.getElementById('result').innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
        }
        async function createDb() {
            const res = await fetch('?action=createdb');
            const data = await res.json();
            document.getElementById('result').innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
        }
        async function importDb() {
            document.getElementById('result').innerHTML = '<pre>Importing...</pre>';
            const res = await fetch('?action=import');
            const data = await res.json();
            document.getElementById('result').innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
        }
        async function checkSqlite() {
            const res = await fetch('?action=checksqlite');
            const data = await res.json();
            document.getElementById('result').innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
        }
        async function initSqlite() {
            document.getElementById('result').innerHTML = '<pre>Initializing SQLite...</pre>';
            const res = await fetch('?action=initsqlite');
            const data = await res.json();
            document.getElementById('result').innerHTML = `<pre class="${data.success ? 'success' : 'error'}">${JSON.stringify(data, null, 2)}</pre>`;
        }
    </script>
</body>
</html>
