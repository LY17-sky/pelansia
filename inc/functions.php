<?php
session_start();

$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$db_error = null;

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        related_id INTEGER,
        is_read INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch(PDOException $e) {}

if (!$conn) {
    $db_error = "Tidak dapat terhubung ke database.";
}

function getDbConn() {
    global $conn;
    return $conn;
}

function validateTokenFromDb($token) {
    $conn = getDbConn();
    if (!$conn || !$token) return null;
    $stmt = $conn->prepare("SELECT user_id FROM tokens WHERE token = ? AND expires_at > datetime('now')");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['user_id'] : null;
}

function loadUserFromToken() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (!$headers) {
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[str_replace('_', '-', strtolower(substr($k, 5)))] = $v;
            }
        }
    }
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? $headers['authorization'] ?? '');
    if (!$token) return false;
    $userId = validateTokenFromDb($token);
    if (!$userId) return false;
    $conn = getDbConn();
    if (!$conn) return false;
    $stmt = $conn->prepare("SELECT id, username, nama_lengkap, role FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        return true;
    }
    return false;
}

// Auto-login via token if no session but token header exists
if (!isset($_SESSION['user'])) {
    loadUserFromToken();
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function redirect($path) {
    global $base_url;
    header("Location: $base_url/$path");
    exit;
}

function getUser() {
    return $_SESSION['user'] ?? null;
}

function getUserRole() {
    return $_SESSION['user']['role'] ?? null;
}

function isSuperAdmin() {
    return getUserRole() === 'super_admin';
}

function isAdminStaff() {
    return getUserRole() === 'admin';
}

function dbQuery($sql, $params = []) {
    $conn = getDbConn();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return false;
    }
}

function dbFetch($sql, $params = []) {
    $conn = getDbConn();
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return false;
    }
}

function dbFetchAll($sql, $params = []) {
    $conn = getDbConn();
    if (!$conn) return [];
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return [];
    }
}

function hitungUsiaPHP($tanggal_lahir) {
    if (!$tanggal_lahir) return 0;
    try {
        $lahir = new DateTime($tanggal_lahir);
        $sekarang = new DateTime();
        return $sekarang->diff($lahir)->y;
    } catch (Exception $e) {
        return 0;
    }
}

function hitungKategoriLansiaPHP($tanggal_lahir) {
    $usia = hitungUsiaPHP($tanggal_lahir);
    if ($usia >= 70) return 'lansia_utama';
    if ($usia >= 60) return 'lansia';
    return 'pra_lansia';
}

function getLabelKategoriLansia($kategori) {
    $labels = [
        'pra_lansia' => 'Pra Lansia (45-59 tahun)',
        'lansia' => 'Lansia (60-69 tahun)',
        'lansia_utama' => 'Lansia Tua (70 tahun)'
    ];
    return $labels[$kategori] ?? $kategori;
}

function getColorKategoriLansia($kategori) {
    $colors = [
        'pra_lansia' => 'bg-blue-100 text-blue-700',
        'lansia' => 'bg-purple-100 text-purple-700',
        'lansia_utama' => 'bg-orange-100 text-orange-700'
    ];
    return $colors[$kategori] ?? 'bg-gray-100 text-gray-600';
}

function isRisti($status_risiko) {
    return $status_risiko === 'risiko_tinggi';
}

function logActivity($user_id, $activity, $description) {
    $conn = getDbConn();
    if (!$conn) return;
    try {
        $stmt = $conn->prepare("INSERT INTO activities (id_user, aktivitas, deskripsi) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $activity, $description]);
    } catch(PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

function createNotification($user_id, $type, $title, $message, $related_id = null) {
    $conn = getDbConn();
    if (!$conn) return;
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $title, $message, $related_id]);
    } catch(PDOException $e) {
        error_log("Notification Error: " . $e->getMessage());
    }
}

function getUnreadNotifCount($user_id) {
    $conn = getDbConn();
    if (!$conn) return 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['count'] : 0;
    } catch(PDOException $e) {
        return 0;
    }
}

function getNotifications($user_id, $limit = 5) {
    $conn = getDbConn();
    if (!$conn) return [];
    try {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Notification Error: " . $e->getMessage());
        return [];
    }
}

function getTimeAgo($datetime) {
    if (!$datetime) return '';
    try {
        $now = new DateTime();
        $then = new DateTime($datetime);
        $diff = $now->diff($then);
        if ($diff->y > 0) return $diff->y . ' tahun lalu';
        if ($diff->m > 0) return $diff->m . ' bulan lalu';
        if ($diff->d > 0) return $diff->d . ' hari lalu';
        if ($diff->h > 0) return $diff->h . ' jam lalu';
        if ($diff->i > 0) return $diff->i . ' menit lalu';
        return 'Baru saja';
    } catch (Exception $e) {
        return '';
    }
}

function broadcastNotification($type, $title, $message, $related_id = null, $exclude_user_id = null, $target_role = null) {
    $conn = getDbConn();
    if (!$conn) return;
    $sql = "SELECT id FROM users WHERE status = 'active'";
    $params = [];
    if ($target_role) {
        $sql .= " AND role = ?";
        $params[] = $target_role;
    }
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $user) {
            if ($exclude_user_id && $user['id'] == $exclude_user_id) continue;
            $insert = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$user['id'], $type, $title, $message, $related_id]);
        }
    } catch(PDOException $e) {
        error_log("Broadcast Error: " . $e->getMessage());
    }
}
?>