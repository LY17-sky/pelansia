<?php
require_once __DIR__ . '/inc/functions.php';

if (!isLoggedIn()) redirect('login.php');

$page = 'dashboard';
$pageTitle = 'Dashboard';

$user = getUser();
$isSuperAdmin = isSuperAdmin();

$today = date('Y-m-d');
$totalLansia = dbFetch("SELECT COUNT(*) as total FROM lansia WHERE status_aktif = 'aktif'")['total'] ?? 0;
$kunjunganHariIni = dbFetch("SELECT COUNT(*) as total FROM visits WHERE tanggal_kunjungan = ?", [$today])['total'] ?? 0;
$lansiaSakit = dbFetch("SELECT COUNT(*) as total FROM lansia WHERE status_aktif = 'aktif' AND status_kesehatan != 'sehat'")['total'] ?? 0;

$chartHarian = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = dbFetch("SELECT COUNT(*) as total FROM visits WHERE tanggal_kunjungan = ?", [$date])['total'] ?? 0;
    $chartHarian[] = ['tanggal' => $date, 'jumlah' => (int)$count];
}

$chartMingguan = [];
for ($i = 3; $i >= 0; $i--) {
    $startDate = date('Y-m-d', strtotime("monday -$i weeks"));
    $endDate = date('Y-m-d', strtotime("sunday -$i weeks"));
    $count = dbFetch("SELECT COUNT(*) as total FROM visits WHERE tanggal_kunjungan BETWEEN ? AND ?", [$startDate, $endDate])['total'] ?? 0;
    $chartMingguan[] = ['minggu' => 'Minggu ' . ($i + 1), 'periode' => $startDate . ' - ' . $endDate, 'jumlah' => (int)$count];
}

$chartBulanan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $dateSql = "strftime('%Y-%m', tanggal_kunjungan)";
    $count = dbFetch("SELECT COUNT(*) as total FROM visits WHERE $dateSql = ?", [$bulan])['total'] ?? 0;
    $chartBulanan[] = ['bulan' => date('M Y', strtotime($bulan)), 'jumlah' => (int)$count];
}

$statusKesehatan = [
    'sehat' => dbFetch("SELECT COUNT(*) as total FROM visits WHERE status_kesehatan = 'sehat'")['total'] ?? 0,
    'sakit_ringan' => dbFetch("SELECT COUNT(*) as total FROM visits WHERE status_kesehatan = 'sakit_ringan'")['total'] ?? 0,
    'sakit_berat' => dbFetch("SELECT COUNT(*) as total FROM visits WHERE status_kesehatan = 'sakit_berat'")['total'] ?? 0,
];

// Calculation for Age Categories
$activeLansia = dbFetchAll("SELECT id, nik, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, status_kesehatan FROM lansia WHERE status_aktif = 'aktif' ORDER BY nama_lengkap ASC");

$kategoriLansia = [
    'pra_lansia' => [],
    'lansia' => [],
    'lansia_tua' => [],
    'risiko_tinggi' => []
];

$todayDate = new DateTime();
foreach ($activeLansia as $l) {
    if (empty($l['tanggal_lahir'])) continue;
    try {
        $bday = new DateTime($l['tanggal_lahir']);
        $age = $todayDate->diff($bday)->y;
        $l['umur'] = $age;
        
        if ($age >= 45 && $age <= 59) {
            $kategoriLansia['pra_lansia'][] = $l;
        } elseif ($age >= 60 && $age <= 69) {
            $kategoriLansia['lansia'][] = $l;
        } elseif ($age >= 70 && $age <= 79) {
            $kategoriLansia['lansia_tua'][] = $l;
        } elseif ($age >= 80) {
            $kategoriLansia['risiko_tinggi'][] = $l;
        }
    } catch(Exception $e) {}
}

ob_start();
?>

<style>
.category-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    border-radius: 12px;
}
.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
}
</style>

<div class="row g-3 g-lg-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #b1c9ef 0%, #c7d8f3 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalLansia ?></h3>
                <p style="color: rgba(255,255,255,0.85);">Total Lansia Terdaftar</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $kunjunganHariIni ?></h3>
                <p style="color: rgba(255,255,255,0.85);">Kunjungan Hari Ini</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $lansiaSakit ?></h3>
                <p style="color: rgba(255,255,255,0.85);">Lansia Sakit</p>
            </div>
        </div>
    </div>
</div>

<!-- Age Category Cards Row -->
<div class="row g-3 g-lg-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card category-card h-100" data-bs-toggle="modal" data-bs-target="#modal-pra-lansia" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= count($kategoriLansia['pra_lansia']) ?></h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.9rem;">Pra Lansia (45-59)</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card category-card h-100" data-bs-toggle="modal" data-bs-target="#modal-lansia" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= count($kategoriLansia['lansia']) ?></h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.9rem;">Lansia (60-69)</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card category-card h-100" data-bs-toggle="modal" data-bs-target="#modal-lansia-tua" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-person-lines-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= count($kategoriLansia['lansia_tua']) ?></h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.9rem;">Lansia Tua (70-79)</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card category-card h-100" data-bs-toggle="modal" data-bs-target="#modal-risiko-tinggi" style="background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= count($kategoriLansia['risiko_tinggi']) ?></h3>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.9rem;">Risiko Tinggi (≥80)</p>
            </div>
        </div>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<div class="custom-card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-calendar-date me-2"></i>Analitik Kunjungan</h5>
            <div class="d-flex gap-2">
                <select id="periodSelector" class="form-select form-select-sm" style="width: auto;">
                    <option value="daily">Per Hari (7 Hari)</option>
                    <option value="weekly">Per Minggu (4 Minggu)</option>
                    <option value="monthly">Per Bulan (6 Bulan)</option>
                </select>
            </div>
        </div>
        <div style="height: 300px;">
            <canvas id="chartAnalytics"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="custom-card h-100">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-heart-pulse me-2"></i>Status Kesehatan Lansia Berobat</h5>
                <div style="height: 250px;">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="custom-card h-100">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Persentase Status Kesehatan</h5>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td><span class="badge bg-success">Sehat</span></td>
                            <td><?= $statusKesehatan['sehat'] ?> (<?= $statusKesehatan['sehat'] > 0 ? round($statusKesehatan['sehat'] / array_sum($statusKesehatan) * 100) : 0 ?>%)</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark">Sakit Ringan</span></td>
                            <td><?= $statusKesehatan['sakit_ringan'] ?> (<?= $statusKesehatan['sakit_ringan'] > 0 ? round($statusKesehatan['sakit_ringan'] / array_sum($statusKesehatan) * 100) : 0 ?>%)</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger">Sakit Berat</span></td>
                            <td><?= $statusKesehatan['sakit_berat'] ?> (<?= $statusKesehatan['sakit_berat'] > 0 ? round($statusKesehatan['sakit_berat'] / array_sum($statusKesehatan) * 100) : 0 ?>%)</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>



<?php if ($isSuperAdmin): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = {
    daily: {
        labels: <?= json_encode(array_map(fn($d) => date('d/M', strtotime($d['tanggal'])), $chartHarian)) ?>,
        data: <?= json_encode(array_column($chartHarian, 'jumlah')) ?>,
        type: 'bar',
        label: 'Kunjungan Harian',
        color: '#b1c9ef'
    },
    weekly: {
        labels: <?= json_encode(array_column($chartMingguan, 'minggu')) ?>,
        data: <?= json_encode(array_column($chartMingguan, 'jumlah')) ?>,
        type: 'bar',
        label: 'Kunjungan Mingguan',
        color: '#b1c9ef'
    },
    monthly: {
        labels: <?= json_encode(array_column($chartBulanan, 'bulan')) ?>,
        data: <?= json_encode(array_column($chartBulanan, 'jumlah')) ?>,
        type: 'line',
        label: 'Kunjungan Bulanan',
        color: '#b1c9ef'
    }
};

let currentChart = null;

function createChart(period) {
    const ctx = document.getElementById('chartAnalytics');
    const data = chartData[period];

    if (currentChart) {
        currentChart.destroy();
    }

    currentChart = new Chart(ctx, {
        type: data.type,
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label,
                data: data.data,
                backgroundColor: data.type === 'bar' ? data.color : 'rgba(177, 201, 239, 0.1)',
                borderColor: data.color,
                borderRadius: data.type === 'bar' ? 5 : 0,
                fill: data.type === 'line',
                tension: data.type === 'line' ? 0.4 : 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// Initialize with daily chart
createChart('daily');

// Period selector functionality
document.getElementById('periodSelector').addEventListener('change', function() {
    createChart(this.value);
});

const chartTotal = <?= array_sum($statusKesehatan) ?>;
const chartStatus = new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Sehat', 'Sakit Ringan', 'Sakit Berat'],
        datasets: [{
            data: [<?= $statusKesehatan['sehat'] ?>, <?= $statusKesehatan['sakit_ringan'] ?>, <?= $statusKesehatan['sakit_berat'] ?>],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const percentage = chartTotal > 0 ? Math.round(value / chartTotal * 100) : 0;
                        return context.label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php
$modalsData = [
    ['id' => 'modal-pra-lansia', 'title' => 'Daftar Pra Lansia (45-59 tahun)', 'data' => $kategoriLansia['pra_lansia'], 'icon' => 'bi-person-fill', 'color' => 'primary'],
    ['id' => 'modal-lansia', 'title' => 'Daftar Lansia (60-69 tahun)', 'data' => $kategoriLansia['lansia'], 'icon' => 'bi-person-check-fill', 'color' => 'success'],
    ['id' => 'modal-lansia-tua', 'title' => 'Daftar Lansia Tua (70-79 tahun)', 'data' => $kategoriLansia['lansia_tua'], 'icon' => 'bi-person-lines-fill', 'color' => 'warning'],
    ['id' => 'modal-risiko-tinggi', 'title' => 'Daftar Lansia Risiko Tinggi (≥80 tahun)', 'data' => $kategoriLansia['risiko_tinggi'], 'icon' => 'bi-heart-pulse-fill', 'color' => 'danger']
];
?>

<?php foreach ($modalsData as $modal): ?>
<div class="modal fade" id="<?= $modal['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-<?= $modal['color'] ?> text-white border-0">
                <h5 class="modal-title"><i class="bi <?= $modal['icon'] ?> me-2"></i><?= $modal['title'] ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <?php if (empty($modal['data'])): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        Tidak ada data lansia pada kategori ini.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nama Lansia</th>
                                    <th>Umur</th>
                                    <th>L/P</th>
                                    <th>Alamat</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modal['data'] as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><a href="detail-lansia.php?id=<?= $row['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($row['nama_lengkap']) ?></a></td>
                                    <td><?= $row['umur'] ?> thn</td>
                                    <td><?= $row['jenis_kelamin'] ?></td>
                                    <td class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($row['alamat']) ?>"><?= htmlspecialchars($row['alamat']) ?></td>
                                    <td>
                                        <?php if ($row['status_kesehatan'] == 'sehat'): ?>
                                            <span class="badge bg-success">Sehat</span>
                                        <?php elseif ($row['status_kesehatan'] == 'sakit_ringan'): ?>
                                            <span class="badge bg-warning text-dark">Ringan</span>
                                        <?php elseif ($row['status_kesehatan'] == 'sakit_berat'): ?>
                                            <span class="badge bg-danger">Berat</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/inc/layout.php';
?>