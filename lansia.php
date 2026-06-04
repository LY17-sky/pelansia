<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');

// Super admin read-only untuk data lansia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSuperAdmin()) {
    redirect('lansia.php');
}

$page = 'lansia';
$pageTitle = 'Data Lansia';

// Get actual villages from database
$villages = dbFetchAll("SELECT id, nama_desa FROM villages ORDER BY nama_desa");
$desaList = [];
$desaNameToId = [];
foreach ($villages as $v) {
    $desaList[$v['id']] = $v['nama_desa'];
    $desaNameToId[$v['nama_desa']] = $v['id'];
}

function getDesaId($nama_desa) {
    global $desaNameToId, $villages;
    if (!$nama_desa) return null;
    
    // If it's already a numeric ID, validate it exists
    if (is_numeric($nama_desa)) {
        $id = (int)$nama_desa;
        foreach ($villages as $v) {
            if ($v['id'] == $id) return $id;
        }
        return null; // Invalid ID
    }
    
    // If it's a name, get the ID
    return $desaNameToId[$nama_desa] ?? null;
}

$search = $_GET['search'] ?? '';
$edit_id = $_GET['edit'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $tanggal_lahir_input = $_POST['tanggal_lahir'];
        function convertTanggal($dateStr) {
            if (!$dateStr) return '';
            $parts = explode('/', $dateStr);
            if (count($parts) === 3) {
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
            return $dateStr;
        }
        $tanggal_lahir_db = convertTanggal($tanggal_lahir_input);
        
        if ($_POST['action'] === 'create') {
            // Check if NIK already exists
            $existingNik = dbFetch("SELECT id FROM lansia WHERE nik = ?", [$_POST['nik']]);
            if ($existingNik) {
                $message = 'NIK sudah terdaftar! Silakan gunakan NIK yang berbeda.';
            } else {
                // Validate desa
                $desaId = getDesaId($_POST['id_desa']);
                if (!$desaId && $_POST['id_desa']) {
                    $message = 'Desa yang dipilih tidak valid!';
                } else {
                    try {
                        $kategori = hitungKategoriLansiaPHP($_POST['tanggal_lahir']);
                        $status_risiko = $_POST['status_risiko'] ?? 'risiko_rendah';
                        
                        $stmt = $conn->prepare("INSERT INTO lansia (nik, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, nama_keluarga, hubungan_keluarga, no_telepon_keluarga, tempat_lahir, bpjs, status_kesehatan, kategori_lansia, status_risiko, status_aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
                        $stmt->execute([
                            $_POST['nik'], $_POST['nama_lengkap'], $tanggal_lahir_db,
                            $_POST['jenis_kelamin'], $_POST['alamat'], $desaId,
                            $_POST['no_telepon'], $_POST['nama_keluarga'], $_POST['hubungan_keluarga'],
                            $_POST['no_telepon_keluarga'], $_POST['tempat_lahir'] ?? '', $_POST['bpjs'],
                            $_POST['status_kesehatan'] ?? 'sehat', $kategori, $status_risiko
                        ]);
                        $message = 'Data berhasil disimpan';
                    } catch(PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($_POST['action'] === 'update') {
            // Check if NIK already exists for other records
            $existingNik = dbFetch("SELECT id FROM lansia WHERE nik = ? AND id != ?", [$_POST['nik'], $_POST['id']]);
            if ($existingNik) {
                $message = 'NIK sudah terdaftar! Silakan gunakan NIK yang berbeda.';
            } else {
                // Validate desa
                $desaId = getDesaId($_POST['id_desa']);
                if (!$desaId && $_POST['id_desa']) {
                    $message = 'Desa yang dipilih tidak valid!';
                } else {
                    try {
                        $kategori = hitungKategoriLansiaPHP($_POST['tanggal_lahir']);
                        $status_risiko = $_POST['status_risiko'] ?? 'risiko_rendah';
                        
                        $stmt = $conn->prepare("UPDATE lansia SET nik=?, nama_lengkap=?, tanggal_lahir=?, jenis_kelamin=?, alamat=?, id_desa=?, no_telepon=?, nama_keluarga=?, hubungan_keluarga=?, no_telepon_keluarga=?, tempat_lahir=?, bpjs=?, status_kesehatan=?, kategori_lansia=?, status_risiko=? WHERE id=?");
                        $stmt->execute([
                            $_POST['nik'], $_POST['nama_lengkap'], $tanggal_lahir_db,
                            $_POST['jenis_kelamin'], $_POST['alamat'], $desaId,
                            $_POST['no_telepon'], $_POST['nama_keluarga'], $_POST['hubungan_keluarga'],
                            $_POST['no_telepon_keluarga'], $_POST['tempat_lahir'] ?? '', $_POST['bpjs'],
                            $_POST['status_kesehatan'], $kategori, $status_risiko, $_POST['id']
                        ]);
                        $message = 'Data berhasil diperbarui';
                    } catch(PDOException $e) {
                        $message = 'Error: ' . $e->getMessage();
                    }
                }
            }
            $edit_id = '';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    dbQuery("UPDATE lansia SET status_aktif = 'nonaktif' WHERE id = ?", [$id]);
    $message = 'Data berhasil dihapus';
}

// Fetch lansia data with search
$sql = "SELECT l.*, v.nama_desa FROM lansia l LEFT JOIN villages v ON l.id_desa = v.id WHERE l.status_aktif = 'aktif'";
if ($search) {
    $searchParam = "%$search%";
    $sql .= " AND (l.nama_lengkap LIKE ? OR l.nik LIKE ?)";
    $lansia = dbFetchAll($sql, [$searchParam, $searchParam]);
} else {
    $sql .= " ORDER BY l.id DESC";
    $lansia = dbFetchAll($sql);
}

$edit_data = null;
if ($edit_id) {
    $edit_data = dbFetch("SELECT * FROM lansia WHERE id = ?", [$edit_id]);
}

ob_start();
?>

<?php if (isSuperAdmin()): ?>
<div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3" style="background: linear-gradient(135deg, #e8f4fd 0%, #d0ebf9 100%); border: 1px solid #b8dff5; border-left: 3px solid #4A90D9; border-radius: 8px;">
    <div style="width: 28px; height: 28px; background: #4A90D9; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="bi bi-eye" style="color: white; font-size: 14px;"></i>
    </div>
    <div>
        <strong style="color: #2c5282; font-size: 13px;">Mode Read-Only</strong>
        <span style="color: #4a5568; font-size: 12px;">— Untuk mengubah data, hubungi admin.</span>
    </div>
</div>
<?php endif; ?>

<?php if ($message): ?>
<div class="alert alert-success py-2 px-3 mb-3">
    <i class="bi bi-check-circle me-2"></i><?= $message ?>
</div>
<?php endif; ?>

<?php if (!isSuperAdmin()): ?>
<div class="custom-card mb-3">
    <div class="card-body">
        <h5 class="mb-3">
            <i class="bi bi-<?= $edit_data ? 'pencil-square' : 'person-plus' ?> me-2"></i>
            <?= $edit_data ? 'Edit Data Lansia' : 'Tambah Data Lansia' ?>
        </h5>
        
        <?php if ($edit_data): ?>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            
            <!-- BAGIAN 1: DATA PRIBADI -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                <h6 class="mb-3 fw-bold" style="color: #667eea;">
                    <i class="bi bi-person-circle me-2"></i>Data Pribadi
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NIK <span class="text-danger">*</span></label>
                        <input type="text" name="nik" class="form-control" value="<?= htmlspecialchars($edit_data['nik']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($edit_data['nama_lengkap']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="L" <?= $edit_data['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $edit_data['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="text" name="tanggal_lahir" class="form-control" placeholder="dd/mm/yyyy" value="<?= $edit_data['tanggal_lahir'] ? date('d/m/Y', strtotime($edit_data['tanggal_lahir'])) : '' ?>" required oninput="formatTanggal(this)" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($edit_data['no_telepon'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Kesehatan</label>
                        <select name="status_kesehatan" class="form-select">
                            <option value="sehat" <?= ($edit_data['status_kesehatan'] ?? 'sehat') === 'sehat' ? 'selected' : '' ?>>Sehat</option>
                            <option value="sakit_ringan" <?= $edit_data['status_kesehatan'] === 'sakit_ringan' ? 'selected' : '' ?>>Sakit Ringan</option>
                            <option value="sakit_berat" <?= $edit_data['status_kesehatan'] === 'sakit_berat' ? 'selected' : '' ?>>Sakit Berat</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Risiko</label>
                        <select name="status_risiko" class="form-select">
                            <option value="risiko_rendah" <?= ($edit_data['status_risiko'] ?? 'risiko_rendah') === 'risiko_rendah' ? 'selected' : '' ?>>Risiko Rendah</option>
                            <option value="risiko_sedang" <?= ($edit_data['status_risiko'] ?? '') === 'risiko_sedang' ? 'selected' : '' ?>>Risiko Sedang</option>
                            <option value="risiko_tinggi" <?= ($edit_data['status_risiko'] ?? '') === 'risiko_tinggi' ? 'selected' : '' ?>>Risiko Tinggi (Risti)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kabupaten</label>
                        <input type="text" class="form-control" value="Kabupaten Pekalongan" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kecamatan</label>
                        <input type="text" class="form-control" value="Kecamatan Doro" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kelurahan / Desa <span class="text-danger">*</span></label>
                        <?php
                        $editDesaId = $edit_data ? $edit_data['id_desa'] : '';
                        ?>
                        <input type="hidden" name="id_desa" id="edit_id_desa_hidden" value="<?= htmlspecialchars($editDesaId) ?>" required>
                        <div class="searchable-select" id="edit_desa_widget">
                            <div class="ss-display form-select" tabindex="0" onclick="toggleSS('edit_desa_widget')" onkeydown="if(event.key==='Enter'||event.key===' ')toggleSS('edit_desa_widget')" style="background-color: #ffffff !important; cursor: pointer;">
                                <span class="ss-label"><?= htmlspecialchars($desaList[$editDesaId] ?? 'Cari desa') ?></span>
                            </div>
                            <div class="ss-dropdown" style="display:none;">
                                <div class="ss-search-wrap">
                                    <input type="text" class="ss-search form-control form-control-sm" placeholder="Cari desa" oninput="filterSS(this,'edit_desa_widget')" autocomplete="off" style="background-color: #ffffff !important;">
                                </div>
                                <ul class="ss-list">
                                    <?php foreach ($villages as $v): ?>
                                    <li data-value="<?= $v['id'] ?>" onclick="selectSS('edit_desa_widget','<?= $v['id'] ?>','<?= htmlspecialchars($v['nama_desa']) ?>')"><?= htmlspecialchars($v['nama_desa']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="alamat" class="form-control" value="<?= htmlspecialchars($edit_data['alamat'] ?? '') ?>" placeholder="Isi RT/RW" required>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 2: DATA KEPESERTAAN -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                <h6 class="mb-3 fw-bold" style="color: #667eea;">
                    <i class="bi bi-file-earmark-medical me-2"></i>Data Kepesertaan
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">No. BPJS</label>
                        <input type="text" name="bpjs" class="form-control" value="<?= htmlspecialchars($edit_data['bpjs'] ?? '') ?>" placeholder="Nomor BPJS Kesehatan">
                    </div>

                </div>
            </div>

            <!-- BAGIAN 3: DATA WALI -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                <h6 class="mb-3 fw-bold" style="color: #667eea;">
                    <i class="bi bi-people me-2"></i>Data Wali
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Wali <span class="text-danger">*</span></label>
                        <input type="text" name="nama_keluarga" class="form-control" value="<?= htmlspecialchars($edit_data['nama_keluarga'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hubungan dengan Lansia <span class="text-danger">*</span></label>
                        <select name="hubungan_keluarga" class="form-select" required>
                            <option value="">Pilih Hubungan</option>
                            <option value="suami" <?= ($edit_data['hubungan_keluarga'] ?? '') === 'suami' ? 'selected' : '' ?>>Suami</option>
                            <option value="istri" <?= ($edit_data['hubungan_keluarga'] ?? '') === 'istri' ? 'selected' : '' ?>>Istri</option>
                            <option value="anak" <?= ($edit_data['hubungan_keluarga'] ?? '') === 'anak' ? 'selected' : '' ?>>Anak</option>
                            <option value="keluarga" <?= ($edit_data['hubungan_keluarga'] ?? '') === 'keluarga' ? 'selected' : '' ?>>Keluarga</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Telepon Wali</label>
                        <input type="text" name="no_telepon_keluarga" class="form-control" value="<?= htmlspecialchars($edit_data['no_telepon_keluarga'] ?? '') ?>" placeholder="Nomor HP wali">
                    </div>
                </div>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-lg me-2"></i>Simpan Perubahan
                </button>
                <a href="lansia.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-lg me-2"></i>Batal
                </a>
            </div>
        </form>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <!-- BAGIAN 1: DATA PRIBADI -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                <h6 class="mb-3 fw-bold" style="color: #667eea;">
                    <i class="bi bi-person-circle me-2"></i>Data Pribadi
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NIK <span class="text-danger">*</span></label>
                        <input type="text" name="nik" class="form-control" placeholder="Nomor NIK" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="text" name="tanggal_lahir" class="form-control" placeholder="dd/mm/yyyy" required oninput="formatTanggal(this)" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" class="form-control" placeholder="No. HP pasien">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Kesehatan</label>
                        <select name="status_kesehatan" class="form-select">
                            <option value="sehat">Sehat</option>
                            <option value="sakit_ringan">Sakit Ringan</option>
                            <option value="sakit_berat">Sakit Berat</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Risiko</label>
                        <select name="status_risiko" class="form-select">
                            <option value="risiko_rendah">Risiko Rendah</option>
                            <option value="risiko_sedang">Risiko Sedang</option>
                            <option value="risiko_tinggi">Risiko Tinggi (Risti)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kabupaten</label>
                        <input type="text" class="form-control" value="Kabupaten Pekalongan" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kecamatan</label>
                        <input type="text" class="form-control" value="Kecamatan Doro" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kelurahan / Desa <span class="text-danger">*</span></label>
                        <input type="hidden" name="id_desa" id="create_id_desa_hidden" value="" required>
                        <div class="searchable-select" id="create_desa_widget">
                            <div class="ss-display form-select" tabindex="0" onclick="toggleSS('create_desa_widget')" onkeydown="if(event.key==='Enter'||event.key===' ')toggleSS('create_desa_widget')" style="background-color: #ffffff !important; cursor: pointer;">
                                <span class="ss-label ss-placeholder">Cari desa</span>
                            </div>
                            <div class="ss-dropdown" style="display:none;">
                                <div class="ss-search-wrap">
                                    <input type="text" class="ss-search form-control form-control-sm" placeholder="Cari desa" oninput="filterSS(this,'create_desa_widget')" autocomplete="off" style="background-color: #ffffff !important;">
                                </div>
                                <ul class="ss-list">
                                    <?php foreach ($villages as $v): ?>
                                    <li data-value="<?= $v['id'] ?>" onclick="selectSS('create_desa_widget','<?= $v['id'] ?>','<?= htmlspecialchars($v['nama_desa']) ?>')"><?= htmlspecialchars($v['nama_desa']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="alamat" class="form-control" placeholder="Isi RT/RW" required>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 2: DATA KEPESERTAAN -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                <h6 class="mb-3 fw-bold" style="color: #667eea;">
                    <i class="bi bi-file-earmark-medical me-2"></i>Data Kepesertaan
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">No. BPJS</label>
                        <input type="text" name="bpjs" class="form-control" placeholder="Nomor BPJS Kesehatan">
                    </div>

                </div>
            </div>

            <!-- BAGIAN 3: DATA WALI -->
            <div class="mb-4 p-4 border rounded-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                <h6 class="mb-3 fw-bold" style="color: #667eea;">
                    <i class="bi bi-people me-2"></i>Data Wali
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Wali <span class="text-danger">*</span></label>
                        <input type="text" name="nama_keluarga" class="form-control" placeholder="Nama wali pasien" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hubungan dengan Lansia <span class="text-danger">*</span></label>
                        <select name="hubungan_keluarga" class="form-select" required>
                            <option value="">Pilih Hubungan</option>
                            <option value="suami">Suami</option>
                            <option value="istri">Istri</option>
                            <option value="anak">Anak</option>
                            <option value="keluarga">Keluarga</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Telepon Wali</label>
                        <input type="text" name="no_telepon_keluarga" class="form-control" placeholder="No. HP wali">
                    </div>
                </div>
            </div>

            <!-- TOMBOL AKSI -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save me-2"></i>Simpan Data
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="custom-card">
    <div class="card-body py-3 px-3">
        <form method="GET" class="row g-1 mb-2">
            <div class="col-auto">
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau NIK..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Cari
                </button>
            </div>
            <?php if ($search): ?>
            <div class="col-auto">
                <a href="lansia.php" class="btn btn-secondary">Reset</a>
            </div>
            <?php endif; ?>
        </form>

        <div style="width: 100%; overflow-x: auto;">
            <table style="width: 100%; table-layout: fixed; border-collapse: collapse; background: white; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
<thead>
                     <tr>
                          <th style="width: <?= isSuperAdmin() ? '13%' : '12%'; ?>; padding: 12px 8px;" class="text-nowrap">NIK</th>
                          <th style="width: <?= isSuperAdmin() ? '17%' : '16%'; ?>; padding: 12px 8px;" class="text-nowrap">Nama Lengkap</th>
                          <th style="width: 5%; padding: 12px 8px;" class="text-nowrap">Usia</th>
                          <th style="width: 9%; padding: 12px 8px;" class="text-nowrap">Tgl Lahir</th>
                          <th style="width: 3%; padding: 12px 8px;" class="text-nowrap">JK</th>
                          <th style="width: <?= isSuperAdmin() ? '14%' : '13%'; ?>; padding: 12px 8px;" class="text-nowrap">Alamat</th>
                          <th style="width: <?= isSuperAdmin() ? '10%' : '9%'; ?>; padding: 12px 8px;" class="text-nowrap">Desa</th>
                          <th style="width: 8%; padding: 12px 10px 12px 8px;" class="text-nowrap">No. Telepon</th>
                          <th style="width: 9%; padding: 12px 8px 12px 10px;" class="text-nowrap">Wali Pasien</th>
                           <th style="width: <?= isSuperAdmin() ? '12%' : '9%'; ?>; padding: 12px 10px 12px 8px;" class="text-nowrap">Status Risiko</th>
                            <?php if (!isSuperAdmin()): ?>
                            <th style="width: 7%; padding: 12px 8px 12px 10px;" class="text-nowrap">Aksi</th>
                            <?php endif; ?>
                       </tr>
                   </thead>
<tbody>
                      <?php foreach ($lansia as $row): ?>
                      <?php $usia = hitungUsiaPHP($row['tanggal_lahir']); ?>
                      <tr>
                          <td class="text-nowrap" style="padding: 8px 8px;"><?= htmlspecialchars($row['nik']) ?></td>
                           <td class="text-nowrap" style="padding: 8px 8px;"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                           <td class="text-nowrap" style="padding: 8px 8px;"><?= $usia ?> th</td>
                           <td class="text-nowrap" style="padding: 8px 8px;"><?= date('d/m/Y', strtotime($row['tanggal_lahir'])) ?></td>
                           <td class="text-nowrap" style="padding: 8px 8px;"><span class="badge bg-<?= $row['jenis_kelamin'] === 'L' ? 'primary' : 'danger' ?>"><?= $row['jenis_kelamin'] ?></span></td>
                           <td style="padding: 8px 8px;" title="<?= htmlspecialchars($row['alamat'] ?? '') ?>"><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                           <td style="padding: 8px 8px;" title="<?= htmlspecialchars($row['nama_desa'] ?? '') ?>"><?= htmlspecialchars($row['nama_desa'] ?? '-') ?></td>
                           <td class="text-nowrap" style="padding: 8px 10px 8px 8px;"><?= htmlspecialchars($row['no_telepon'] ?? '-') ?></td>
                           <td style="padding: 8px 8px 8px 10px;">
                              <?php
                              $waliInfo = [];
                              if (!empty($row['nama_keluarga'])) {
                                  $waliInfo[] = htmlspecialchars($row['nama_keluarga']);
                              }
                              if (!empty($row['hubungan_keluarga'])) {
                                  $hubunganMap = [
                                      'suami' => 'Suami',
                                      'istri' => 'Istri',
                                      'anak' => 'Anak',
                                      'keluarga' => 'Keluarga'
                                  ];
                                  $waliInfo[] = '(' . ($hubunganMap[$row['hubungan_keluarga']] ?? $row['hubungan_keluarga']) . ')';
                              }
                              $waliText = implode(' ', $waliInfo) ?: '-';
                              echo '<span title="' . htmlspecialchars($waliText) . '">' . $waliText . '</span>';
                              ?>
                          </td>
                           <td class="text-nowrap" style="padding: 8px 10px 8px 8px;">
                               <span class="badge bg-<?= $row['status_risiko'] === 'risiko_rendah' ? 'success' : ($row['status_risiko'] === 'risiko_sedang' ? 'warning' : 'danger') ?>">
                                   <?= $row['status_risiko'] === 'risiko_rendah' ? 'Rendah' : ($row['status_risiko'] === 'risiko_sedang' ? 'Sedang' : 'Tinggi') ?>
                               </span>
                           </td>
                            <?php if (!isSuperAdmin()): ?>
                            <td class="text-nowrap" style="padding: 8px 8px 8px 10px;">
                                <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Yakin hapus data <?= htmlspecialchars($row['nama_lengkap']) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                      </tr>
                     <?php endforeach; ?>
                     <?php if (empty($lansia)): ?>
                     <tr>
                          <td colspan="<?= isSuperAdmin() ? 10 : 11 ?>" class="text-center text-muted py-4">
                             <i class="bi bi-inbox me-2"></i>Tidak ada data
                         </td>
                     </tr>
                     <?php endif; ?>
                 </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* === Fix table layout === */
table {
    width: 100% !important;
    min-width: 1000px !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
    display: table !important;
}

table thead {
    display: table-header-group !important;
}

table tbody {
    display: table-row-group !important;
}

table tr {
    display: table-row !important;
}

table th,
table td {
    display: table-cell !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Hover effect */
table tbody tr:hover {
    background-color: #f5f5f5;
}

a[href*="detail-lansia.php"] {
    color: inherit !important;
    text-decoration: none !important;
}
a[href*="detail-lansia.php"]:hover {
    color: #4A90D9 !important;
    text-decoration: underline !important;
}

/* === Searchable Select === */
.searchable-select { position: relative; }
.ss-display {
    cursor: pointer;
    user-select: none;
    background-color: #ffffff !important;
    display: flex;
    align-items: center;
}
.ss-label { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #212529; }
.ss-label.ss-placeholder { color: #6c757d; }
.ss-dropdown {
    position: absolute;
    top: calc(100% + 2px);
    left: 0; right: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    overflow: hidden;
}
.ss-search-wrap { padding: 8px 8px 4px; border-bottom: 1px solid #f0f0f0; background-color: #ffffff !important; }
.ss-search { width: 100%; background-color: #ffffff !important; }
.ss-list {
    list-style: none;
    margin: 0;
    padding: 4px 0;
    max-height: 200px;
    overflow-y: auto;
}
.ss-list li {
    padding: 7px 14px;
    cursor: pointer;
    font-size: 0.9rem;
    color: #212529;
    transition: background .15s;
}
.ss-list li:hover, .ss-list li.active { background: #667eea; color: #fff; }
.ss-list li.hidden { display: none; }
.ss-list li.ss-empty { color: #6c757d; font-style: italic; cursor: default; padding: 10px 14px; }
.ss-list li.ss-empty:hover { background: none; }
</style>

<script>
function formatTanggal(input) {
    var value = input.value.replace(/[^0-9]/g, '');
    if (value.length >= 2) {
        value = value.substring(0,2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0,5) + '/' + value.substring(5);
    }
    input.value = value;
}

function convertTanggal(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('/');
    if (parts.length === 3) {
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    return dateStr;
}

/* === Searchable Select === */
function toggleSS(widgetId) {
    var widget = document.getElementById(widgetId);
    var dropdown = widget.querySelector('.ss-dropdown');
    var isOpen = widget.classList.contains('open');
    // Close all other dropdowns first
    document.querySelectorAll('.searchable-select.open').forEach(function(w) {
        w.classList.remove('open');
        w.querySelector('.ss-dropdown').style.display = 'none';
    });
    if (!isOpen) {
        widget.classList.add('open');
        dropdown.style.display = 'block';
        var search = dropdown.querySelector('.ss-search');
        if (search) { setTimeout(function(){ search.focus(); }, 50); }
    }
}

function filterSS(input, widgetId) {
    var query = input.value.trim().toLowerCase();
    var widget = document.getElementById(widgetId);
    var items = widget.querySelectorAll('.ss-list li:not(.ss-empty)');
    var visible = 0;
    items.forEach(function(li) {
        var match = li.textContent.toLowerCase().indexOf(query) !== -1;
        li.classList.toggle('hidden', !match);
        if (match) visible++;
    });
    var empty = widget.querySelector('.ss-list .ss-empty');
    if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
}

function selectSS(widgetId, value, label) {
    var widget = document.getElementById(widgetId);
    // Determine the hidden input id
    var hiddenId = widgetId === 'edit_desa_widget' ? 'edit_id_desa_hidden' : 'create_id_desa_hidden';
    document.getElementById(hiddenId).value = value;
    var lbl = widget.querySelector('.ss-label');
    lbl.textContent = label;
    lbl.classList.remove('ss-placeholder');
    // Mark active
    widget.querySelectorAll('.ss-list li').forEach(function(li){ li.classList.remove('active'); });
    widget.querySelectorAll('.ss-list li[data-value="' + value + '"]').forEach(function(li){ li.classList.add('active'); });
    // Close
    widget.classList.remove('open');
    widget.querySelector('.ss-dropdown').style.display = 'none';
    // Reset search
    var search = widget.querySelector('.ss-search');
    if (search) { search.value = ''; filterSS(search, widgetId); }
}

// Close on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.searchable-select')) {
        document.querySelectorAll('.searchable-select.open').forEach(function(w) {
            w.classList.remove('open');
            w.querySelector('.ss-dropdown').style.display = 'none';
        });
    }
});

// Validate hidden input on form submit
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var hidden = form.querySelector('input[name="id_desa"]');
            if (hidden && !hidden.value) {
                e.preventDefault();
                var widget = form.querySelector('.searchable-select');
                if (widget) {
                    widget.querySelector('.ss-display').style.borderColor = '#dc3545';
                    widget.querySelector('.ss-display').style.boxShadow = '0 0 0 0.25rem rgba(220,53,69,.25)';
                }
                alert('Silakan pilih Kelurahan / Desa terlebih dahulu.');
            }
        });
    });
    // Mark empty placeholder labels
    document.querySelectorAll('.ss-label').forEach(function(lbl) {
        if (lbl.textContent.indexOf('Cari desa') === 0) lbl.classList.add('ss-placeholder');
    });
    // Init empty message in lists
    document.querySelectorAll('.ss-list').forEach(function(ul) {
        var empty = document.createElement('li');
        empty.className = 'ss-empty';
        empty.style.display = 'none';
        empty.textContent = 'Desa tidak ditemukan';
        ul.appendChild(empty);
    });
});
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>