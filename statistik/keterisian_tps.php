<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);
$currentUser = current_user();
$role = $currentUser['role'] ?? '';
$currentUserId = (int)$currentUser['id'];

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$allowedKabKota = [];

if ($role === 'admin') {

    $stmt = $pdo->prepare("
        SELECT id
        FROM profiles
        WHERE user_id=?
        AND type='admin'
        LIMIT 1
    ");
    $stmt->execute([$currentUserId]);

    $adminProfileId = $stmt->fetchColumn();

    if ($adminProfileId) {

        $stmt = $pdo->prepare("
            SELECT d.kab_kota
            FROM profile_dapil pd
            JOIN dapil d
            ON d.id=pd.dapil_id
            WHERE pd.profile_id=?
        ");

        $stmt->execute([$adminProfileId]);

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            $kab = json_decode($row['kab_kota'], true);

            if(is_array($kab)){
                foreach($kab as $k){
                    $allowedKabKota[] = trim($k);
                }
            }
        }

        $allowedKabKota = array_unique($allowedKabKota);
    }
}

if ($role === 'superadmin') {

    $sql = "
        SELECT
            d.daerah_pemilihan AS wilayah,
            COUNT(DISTINCT CASE
                WHEN p.type='dukungan'
                THEN pt.tps_id
            END) AS tps_terisi,
            COUNT(DISTINCT t.id) AS total_tps
        FROM dapil d

        LEFT JOIN tps_kalsel t
            ON JSON_CONTAINS(
                d.kab_kota,
                JSON_QUOTE(t.kabupaten)
            )

        LEFT JOIN profiles_tps pt
            ON pt.tps_id = t.id

        LEFT JOIN profiles p
            ON p.id = pt.profile_id
            AND p.type = 'dukungan'

        GROUP BY d.id, d.daerah_pemilihan
        ORDER BY d.daerah_pemilihan
    ";

    $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} else {

    // $allowedKabKota berasal dari dashboard (dapil admin)
    $placeholders = implode(',', array_fill(0, count($allowedKabKota), '?'));

    $sql = "
        SELECT
    t.kabupaten AS wilayah,
    COUNT(DISTINCT pt.tps_id) AS tps_terisi,
    COUNT(DISTINCT t.id) AS total_tps
FROM tps_kalsel t

LEFT JOIN profiles_tps pt
    ON pt.tps_id = t.id

WHERE t.kabupaten IN ($placeholders)

GROUP BY t.kabupaten
ORDER BY t.kabupaten;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($allowedKabKota);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$wilayah = [];
$tpsTerisiData = [];
$totalTPSData = [];

foreach ($data as $row) {

    $wilayah[] = $row['wilayah'];
    $tpsTerisiData[] = (int)$row['tps_terisi'];
    $totalTPSData[] = (int)$row['total_tps'];

}

$tpsTerisi = array_sum($tpsTerisiData);
$totalTPS = array_sum($totalTPSData);

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
        <div class="text-center mb-3">
            <h3><?= $persen ?>%</h3>
            <p>
                <?= $tpsTerisi ?> dari <?= $totalTPS ?> TPS sudah terisi
            </p>
        </div>

        <canvas id="tpsChart" height="100"></canvas>

    </div>
</div>

<script src="../vendor/chart.js/Chart.min.js"></script>

<script>
    var ctxbar = document.getElementById("grafikWilayah");

    new Chart(ctxbar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($wilayah) ?>,
            datasets: [
                            {
                                label: "Pendukung",
                                data: <?= json_encode($tpsTerisiData) ?>,
                                backgroundColor: "#1cc88a"
                            },
                            {
                                label: "Total TPS",
                                data: <?= json_encode($totalTPSData) ?>,
                                backgroundColor: "#4e73df"
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

<?php require_once __DIR__ . '/../partials/footer.php'; ?>