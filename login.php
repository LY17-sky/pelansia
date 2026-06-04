<?php
require_once __DIR__ . '/inc/functions.php';

global $conn, $db_error;

$error = '';

if ($db_error) {
    $error = $db_error;
} elseif ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $user = dbFetch("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?", [$username]);
    
    if ($user) {
        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            // Generate token for SPA compatibility
            $token = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, datetime('now', '+24 hours'))");
            $stmt->execute([$user['id'], $token]);
            redirect('dashboard.php');
        } else {
            $error = 'Password salah';
        }
    } else {
        $error = 'Username tidak ditemukan';
    }
}

$pageTitle = 'Login - Sistem Pelaporan Lansia';
ob_start();
?>
<div class="login-card">
    <div class="text-center">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(177, 201, 239, 0.3);">
            <i class="bi bi-heart-pulse" style="font-size: 40px; color: white;"></i>
        </div>
        <h3 class="mt-3" style="font-family: 'Poppins', sans-serif; font-weight: 700; letter-spacing: 2px;">PELANSIA</h3>
        <p>Sistem Pelaporan Kunjungan Lansia Puskesmas</p>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text" style="border-radius: 10px 0 0 10px; border: 2px solid var(--border); border-right: none; background: var(--light);">
                    <i class="bi bi-person"></i>
                </span>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" style="border-radius: 0 10px 10px 0;" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text" style="border-radius: 10px 0 0 10px; border: 2px solid var(--border); border-right: none; background: var(--light);">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" style="border-radius: 0 10px 10px 0;" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2" style="font-size: 15px;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </button>
    </form>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>