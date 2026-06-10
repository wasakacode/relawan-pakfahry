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

<script src="../vendor/chart.js/Chart.min.js"></script>

<script>
    var ctx = document.getElementById("grafikWilayah");

    new Chart(ctx, {
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
</script>