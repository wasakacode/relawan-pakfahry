<?php

require_once __DIR__ . '/../auth/auth.php';

require_role([
    'superadmin',
    'admin',
    'relawan'
]);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

$user = current_user();
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| PARAMETER
|--------------------------------------------------------------------------
*/

$dapil = $_GET['dapil'] ?? '';
$kab   = $_GET['kab'] ?? '';
$kec   = $_GET['kec'] ?? '';
$desa  = $_GET['desa'] ?? '';

/*
|--------------------------------------------------------------------------
| VARIABEL
|--------------------------------------------------------------------------
*/

$totalRelawan = 0;

$dapilAdmin = null;
$namaDapilAdmin = null;
$kabupatenAdmin = [];

$dataLevel1 = [];
$dataLevel2 = [];
$dataLevel3 = [];
$dataLevel4 = [];

$dataTampil = [];

$breadcrumb = [];


/*
|--------------------------------------------------------------------------
| WILAYAH ADMIN
|--------------------------------------------------------------------------
*/

if ($role == 'admin') {

    $stmt = $pdo->prepare("
        SELECT
            d.id,
            d.daerah_pemilihan,
            d.kab_kota
        FROM profile_dapil pd

        JOIN dapil d
            ON d.id = pd.dapil_id

        JOIN profiles p
            ON p.id = pd.profile_id

        WHERE
            p.user_id = ?
            AND p.type='admin'
    ");

    $stmt->execute([$user['id']]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dapilAdmin = $row['id'];
        $namaDapilAdmin = $row['daerah_pemilihan'];

        $list = json_decode($row['kab_kota'] ?? '[]', true);

        if (is_array($list)) {

            $kabupatenAdmin = array_merge(
                $kabupatenAdmin,
                $list
            );
        }
    }

    $kabupatenAdmin = array_unique($kabupatenAdmin);
}

/*
|--------------------------------------------------------------------------
| TOTAL RELAWAN
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin') {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM profiles
        WHERE type='relawan'
    ");

    $totalRelawan = $stmt->fetchColumn();
} else {

    if (!empty($kabupatenAdmin)) {

        $placeholder = implode(
            ',',
            array_fill(0, count($kabupatenAdmin), '?')
        );

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE
                type='relawan'
                AND kab_kota IN ($placeholder)
        ");

        $stmt->execute($kabupatenAdmin);

        $totalRelawan = $stmt->fetchColumn();
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 1
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin') {

    $stmt = $pdo->query("
        SELECT
            id,
            daerah_pemilihan,
            kab_kota
        FROM dapil
        ORDER BY id
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Ambil daftar kabupaten dalam dapil
        $kabupaten = json_decode($row['kab_kota'], true);

        $total = 0;

        if (!empty($kabupaten)) {

            $placeholder = implode(',', array_fill(0, count($kabupaten), '?'));

            $stmtTotal = $pdo->prepare("
                SELECT COUNT(*)
                FROM profiles
                WHERE
                    type = 'relawan'
                    AND kab_kota IN ($placeholder)
            ");

            $stmtTotal->execute($kabupaten);

            $total = $stmtTotal->fetchColumn();
        }

        $dataLevel1[] = [
            'nama'  => $row['daerah_pemilihan'],
            'total' => $total,
            'url'   => '?dapil=' . $row['id']
        ];
    }
} else {

    foreach ($kabupatenAdmin as $kabupaten) {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE
                type = 'relawan'
                AND kab_kota = ?
        ");

        $stmt->execute([$kabupaten]);

        $dataLevel1[] = [
            'nama'  => $kabupaten,
            'total' => $stmt->fetchColumn(),
            'url'   => '?kab=' . urlencode($kabupaten)
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 2
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin' && $dapil != '') {

    $stmt = $pdo->prepare("
        SELECT kab_kota
        FROM dapil
        WHERE id = ?
    ");

    $stmt->execute([$dapil]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {

        $listKabupaten = json_decode($row['kab_kota'], true);

        foreach ($listKabupaten as $kabupaten) {

            $stmtJumlah = $pdo->prepare("
                SELECT COUNT(*)
                FROM profiles
                WHERE
                    type='relawan'
                    AND kab_kota=?
            ");

            $stmtJumlah->execute([$kabupaten]);

            $dataLevel2[] = [
                'nama'  => $kabupaten,
                'total' => $stmtJumlah->fetchColumn(),
                'url'   => '?dapil=' . $dapil .
                    '&kab=' . urlencode($kabupaten)
            ];
        }
    }
} elseif ($role == 'admin' && $kab != '') {

    $stmt = $pdo->prepare("
        SELECT
            t.kecamatan,
            COUNT(p.id) AS total
        FROM (
            SELECT DISTINCT kecamatan
            FROM tps_kalsel
            WHERE kabupaten = ?
        ) t

        LEFT JOIN profiles p
            ON p.kab_kota = ?
            AND p.kecamatan = t.kecamatan
            AND p.type = 'relawan'

        GROUP BY t.kecamatan
        ORDER BY t.kecamatan
    ");

    $stmt->execute([
        $kab,
        $kab
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel2[] = [
            'nama'  => $row['kecamatan'],
            'total' => $row['total'],
            'url'   => '?kab=' . urlencode($kab) .
                '&kec=' . urlencode($row['kecamatan'])
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 3
|--------------------------------------------------------------------------
*/

if ($kab != '' && $kec != '') {

    // Batasi akses admin
    if ($role == 'admin' && !in_array($kab, $kabupatenAdmin)) {
        $kab = '';
    }

    if ($kab != '') {

        $stmt = $pdo->prepare("
            SELECT
                t.kelurahan,
                COUNT(p.id) AS total
            FROM (
                SELECT DISTINCT kelurahan
                FROM tps_kalsel
                WHERE
                    kabupaten = ?
                    AND kecamatan = ?
            ) t

            LEFT JOIN profiles p
                ON p.kab_kota = ?
                AND p.kecamatan = ?
                AND p.desa_kelurahan = t.kelurahan
                AND p.type = 'relawan'

            GROUP BY t.kelurahan
            ORDER BY t.kelurahan
        ");

        $stmt->execute([
            $kab,
            $kec,
            $kab,
            $kec
        ]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            if ($role == 'superadmin') {

                $url = '?dapil=' . $dapil .
                    '&kab=' . urlencode($kab) .
                    '&kec=' . urlencode($kec) .
                    '&desa=' . urlencode($row['kelurahan']);
            } else {

                $url = '?kab=' . urlencode($kab) .
                    '&kec=' . urlencode($kec) .
                    '&desa=' . urlencode($row['kelurahan']);
            }

            $dataLevel3[] = [
                'nama'  => $row['kelurahan'],
                'total' => $row['total'],
                'url'   => $url
            ];
        }
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 4
|--------------------------------------------------------------------------
*/

if ($kab != '' && $kec != '' && $desa != '') {

    // Batasi akses admin
    if ($role == 'admin' && !in_array($kab, $kabupatenAdmin)) {
        $kab = '';
    }

    if ($kab != '') {

        $stmt = $pdo->prepare("
            SELECT
                t.no_tps,
                COUNT(p.id) AS total
            FROM tps_kalsel t

            LEFT JOIN profiles p
                ON p.tps = t.no_tps
                AND p.kab_kota = t.kabupaten
                AND p.kecamatan = t.kecamatan
                AND p.desa_kelurahan = t.kelurahan
                AND p.type = 'relawan'

            WHERE
                t.kabupaten = ?
                AND t.kecamatan = ?
                AND t.kelurahan = ?

            GROUP BY t.no_tps
            ORDER BY CAST(t.no_tps AS UNSIGNED)
        ");

        $stmt->execute([
            $kab,
            $kec,
            $desa
        ]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $dataLevel4[] = [
                'nama'  => 'TPS ' . str_pad($row['no_tps'], 3, '0', STR_PAD_LEFT),
                'total' => $row['total']
            ];
        }
    }
}

/*
|--------------------------------------------------------------------------
| DATA TAMPIL
|--------------------------------------------------------------------------
*/

$dataTampil = $dataLevel1;

if ($role == 'superadmin') {

    if ($dapil != '') {
        $dataTampil = $dataLevel2;
    }

    if ($kab != '') {
        $dataTampil = $dataLevel3;
    }

    if ($kec != '' && $desa != '') {
        $dataTampil = $dataLevel4;
    }
} else {

    if ($kab != '') {
        $dataTampil = $dataLevel2;
    }

    if ($kec != '') {
        $dataTampil = $dataLevel3;
    }

    if ($desa != '') {
        $dataTampil = $dataLevel4;
    }
}

/*
|--------------------------------------------------------------------------
| BREADCRUMB
|--------------------------------------------------------------------------
*/

$breadcrumb = [];

$breadcrumb[] = [
    'nama' => 'Kalimantan Selatan',
    'url'  => '?'
];

if ($role == 'superadmin' && $dapil != '') {

    $stmt = $pdo->prepare("
        SELECT daerah_pemilihan
        FROM dapil
        WHERE id = ?
    ");

    $stmt->execute([$dapil]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $breadcrumb[] = [
            'nama' => $row['daerah_pemilihan'],
            'url'  => '?dapil=' . $dapil
        ];
    }
}

if ($role == 'admin' && $namaDapilAdmin != '') {

    $breadcrumb[] = [
        'nama' => $namaDapilAdmin,
        'url'  => '#'
    ];
}

if ($kab != '') {

    if ($role == 'superadmin') {

        $url = '?dapil=' . $dapil .
            '&kab=' . urlencode($kab);
    } else {

        $url = '?kab=' . urlencode($kab);
    }

    $breadcrumb[] = [
        'nama' => $kab,
        'url'  => $url
    ];
}

if ($kec != '') {

    if ($role == 'superadmin') {

        $url = '?dapil=' . $dapil .
            '&kab=' . urlencode($kab) .
            '&kec=' . urlencode($kec);
    } else {

        $url = '?kab=' . urlencode($kab) .
            '&kec=' . urlencode($kec);
    }

    $breadcrumb[] = [
        'nama' => $kec,
        'url'  => $url
    ];
}

if ($desa != '') {

    $breadcrumb[] = [
        'nama' => $desa,
        'url'  => '#'
    ];
}
?>

<!-- JUDUL -->
<div class="text-center mb-4">

    <h3 class="font-weight-bold">
        Statistik Wilayah Relawan
        <?php if ($role == 'superadmin'): ?>
            (Role Superadmin)
        <?php else: ?>
            (Role Admin)
        <?php endif; ?>
    </h3>

</div>

<!-- TOTAL RELAWAN -->
<div class="row justify-content-center mb-4">

    <div class="col-md-3">

        <div class="card border-primary shadow">

            <div class="card-body text-center">

                <h5 class="mb-2">TOTAL RELAWAN</h5>

                <h2 class="font-weight-bold text-primary">
                    <?= number_format($totalRelawan) ?>
                </h2>

            </div>

        </div>

    </div>

</div>

<!-- BREADCRUMB -->
<nav aria-label="breadcrumb">

    <ol class="breadcrumb bg-white">

        <?php foreach ($breadcrumb as $index => $item): ?>

            <?php if ($index == count($breadcrumb) - 1): ?>

                <li class="breadcrumb-item active">

                    <?= e($item['nama']) ?>

                </li>

            <?php else: ?>

                <li class="breadcrumb-item">

                    <?php if ($item['url'] != '#'): ?>

                        <a href="<?= $item['url'] ?>">

                            <?= e($item['nama']) ?>

                        </a>

                    <?php else: ?>

                        <?= e($item['nama']) ?>

                    <?php endif; ?>

                </li>

            <?php endif; ?>

        <?php endforeach; ?>

    </ol>

</nav>

<!-- LIST DATA -->
<div class="card shadow">

    <div class="list-group list-group-flush">

        <?php if (empty($dataTampil)): ?>

            <div class="list-group-item text-center text-muted py-5">

                Tidak ada data.

            </div>

        <?php else: ?>

            <?php foreach ($dataTampil as $row): ?>

                <?php if (!empty($row['url'])): ?>

                    <a href="<?= $row['url'] ?>"
                        class="list-group-item list-group-item-action">

                        <div class="d-flex justify-content-between align-items-center">

                            <strong>

                                <?= e($row['nama']) ?>

                            </strong>

                            <span class="badge badge-primary badge-pill">

                                <?= number_format($row['total']) ?>

                            </span>

                        </div>

                    </a>

                <?php else: ?>

                    <div class="list-group-item">

                        <div class="d-flex justify-content-between align-items-center">

                            <strong>

                                <?= e($row['nama']) ?>

                            </strong>

                            <span class="badge badge-secondary badge-pill">

                                <?= number_format($row['total']) ?>

                            </span>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>