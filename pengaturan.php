<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');

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

// Tambah pengguna baru (super_admin only)
if (isset($_POST['add_user']) && isSuperAdmin()) {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $new_nama = trim($_POST['new_nama'] ?? '');
    $new_role = $_POST['new_role'] ?? 'admin';

    if (empty($new_username) || empty($new_password) || empty($new_nama)) {
        $error = 'Semua field wajib diisi';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        try {
            $existing = dbFetch("SELECT id FROM users WHERE username = ?", [$new_username]);
            if ($existing) {
                $error = 'Username sudah digunakan';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                dbQuery("INSERT INTO users (username, password, nama_lengkap, role, status) VALUES (?, ?, ?, ?, 'active')", [$new_username, $hash, $new_nama, $new_role]);
                $message = 'Pengguna berhasil ditambahkan';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Nonaktifkan pengguna (super_admin only)
if (isset($_GET['deactivate']) && isSuperAdmin()) {
    $id = (int)$_GET['deactivate'];
    if ($id !== (int)$_SESSION['user']['id']) {
        dbQuery("UPDATE users SET status = 'inactive' WHERE id = ?", [$id]);
        $message = 'Pengguna berhasil dinonaktifkan';
    } else {
        $error = 'Tidak dapat menonaktifkan akun sendiri';
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

<?php if (isSuperAdmin()): ?>
<hr class="my-5">
<div class="custom-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="bi bi-people me-2"></i>Kelola Pengguna
            </h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                <i class="bi bi-plus-lg me-1"></i>Tambah Pengguna
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $users = dbFetchAll("SELECT * FROM users ORDER BY id"); $no = 1; ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                        <td><span class="badge bg-<?= $u['role'] === 'super_admin' ? 'danger' : 'primary' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $u['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <?php if ($u['status'] === 'active' && $u['id'] != $_SESSION['user']['id']): ?>
                            <a href="?deactivate=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Nonaktifkan pengguna <?= htmlspecialchars($u['username']) ?>?')">
                                <i class="bi bi-person-x"></i> Nonaktifkan
                            </a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="new_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="new_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="new_role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>