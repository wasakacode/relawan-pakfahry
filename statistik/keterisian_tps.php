<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$sql = "
SELECT
    kab_kota,
    SUM(CASE WHEN type = 'dukungan' THEN 1 ELSE 0 END) AS jumlah_pendukung,
    COUNT(DISTINCT CASE WHEN type = 'dukungan' THEN tps END) AS jumlah_tps
FROM profiles
GROUP BY kab_kota
";

$data = $pdo->query($sql)->fetchAll();

$wilayah = [];
$pendukung = [];
$tps = [];

foreach ($data as $row) {
    $wilayah[] = $row['kab_kota'];
    $pendukung[] = (int)$row['jumlah_pendukung'];
    $tps[] = (int)$row['jumlah_tps'];
}

$tpsTerisi = $pdo->query("
    SELECT COUNT(*)
    FROM (
        SELECT DISTINCT
            provinsi,
            kab_kota,
            kecamatan,
            desa_kelurahan,
            rt,
            rw,
            tps
        FROM profiles
        WHERE type='dukungan'
        AND tps IS NOT NULL
        AND tps <> ''
    ) x
")->fetchColumn();

$totalTPS = isset($_GET['total_tps'])
    ? (int)$_GET['total_tps']
    : 100;

$tpsBelum = max(0, $totalTPS - $tpsTerisi);

$persen = $totalTPS > 0
    ? round(($tpsTerisi / $totalTPS) * 100, 2)
    : 0;
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Grafik Sebaran Wilayah
        </h6>
    </div>

    <div class="card-body">
        <canvas id="grafikWilayah"></canvas>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">
            Persentase TPS Terisi
        </h6>
    </div>

    <div class="card-body">
         <form method="GET" class="mb-4">

            <div class="row">

                <div class="col-md-4">
                    <label>Total TPS</label>
                    <input type="number"
                           name="total_tps"
                           class="form-control"
                           value="<?= $totalTPS ?>"
                           min="1">
                </div>

                <div class="col-md-2 align-self-end">
                    <button type="submit"
                            class="btn btn-primary btn-block">
                        Hitung
                    </button>
                </div>

            </div>

        </form>
        <div class="text-center mb-3">
            <h3><?= $persen ?>%</h3>
            <p>
                <?= $tpsTerisi ?> dari <?= $totalTPS ?> TPS sudah terisi
            </p>
        </div>

        <canvas id="tpsChart" height="250"></canvas>

    </div>
</div>

<script src="../vendor/chart.js/Chart.min.js"></script>

<script>
    var ctxbar = document.getElementById("grafikWilayah");

    new Chart(ctxbar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($wilayah) ?>,
            datasets: [{
                    label: "Pendukung",
                    data: <?= json_encode($pendukung) ?>,
                    backgroundColor: "#4e73df"
                },
                {
                    label: "TPS",
                    data: <?= json_encode($tps) ?>,
                    backgroundColor: "#e74a3b"
                }
            ]
        },
        options: {
            indexAxis: 'y', // Membuat grafik horizontal
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });

const ctxdonut = document.getElementById('tpsChart');

new Chart(ctxdonut, {
    type: 'doughnut',
    data: {
        labels: ['TPS Terisi', 'Belum Terisi'],
        datasets: [{
            data: [
                <?= $tpsTerisi ?>,
                <?= $tpsBelum ?>
            ],
            backgroundColor: [
                '#1cc88a',
                '#e74a3b'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>