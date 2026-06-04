<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');
if (!isSuperAdmin()) redirect('dashboard.php');

$page = 'pengaturan';
$pageTitle = 'Pengaturan Akun';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_username = $_POST['username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $user_id = $_SESSION['user']['id'];
    $user = dbFetch("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    if (!password_verify($current_password, $user['password'])) {
        $error = 'Password saat ini salah';
    } else {
        try {
            $updates = [];
            $params = [];
            
            // Update username if provided
            if (!empty($new_username) && $new_username !== $user['username']) {
                $existing = dbFetch("SELECT id FROM users WHERE username = ? AND id != ?", [$new_username, $user_id]);
                if ($existing) {
                    $error = 'Username sudah digunakan';
                } else {
                    $updates[] = "username = ?";
                    $params[] = $new_username;
                }
            }
            
            // Update password if provided
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'Konfirmasi password tidak cocok';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password minimal 6 karakter';
                } else {
                    $updates[] = "password = ?";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($error) && !empty($updates)) {
                $params[] = $user_id;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                dbQuery($sql, $params);
                
                // Update session
                $_SESSION['user']['username'] = $new_username ?: $user['username'];
                $message = 'Pengaturan akun berhasil diperbarui';
            } elseif (empty($updates)) {
                $error = 'Tidak ada perubahan yang dilakukan';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

ob_start();
?>

<div class="custom-card">
    <div class="card-body">
        <h5 class="mb-4">
            <i class="bi bi-gear me-2"></i>Pengaturan Akun
        </h5>
        
        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i><?= $message ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Current Info -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa;">
                <h6 class="mb-3 fw-bold" style="color: #4A90D9;">
                    <i class="bi bi-info-circle me-2"></i>Informasi Saat Ini
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['role']) ?>" disabled>
                    </div>
                </div>
            </div>
            
            <!-- Change Password Section -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa;">
                <h6 class="mb-3 fw-bold" style="color: #4A90D9;">
                    <i class="bi bi-key me-2"></i>Ubah Username & Password
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username Baru</label>
                        <input type="text" name="username" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                </div>
                <small class="text-muted">Kosongkan field yang tidak ingin diubah. Password minimal 6 karakter.</small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save me-2"></i>Simpan Perubahan
            </button>
            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>