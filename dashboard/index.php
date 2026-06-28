<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$currentUser = current_user();
$role = $currentUser['role'] ?? '';
$currentUserId = (int)($currentUser['id'] ?? ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0)));

/*
|--------------------------------------------------------------------------
| Daftar Kabupaten/Kota Kalimantan Selatan
|--------------------------------------------------------------------------
| Disesuaikan dengan data di tabel dapil.
| Di database kamu tertulis "KOTA BANJAR BARU", bukan "KOTA BANJARBARU".
*/
$allKabKota = [
    'KOTA BANJARMASIN',
    'KOTA BANJAR BARU',
    'KABUPATEN BANJAR',
    'KABUPATEN BARITO KUALA',
    'KABUPATEN TAPIN',
    'KABUPATEN HULU SUNGAI SELATAN',
    'KABUPATEN HULU SUNGAI TENGAH',
    'KABUPATEN HULU SUNGAI UTARA',
    'KABUPATEN TABALONG',
    'KABUPATEN BALANGAN',
    'KABUPATEN TANAH LAUT',
    'KABUPATEN TANAH BUMBU',
    'KABUPATEN KOTA BARU'
];

/*
|--------------------------------------------------------------------------
| Helper placeholder query IN (?, ?, ?)
|--------------------------------------------------------------------------
*/
$makePlaceholders = function ($count) {
    return implode(',', array_fill(0, $count, '?'));
};

/*
|--------------------------------------------------------------------------
| Tentukan wilayah statistik berdasarkan role
|--------------------------------------------------------------------------
| Superadmin : melihat seluruh data
| Admin      : hanya melihat data sesuai dapil yang dipegang
*/
$isStatLimited = false;
$allowedKabKota = $allKabKota;
$adminProfile = null;

if ($role === 'admin') {
    $isStatLimited = true;
    $allowedKabKota = [];

    /*
    |--------------------------------------------------------------------------
    | Ambil profile admin yang sedang login
    |--------------------------------------------------------------------------
    */
    $stmtAdminProfile = $pdo->prepare("
        SELECT id, kab_kota, kecamatan
        FROM profiles
        WHERE user_id = ?
          AND type = 'admin'
        LIMIT 1
    ");
    $stmtAdminProfile->execute([$currentUserId]);
    $adminProfile = $stmtAdminProfile->fetch(PDO::FETCH_ASSOC);

    if ($adminProfile) {
        $adminProfileId = (int)$adminProfile['id'];

        /*
        |--------------------------------------------------------------------------
        | Ambil daftar kabupaten/kota dari dapil admin
        |--------------------------------------------------------------------------
        */
        $stmtDapil = $pdo->prepare("
            SELECT d.kab_kota
            FROM profile_dapil pd
            INNER JOIN dapil d ON d.id = pd.dapil_id
            WHERE pd.profile_id = ?
        ");
        $stmtDapil->execute([$adminProfileId]);
        $dapilRows = $stmtDapil->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dapilRows as $dapilRow) {
            $kabKotaJson = $dapilRow['kab_kota'] ?? '';
            $decodedKabKota = json_decode($kabKotaJson, true);

            if (is_array($decodedKabKota)) {
                foreach ($decodedKabKota as $kabKota) {
                    $kabKota = trim($kabKota);

                    if ($kabKota !== '') {
                        $allowedKabKota[] = $kabKota;
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Cadangan:
        | Kalau admin belum punya relasi di profile_dapil,
        | dashboard tetap menampilkan data berdasarkan kab_kota profil admin.
        |--------------------------------------------------------------------------
        */
        if (empty($allowedKabKota) && !empty($adminProfile['kab_kota'])) {
            $allowedKabKota[] = trim($adminProfile['kab_kota']);
        }
    }

    $allowedKabKota = array_values(array_unique($allowedKabKota));
}

/*
|--------------------------------------------------------------------------
| Fungsi menghitung total profiles
|--------------------------------------------------------------------------
*/
$countProfiles = function ($type) use ($pdo, $isStatLimited, $allowedKabKota, $makePlaceholders) {
    if ($isStatLimited && empty($allowedKabKota)) {
        return 0;
    }

    if ($isStatLimited) {
        $placeholders = $makePlaceholders(count($allowedKabKota));

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE type = ?
              AND kab_kota IN ($placeholders)
        ");

        $stmt->execute(array_merge([$type], $allowedKabKota));

        return (int)$stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM profiles
        WHERE type = ?
    ");
    $stmt->execute([$type]);

    return (int)$stmt->fetchColumn();
};

/*
|--------------------------------------------------------------------------
| Fungsi menghitung akun aktif
|--------------------------------------------------------------------------
| Superadmin : semua user aktif
| Admin      : user aktif yang profilnya masuk dapil admin
*/
$countActiveUsers = function () use ($pdo, $isStatLimited, $allowedKabKota, $makePlaceholders) {
    if ($isStatLimited && empty($allowedKabKota)) {
        return 0;
    }

    if ($isStatLimited) {
        $placeholders = $makePlaceholders(count($allowedKabKota));

        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT u.id)
            FROM users u
            INNER JOIN profiles p ON p.user_id = u.id
            WHERE u.is_active = 1
              AND p.kab_kota IN ($placeholders)
        ");

        $stmt->execute($allowedKabKota);

        return (int)$stmt->fetchColumn();
    }

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE is_active = 1
    ");

    return (int)$stmt->fetchColumn();
};

/*
|--------------------------------------------------------------------------
| Total statistik
|--------------------------------------------------------------------------
*/
$totalRelawan = $countProfiles('relawan');
$totalDukungan = $countProfiles('dukungan');
$totalAdmin = $countProfiles('admin');
$totalUser = $countActiveUsers();

/*
|--------------------------------------------------------------------------
| Teks sambutan dan judul chart
|--------------------------------------------------------------------------
*/
if ($role === 'superadmin') {
    $welcomeRole = 'Superadmin';
    $welcomeText = 'Anda memiliki akses penuh untuk mengelola admin kecamatan, relawan, dukungan, serta akun pengguna dalam sistem.';
    $chartTitle = 'Bar Chart Relawan & Dukungan Seluruh Kabupaten/Kota';
    $pieTitle = 'Persentase Data Keseluruhan';
} elseif ($role === 'admin') {
    $welcomeRole = 'Admin Dapil';
    $welcomeText = 'Anda dapat melihat statistik relawan dan dukungan sesuai daerah pemilihan yang menjadi tanggung jawab Anda.';
    $chartTitle = 'Bar Chart Relawan & Dukungan Berdasarkan Dapil Anda';
    $pieTitle = 'Persentase Data Dapil Anda';
} else {
    $welcomeRole = 'Relawan';
    $welcomeText = 'Anda dapat melihat profil pendaftaran diri dan menambahkan data dukungan yang berhasil dikumpulkan.';
    $chartTitle = 'Bar Chart Relawan & Dukungan';
    $pieTitle = 'Persentase Data';
}

/*
|--------------------------------------------------------------------------
| Ambil data chart
|--------------------------------------------------------------------------
*/
if ($isStatLimited && empty($allowedKabKota)) {
    $rows = [];
} elseif ($isStatLimited) {
    $placeholders = $makePlaceholders(count($allowedKabKota));

    $stmt = $pdo->prepare("
        SELECT
            kab_kota,
            SUM(CASE WHEN type = 'relawan' THEN 1 ELSE 0 END) AS total_relawan,
            SUM(CASE WHEN type = 'dukungan' THEN 1 ELSE 0 END) AS total_dukungan
        FROM profiles
        WHERE kab_kota IN ($placeholders)
        GROUP BY kab_kota
    ");

    $stmt->execute($allowedKabKota);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("
        SELECT
            kab_kota,
            SUM(CASE WHEN type = 'relawan' THEN 1 ELSE 0 END) AS total_relawan,
            SUM(CASE WHEN type = 'dukungan' THEN 1 ELSE 0 END) AS total_dukungan
        FROM profiles
        GROUP BY kab_kota
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| Susun data chart
|--------------------------------------------------------------------------
*/
$dataMap = [];

foreach ($rows as $row) {
    $kabKota = trim($row['kab_kota'] ?? '');

    if ($kabKota !== '') {
        $dataMap[$kabKota] = [
            'relawan' => (int)$row['total_relawan'],
            'dukungan' => (int)$row['total_dukungan']
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Label chart
|--------------------------------------------------------------------------
| Superadmin : tampilkan semua kab/kota, termasuk data luar Kalsel jika ada.
| Admin      : hanya tampilkan kab/kota dalam dapilnya.
*/
if ($isStatLimited) {
    $chartKabKota = [];

    foreach ($allKabKota as $kabKota) {
        if (in_array($kabKota, $allowedKabKota, true)) {
            $chartKabKota[] = $kabKota;
        }
    }

    foreach ($allowedKabKota as $kabKota) {
        if (!in_array($kabKota, $chartKabKota, true)) {
            $chartKabKota[] = $kabKota;
        }
    }
} else {
    $chartKabKota = $allKabKota;

    foreach ($rows as $row) {
        $kabKota = trim($row['kab_kota'] ?? '');

        if ($kabKota !== '' && !in_array($kabKota, $chartKabKota, true)) {
            $chartKabKota[] = $kabKota;
        }
    }
}

$labels = [];
$relawanData = [];
$dukunganData = [];

foreach ($chartKabKota as $kabKota) {
    $labels[] = $kabKota;
    $relawanData[] = $dataMap[$kabKota]['relawan'] ?? 0;
    $dukunganData[] = $dataMap[$kabKota]['dukungan'] ?? 0;
}

?>

<div class="dashboard-hero">
    <div class="hero-content">
        <span class="hero-badge">
            <i class="fas fa-sparkles"></i> Selamat Datang
        </span>

        <h1 class="hero-title">
            Halo, <?= e($currentUser['name'] ?? '-') ?> 👋
        </h1>

        <p class="hero-desc">
            Anda masuk sebagai <b><?= e($welcomeRole) ?></b>.
            <?= e($welcomeText) ?>
        </p>
    </div>
</div>

<?php if ($role === 'admin' && empty($allowedKabKota)): ?>
    <div class="alert alert-warning shadow-sm">
        <b>Perhatian:</b> akun admin ini belum memiliki dapil.
        Silakan hubungkan admin dengan daerah pemilihan terlebih dahulu agar statistik dapil dapat muncul.
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <?= e($chartTitle) ?>
                </h6>
            </div>

            <div class="card-body">
                <canvas id="kabupatenChart" height="135"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <?= e($pieTitle) ?>
                </h6>
            </div>

            <div class="card-body">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">
                            <?= $role === 'admin' ? 'Admin di Dapil' : 'Admin Kecamatan' ?>
                        </div>
                        <div class="stat-number"><?= e($totalAdmin) ?></div>
                    </div>

                    <div class="stat-icon primary">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Relawan</div>
                        <div class="stat-number"><?= e($totalRelawan) ?></div>
                    </div>

                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Dukungan</div>
                        <div class="stat-number"><?= e($totalDukungan) ?></div>
                    </div>

                    <div class="stat-icon info">
                        <i class="fas fa-handshake"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Akun Aktif</div>
                        <div class="stat-number"><?= e($totalUser) ?></div>
                    </div>

                    <div class="stat-icon warning">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-lg-8 mb-4">
        <div class="card content-card h-100">

            <div class="card-header">
                <h6 class="m-0">
                    <i class="fas fa-layer-group mr-2" style="color:#3db7ee;"></i>
                    Ringkasan Hak Akses Sistem
                </h6>
            </div>

            <div class="card-body">

                <p style="color:#5f788f; line-height:1.7;">
                    Sistem ini digunakan untuk membantu proses pencatatan dan pengelolaan data
                    <b>admin dapil</b>, <b>relawan</b>, dan <b>dukungan</b>.
                    Setiap pengguna memiliki hak akses yang berbeda agar pengelolaan data lebih tertata.
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Role</th>
                                <th>Hak Akses</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>
                                    <span class="role-pill">Superadmin</span>
                                </td>
                                <td>
                                    Melihat seluruh statistik, mengelola seluruh admin, relawan, dukungan, dan akun pengguna dalam sistem.
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="role-pill">Admin Dapil</span>
                                </td>
                                <td>
                                    Melihat statistik hanya berdasarkan dapil yang menjadi tanggung jawabnya, serta mengelola relawan dan dukungan pada wilayah tersebut.
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="role-pill">Relawan</span>
                                </td>
                                <td>
                                    Melihat profil dirinya dan menginput data dukungan yang diperoleh.
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="role-pill">Dukungan</span>
                                </td>
                                <td>
                                    Tidak memiliki akun login. Data dukungan hanya dicatat oleh admin atau relawan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card content-card h-100">

            <div class="card-header">
                <h6 class="m-0">
                    <i class="fas fa-user-circle mr-2" style="color:#3db7ee;"></i>
                    Profil Pengguna
                </h6>
            </div>

            <div class="card-body">

                <div class="profile-box">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="profile-name">
                        <?= e($currentUser['name'] ?? '-') ?>
                    </div>

                    <div class="profile-role">
                        <?= e(ucfirst($role)) ?>
                    </div>

                    <p style="color:#7890a6; font-size:14px; margin-bottom:20px;">
                        Kecamatan:
                        <b><?= e($currentUser['kecamatan'] ?? '-') ?></b>
                    </p>

                    <?php if ($role === 'admin' && !empty($allowedKabKota)): ?>
                        <p style="color:#7890a6; font-size:13px; line-height:1.6; margin-bottom:20px;">
                            Wilayah Dapil:
                            <br>
                            <b><?= e(implode(', ', $allowedKabKota)) ?></b>
                        </p>
                    <?php endif; ?>
                </div>

                <a href="<?= url('dashboard/index.php') ?>" class="quick-action">
                    <i class="fas fa-home"></i> Dashboard
                </a>

                <?php if (in_array($role, ['superadmin', 'admin'])): ?>
                    <a href="<?= url('admin/create-relawan.php') ?>" class="quick-action">
                        <i class="fas fa-user-plus"></i> Tambah Relawan
                    </a>
                <?php endif; ?>

                <?php if (in_array($role, ['superadmin', 'admin', 'relawan'])): ?>
                    <a href="<?= url('dukungan/create.php') ?>" class="quick-action">
                        <i class="fas fa-hand-holding-heart"></i> Tambah Dukungan
                    </a>
                <?php endif; ?>

                <a href="<?= url('logout.php') ?>" class="quick-action">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('kabupatenChart');

new Chart(ctx, {
    type: 'bar',

    data: {
        labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,

        datasets: [
            {
                label: 'Relawan',
                data: <?= json_encode($relawanData) ?>,
                backgroundColor: '#1cc88a',
                borderRadius: 6
            },

            {
                label: 'Dukungan',
                data: <?= json_encode($dukunganData) ?>,
                backgroundColor: '#36b9cc',
                borderRadius: 6
            }
        ]
    },

    options: {
        responsive: true,
        maintainAspectRatio: true,

        plugins: {
            legend: {
                position: 'top'
            }
        },

        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            },

            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 35
                }
            }
        }
    }
});

const pieCtx = document.getElementById('pieChart');

new Chart(pieCtx, {
    type: 'doughnut',

    data: {
        labels: ['Relawan', 'Dukungan'],

        datasets: [{
            data: [
                <?= (int)$totalRelawan ?>,
                <?= (int)$totalDukungan ?>
            ],

            backgroundColor: [
                '#1cc88a',
                '#36b9cc'
            ],

            borderWidth: 1
        }]
    },

    options: {
        responsive: true,

        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>