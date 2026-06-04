<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');
if (!isSuperAdmin()) redirect('dashboard.php');

$page = 'laporan';
$pageTitle = 'Laporan';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$sql = "SELECT v.*, l.nama_lengkap, l.nik 
        FROM visits v 
        JOIN lansia l ON v.id_lansia = l.id 
        WHERE v.tanggal_kunjungan = ? 
        ORDER BY v.jam_kunjungan DESC";

$result = dbFetchAll($sql, [$tanggal]);

$export = $_GET['export'] ?? '';
if ($export === 'pdf') {
    $user = getUser();
    $nama_petugas = $_GET['petugas'] ?? ($user['nama_lengkap'] ?? 'Admin');
    $nama_kepala = $_GET['kepala'] ?? 'Kepala Puskesmas';
    $isPreviewMode = isset($_GET['preview_mode']);
    
    $totalKunjungan = count($result);
    $pasienBaru = count(array_filter($result, fn($r) => ($r['jenis_kunjungan'] ?? '') === 'baru'));
    $kontrol = count(array_filter($result, fn($r) => ($r['jenis_kunjungan'] ?? '') === 'lama'));
    $statusSehat = count(array_filter($result, fn($r) => ($r['status_kesehatan'] ?? '') === 'sehat'));
    $statusSakitRingan = count(array_filter($result, fn($r) => ($r['status_kesehatan'] ?? '') === 'sakit_ringan'));
    $statusSakitBerat = count(array_filter($result, fn($r) => ($r['status_kesehatan'] ?? '') === 'sakit_berat'));
    
    function getPercentage($value, $total) {
        return $total > 0 ? round($value / $total * 100) : 0;
    }
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kunjungan Lansia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 15px;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0d9488;
        }
        .header h1 {
            font-size: 20px;
            color: #0d9488;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .header .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .header .periode {
            font-size: 12px;
            background: #e6f5f4;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            color: #0d9488;
            font-weight: 600;
        }
        .info-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }
        .info-box div {
            text-align: center;
        }
        .info-box .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .info-box .value {
            font-size: 16px;
            font-weight: 700;
            color: #0d9488;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 10px;
            margin-top: 10px;
        }
        table th { 
            background: #0d9488; 
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #0d9488;
        }
        table td { 
            border: 1px solid #d1d5db; 
            padding: 6px;
            vertical-align: top;
        }
        table tr:nth-child(even) {
            background: #f9fafb;
        }
        table tr:hover {
            background: #e6f5f4;
        }
        .status-sehat { color: #059669; font-weight: 600; }
        .status-sakit_ringan { color: #d97706; font-weight: 600; }
        .status-sakit_berat { color: #dc2626; font-weight: 600; }
        .jenis-bar { color: #2563eb; }
        .jenis-lama { color: #7c3aed; }
        .footer { 
            margin-top: 20px; 
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #6b7280;
        }
        .footer .left { text-align: left; }
        .footer .right { text-align: right; }
        .ttd {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .ttd-box {
            text-align: center;
            width: 200px;
        }
        .ttd-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        @media print {
            @page { 
                margin: 10mm; 
                size: landscape; 
            }
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
        .chart-section {
            margin-top: 15px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }
        .chart-title {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .bar-chart {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            height: 80px;
            padding-top: 10px;
        }
        .bar-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .bar-fill {
            width: 100%;
            border-radius: 3px 3px 0 0;
            transition: height 0.3s;
        }
        .bar-fill.sehat { background: #10b981; }
        .bar-fill.sakit_ringan { background: #f59e0b; }
        .bar-fill.sakit_berat { background: #dc2626; }
        .bar-label {
            font-size: 8px;
            margin-top: 3px;
            text-align: center;
        }
        .bar-value {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .pie-chart {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
        }
        .pie-legend {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .pie-legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
        }
        .pie-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KUNJUNGAN LANSIA</h1>
        <div class="subtitle">Puskesmas Utama</div>
        <div class="periode">📅 Tanggal: <?= date('d F Y', strtotime($tanggal)) ?></div>
    </div>
    
    <div class="info-box">
        <div>
            <div class="label">Total Kunjungan</div>
            <div class="value"><?= $totalKunjungan ?></div>
        </div>
        <div>
            <div class="label">Pasien Baru</div>
            <div class="value"><?= $pasienBaru ?></div>
        </div>
        <div>
            <div class="label">Kontrol</div>
            <div class="value"><?= $kontrol ?></div>
        </div>
        <div>
            <div class="label">Sakit Ringan</div>
            <div class="value"><?= $statusSakitRingan ?></div>
        </div>
    </div>
    
    <div class="chart-section">
        <div class="chart-title">📊 Status Kesehatan Lansia</div>
        <div class="bar-chart">
            <div class="bar-item">
                <div class="bar-value" style="color:#10b981"><?= $statusSehat ?></div>
                <div class="bar-fill sehat" style="height: <?= $statusSehat > 0 ? ($statusSehat / max($totalKunjungan, 1)) * 70 : 0 ?>px; min-height: 2px;"></div>
                <div class="bar-label">Sehat (<?= getPercentage($statusSehat, $totalKunjungan) ?>%)</div>
            </div>
            <div class="bar-item">
                <div class="bar-value" style="color:#f59e0b"><?= $statusSakitRingan ?></div>
                <div class="bar-fill sakit_ringan" style="height: <?= $statusSakitRingan > 0 ? ($statusSakitRingan / max($totalKunjungan, 1)) * 70 : 0 ?>px; min-height: 2px;"></div>
                <div class="bar-label">Sakit Ringan (<?= getPercentage($statusSakitRingan, $totalKunjungan) ?>%)</div>
            </div>
            <div class="bar-item">
                <div class="bar-value" style="color:#dc2626"><?= $statusSakitBerat ?></div>
                <div class="bar-fill sakit_berat" style="height: <?= $statusSakitBerat > 0 ? ($statusSakitBerat / max($totalKunjungan, 1)) * 70 : 0 ?>px; min-height: 2px;"></div>
                <div class="bar-label">Sakit Berat (<?= getPercentage($statusSakitBerat, $totalKunjungan) ?>%)</div>
            </div>
        </div>
        <div style="display:flex; justify-content:space-around; margin-top:15px;">
            <div style="text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#10b981;"><?= $statusSehat ?></div>
                <div style="font-size:9px; color:#666;">Sehat</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#f59e0b;"><?= $statusSakitRingan ?></div>
                <div style="font-size:9px; color:#666;">Sakit Ringan</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#dc2626;"><?= $statusSakitBerat ?></div>
                <div style="font-size:9px; color:#666;">Sakit Berat</div>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th style="width:120px">Nama Lansia</th>
                <th style="width:80px">NIK</th>
                <th style="width:70px">Tanggal</th>
                <th style="width:50px">Jam</th>
                <th style="width:50px">Jenis</th>
                <th style="width:70px">Status</th>
                <th style="width:40px">TD</th>
                <th style="width:35px">BB</th>
                <th style="width:35px">TB</th>
                <th style="width:35px">IMT</th>
                <th style="width:100px">Keluhan</th>
                <th style="width:100px">Diagnosa</th>
                <th style="width:100px">Rujukan</th>
            </tr>
        </thead>
        <tbody>
<?php $no = 1; foreach ($result as $row): ?>
            <tr>
                <td style="text-align:center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_lengkap'] ?? '-') ?></td>
                <td><?= $row['nik'] ?? '-' ?></td>
                <td style="text-align:center"><?= date('d/m/Y', strtotime($row['tanggal_kunjungan'] ?? '')) ?></td>
                <td style="text-align:center"><?= $row['jam_kunjungan'] ?? '-' ?></td>
                <td style="text-align:center">
                    <span class="<?= ($row['jenis_kunjungan'] ?? '') === 'baru' ? 'jenis-bar' : 'jenis-lama' ?>">
                        <?= ($row['jenis_kunjungan'] ?? '') === 'baru' ? 'Baru' : 'Lama' ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <?php $status = $row['status_kesehatan'] ?? ''; ?>
                    <span class="<?= $status === 'sehat' ? 'status-sehat' : ($status === 'sakit_ringan' ? 'status-sakit_ringan' : 'status-sakit_berat') ?>">
                        <?= $status === 'sehat' ? 'Sehat' : ($status === 'sakit_ringan' ? 'Sakit Ringan' : ($status === 'sakit_berat' ? 'Sakit Berat' : '-')) ?>
                    </span>
                </td>
                <td style="text-align:center"><?= ($row['tekanan_darah_sistol'] ?? '').'/'.($row['tekanan_darah_diastol'] ?? '-') ?></td>
                <td style="text-align:center"><?= $row['berat_badan'] ?? '-' ?></td>
                <td style="text-align:center"><?= $row['tinggi_badan'] ?? '-' ?></td>
                <td style="text-align:center"><?= $row['imt'] ?? '-' ?></td>
                <td><?= htmlspecialchars($row['keluhan'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['diagnosa'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['rujukan'] ?? '-') ?></td>
            </tr>
<?php endforeach; ?>
<?php if (empty($result)): ?>
            <tr>
                <td colspan="14" style="text-align:center; padding: 20px; color: #999;">Tidak ada data kunjungan</td>
            </tr>
<?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <div class="left">
            <p>Dicetak oleh: <?= htmlspecialchars($user['nama_lengkap'] ?? 'Admin') ?></p>
            <p>Tanggal cetak: <?= date('d F Y') ?></p>
        </div>
        <div class="right">
            <p>Halaman 1 dari 1</p>
        </div>
    </div>
    
    <div class="ttd">
        <div class="ttd-box">
            <div style="margin-bottom: 50px;">Petugas,</div>
            <div class="ttd-line" style="font-weight: 600; border-top: none;"><?= htmlspecialchars($nama_petugas) ?></div>
            <div style="border-top: 1px solid #333; margin-top: 2px;"></div>
        </div>
        <div class="ttd-box">
            <div style="margin-bottom: 50px;">Mengetahui, <br>Kepala Puskesmas</div>
            <div class="ttd-line" style="font-weight: 600; border-top: none;"><?= htmlspecialchars($nama_kepala) ?></div>
            <div style="border-top: 1px solid #333; margin-top: 2px;"></div>
        </div>
    </div>
    <?php if (!$isPreviewMode): ?>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <?php endif; ?>
</body>
</html>
    <?php
    exit;
}

ob_start();
?>

<div class="custom-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Cari
                </button>
            </div>
            <?php if (isSuperAdmin()): ?>
            <div class="col-md-2">
                <button type="button" class="btn btn-success w-100" onclick="openPreviewModal()">
                    <i class="bi bi-file-pdf me-2"></i>Export PDF
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="custom-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Data Kunjungan</h5>
            <span class="badge bg-info"><?= count($result) ?> data</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama Lansia</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>TD</th>
                        <th>BB</th>
                        <th>TB</th>
                        <th>IMT</th>
                        <th>Nadi</th>
                        <th>RR</th>
                        <th>Disabilitas</th>
                        <th>Keluhan</th>
                        <th>Kelainan</th>
                        <th>Diagnosa</th>
                        <th>Tindakan</th>
                        <th>Rujukan</th>
                        <th>Obat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_kunjungan'])) ?></td>
                        <td><?= $row['jam_kunjungan'] ?></td>
                        <td><span class="badge bg-<?= $row['jenis_kunjungan'] === 'baru' ? 'primary' : 'secondary' ?>"><?= $row['jenis_kunjungan'] === 'baru' ? 'Baru' : 'Lama' ?></span></td>
                        <td>
                            <span class="badge bg-<?= $row['status_kesehatan'] === 'sehat' ? 'success' : ($row['status_kesehatan'] === 'sakit_ringan' ? 'warning' : 'danger') ?>">
                                <?= $row['status_kesehatan'] === 'sehat' ? 'Sehat' : ($row['status_kesehatan'] === 'sakit_ringan' ? 'Sakit Ringan' : 'Sakit Berat') ?>
                            </span>
                        </td>
                        <td><?= $row['tekanan_darah_sistol'] ?>/<?= $row['tekanan_darah_diastol'] ?></td>
                        <td><?= $row['berat_badan'] ?? '-' ?></td>
                        <td><?= $row['tinggi_badan'] ?? '-' ?></td>
                        <td><?= $row['imt'] ?? '-' ?></td>
                        <td><?= $row['nadi'] ?? '-' ?></td>
                        <td><?= $row['respiratory_rate'] ?? '-' ?></td>
                        <td><?= $row['status_disabilitas'] === 'tidak_ada' ? '-' : $row['status_disabilitas'] ?></td>
                        <td><?= $row['keluhan'] ? htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($row['keluhan'], 0, 20, '...') : (strlen($row['keluhan']) > 20 ? substr($row['keluhan'], 0, 20) . '...' : $row['keluhan'])) : '-' ?></td>
                        <td><?= $row['kelainan'] ? htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($row['kelainan'], 0, 20, '...') : (strlen($row['kelainan']) > 20 ? substr($row['kelainan'], 0, 20) . '...' : $row['kelainan'])) : '-' ?></td>
                        <td><?= $row['diagnosa'] ? htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($row['diagnosa'], 0, 20, '...') : (strlen($row['diagnosa']) > 20 ? substr($row['diagnosa'], 0, 20) . '...' : $row['diagnosa'])) : '-' ?></td>
                        <td><?= $row['tindakan'] ? htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($row['tindakan'], 0, 20, '...') : (strlen($row['tindakan']) > 20 ? substr($row['tindakan'], 0, 20) . '...' : $row['tindakan'])) : '-' ?></td>
                        <td><?= $row['rujukan'] ? htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($row['rujukan'], 0, 20, '...') : (strlen($row['rujukan']) > 20 ? substr($row['rujukan'], 0, 20) . '...' : $row['rujukan'])) : '-' ?></td>
                        <td><?= $row['obat'] ? htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($row['obat'], 0, 20, '...') : (strlen($row['obat']) > 20 ? substr($row['obat'], 0, 20) . '...' : $row['obat'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($result)): ?>
                    <tr>
                        <td colspan="18" class="text-center text-muted py-4">
                            <i class="bi bi-inbox me-2"></i>Tidak ada data
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Preview PDF -->
<div class="modal fade" id="modalPreviewPDF" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title"><i class="bi bi-file-earmark-pdf me-2"></i>Preview Laporan PDF</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <!-- Left: Form Edit Names -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <h6 class="mb-4 fw-bold text-secondary">Pengaturan Tanda Tangan</h6>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-medium">Nama Petugas</label>
                                    <input type="text" id="inputPetugas" class="form-control form-control-lg rounded-3" value="<?= htmlspecialchars(getUser()['nama_lengkap'] ?? 'Admin') ?>">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-medium">Nama Kepala Puskesmas</label>
                                    <input type="text" id="inputKepala" class="form-control form-control-lg rounded-3" value="Dr. Kepala Puskesmas">
                                </div>
                                <hr class="opacity-10 mb-4">
                                <button type="button" class="btn btn-primary w-100 mb-2 py-2 rounded-3 fw-medium shadow-sm" onclick="generateFinalPDF()">
                                    <i class="bi bi-printer me-2"></i>Cetak & Download PDF
                                </button>
                                <button type="button" class="btn btn-light w-100 py-2 rounded-3 fw-medium text-secondary border" data-bs-dismiss="modal">
                                    Kembali
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Iframe Preview -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative">
                            <!-- Loading indicator overlay -->
                            <div id="previewLoader" class="position-absolute top-0 start-0 w-100 h-100 bg-white d-flex flex-column align-items-center justify-content-center" style="z-index: 10;">
                                <div class="spinner-border text-success mb-3" role="status"></div>
                                <span class="text-muted fw-medium">Memuat Preview...</span>
                            </div>
                            <iframe id="iframePreview" class="w-100 h-100 border-0" style="min-height: 500px;" onload="document.getElementById('previewLoader').classList.add('d-none')"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let debounceTimer;
const iframe = document.getElementById('iframePreview');
const inputPetugas = document.getElementById('inputPetugas');
const inputKepala = document.getElementById('inputKepala');
const loader = document.getElementById('previewLoader');
const baseUrl = "?tanggal=<?= $tanggal ?>&export=pdf&preview_mode=1";

function updatePreview() {
    loader.classList.remove('d-none');
    const petugas = encodeURIComponent(inputPetugas.value.trim());
    const kepala = encodeURIComponent(inputKepala.value.trim());
    iframe.src = baseUrl + "&petugas=" + petugas + "&kepala=" + kepala;
}

function handleInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(updatePreview, 500);
}

inputPetugas.addEventListener('input', handleInput);
inputKepala.addEventListener('input', handleInput);

function openPreviewModal() {
    updatePreview(); 
    new bootstrap.Modal(document.getElementById('modalPreviewPDF')).show();
}

function generateFinalPDF() {
    if (!inputPetugas.value.trim() || !inputKepala.value.trim()) {
        alert('Nama Petugas dan Kepala Puskesmas tidak boleh kosong!');
        return;
    }
    
    const toast = document.createElement('div');
    toast.className = 'position-fixed top-0 start-50 translate-middle-x p-3';
    toast.style.zIndex = 9999;
    toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow-lg" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-medium px-4 py-3">
                <i class="bi bi-check-circle-fill me-2"></i>PDF siap dicetak! Membuka halaman...
            </div>
        </div>
    </div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
    
    const petugas = encodeURIComponent(inputPetugas.value.trim());
    const kepala = encodeURIComponent(inputKepala.value.trim());
    const printUrl = "?tanggal=<?= $tanggal ?>&export=pdf&petugas=" + petugas + "&kepala=" + kepala;
    window.open(printUrl, '_blank');
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>