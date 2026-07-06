<?php
$exitCode = 0;

echo "=== Smoke Test Database ===\n\n";

try {
    require_once __DIR__ . '/config/database.php';

    echo "[OK] File config/database.php loaded\n";

    $db = getDb();

    if ($db instanceof PDO) {
        echo "[OK] Database connection established (PDO)\n";
    } else {
        echo "[FAIL] Database connection is not a PDO instance\n";
        exit(1);
    }

    $tables = [
        'puskesmas', 'users', 'villages', 'lansia', 'visits',
        'activities', 'settings', 'tokens'
    ];

    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            echo "[OK] Table '$table' exists\n";
        } else {
            echo "[FAIL] Table '$table' is missing\n";
            $exitCode = 1;
        }
    }

    $countLansia = $db->query("SELECT COUNT(*) FROM lansia")->fetchColumn();
    echo "[OK] lansia records: $countLansia\n";

    $countVisits = $db->query("SELECT COUNT(*) FROM visits")->fetchColumn();
    echo "[OK] visits records: $countVisits\n";

    echo "\n=== All smoke tests passed ===\n";
} catch (Exception $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    $exitCode = 1;
}

exit($exitCode);
