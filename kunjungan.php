<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');
if (!isAdminStaff()) redirect('dashboard.php');

$page = 'kunjungan';
$pageTitle = 'Input Kunjungan';

$lansiaList = dbFetchAll("SELECT id, nik, nama_lengkap FROM lansia WHERE status_aktif = 'aktif' ORDER BY nama_lengkap");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_lansia = $_POST['id_lansia'] ?? '';
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'] ?? '';
    $jam_kunjungan = $_POST['jam_kunjungan'] ?? '';
    $jenis_kunjungan = $_POST['jenis_kunjungan'] ?? 'baru';
    $status_kesehatan = $_POST['status_kesehatan'] ?? 'sehat';
    $tekanan_darah_sistol = !empty($_POST['tekanan_darah_sistol']) ? (int)$_POST['tekanan_darah_sistol'] : null;
    $tekanan_darah_diastol = !empty($_POST['tekanan_darah_diastol']) ? (int)$_POST['tekanan_darah_diastol'] : null;
    $berat_badan = !empty($_POST['berat_badan']) ? (float)$_POST['berat_badan'] : null;
    $tinggi_badan = !empty($_POST['tinggi_badan']) ? (float)$_POST['tinggi_badan'] : null;
    $imt = ($berat_badan && $tinggi_badan) ? round($berat_badan / (($tinggi_badan/100) ** 2), 1) : null;
    $nadi = !empty($_POST['nadi']) ? (int)$_POST['nadi'] : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null;
    $status_disabilitas = $_POST['status_disabilitas'] ?? 'tidak_ada';
    $kelainan = $_POST['kelainan'] ?? '';
    $keluhan = $_POST['keluhan'] ?? '';
    $diagnosa = $_POST['diagnosa'] ?? '';
    $tindakan = $_POST['tindakan'] ?? '';
    $obat = $_POST['obat'] ?? '';
    
    $rujukan = $_POST['rujukan'] ?? '';
    
    // Get user id from session
    $user = getUser();
    $id_petugas = isset($user['id']) ? $user['id'] : null;
    
    // Validation
    if (empty($id_lansia)) {
        $error = 'Silakan pilih lansia terlebih dahulu';
    } elseif (empty($tanggal_kunjungan)) {
        $error = 'Tanggal kunjungan wajib diisi';
    } elseif (empty($jam_kunjungan)) {
        $error = 'Jam kunjungan wajib diisi';
    } elseif (!$id_petugas) {
        $error = 'Error: Sesi login tidak valid. Silakan login ulang.';
    } else {
        try {
            $tujuan_rujukan = $_POST['tujuan_rujukan'] ?? '';
            $rekomendasi = $_POST['rekomendasi'] ?? 'pemeriksaan_biasa';
            $gula_darah = $_POST['gula_darah'] ?? null;
            $kolesterol = $_POST['kolesterol'] ?? null;
            $hemoglobin = $_POST['hemoglobin'] ?? null;
            $spo2 = $_POST['spo2'] ?? null;
            $suhu_tubuh = $_POST['suhu_tubuh'] ?? null;
            $stmt = $conn->prepare("INSERT INTO visits (id_lansia, id_petugas, tanggal_kunjungan, jam_kunjungan, jenis_kunjungan, status_kesehatan, tekanan_darah_sistol, tekanan_darah_diastol, berat_badan, tinggi_badan, imt, nadi, respiratory_rate, status_disabilitas, kelainan, keluhan, diagnosa, tindakan, rujukan, tujuan_rujukan, rekomendasi, obat, gula_darah, kolesterol, hemoglobin, spo2, suhu_tubuh) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_lansia, $id_petugas, $tanggal_kunjungan, $jam_kunjungan,
                $jenis_kunjungan, $status_kesehatan, $tekanan_darah_sistol, $tekanan_darah_diastol,
                $berat_badan, $tinggi_badan, $imt, $nadi, $respiratory_rate,
                $status_disabilitas, $kelainan, $keluhan, $diagnosa, $tindakan, $rujukan,
                $tujuan_rujukan, $rekomendasi, $obat, $gula_darah, $kolesterol, $hemoglobin, $spo2, $suhu_tubuh
            ]);
            $message = 'Data kunjungan berhasil disimpan';
        } catch(PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

ob_start();
?>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
</div>
<?php endif; ?>

<?php if ($message): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i><?= $message ?>
</div>
<?php endif; ?>

<form method="POST" id="kunjForm" onsubmit="return convertTanggalSubmit()">
    <div class="custom-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-4">
                <div class="card-icon-bg">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <h5 class="mb-0">Data Kunjungan</h5>
                    <small class="text-muted">Waktu otomatis sesuai server</small>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><i class="bi bi-person me-1"></i>Pilih Lansia</label>
                    <select name="id_lansia" class="form-select" required>
                        <option value="">Pilih Lansia...</option>
                        <?php foreach ($lansiaList as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nama_lengkap']) ?> - <?= htmlspecialchars($l['nik']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-calendar me-1"></i>Tanggal</label>
                    <input type="text" id="tanggal_kunjungan" name="tanggal_kunjungan" class="form-control" placeholder="dd/mm/yyyy" required oninput="formatTanggal(this)" maxlength="10">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-clock me-1"></i>Jam</label>
                    <input type="time" id="jam_kunjungan" name="jam_kunjungan" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-tag me-1"></i>Jenis Kunjungan</label>
                    <select name="jenis_kunjungan" class="form-select">
                        <option value="baru">Baru</option>
                        <option value="lama">Lama</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-heart-pulse me-1"></i>Status Kesehatan</label>
                    <select name="status_kesehatan" class="form-select">
                        <option value="sehat">Sehat</option>
                        <option value="sakit_ringan">Sakit Ringan</option>
                        <option value="sakit_berat">Sakit Berat</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="custom-card mb-4">
        <div class="card-body">
            
            <!-- SECTION 1: Hasil Pemeriksaan -->
            <div class="d-flex align-items-center mb-4">
                <div class="card-icon-bg" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%);">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div>
                    <h5 class="mb-0">Hasil Pemeriksaan</h5>
                    <small class="text-muted">Hasil pemeriksaan fisik</small>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-speedometer2 me-1"></i>Sistol (mmHg)</label>
                    <input type="number" name="tekanan_darah_sistol" class="form-control" placeholder="120" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-speedometer2 me-1"></i>Diastol (mmHg)</label>
                    <input type="number" name="tekanan_darah_diastol" class="form-control" placeholder="80" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-activity me-1"></i>Nadi (bpm)</label>
                    <input type="number" name="nadi" class="form-control" placeholder="72" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-wind me-1"></i>RR (x/menit)</label>
                    <input type="number" name="respiratory_rate" class="form-control" placeholder="20" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-ruler me-1"></i>Berat Badan (kg)</label>
                    <input type="number" step="0.1" name="berat_badan" class="form-control" placeholder="65" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="bi bi-ruler-vertical me-1"></i>Tinggi Badan (cm)</label>
                    <input type="number" step="0.1" name="tinggi_badan" class="form-control" placeholder="165" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-percent me-1"></i>Status Disabilitas</label>
                    <select name="status_disabilitas" class="form-select">
                        <option value="tidak_ada">Tidak Ada</option>
                        <option value="ringan">Ringan</option>
                        <option value="sedang">Sedang</option>
                        <option value="berat">Berat</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label"><i class="bi bi-exclamation-triangle me-1"></i>Kelainan/Fisik (opsional)</label>
                    <input type="text" name="kelainan" class="form-control" placeholder="Kelainan fisik atau penyandang disabilitas...">
                </div>
            </div>

            <hr class="my-4 border-2 opacity-10">

            <!-- SECTION 2: Tindakan & Pengobatan -->
            <div class="d-flex align-items-center mb-4">
                <div class="card-icon-bg" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%);">
                    <i class="bi bi-pen"></i>
                </div>
                <div>
                    <h5 class="mb-0">Tindakan & Pengobatan</h5>
                    <small class="text-muted">Diagnosa, Tindakan, dan Obat</small>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-search me-1"></i>Keluhan Pasien</label>
                    <textarea name="keluhan" class="form-control" rows="2" placeholder="Keluhan yang disampaikan pasien..."></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-clipboard2-pulse me-1"></i>Diagnosa</label>
                    <textarea name="diagnosa" class="form-control" rows="2" placeholder="Hasil diagnosa dokter/petugas..."></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-bandaid me-1"></i>Tindakan Lanjut Medis</label>
                    <select name="tindakan" class="form-select">
                        <option value="">Pilih Tindakan Lanjut Medis</option>
                        <option value="Rawat Jalan">Rawat Jalan</option>
                        <option value="Rawat Inap">Rawat Inap</option>
                        <option value="Rujuk ke RS">Rujuk ke RS</option>
                        <option value="Kontrol Ulang">Kontrol Ulang</option>
                        <option value="Pulang dengan Terapi">Pulang dengan Terapi</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-capsule me-1"></i>Obat yang Diberikan</label>
                    <input type="text" name="obat" class="form-control" placeholder="Nama obat dan dosis (opsional)">
                </div>
                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-hospital me-1"></i>Rujukan</label>
                    <select name="rujukan" class="form-select">
                        <option value="">Pilih Rujukan</option>
                        <option value="Poli Umum">Poli Umum</option>
                        <option value="Poli Gigi">Poli Gigi</option>
                        <option value="Poli Konseling">Poli Konseling</option>
                        <option value="IGD Tindakan">IGD Tindakan</option>
                        <option value="Laboratorium">Laboratorium</option>
                        <option value="Poli Vaksin (Coldchain)">Poli Vaksin (Coldchain)</option>
                    </select>
                </div>
            </div>

            <hr class="my-4 border-2 opacity-10">

            <!-- SECTION 3: Simpan Kunjungan -->
            <div class="row g-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Simpan Kunjungan
                    </button>
                    <button type="reset" class="btn btn-secondary btn-lg ms-2">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>

<script>
function hitungIMT() {
    const bb = parseFloat(document.getElementById('berat_badan').value);
    const tb = parseFloat(document.getElementById('tinggi_badan').value);
    
    if (bb > 0 && tb > 0) {
        const imt = bb / Math.pow(tb / 100, 2);
        document.getElementById('imt_display').value = imt.toFixed(1);
        document.getElementById('imt_value').value = imt.toFixed(1);
    } else {
        document.getElementById('imt_display').value = '-';
        document.getElementById('imt_value').value = '';
    }
}

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

function convertTanggalDB(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('/');
    if (parts.length === 3) {
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    return dateStr;
}

function convertTanggalSubmit() {
    var tanggalInput = document.getElementById('tanggal_kunjungan');
    tanggalInput.value = convertTanggalDB(tanggalInput.value);
    return true;
}

function setWaktuSekarang() {
    const now = new Date();
    const jamInput = document.getElementById('jam_kunjungan');
    const tanggalInput = document.getElementById('tanggal_kunjungan');
    
    const hari = String(now.getDate()).padStart(2, '0');
    const bulan = String(now.getMonth() + 1).padStart(2, '0');
    const tahun = now.getFullYear();
    const jam = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    
    tanggalInput.value = hari + '/' + bulan + '/' + tahun;
    jamInput.value = jam + ':' + menit;
}

window.onload = setWaktuSekarang;
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>