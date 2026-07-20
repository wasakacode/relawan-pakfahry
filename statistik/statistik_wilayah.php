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
| Login
|--------------------------------------------------------------------------
*/

$user = current_user();
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| Parameter URL
|--------------------------------------------------------------------------
*/

$dapil = $_GET['dapil'] ?? null;
$kab   = $_GET['kab'] ?? null;
$kec   = $_GET['kec'] ?? null;
$desa  = $_GET['desa'] ?? null;

/*
|--------------------------------------------------------------------------
| Variabel
|--------------------------------------------------------------------------
*/

$totalRelawan = 0;

$dataLevel1 = [];
$dataLevel2 = [];
$dataLevel3 = [];
$dataLevel4 = [];
$dataLevel5 = [];

$breadcrumb = [];

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

function persen($total, $grandTotal)
{
    if ($grandTotal == 0) {
        return 0;
    }

    return round(($total / $grandTotal) * 100, 2);
}

/*
|--------------------------------------------------------------------------
| TOTAL RELAWAN
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| SUPERADMIN
| Menghitung seluruh relawan
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin') {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM profiles
        WHERE type='relawan'
    ");

    $totalRelawan = $stmt->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| ADMIN
| Menghitung relawan sesuai dapil admin
|--------------------------------------------------------------------------
*/ elseif ($role == 'admin') {

    /*
    |--------------------------------------------------------------------------
    | Ambil semua kabupaten yang dimiliki admin
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT d.kab_kota
        FROM profile_dapil pd

        INNER JOIN dapil d
            ON d.id = pd.dapil_id

        INNER JOIN profiles p
            ON p.id = pd.profile_id

        WHERE
            p.user_id = ?
            AND p.type='admin'
    ");

    $stmt->execute([$user['id']]);

    $kabupaten = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $listKabupaten = json_decode($row['kab_kota'], true);

        if (is_array($listKabupaten)) {

            $kabupaten = array_merge(
                $kabupaten,
                $listKabupaten
            );
        }
    }

    $kabupaten = array_unique($kabupaten);

    /*
    |--------------------------------------------------------------------------
    | Hitung total relawan
    |--------------------------------------------------------------------------
    */

    if (!empty($kabupaten)) {

        $placeholder = implode(',', array_fill(0, count($kabupaten), '?'));

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE
                type='relawan'
                AND kab_kota IN ($placeholder)
        ");

        $stmt->execute($kabupaten);

        $totalRelawan = $stmt->fetchColumn();
    }
}

/*
|--------------------------------------------------------------------------
| RELAWAN
|--------------------------------------------------------------------------
*/ else {

    $totalRelawan = 0;
}

/*
|--------------------------------------------------------------------------
| LEVEL 1
|--------------------------------------------------------------------------
| Superadmin : Daftar Dapil
| Admin      : Daftar Kabupaten sesuai Dapil
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin') {

    $stmt = $pdo->query("
        SELECT
            id,
            daerah_pemilihan,
            kab_kota
        FROM dapil
        ORDER BY daerah_pemilihan
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $kabupaten = json_decode($row['kab_kota'], true);

        if (!is_array($kabupaten)) {
            $kabupaten = [];
        }

        $total = 0;

        if (!empty($kabupaten)) {

            $placeholder = implode(',', array_fill(0, count($kabupaten), '?'));

            $q = $pdo->prepare("
                SELECT COUNT(*)
                FROM profiles
                WHERE
                    type='relawan'
                    AND kab_kota IN ($placeholder)
            ");

            $q->execute($kabupaten);

            $total = $q->fetchColumn();
        }

        $dataLevel1[] = [
            'id'      => $row['id'],
            'nama'    => $row['daerah_pemilihan'],
            'total'   => $total,
            'persen'  => persen($total, $totalRelawan),
            'url'     => '?dapil=' . $row['id']
        ];
    }
} elseif ($role == 'admin') {

    /*
    |--------------------------------------------------------------------------
    | Ambil kabupaten yang menjadi wilayah admin
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT d.kab_kota
        FROM profile_dapil pd

        INNER JOIN dapil d
            ON d.id = pd.dapil_id

        INNER JOIN profiles p
            ON p.id = pd.profile_id

        WHERE
            p.user_id = ?
            AND p.type='admin'
    ");

    $stmt->execute([$user['id']]);

    $kabupaten = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $listKabupaten = json_decode($row['kab_kota'], true);

        if (is_array($listKabupaten)) {

            $kabupaten = array_merge(
                $kabupaten,
                $listKabupaten
            );
        }
    }

    $kabupaten = array_unique($kabupaten);

    if (!empty($kabupaten)) {

        $placeholder = implode(',', array_fill(0, count($kabupaten), '?'));

        $stmt = $pdo->prepare("
            SELECT
                kab_kota,
                COUNT(*) AS total
            FROM profiles
            WHERE
                type='relawan'
                AND kab_kota IN ($placeholder)
            GROUP BY kab_kota
            ORDER BY kab_kota
        ");

        $stmt->execute($kabupaten);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $dataLevel1[] = [
                'nama'   => $row['kab_kota'],
                'total'  => $row['total'],
                'persen' => persen($row['total'], $totalRelawan),
                'url'    => '?kab=' . urlencode($row['kab_kota'])
            ];
        }
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 2
|--------------------------------------------------------------------------
| Superadmin : Setelah memilih Dapil -> tampilkan Kabupaten
| Admin      : Setelah memilih Kabupaten -> tampilkan Kecamatan
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin' && $dapil && !$kab) {

    /*
    |--------------------------------------------------------------------------
    | Ambil daftar kabupaten dalam dapil
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            kab_kota
        FROM dapil
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$dapil]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {

        $kabupaten = json_decode($row['kab_kota'], true);

        if (!is_array($kabupaten)) {
            $kabupaten = [];
        }

        if (!empty($kabupaten)) {

            $placeholder = implode(',', array_fill(0, count($kabupaten), '?'));

            $stmt = $pdo->prepare("
                SELECT
                    kab_kota,
                    COUNT(*) AS total
                FROM profiles
                WHERE
                    type='relawan'
                    AND kab_kota IN ($placeholder)
                GROUP BY kab_kota
                ORDER BY kab_kota
            ");

            $stmt->execute($kabupaten);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $dataLevel2[] = [
                    'nama'   => $row['kab_kota'],
                    'total'  => $row['total'],
                    'persen' => persen($row['total'], $totalRelawan),
                    'url'    => '?dapil=' . $dapil .
                        '&kab=' . urlencode($row['kab_kota'])
                ];
            }
        }
    }
} elseif ($role == 'admin' && $kab && !$kec) {

    /*
    |--------------------------------------------------------------------------
    | Tampilkan Kecamatan
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            kecamatan,
            COUNT(*) AS total
        FROM profiles
        WHERE
            type='relawan'
            AND kab_kota = ?
        GROUP BY kecamatan
        ORDER BY kecamatan
    ");

    $stmt->execute([$kab]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel2[] = [
            'nama'   => $row['kecamatan'],
            'total'  => $row['total'],
            'persen' => persen($row['total'], $totalRelawan),
            'url'    => '?kab=' . urlencode($kab) .
                '&kec=' . urlencode($row['kecamatan'])
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 3
|--------------------------------------------------------------------------
| Superadmin : Setelah memilih Kabupaten -> tampilkan Kecamatan
| Admin      : Setelah memilih Kecamatan -> tampilkan Desa/Kelurahan
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin' && $dapil && $kab && !$kec) {

    /*
    |--------------------------------------------------------------------------
    | Tampilkan Kecamatan
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            kecamatan,
            COUNT(*) AS total
        FROM profiles
        WHERE
            type='relawan'
            AND kab_kota = ?
        GROUP BY kecamatan
        ORDER BY kecamatan
    ");

    $stmt->execute([$kab]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel3[] = [
            'nama'   => $row['kecamatan'],
            'total'  => $row['total'],
            'persen' => persen($row['total'], $totalRelawan),
            'url'    => '?dapil=' . $dapil .
                '&kab=' . urlencode($kab) .
                '&kec=' . urlencode($row['kecamatan'])
        ];
    }
} elseif ($role == 'admin' && $kab && $kec && !$desa) {

    /*
    |--------------------------------------------------------------------------
    | Tampilkan Desa / Kelurahan
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            desa_kelurahan,
            COUNT(*) AS total
        FROM profiles
        WHERE
            type='relawan'
            AND kab_kota = ?
            AND kecamatan = ?
        GROUP BY desa_kelurahan
        ORDER BY desa_kelurahan
    ");

    $stmt->execute([
        $kab,
        $kec
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel3[] = [
            'nama'   => $row['desa_kelurahan'],
            'total'  => $row['total'],
            'persen' => persen($row['total'], $totalRelawan),
            'url'    => '?kab=' . urlencode($kab) .
                '&kec=' . urlencode($kec) .
                '&desa=' . urlencode($row['desa_kelurahan'])
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 4
|--------------------------------------------------------------------------
| Superadmin : Setelah memilih Kecamatan -> tampilkan Desa/Kelurahan
| Admin      : Setelah memilih Desa -> tampilkan RT / RW
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin' && $dapil && $kab && $kec && !$desa) {

    /*
    |--------------------------------------------------------------------------
    | Tampilkan Desa / Kelurahan
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            desa_kelurahan,
            COUNT(*) AS total
        FROM profiles
        WHERE
            type='relawan'
            AND kab_kota = ?
            AND kecamatan = ?
        GROUP BY desa_kelurahan
        ORDER BY desa_kelurahan
    ");

    $stmt->execute([
        $kab,
        $kec
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel4[] = [
            'nama'   => $row['desa_kelurahan'],
            'total'  => $row['total'],
            'persen' => persen($row['total'], $totalRelawan),
            'url'    => '?dapil=' . $dapil .
                '&kab=' . urlencode($kab) .
                '&kec=' . urlencode($kec) .
                '&desa=' . urlencode($row['desa_kelurahan'])
        ];
    }
} elseif ($role == 'admin' && $kab && $kec && $desa) {

    /*
    |--------------------------------------------------------------------------
    | Tampilkan RT / RW
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            rt,
            rw,
            COUNT(*) AS total
        FROM profiles
        WHERE
            type='relawan'
            AND kab_kota = ?
            AND kecamatan = ?
            AND desa_kelurahan = ?
        GROUP BY rt, rw
        ORDER BY rt, rw
    ");

    $stmt->execute([
        $kab,
        $kec,
        $desa
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel4[] = [
            'rt'     => $row['rt'],
            'rw'     => $row['rw'],
            'total'  => $row['total'],
            'persen' => persen($row['total'], $totalRelawan)
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL 5
|--------------------------------------------------------------------------
| Superadmin : Setelah memilih Desa -> tampilkan RT / RW
|--------------------------------------------------------------------------
*/

if ($role == 'superadmin' && $dapil && $kab && $kec && $desa) {

    /*
    |--------------------------------------------------------------------------
    | Tampilkan RT / RW
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            rt,
            rw,
            COUNT(*) AS total
        FROM profiles
        WHERE
            type = 'relawan'
            AND kab_kota = ?
            AND kecamatan = ?
            AND desa_kelurahan = ?
        GROUP BY rt, rw
        ORDER BY rt, rw
    ");

    $stmt->execute([
        $kab,
        $kec,
        $desa
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $dataLevel5[] = [
            'rt'     => $row['rt'],
            'rw'     => $row['rw'],
            'total'  => $row['total'],
            'persen' => persen($row['total'], $totalRelawan)
        ];
    }
}

?>


<h1 class="h3 mb-4 text-gray-800">
    <i class="fas fa-chart-pie"></i>
    Statistik Wilayah Relawan
</h1>


<!-- ==========================================================
     TOTAL RELAWAN
=========================================================== -->

<div class="row mb-4">

    <div class="col-lg-4">

        <div class="card border-left-primary shadow h-100">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-primary text-uppercase mb-2">
                    Total Relawan
                </div>

                <div class="h3 mb-0 font-weight-bold text-gray-800">
                    <?= number_format($totalRelawan) ?>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================================
     BREADCRUMB
=========================================================== -->

<div class="mb-4">

    <?php if ($role == 'superadmin'): ?>

        <a href="statistik_wilayah.php"
            class="badge badge-primary">

            Semua Dapil

        </a>

    <?php endif; ?>


    <?php if ($dapil): ?>

        <?php

        $stmt = $pdo->prepare("
        SELECT daerah_pemilihan
        FROM dapil
        WHERE id=?
    ");

        $stmt->execute([$dapil]);

        ?>

        <span class="mx-2">></span>

        <span class="badge badge-info">

            <?= e($stmt->fetchColumn()) ?>

        </span>

    <?php endif; ?>


    <?php if ($kab): ?>

        <span class="mx-2">></span>

        <span class="badge badge-success">

            <?= e($kab) ?>

        </span>

    <?php endif; ?>


    <?php if ($kec): ?>

        <span class="mx-2">></span>

        <span class="badge badge-warning">

            <?= e($kec) ?>

        </span>

    <?php endif; ?>


    <?php if ($desa): ?>

        <span class="mx-2">></span>

        <span class="badge badge-dark">

            <?= e($desa) ?>

        </span>

    <?php endif; ?>

</div>

<!-- ==========================================================
     LEVEL 1
=========================================================== -->

<?php if (!empty($dataLevel1)): ?>

    <div class="row">

        <?php foreach ($dataLevel1 as $row): ?>

            <div class="col-lg-4 mb-4">

                <div class="card shadow h-100">

                    <div class="card-body">

                        <h5 class="font-weight-bold">
                            <?= e($row['nama']) ?>
                        </h5>

                        <h3 class="text-primary">
                            <?= number_format($row['total']) ?>
                        </h3>

                        <div class="progress mb-3">

                            <div class="progress-bar"
                                role="progressbar"
                                style="width: <?= $row['persen'] ?>%">

                            </div>

                        </div>

                        <small class="text-muted d-block mb-3">
                            <?= $row['persen'] ?>%
                            dari total relawan
                        </small>

                        <a href="<?= $row['url'] ?>"
                            class="btn btn-primary btn-sm">

                            <?= $role == 'superadmin'
                                ? 'Lihat Kabupaten'
                                : 'Lihat Kecamatan' ?>

                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<!-- ==========================================================
     LEVEL 2
=========================================================== -->

<?php if (!empty($dataLevel2)): ?>

    <div class="row">

        <?php foreach ($dataLevel2 as $row): ?>

            <div class="col-lg-4 mb-4">

                <div class="card shadow h-100">

                    <div class="card-body">

                        <h5 class="font-weight-bold">
                            <?= e($row['nama']) ?>
                        </h5>

                        <h3 class="text-info">
                            <?= number_format($row['total']) ?>
                        </h3>

                        <div class="progress mb-3">

                            <div class="progress-bar bg-info"
                                role="progressbar"
                                style="width: <?= $row['persen'] ?>%">

                            </div>

                        </div>

                        <small class="text-muted d-block mb-3">
                            <?= $row['persen'] ?>%
                            dari total relawan
                        </small>

                        <a href="<?= $row['url'] ?>"
                            class="btn btn-info btn-sm">

                            <?= $role == 'superadmin'
                                ? 'Lihat Kecamatan'
                                : 'Lihat Kelurahan' ?>

                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<!-- ==========================================================
     LEVEL 3
=========================================================== -->

<?php if (!empty($dataLevel3)): ?>

    <div class="row">

        <?php foreach ($dataLevel3 as $row): ?>

            <div class="col-lg-4 mb-4">

                <div class="card shadow h-100">

                    <div class="card-body">

                        <h5 class="font-weight-bold">
                            <?= e($row['nama']) ?>
                        </h5>

                        <h3 class="text-success">
                            <?= number_format($row['total']) ?>
                        </h3>

                        <div class="progress mb-3">

                            <div class="progress-bar bg-success"
                                role="progressbar"
                                style="width: <?= $row['persen'] ?>%">

                            </div>

                        </div>

                        <small class="text-muted d-block mb-3">
                            <?= $row['persen'] ?>%
                            dari total relawan
                        </small>

                        <a href="<?= $row['url'] ?>"
                            class="btn btn-success btn-sm">

                            <?= $role == 'superadmin'
                                ? 'Lihat Kelurahan'
                                : 'Lihat RT / RW' ?>

                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<!-- ==========================================================
     LEVEL 4
=========================================================== -->

<?php if (!empty($dataLevel4)): ?>

    <div class="row">

        <?php foreach ($dataLevel4 as $row): ?>

            <div class="col-lg-4 mb-4">

                <div class="card shadow h-100">

                    <div class="card-body">

                        <?php if (isset($row['nama'])): ?>

                            <h5 class="font-weight-bold">

                                <?= e($row['nama']) ?>

                            </h5>

                            <h3 class="text-warning">

                                <?= number_format($row['total']) ?>

                            </h3>

                            <div class="progress mb-3">

                                <div class="progress-bar bg-warning"
                                    style="width:<?= $row['persen'] ?>%">

                                </div>

                            </div>

                            <small class="text-muted d-block mb-3">

                                <?= $row['persen'] ?>%
                                dari total relawan

                            </small>

                            <a href="<?= $row['url'] ?>"
                                class="btn btn-warning btn-sm">

                                Lihat RT / RW

                            </a>

                        <?php else: ?>

                            <h5 class="font-weight-bold">

                                RT <?= e($row['rt']) ?>
                                /
                                RW <?= e($row['rw']) ?>

                            </h5>

                            <h2 class="text-success">

                                <?= number_format($row['total']) ?>

                            </h2>

                            <small class="text-muted">

                                Relawan

                            </small>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<!-- ==========================================================
     LEVEL 5
=========================================================== -->

<?php if (!empty($dataLevel5)): ?>

    <div class="row">

        <?php foreach ($dataLevel5 as $row): ?>

            <div class="col-lg-3 mb-4">

                <div class="card border-left-success shadow h-100">

                    <div class="card-body text-center">

                        <h5>

                            RT <?= e($row['rt']) ?>

                            /

                            RW <?= e($row['rw']) ?>

                        </h5>

                        <h2 class="text-success">

                            <?= number_format($row['total']) ?>

                        </h2>

                        <small>

                            Relawan

                        </small>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>