<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// If accessed directly (not via api.php or router), parse URI
if (!isset($endpoint)) {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    if ($base_path && $base_path !== '/') {
        $uri = substr($uri, strlen($base_path));
    }
    $uri = preg_replace('#^/api/?#', '', $uri);
    $request = explode('/', trim($uri, '/'));
    $endpoint = $request[0] ?? '';
    $id = $request[1] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true);
}

function validateToken($conn) {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    if (!$token) return null;
    $stmt = $conn->prepare("SELECT user_id FROM tokens WHERE token = ? AND expires_at > datetime('now')");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['user_id'] : null;
}

function requireAuth($conn) {
    $userId = validateToken($conn);
    if (!$userId) {
        respond(["success" => false, "message" => "Unauthorized"], 401);
    }
    return $userId;
}

function requireRole($role, $conn, $userId = null) {
    if (!$userId) $userId = requireAuth($conn);
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== $role) {
        respond(["success" => false, "message" => "Forbidden"], 403);
    }
    return $userId;
}

// Ensure tokens table exists (run every request for compatibility with existing DBs)
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL UNIQUE,
        expires_at TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch(PDOException $e) {}

function hitungUsia($tanggal_lahir) {
    if (!$tanggal_lahir) return 0;
    try {
        $lahir = new DateTime($tanggal_lahir);
        $sekarang = new DateTime();
        return $sekarang->diff($lahir)->y;
    } catch (Exception $e) {
        return 0;
    }
}

function hitungKategoriLansia($tanggal_lahir) {
    $usia = hitungUsia($tanggal_lahir);
    if ($usia >= 70) return 'lansia_utama';
    if ($usia >= 60) return 'lansia';
    if ($usia >= 45) return 'pra_lansia';
    return 'pra_lansia';
}

function hitungKategoriLansiaNama($kategori, $status_risiko = null) {
    $labels = [
        'pra_lansia' => 'Pra Lansia',
        'lansia' => 'Lansia',
        'lansia_utama' => 'Lansia Tua'
    ];
    return $labels[$kategori] ?? $kategori;
}

function klasifikasiIMT($imt) {
    if (!$imt || $imt <= 0) return '';
    if ($imt < 18.5) return 'kurus';
    if ($imt < 25) return 'normal';
    if ($imt < 30) return 'gemuk';
    return 'obesitas';
}

function getVillageId($desaInput, $conn) {
    if (empty($desaInput)) return null;
    if (ctype_digit(strval($desaInput))) {
        $id = (int)$desaInput;
        $stmt = $conn->prepare("SELECT id FROM villages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? $id : null;
    }
    try {
        $stmt = $conn->prepare("SELECT id FROM villages WHERE nama_desa = ?");
        $stmt->execute([$desaInput]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    } catch(PDOException $e) {
        return null;
    }
}

switch ($endpoint) {
    case 'login':
        if ($method === 'POST') {
            $data = getJsonInput();
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            
            $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role, status FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                respond(["success" => false, "message" => "Username tidak ditemukan"], 401);
            }
            
            if ($user['status'] !== 'active') {
                respond(["success" => false, "message" => "Akun tidak aktif"], 401);
            }
            
            if (password_verify($password, $user['password'])) {
                $token = bin2hex(random_bytes(32));
                $stmt = $conn->prepare("INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, datetime('now', '+24 hours'))");
                $stmt->execute([$user['id'], $token]);
                respond([
                    "success" => true,
                    "message" => "Login berhasil",
                    "user" => [
                        "id" => $user['id'],
                        "username" => $user['username'],
                        "nama_lengkap" => $user['nama_lengkap'],
                        "role" => $user['role']
                    ],
                    "token" => $token
                ]);
            }
            
            respond(["success" => false, "message" => "Password salah"], 401);
        }
        break;
    
    case 'lansia':
        if ($method === 'GET') {
            $search = $_GET['search'] ?? '';
            $sql = "SELECT l.*, v.nama_desa FROM lansia l LEFT JOIN villages v ON l.id_desa = v.id WHERE l.status_aktif = 'aktif'";
            $params = [];
            
            if ($search) {
                $sql .= " AND (l.nama_lengkap LIKE ? OR l.nik LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql .= " ORDER BY l.id DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tambahkan perhitungan usia untuk setiap record
            foreach ($data as &$row) {
                $row['usia'] = hitungUsia($row['tanggal_lahir']);
                $row['kategori_lansia_nama'] = hitungKategoriLansiaNama($row['kategori_lansia']);
                // Tambahkan flag risti untuk lansia dengan status risiko tinggi
                $row['is_risti'] = ($row['status_risiko'] === 'risiko_tinggi');
            }
            
            respond(["success" => true, "data" => $data]);
        }
        
        if ($method === 'POST') {
            $userId = requireRole('admin', $conn);
            $data = getJsonInput();
            $kategori = hitungKategoriLansia($data['tanggal_lahir']);
            
            $stmt = $conn->prepare("INSERT INTO lansia (nik, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, nama_keluarga, hubungan_keluarga, no_telepon_keluarga, tempat_lahir, bpjs, status_kesehatan, status_risiko, kategori_lansia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $data['nik'],
                $data['nama_lengkap'],
                $data['tanggal_lahir'],
                $data['jenis_kelamin'],
                $data['alamat'],
                getVillageId($data['id_desa'], $conn),
                $data['no_telepon'],
                $data['nama_keluarga'],
                $data['hubungan_keluarga'] ?? '',
                $data['no_telepon_keluarga'],
                $data['tempat_lahir'],
                $data['bpjs'],
                $data['status_kesehatan'] ?? 'sehat',
                $data['status_risiko'] ?? 'risiko_rendah',
                $kategori
            ]);
            
            if ($stmt->rowCount() > 0) {
                respond(["success" => true, "message" => "Data berhasil disimpan", "id" => $conn->lastInsertId()]);
            }
            respond(["success" => false, "message" => "Gagal menyimpan data"], 500);
        }
        
        if ($method === 'PUT') {
            $userId = requireRole('admin', $conn);
            $id = $request[1] ?? '';
            $data = getJsonInput();
            $kategori = hitungKategoriLansia($data['tanggal_lahir']);
            
            $stmt = $conn->prepare("UPDATE lansia SET nik=?, nama_lengkap=?, tanggal_lahir=?, jenis_kelamin=?, alamat=?, id_desa=?, no_telepon=?, nama_keluarga=?, hubungan_keluarga=?, no_telepon_keluarga=?, tempat_lahir=?, bpjs=?, status_kesehatan=?, status_risiko=?, kategori_lansia=? WHERE id=?");
            
            $stmt->execute([
                $data['nik'],
                $data['nama_lengkap'],
                $data['tanggal_lahir'],
                $data['jenis_kelamin'],
                $data['alamat'],
                getVillageId($data['id_desa'], $conn),
                $data['no_telepon'],
                $data['nama_keluarga'],
                $data['hubungan_keluarga'] ?? '',
                $data['no_telepon_keluarga'],
                $data['tempat_lahir'],
                $data['bpjs'],
                $data['status_kesehatan'] ?? 'sehat',
                $data['status_risiko'] ?? 'risiko_rendah',
                $kategori,
                $id
            ]);
            
            if ($stmt->rowCount() > 0) {
                respond(["success" => true, "message" => "Data berhasil diupdate"]);
            }
            respond(["success" => false, "message" => "Gagal mengupdate data"], 500);
        }
        
        if ($method === 'DELETE') {
            $userId = requireRole('admin', $conn);
            $id = $request[1] ?? '';
            
            $stmt = $conn->prepare("UPDATE lansia SET status_aktif = 'nonaktif' WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                respond(["success" => true, "message" => "Data berhasil dihapus"]);
            }
            respond(["success" => false, "message" => "Gagal menghapus data"], 500);
        }
        break;
    
    case 'visits':
        if ($method === 'GET') {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $sql = "SELECT v.*, l.nama_lengkap, l.nik FROM visits v 
                    JOIN lansia l ON v.id_lansia = l.id 
                    WHERE v.tanggal_kunjungan BETWEEN ? AND ? 
                    ORDER BY v.tanggal_kunjungan DESC, v.jam_kunjungan DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(["success" => true, "data" => $data]);
        }
        
        if ($method === 'POST') {
            $userId = requireRole('admin', $conn);
            $data = getJsonInput();
            
            $tujuanRujukan = '';
            if (isset($data['tujuan_rujukan'])) {
                $tujuanRujukan = is_array($data['tujuan_rujukan']) ? implode(',', $data['tujuan_rujukan']) : $data['tujuan_rujukan'];
            }
            
            $stmt = $conn->prepare("INSERT INTO visits (id_lansia, id_petugas, tanggal_kunjungan, jam_kunjungan, jenis_kunjungan, status_kesehatan, tekanan_darah_sistol, tekanan_darah_diastol, berat_badan, tinggi_badan, imt, nadi, respiratory_rate, status_disabilitas, kelainan, keluhan, diagnosa, tindakan, rujukan, tujuan_rujukan, rekomendasi, obat, gula_darah, kolesterol, hemoglobin, spo2, suhu_tubuh, gangguan_penglihatan, gangguan_pendengaran, risiko_jatuh, status_kemandirian, gangguan_daya_ingat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $data['id_lansia'],
                $data['id_petugas'],
                $data['tanggal_kunjungan'],
                $data['jam_kunjungan'],
                $data['jenis_kunjungan'],
                $data['status_kesehatan'],
                $data['tekanan_darah_sistol'],
                $data['tekanan_darah_diastol'],
                $data['berat_badan'],
                $data['tinggi_badan'],
                $data['imt'],
                $data['nadi'],
                $data['respiratory_rate'],
                $data['status_disabilitas'],
                $data['kelainan'],
                $data['keluhan'],
                $data['diagnosa'],
                $data['tindakan'],
                $data['rujukan'] ?? '',
                $tujuanRujukan,
                $data['rekomendasi'] ?? 'pemeriksaan_biasa',
                $data['obat'],
                $data['gula_darah'] ?? null,
                $data['kolesterol'] ?? null,
                $data['hemoglobin'] ?? null,
                $data['spo2'] ?? null,
                $data['suhu_tubuh'] ?? null,
                $data['gangguan_penglihatan'] ?? 'tidak_ada',
                $data['gangguan_pendengaran'] ?? 'tidak_ada',
                $data['risiko_jatuh'] ?? 'rendah',
                $data['status_kemandirian'] ?? 'mandiri',
                $data['gangguan_daya_ingat'] ?? 'tidak_ada'
            ]);
            
            if ($stmt->rowCount() > 0) {
                respond(["success" => true, "message" => "Kunjungan berhasil disimpan", "id" => $conn->lastInsertId()]);
            }
            respond(["success" => false, "message" => "Gagal menyimpan kunjungan"], 500);
        }
        break;
    
    case 'dashboard':
        if ($method === 'GET') {
            $today = date('Y-m-d');
            
            $totalLansia = $conn->query("SELECT COUNT(*) as total FROM lansia WHERE status_aktif = 'aktif'")->fetch(PDO::FETCH_ASSOC)['total'];
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM visits WHERE tanggal_kunjungan = ?");
            $stmt->execute([$today]);
            $kunjunganHariIni = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $lansiaSakit = $conn->query("SELECT COUNT(*) as total FROM lansia WHERE status_aktif = 'aktif' AND status_kesehatan != 'sehat'")->fetch(PDO::FETCH_ASSOC)['total'];
            
            $chartData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dayName = date('D', strtotime($date));
                $dayNames = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
                $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM visits WHERE tanggal_kunjungan = ?");
                $stmt2->execute([$date]);
                $count = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
                $chartData[] = [
                    'hari' => $dayNames[$dayName] ?? $dayName,
                    'tanggal' => $date,
                    'jumlah' => (int)$count
                ];
            }
            
            // Kategori usia distribution
            $kategoriData = [];
            $kategoriResult = $conn->query("SELECT kategori_lansia, COUNT(*) as total FROM lansia WHERE status_aktif = 'aktif' GROUP BY kategori_lansia");
            while ($row = $kategoriResult->fetch(PDO::FETCH_ASSOC)) {
                $kategoriData[] = ['kategori' => $row['kategori_lansia'], 'total' => (int)$row['total']];
            }
            
            $risikoData = [];
            $risikoResult = $conn->query("SELECT status_risiko, COUNT(*) as total FROM lansia WHERE status_aktif = 'aktif' GROUP BY status_risiko");
            while ($row = $risikoResult->fetch(PDO::FETCH_ASSOC)) {
                $risikoData[] = ['risiko' => $row['status_risiko'], 'total' => (int)$row['total']];
            }
            
            $rekomendasiData = [];
            $rekomResult = $conn->query("SELECT rekomendasi, COUNT(*) as total FROM visits GROUP BY rekomendasi");
            while ($row = $rekomResult->fetch(PDO::FETCH_ASSOC)) {
                $rekomendasiData[] = ['rekomendasi' => $row['rekomendasi'], 'total' => (int)$row['total']];
            }
            
            // Rujukan per poli
            $rujukanPoli = [];
            $poliResult = $conn->query("SELECT tujuan_rujukan FROM visits WHERE tujuan_rujukan IS NOT NULL AND tujuan_rujukan != ''");
            $poliCounts = [];
            while ($row = $poliResult->fetch(PDO::FETCH_ASSOC)) {
                $polis = explode(',', $row['tujuan_rujukan']);
                foreach ($polis as $p) {
                    $p = trim($p);
                    if ($p) $poliCounts[$p] = ($poliCounts[$p] ?? 0) + 1;
                }
            }
            foreach ($poliCounts as $poli => $count) {
                $rujukanPoli[] = ['poli' => $poli, 'total' => $count];
            }
            
            respond([
                "success" => true,
                "data" => [
                    "totalLansia" => (int)$totalLansia,
                    "kunjunganHariIni" => (int)$kunjunganHariIni,
                    "lansiaSakit" => (int)$lansiaSakit,
                    "chartData" => $chartData,
                    "kategoriData" => $kategoriData,
                    "risikoData" => $risikoData,
                    "rekomendasiData" => $rekomendasiData,
                    "rujukanPoli" => $rujukanPoli
                ]
            ]);
        }
        break;
    
    case 'villages':
        if ($method === 'GET') {
            $puskesmasId = $_GET['id_puskesmas'] ?? '';
            if ($puskesmasId) {
                $stmt = $conn->prepare("SELECT * FROM villages WHERE id_puskesmas = ? ORDER BY nama_desa");
                $stmt->execute([$puskesmasId]);
            } else {
                $stmt = $conn->query("SELECT v.*, p.nama_puskesmas FROM villages v LEFT JOIN puskesmas p ON v.id_puskesmas = p.id ORDER BY v.nama_desa");
            }
            respond(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        
        if ($method === 'POST') {
            $userId = requireRole('super_admin', $conn);
            $data = getJsonInput();
            $stmt = $conn->prepare("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES (?, ?, ?)");
            $stmt->execute([$data['nama_desa'], $data['kode_desa'] ?? '', $data['id_puskesmas'] ?? null]);
            respond(["success" => true, "message" => "Desa berhasil ditambahkan", "id" => $conn->lastInsertId()]);
        }
        
        if ($method === 'PUT') {
            $userId = requireRole('super_admin', $conn);
            $id = $request[1] ?? '';
            $data = getJsonInput();
            $stmt = $conn->prepare("UPDATE villages SET nama_desa=?, kode_desa=?, id_puskesmas=? WHERE id=?");
            $stmt->execute([$data['nama_desa'], $data['kode_desa'] ?? '', $data['id_puskesmas'] ?? null, $id]);
            respond(["success" => true, "message" => "Desa berhasil diupdate"]);
        }
        
        if ($method === 'DELETE') {
            $userId = requireRole('super_admin', $conn);
            $id = $request[1] ?? '';
            $conn->prepare("UPDATE lansia SET id_desa = NULL WHERE id_desa = ?")->execute([$id]);
            $stmt = $conn->prepare("DELETE FROM villages WHERE id=?");
            $stmt->execute([$id]);
            respond(["success" => true, "message" => "Desa berhasil dihapus"]);
        }
        break;
    
    case 'riwayat':
        if ($method === 'GET') {
            $id = $request[1] ?? $_GET['id'] ?? '';
            if (!$id) {
                respond(["success" => false, "message" => "ID lansia diperlukan"], 400);
            }
            
            $stmtL = $conn->prepare("SELECT l.*, v.nama_desa FROM lansia l LEFT JOIN villages v ON l.id_desa = v.id WHERE l.id = ?");
            $stmtL->execute([$id]);
            $dataLansia = $stmtL->fetch(PDO::FETCH_ASSOC);
            
            if (!$dataLansia) {
                respond(["success" => false, "message" => "Data lansia tidak ditemukan"], 404);
            }
            
            $stmtV = $conn->prepare("SELECT v.* FROM visits v WHERE v.id_lansia = ? ORDER BY v.tanggal_kunjungan DESC, v.jam_kunjungan DESC");
            $stmtV->execute([$id]);
            $dataVisits = $stmtV->fetchAll(PDO::FETCH_ASSOC);
            
            respond([
                "success" => true,
                "data" => [
                    "lansia" => $dataLansia,
                    "visits" => $dataVisits
                ]
            ]);
        }
        break;
    
    case 'laporan':
        if ($method === 'GET') {
            $userId = requireRole('super_admin', $conn);
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $rekomendasiFilter = $_GET['rekomendasi'] ?? '';
            $risikoFilter = $_GET['status_risiko'] ?? '';
            $poliFilter = $_GET['tujuan_rujukan'] ?? '';
            
            $sql = "SELECT v.*, l.nama_lengkap, l.nik, l.tanggal_lahir, l.status_risiko, l.kategori_lansia
                    FROM visits v 
                    JOIN lansia l ON v.id_lansia = l.id 
                    WHERE v.tanggal_kunjungan BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($rekomendasiFilter) {
                $sql .= " AND v.rekomendasi = ?";
                $params[] = $rekomendasiFilter;
            }
            if ($risikoFilter) {
                $sql .= " AND l.status_risiko = ?";
                $params[] = $risikoFilter;
            }
            if ($poliFilter) {
                $sql .= " AND v.tujuan_rujukan LIKE ?";
                $params[] = "%$poliFilter%";
            }
            
            $sql .= " ORDER BY v.tanggal_kunjungan DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['usia'] = (new DateTime())->diff(new DateTime($row['tanggal_lahir']))->y;
                $row['klasifikasi_imt'] = klasifikasiIMT($row['imt']);
                $data[] = $row;
            }
            
            respond(["success" => true, "data" => $data]);
        }
        break;
    
    case 'users':
        if ($method === 'GET') {
            $stmt = $conn->prepare("SELECT u.id, u.username, u.nama_lengkap, u.email, u.role, u.status, u.id_puskesmas, p.nama_puskesmas FROM users u LEFT JOIN puskesmas p ON u.id_puskesmas = p.id ORDER BY u.id");
            $stmt->execute();
            respond(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        
        if ($method === 'POST') {
            $userId = requireRole('super_admin', $conn);
            $data = getJsonInput();
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$data['username']]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                respond(["success" => false, "message" => "Username sudah digunakan"], 409);
            }
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, id_puskesmas, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['username'], $hashed, $data['nama_lengkap'], $data['email'] ?? '', $data['role'] ?? 'admin', $data['id_puskesmas'] ?? null, $data['status'] ?? 'active']);
            respond(["success" => true, "message" => "User berhasil ditambahkan", "id" => $conn->lastInsertId()]);
        }
        
        if ($method === 'PUT') {
            $userId = requireRole('super_admin', $conn);
            $id = $request[1] ?? '';
            $data = getJsonInput();
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$data['username'], $id]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                respond(["success" => false, "message" => "Username sudah digunakan"], 409);
            }
            if (!empty($data['password'])) {
                $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, email=?, role=?, id_puskesmas=?, status=? WHERE id=?");
                $stmt->execute([$data['username'], $hashed, $data['nama_lengkap'], $data['email'] ?? '', $data['role'] ?? 'admin', $data['id_puskesmas'] ?? null, $data['status'] ?? 'active', $id]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, nama_lengkap=?, email=?, role=?, id_puskesmas=?, status=? WHERE id=?");
                $stmt->execute([$data['username'], $data['nama_lengkap'], $data['email'] ?? '', $data['role'] ?? 'admin', $data['id_puskesmas'] ?? null, $data['status'] ?? 'active', $id]);
            }
            respond(["success" => true, "message" => "User berhasil diupdate"]);
        }
        
        if ($method === 'DELETE') {
            $userId = requireRole('super_admin', $conn);
            $id = $request[1] ?? '';
            $stmt = $conn->prepare("UPDATE users SET status='inactive' WHERE id=?");
            $stmt->execute([$id]);
            respond(["success" => true, "message" => "User berhasil dinonaktifkan"]);
        }
        break;
    
    case 'puskesmas':
        if ($method === 'GET') {
            $stmt = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM villages WHERE id_puskesmas = p.id) as total_desa FROM puskesmas p ORDER BY p.nama_puskesmas");
            respond(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        
        if ($method === 'POST') {
            $userId = requireRole('super_admin', $conn);
            $data = getJsonInput();
            $stmt = $conn->prepare("INSERT INTO puskesmas (nama_puskesmas, alamat, telepon, kode_puskesmas) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['nama_puskesmas'], $data['alamat'] ?? '', $data['telepon'] ?? '', $data['kode_puskesmas'] ?? '']);
            respond(["success" => true, "message" => "Puskesmas berhasil ditambahkan", "id" => $conn->lastInsertId()]);
        }
        
        if ($method === 'PUT') {
            $userId = requireRole('super_admin', $conn);
            $id = $request[1] ?? '';
            $data = getJsonInput();
            $stmt = $conn->prepare("UPDATE puskesmas SET nama_puskesmas=?, alamat=?, telepon=?, kode_puskesmas=? WHERE id=?");
            $stmt->execute([$data['nama_puskesmas'], $data['alamat'] ?? '', $data['telepon'] ?? '', $data['kode_puskesmas'] ?? '', $id]);
            respond(["success" => true, "message" => "Puskesmas berhasil diupdate"]);
        }
        
        if ($method === 'DELETE') {
            $userId = requireRole('super_admin', $conn);
            $id = $request[1] ?? '';
            $conn->prepare("DELETE FROM villages WHERE id_puskesmas = ?")->execute([$id]);
            $stmt = $conn->prepare("DELETE FROM puskesmas WHERE id=?");
            $stmt->execute([$id]);
            respond(["success" => true, "message" => "Puskesmas berhasil dihapus"]);
        }
        break;
    
    case 'profile':
        $userId = validateToken($conn);
        if (!$userId) respond(["success" => false, "message" => "Unauthorized"], 401);
        if ($method === 'GET') {
            $stmt = $conn->prepare("SELECT id, username, nama_lengkap, email, role FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) respond(["success" => false, "message" => "User tidak ditemukan"], 404);
            respond(["success" => true, "data" => $user]);
        }
        if ($method === 'PUT') {
            $data = getJsonInput();
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, email=? WHERE id=?");
            $stmt->execute([$data['nama_lengkap'], $data['email'] ?? '', $userId]);
            if (!empty($data['password'])) {
                $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
                $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $userId]);
            }
            respond(["success" => true, "message" => "Profil berhasil diupdate"]);
        }
        break;
    
    case 'activities':
        if ($method === 'GET') {
            $userId = requireRole('super_admin', $conn);
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $total = $conn->query("SELECT COUNT(*) FROM activities")->fetchColumn();
            $stmt = $conn->prepare("SELECT a.*, u.nama_lengkap FROM activities a LEFT JOIN users u ON a.id_user = u.id ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            respond(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC), "total" => (int)$total, "page" => $page]);
        }
        break;
    
    case 'settings':
        if ($method === 'GET') {
            $stmt = $conn->query("SELECT * FROM settings");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['key']] = $row['value'];
            }
            respond(["success" => true, "data" => $settings]);
        }
        if ($method === 'PUT') {
            $userId = requireRole('super_admin', $conn);
            $data = getJsonInput();
            foreach ($data as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (\"key\", \"value\") VALUES (?, ?) ON CONFLICT(\"key\") DO UPDATE SET \"value\" = ?");
                $stmt->execute([$key, $value, $value]);
            }
            respond(["success" => true, "message" => "Pengaturan berhasil disimpan"]);
        }
        break;
    
    case 'health-classify':
        if ($method === 'GET') {
            $usia = (int)($_GET['usia'] ?? 0);
            $td_sistol = (int)($_GET['td_sistol'] ?? 0);
            $td_diastol = (int)($_GET['td_diastol'] ?? 0);
            $imt = (float)($_GET['imt'] ?? 0);
            $nadi = (int)($_GET['nadi'] ?? 0);
            $rr = (int)($_GET['rr'] ?? 0);
            $disabilitas = $_GET['disabilitas'] ?? '';
            $gula_darah = (int)($_GET['gula_darah'] ?? 0);
            $kolesterol = (int)($_GET['kolesterol'] ?? 0);
            $hemoglobin = (float)($_GET['hemoglobin'] ?? 0);
            $spo2 = (int)($_GET['spo2'] ?? 0);
            $suhu_tubuh = (float)($_GET['suhu_tubuh'] ?? 0);
            $jenis_kelamin = $_GET['jenis_kelamin'] ?? 'L';
            
            $kategori_usia = $usia >= 70 ? 'lansia_utama' : ($usia >= 60 ? 'lansia' : 'pra_lansia');
            
            $issues = [];
            $max_severity = 'sehat';
            
            // TD Sistol thresholds by age
            $td_s_ranges = [
                'pra_lansia' => ['sehat' => [90, 120], 'waspada' => [121, 139]],
                'lansia' => ['sehat' => [100, 140], 'waspada' => [141, 159]],
                'lansia_utama' => ['sehat' => [110, 150], 'waspada' => [151, 169]],
            ];
            $td_s = $td_s_ranges[$kategori_usia];
            if ($td_sistol > 0) {
                if ($td_sistol < $td_s['sehat'][0] || $td_sistol >= $td_s['waspada'][1] + 1) {
                    $issues[] = ['parameter' => 'TD Sistol', 'value' => "$td_sistol mmHg", 'category' => $td_sistol < $td_s['sehat'][0] ? 'Hipotensi' : 'Hipertensi', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($td_sistol > $td_s['sehat'][1]) {
                    $issues[] = ['parameter' => 'TD Sistol', 'value' => "$td_sistol mmHg", 'category' => 'Pre-hipertensi', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // TD Diastol thresholds by age
            $td_d_ranges = [
                'pra_lansia' => ['sehat' => [60, 80], 'waspada' => [81, 89]],
                'lansia' => ['sehat' => [60, 90], 'waspada' => [91, 99]],
                'lansia_utama' => ['sehat' => [60, 90], 'waspada' => [91, 104]],
            ];
            $td_d = $td_d_ranges[$kategori_usia];
            if ($td_diastol > 0) {
                if ($td_diastol < $td_d['sehat'][0] || $td_diastol >= $td_d['waspada'][1] + 1) {
                    $issues[] = ['parameter' => 'TD Diastol', 'value' => "$td_diastol mmHg", 'category' => $td_diastol < $td_d['sehat'][0] ? 'Hipotensi' : 'Hipertensi', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($td_diastol > $td_d['sehat'][1]) {
                    $issues[] = ['parameter' => 'TD Diastol', 'value' => "$td_diastol mmHg", 'category' => 'Pre-hipertensi', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // IMT thresholds by age
            $imt_ranges = [
                'pra_lansia' => ['sehat' => [18.5, 24.9], 'waspada_low' => [17, 18.4], 'waspada_high' => [25, 29.9]],
                'lansia' => ['sehat' => [20, 27], 'waspada_low' => [18.5, 19.9], 'waspada_high' => [27.1, 30]],
                'lansia_utama' => ['sehat' => [22, 28], 'waspada_low' => [20, 21.9], 'waspada_high' => [28.1, 32]],
            ];
            $imt_r = $imt_ranges[$kategori_usia];
            if ($imt > 0) {
                if ($imt < $imt_r['waspada_low'][0] || $imt >= 30 && $kategori_usia === 'pra_lansia' || $imt >= 30 && $kategori_usia === 'lansia' || $imt >= 32 && $kategori_usia === 'lansia_utama') {
                    $cat = $imt < $imt_r['waspada_low'][0] ? 'Gizi kurang' : 'Obesitas';
                    $issues[] = ['parameter' => 'IMT', 'value' => "$imt kg/m²", 'category' => $cat, 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($imt < $imt_r['sehat'][0] || $imt > $imt_r['sehat'][1]) {
                    $cat = $imt < $imt_r['sehat'][0] ? 'Kurus' : ($imt <= $imt_r['waspada_high'][1] ? 'Gemuk' : 'Obesitas');
                    $issues[] = ['parameter' => 'IMT', 'value' => "$imt kg/m²", 'category' => $cat, 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Nadi
            if ($nadi > 0) {
                if ($nadi < 50 || $nadi > 110) {
                    $issues[] = ['parameter' => 'Nadi', 'value' => "$nadi x/menit", 'category' => $nadi < 50 ? 'Bradikardia' : 'Takikardia', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($nadi < 60 || $nadi > 100) {
                    $issues[] = ['parameter' => 'Nadi', 'value' => "$nadi x/menit", 'category' => $nadi < 60 ? 'Bradikardia ringan' : 'Takikardia ringan', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Respiratory Rate
            if ($rr > 0) {
                if ($rr < 12 || $rr > 25) {
                    $issues[] = ['parameter' => 'Respiratory Rate', 'value' => "$rr x/menit", 'category' => $rr < 12 ? 'Bradipnea' : 'Takipnea', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($rr < 16 || $rr > 20) {
                    $issues[] = ['parameter' => 'Respiratory Rate', 'value' => "$rr x/menit", 'category' => 'Tidak normal', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Disabilitas
            if ($disabilitas && $disabilitas !== 'tidak_ada') {
                if ($disabilitas === 'sedang' || $disabilitas === 'berat') {
                    $issues[] = ['parameter' => 'Disabilitas', 'value' => ucfirst($disabilitas), 'category' => 'Disabilitas', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } else {
                    $issues[] = ['parameter' => 'Disabilitas', 'value' => 'Ringan', 'category' => 'Disabilitas ringan', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Gula Darah (opsional)
            if ($gula_darah > 0) {
                if ($gula_darah < 70 || $gula_darah >= 200) {
                    $cat = $gula_darah < 70 ? 'Hipoglikemia' : 'Diabetes';
                    $issues[] = ['parameter' => 'Gula Darah', 'value' => "$gula_darah mg/dL", 'category' => $cat, 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($gula_darah > 140) {
                    $issues[] = ['parameter' => 'Gula Darah', 'value' => "$gula_darah mg/dL", 'category' => 'Pre-diabetes', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Kolesterol (opsional)
            if ($kolesterol > 0) {
                if ($kolesterol >= 240) {
                    $issues[] = ['parameter' => 'Kolesterol', 'value' => "$kolesterol mg/dL", 'category' => 'Kolesterol tinggi', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($kolesterol >= 200) {
                    $issues[] = ['parameter' => 'Kolesterol', 'value' => "$kolesterol mg/dL", 'category' => 'Borderline', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Hemoglobin (opsional)
            if ($hemoglobin > 0) {
                if ($jenis_kelamin === 'L') {
                    if ($hemoglobin < 11) {
                        $issues[] = ['parameter' => 'Hemoglobin', 'value' => "$hemoglobin g/dL", 'category' => 'Anemia berat', 'severity' => 'bahaya'];
                        $max_severity = 'bahaya';
                    } elseif ($hemoglobin < 13) {
                        $issues[] = ['parameter' => 'Hemoglobin', 'value' => "$hemoglobin g/dL", 'category' => 'Anemia ringan', 'severity' => 'waspada'];
                        if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                    }
                } else {
                    if ($hemoglobin < 10) {
                        $issues[] = ['parameter' => 'Hemoglobin', 'value' => "$hemoglobin g/dL", 'category' => 'Anemia berat', 'severity' => 'bahaya'];
                        $max_severity = 'bahaya';
                    } elseif ($hemoglobin < 12) {
                        $issues[] = ['parameter' => 'Hemoglobin', 'value' => "$hemoglobin g/dL", 'category' => 'Anemia ringan', 'severity' => 'waspada'];
                        if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                    }
                }
            }
            
            // SpO2 (opsional)
            if ($spo2 > 0) {
                if ($spo2 <= 90) {
                    $issues[] = ['parameter' => 'SpO2', 'value' => "$spo2%", 'category' => 'Hipoksia', 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($spo2 < 95) {
                    $issues[] = ['parameter' => 'SpO2', 'value' => "$spo2%", 'category' => 'Hipoksia ringan', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            // Suhu Tubuh (opsional)
            if ($suhu_tubuh > 0) {
                if ($suhu_tubuh >= 38.5 || $suhu_tubuh < 35) {
                    $cat = $suhu_tubuh >= 38.5 ? 'Demam tinggi' : 'Hipotermia';
                    $issues[] = ['parameter' => 'Suhu Tubuh', 'value' => "$suhu_tubuh °C", 'category' => $cat, 'severity' => 'bahaya'];
                    $max_severity = 'bahaya';
                } elseif ($suhu_tubuh >= 37.6) {
                    $issues[] = ['parameter' => 'Suhu Tubuh', 'value' => "$suhu_tubuh °C", 'category' => 'Sub-febris', 'severity' => 'waspada'];
                    if ($max_severity !== 'bahaya') $max_severity = 'waspada';
                }
            }
            
            $status_map = ['sehat' => 'sehat', 'waspada' => 'sakit_ringan', 'bahaya' => 'sakit_berat'];
            $label_map = ['sehat' => 'Sehat', 'waspada' => 'Sakit Ringan', 'bahaya' => 'Sakit Berat'];
            $color_map = ['sehat' => 'green', 'waspada' => 'amber', 'bahaya' => 'red'];
            
            $recommendations = [];
            foreach ($issues as $issue) {
                if (strpos($issue['parameter'], 'TD') !== false) $recommendations[] = 'Pemantauan tekanan darah rutin';
                if ($issue['parameter'] === 'IMT') $recommendations[] = 'Konsultasi gizi dan pengaturan diet';
                if ($issue['parameter'] === 'Nadi') $recommendations[] = 'Pemeriksaan EKG dan konsultasi jantung';
                if ($issue['parameter'] === 'Gula Darah') $recommendations[] = 'Kontrol gula darah rutin';
                if ($issue['parameter'] === 'Kolesterol') $recommendations[] = 'Pola makan rendah lemak dan olahraga';
                if ($issue['parameter'] === 'Hemoglobin') $recommendations[] = 'Suplemen zat besi dan makanan bergizi';
                if ($issue['parameter'] === 'SpO2') $recommendations[] = 'Pemberian oksigen jika diperlukan';
                if ($issue['parameter'] === 'Suhu Tubuh') $recommendations[] = 'Penanganan demam dan identifikasi infeksi';
                if ($issue['parameter'] === 'Respiratory Rate') $recommendations[] = 'Pemeriksaan fungsi paru';
                if ($issue['parameter'] === 'Disabilitas') $recommendations[] = 'Pendampingan dan rehabilitasi';
            }
            if (empty($recommendations)) $recommendations[] = 'Pertahankan pola hidup sehat';
            
            respond([
                "success" => true,
                "data" => [
                    "status" => $status_map[$max_severity],
                    "label" => $label_map[$max_severity],
                    "color" => $color_map[$max_severity],
                    "issues" => $issues,
                    "recommendation" => implode('. ', array_unique($recommendations))
                ]
            ]);
        }
        break;
    
    default:
        respond(["success" => false, "message" => "Endpoint tidak ditemukan"], 404);
}
