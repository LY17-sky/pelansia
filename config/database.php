<?php
class Database {
    public $conn;

    public function getConnection() {
        if ($this->conn) return $this->conn;
        $dbDir = __DIR__ . '/../data';
        $dbFile = $dbDir . '/lansia.db';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        $needsInit = !file_exists($dbFile);
        $this->conn = new PDO("sqlite:$dbFile");
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("PRAGMA journal_mode=WAL");
        $this->conn->exec("PRAGMA foreign_keys=ON");
        // Auto-migration for new columns (SQLite — wrapped in try/catch because no IF NOT EXISTS ALTER COLUMN)
        try { $this->conn->exec("ALTER TABLE lansia ADD COLUMN tempat_lahir TEXT DEFAULT ''"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE lansia ADD COLUMN status_kesehatan TEXT DEFAULT 'sehat'"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE visits ADD COLUMN gangguan_penglihatan TEXT DEFAULT 'tidak_ada'"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE visits ADD COLUMN gangguan_pendengaran TEXT DEFAULT 'tidak_ada'"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE visits ADD COLUMN risiko_jatuh TEXT DEFAULT 'rendah'"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE visits ADD COLUMN status_kemandirian TEXT DEFAULT 'mandiri'"); } catch(PDOException $e) {}
        try { $this->conn->exec("ALTER TABLE visits ADD COLUMN gangguan_daya_ingat TEXT DEFAULT 'tidak_ada'"); } catch(PDOException $e) {}
        if ($needsInit) {
            require_once __DIR__ . '/../init_db.php';
            initSqliteDatabase($this->conn);
        }
        return $this->conn;
    }

    public function query($sql, $params = []) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public function exec($sql, $params = []) {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            return 0;
        }
    }

    public function lastInsertId() {
        return $this->conn ? $this->conn->lastInsertId() : 0;
    }
}

function getDb() {
    $db = new Database();
    return $db->getConnection();
}
