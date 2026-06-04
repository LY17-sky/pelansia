<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');

$lansia_id = $_GET['id'] ?? null;
if (!$lansia_id) {
    redirect('lansia.php');
}

// Ambil data lansia
$lansia = dbFetch("SELECT l.*, v.nama_desa FROM lansia l LEFT JOIN villages v ON l.id_desa = v.id WHERE l.id = ? AND l.status_aktif = 'aktif'", [$lansia_id]);

if (!$lansia) {
    echo '<div class="alert alert-danger">Data lansia tidak ditemukan</div>';
    exit;
}

// Ambil riwayat kunjungan
$visits = dbFetchAll("SELECT v.*, u.nama_lengkap as petugas_nama FROM visits v LEFT JOIN users u ON v.id_petugas = u.id WHERE v.id_lansia = ? ORDER BY v.tanggal_kunjungan DESC, v.jam_kunjungan DESC", [$lansia_id]);

$page = 'lansia';
$pageTitle = 'Detail Lansia - ' . $lansia['nama_lengkap'];

// Hitung usia
$usia = hitungUsiaPHP($lansia['tanggal_lahir']);
$kategori = $lansia['kategori_lansia'];
$kategori_label = getLabelKategoriLansia($kategori);
$status_risti = isRisti($lansia['status_risiko']);

ob_start();
?>

<style>
    .header-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    }

    .header-section h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .info-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        margin-right: 10px;
        margin-top: 10px;
    }

    .data-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .data-card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        border-color: #667eea;
    }

    .card-header-custom {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        padding: 20px;
        border-bottom: 2px solid #667eea;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-header-custom h5 {
        margin: 0;
        color: #333;
        font-weight: 600;
    }

    .card-header-custom i {
        color: #667eea;
        font-size: 1.3rem;
    }

    .card-body-custom {
        padding: 25px;
    }

    .info-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }

    .info-label {
        font-size: 0.85rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    .badge-custom {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .riwayat-table {
        margin-top: 15px;
    }

    .riwayat-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .riwayat-table tbody tr {
        border-bottom: 1px solid #e0e0e0;
        transition: background-color 0.2s ease;
    }

    .riwayat-table tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .riwayat-table tbody tr:hover .expand-icon {
        color: #667eea;
    }

    .expand-icon {
        transition: color 0.2s ease;
    }

    .detail-row-content {
        background: #f8f9fa;
        padding: 25px;
    }

    .detail-section {
        margin-bottom: 20px;
    }

    .detail-section h6 {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 500;
        color: #666;
        min-width: 150px;
    }

    .detail-value {
        font-weight: 600;
        color: #333;
        text-align: right;
    }

    .alert-risti {
        background: linear-gradient(135deg, #ff6b6b15 0%, #ee563515 100%);
        border: 2px solid #ff6b6b;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .alert-risti-icon {
        font-size: 1.8rem;
        color: #ff6b6b;
        flex-shrink: 0;
    }

    .alert-risti-content {
        color: #ee5635;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 25px;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #ddd;
    }

    .status-badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
</style>

<div class="header-section">
    <a href="lansia.php" class="btn-back" style="color: white; text-decoration: none; margin-bottom: 15px; display: inline-flex; gap: 8px;">
        <i class="bi bi-arrow-left"></i>Kembali
    </a>
    <h1><?= htmlspecialchars($lansia['nama_lengkap']) ?></h1>
    <div>
        <span class="info-badge">
            <i class="bi bi-credit-card"></i> <?= htmlspecialchars($lansia['nik']) ?>
        </span>
        <span class="info-badge">
            <i class="bi bi-cake2"></i> <?= $usia ?> tahun
        </span>
        <span class="info-badge">
            <i class="bi bi-gender-<?= $lansia['jenis_kelamin'] === 'L' ? 'ambiguous' : 'female' ?>"></i>
            <?= $lansia['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
        </span>
    </div>
</div>

<?php if ($status_risti): ?>
<div class="alert-risti">
    <div class="alert-risti-icon">
        <i class="bi bi-exclamation-triangle-fill"></i>
    </div>
    <div class="alert-risti-content">
        <strong>⚠️ PERHATIAN: Status Risiko Tinggi (Risti)</strong><br>
        Lansia ini memerlukan monitoring dan penanganan khusus. Pastikan untuk melakukan kunjungan rutin dan evaluasi kesehatan secara berkala.
    </div>
</div>
<?php endif; ?>

<!-- Data Pribadi -->
<div class="data-card">
    <div class="card-header-custom">
        <i class="bi bi-person-badge"></i>
        <h5>Data Pribadi</h5>
    </div>
    <div class="card-body-custom">
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Tanggal Lahir</div>
                <div class="info-value"><?= date('d F Y', strtotime($lansia['tanggal_lahir'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Tempat Lahir</div>
                <div class="info-value"><?= htmlspecialchars($lansia['tempat_lahir'] ?? '-') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Kategori Usia</div>
                <div class="info-value">
                    <span class="badge-custom <?= getColorKategoriLansia($kategori) ?>">
                        <?= $kategori_label ?>
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Status Risiko</div>
                <div class="info-value">
                    <span class="badge-custom <?= 
                        $lansia['status_risiko'] === 'risiko_rendah' ? 'bg-success text-white' : 
                        ($lansia['status_risiko'] === 'risiko_sedang' ? 'bg-warning text-dark' : 'bg-danger text-white')
                    ?>">
                        <?= $lansia['status_risiko'] === 'risiko_rendah' ? 'Risiko Rendah' : 
                            ($lansia['status_risiko'] === 'risiko_sedang' ? 'Risiko Sedang' : 'Risiko Tinggi') ?>
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Status Kesehatan</div>
                <div class="info-value">
                    <span class="badge-custom <?= 
                        $lansia['status_kesehatan'] === 'sehat' ? 'bg-success text-white' : 
                        ($lansia['status_kesehatan'] === 'sakit_ringan' ? 'bg-warning text-dark' : 'bg-danger text-white')
                    ?>">
                        <?= $lansia['status_kesehatan'] === 'sehat' ? 'Sehat' : 
                            ($lansia['status_kesehatan'] === 'sakit_ringan' ? 'Sakit Ringan' : 'Sakit Berat') ?>
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Desa/Kelurahan</div>
                <div class="info-value"><?= htmlspecialchars($lansia['nama_desa'] ?? '-') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Alamat</div>
                <div class="info-value"><?= htmlspecialchars($lansia['alamat'] ?? '-') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">No. BPJS</div>
                <div class="info-value"><?= htmlspecialchars($lansia['bpjs'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Kontak -->
<div class="data-card">
    <div class="card-header-custom">
        <i class="bi bi-telephone"></i>
        <h5>Informasi Kontak</h5>
    </div>
    <div class="card-body-custom">
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">No. Telepon (Lansia)</div>
                <div class="info-value">
                    <?php if ($lansia['no_telepon']): ?>
                        <a href="tel:<?= htmlspecialchars($lansia['no_telepon']) ?>" style="color: #667eea; text-decoration: none;">
                            <i class="bi bi-telephone-outbound"></i> <?= htmlspecialchars($lansia['no_telepon']) ?>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">No. Telepon (Wali)</div>
                <div class="info-value">
                    <?php if ($lansia['no_telepon_keluarga']): ?>
                        <a href="tel:<?= htmlspecialchars($lansia['no_telepon_keluarga']) ?>" style="color: #667eea; text-decoration: none;">
                            <i class="bi bi-telephone-outbound"></i> <?= htmlspecialchars($lansia['no_telepon_keluarga']) ?>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Wali/Keluarga -->
<div class="data-card">
    <div class="card-header-custom">
        <i class="bi bi-people"></i>
        <h5>Data Wali/Keluarga</h5>
    </div>
    <div class="card-body-custom">
        <div class="info-row">
            <div class="info-item">
                <div class="info-label">Nama Wali</div>
                <div class="info-value"><?= htmlspecialchars($lansia['nama_keluarga'] ?? '-') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Hubungan dengan Lansia</div>
                <div class="info-value">
                    <?php
                    $hubunganMap = [
                        'suami' => 'Suami',
                        'istri' => 'Istri',
                        'anak' => 'Anak',
                        'keluarga' => 'Keluarga'
                    ];
                    $relationshipKey = $lansia['hubungan_keluarga'] ?? '';
                    echo htmlspecialchars($relationshipKey ? ($hubunganMap[$relationshipKey] ?? $relationshipKey) : '-');
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Kunjungan -->
<div class="data-card">
    <div class="card-header-custom">
        <i class="bi bi-clock-history"></i>
        <h5>Riwayat Kunjungan (<?= count($visits) ?> Kunjungan)</h5>
    </div>
    <div class="card-body-custom">
        <?php if (empty($visits)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Belum ada riwayat kunjungan</p>
        </div>
        <?php else: ?>
        <div class="table-responsive riwayat-table">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 20px;"></th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Jenis</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th>TD</th>
                        <th>IMT</th>
                        <th>Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                    <tr onclick="toggleDetail(<?= $visit['id'] ?>)" style="cursor: pointer;">
                        <td>
                            <span class="expand-icon">
                                <i class="bi bi-chevron-down" id="icon-<?= $visit['id'] ?>"></i>
                            </span>
                        </td>
                        <td><strong><?= date('d/m/Y', strtotime($visit['tanggal_kunjungan'])) ?></strong></td>
                        <td><?= date('H:i', strtotime($visit['jam_kunjungan'])) ?> WIB</td>
                        <td>
                            <span class="badge-custom bg-<?= $visit['jenis_kunjungan'] === 'baru' ? 'primary text-white' : 'secondary text-white' ?>">
                                <?= ucfirst($visit['jenis_kunjungan']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($visit['petugas_nama'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge-custom bg-<?= 
                                $visit['status_kesehatan'] === 'sehat' ? 'success text-white' : 
                                ($visit['status_kesehatan'] === 'sakit_ringan' ? 'warning text-dark' : 'danger text-white')
                            ?>">
                                <?= $visit['status_kesehatan'] === 'sehat' ? 'Sehat' : 
                                    ($visit['status_kesehatan'] === 'sakit_ringan' ? 'Ringan' : 'Berat') ?>
                            </span>
                        </td>
                        <td><?= ($visit['tekanan_darah_sistol'] ?? '') && ($visit['tekanan_darah_diastol'] ?? '') ? $visit['tekanan_darah_sistol'] . '/' . $visit['tekanan_darah_diastol'] : '-' ?></td>
                        <td><?= $visit['imt'] ? number_format($visit['imt'], 1) : '-' ?></td>
                        <td>
                            <?php if ($visit['rekomendasi']): ?>
                            <span class="badge-custom bg-<?= 
                                $visit['rekomendasi'] === 'pemeriksaan_biasa' ? 'info text-white' : 
                                ($visit['rekomendasi'] === 'rawat_inap' ? 'danger text-white' : 
                                ($visit['rekomendasi'] === 'rujuk_rs' ? 'warning text-dark' : 'success text-white'))
                            ?>" style="font-size: 0.75rem;">
                                <?= 
                                    $visit['rekomendasi'] === 'pemeriksaan_biasa' ? 'Pemeriksaan' :
                                    ($visit['rekomendasi'] === 'rawat_inap' ? 'Rawat Inap' :
                                    ($visit['rekomendasi'] === 'rujuk_rs' ? 'Rujuk RS' : 'Rawat Jalan'))
                                ?>
                            </span>
                            <?php else: ?>
                            <span class="badge-custom bg-secondary text-white" style="font-size: 0.75rem;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- Detail Row -->
                    <tr class="detail-row" id="detail-<?= $visit['id'] ?>" style="display: none;">
                        <td colspan="9" class="detail-row-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <h6>Vital Sign & Pengukuran</h6>
                                        <div class="detail-item">
                                            <span class="detail-label">Tekanan Darah</span>
                                            <span class="detail-value"><?= ($visit['tekanan_darah_sistol'] ?? '') && ($visit['tekanan_darah_diastol'] ?? '') ? $visit['tekanan_darah_sistol'] . '/' . $visit['tekanan_darah_diastol'] . ' mmHg' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Berat Badan</span>
                                            <span class="detail-value"><?= $visit['berat_badan'] ? $visit['berat_badan'] . ' kg' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Tinggi Badan</span>
                                            <span class="detail-value"><?= $visit['tinggi_badan'] ? $visit['tinggi_badan'] . ' cm' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">IMT</span>
                                            <span class="detail-value"><?= $visit['imt'] ? number_format($visit['imt'], 1) : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Nadi</span>
                                            <span class="detail-value"><?= $visit['nadi'] ? $visit['nadi'] . ' bpm' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">RR</span>
                                            <span class="detail-value"><?= $visit['respiratory_rate'] ? $visit['respiratory_rate'] . ' x/menit' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Suhu Tubuh</span>
                                            <span class="detail-value"><?= $visit['suhu_tubuh'] ? $visit['suhu_tubuh'] . ' °C' : '-' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-section">
                                        <h6>Hasil Laboratorium</h6>
                                        <div class="detail-item">
                                            <span class="detail-label">Gula Darah</span>
                                            <span class="detail-value"><?= $visit['gula_darah'] ? $visit['gula_darah'] . ' mg/dL' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Kolesterol</span>
                                            <span class="detail-value"><?= $visit['kolesterol'] ? $visit['kolesterol'] . ' mg/dL' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Hemoglobin</span>
                                            <span class="detail-value"><?= $visit['hemoglobin'] ? $visit['hemoglobin'] . ' g/dL' : '-' ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">SpO₂</span>
                                            <span class="detail-value"><?= $visit['spo2'] ? $visit['spo2'] . ' %' : '-' ?></span>
                                        </div>
                                    </div>

                                    <div class="detail-section">
                                        <h6>Kondisi Kesehatan</h6>
                                        <div class="detail-item">
                                            <span class="detail-label">Disabilitas</span>
                                            <span class="detail-value"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $visit['status_disabilitas'] ?? 'Tidak Ada'))) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Kelainan</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['kelainan'] ?? '-') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Keluhan</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['keluhan'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="detail-section">
                                        <h6>Diagnosis & Tindakan</h6>
                                        <div class="detail-item">
                                            <span class="detail-label">Diagnosa</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['diagnosa'] ?? '-') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Tindakan</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['tindakan'] ?? '-') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Obat</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['obat'] ?? '-') ?></span>
                                        </div>
                                        <?php if ($visit['rujukan']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Rujukan</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['rujukan']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Tujuan Rujukan</span>
                                            <span class="detail-value"><?= htmlspecialchars($visit['tujuan_rujukan'] ?? '-') ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div style="margin-top: 30px; margin-bottom: 20px;">
    <a href="lansia.php" class="btn btn-secondary btn-lg" style="display: inline-flex; gap: 8px; align-items: center;">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Lansia
    </a>
</div>

<script>
function toggleDetail(visitId) {
    const detailRow = document.getElementById('detail-' + visitId);
    const icon = document.getElementById('icon-' + visitId);
    if (detailRow) {
        const isHidden = detailRow.style.display === 'none';
        detailRow.style.display = isHidden ? 'table-row' : 'none';
        if (icon) {
            icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            icon.style.transition = 'transform 0.3s ease';
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>

