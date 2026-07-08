<?php
echo "=== PHP Version: " . PHP_VERSION . " ===\n\n";

echo "1. PDO Drivers:\n";
print_r(PDO::getAvailableDrivers());

echo "\n2. SQLite extension:\n";
echo "sqlite3: " . (extension_loaded('sqlite3') ? 'ON' : 'OFF') . "\n";
echo "pdo_sqlite: " . (extension_loaded('pdo_sqlite') ? 'ON' : 'OFF') . "\n";

echo "\n3. Folder data/ permission:\n";
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    echo "Folder data/ tidak ditemukan\n";
} else {
    echo "Folder data/ exists\n";
    echo "Writable: " . (is_writable($dataDir) ? 'YES' : 'NO') . "\n";
    $perms = fileperms($dataDir);
    echo "Permission: " . substr(sprintf('%o', $perms), -4) . "\n";
}

echo "\n4. File .htaccess exists:\n";
echo file_exists(__DIR__ . '/.htaccess') ? 'YES' : 'NO';

echo "\n\n5. File index.php exists:\n";
echo file_exists(__DIR__ . '/index.php') ? 'YES' : 'NO';

echo "\n\n6. File login.php exists:\n";
echo file_exists(__DIR__ . '/login.php') ? 'YES' : 'NO';

echo "\n\n7. File config/database.php exists:\n";
echo file_exists(__DIR__ . '/config/database.php') ? 'YES' : 'NO';

echo "\n\n8. Folder dist/ exists:\n";
echo is_dir(__DIR__ . '/dist') ? 'YES' : 'NO';

echo "\n\n Selesai";
