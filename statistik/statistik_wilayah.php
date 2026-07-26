<?php

require_once __DIR__ . '/../auth/auth.php';

require_role([
    'superadmin',
    'admin'
]);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$user = current_user();
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function buat_url(array $parameter = []): string
{
    return '?' . http_build_query($parameter);
}

function normalisasi_daftar_wilayah($json): array
{
    $daftar = json_decode($json ?? '[]', true);

    if (!is_array($daftar)) {
        return [];
    }

    $daftar = array_map('trim', $daftar);
    $daftar = array_filter($daftar, static function ($item) {
        return $item !== '';
    });

    return array_values(array_unique($daftar));
}

function ambil_kabupaten_dapil(PDO $pdo, int $dapilId): array
{
    $stmt = $pdo->prepare("
        SELECT kab_kota
        FROM dapil
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$dapilId]);

    return normalisasi_daftar_wilayah($stmt->fetchColumn());
}

function hitung_relawan_kabupaten(PDO $pdo, array $daftarKabupaten): int
{
    if (empty($daftarKabupaten)) {
        return 0;
    }

    $placeholder = implode(
        ',',
        array_fill(0, count($daftarKabupaten), '?')
    );

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM profiles
        WHERE
            type = 'relawan'
            AND kab_kota IN ($placeholder)
    ");
    $stmt->execute($daftarKabupaten);

    return (int) $stmt->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| PARAMETER
|--------------------------------------------------------------------------
*/

$dapil = isset($_GET['dapil']) ? (int) $_GET['dapil'] : 0;
$kab   = trim($_GET['kab'] ?? '');
$kec   = trim($_GET['kec'] ?? '');
$desa  = trim($_GET['desa'] ?? '');

/*
|--------------------------------------------------------------------------
| WILAYAH ADMIN
|--------------------------------------------------------------------------
| Admin hanya dapat melihat relawan pada kabupaten/kota yang berada dalam
| dapil yang terhubung dengan profil admin tersebut.
*/

$dapilAdmin = [];
$namaDapilAdmin = [];
$kabupatenAdmin = [];

if ($role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            d.id,
            d.daerah_pemilihan,
            d.kab_kota
        FROM profile_dapil pd

        INNER JOIN dapil d
            ON d.id = pd.dapil_id

        INNER JOIN profiles p
            ON p.id = pd.profile_id

        WHERE
            p.user_id = ?
            AND p.type = 'admin'

        ORDER BY d.id
    ");
    $stmt->execute([$user['id']]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dapilAdmin[] = (int) $row['id'];
        $namaDapilAdmin[] = $row['daerah_pemilihan'];

        $kabupatenAdmin = array_merge(
            $kabupatenAdmin,
            normalisasi_daftar_wilayah($row['kab_kota'])
        );
    }

    $dapilAdmin = array_values(array_unique($dapilAdmin));
    $namaDapilAdmin = array_values(array_unique($namaDapilAdmin));
    $kabupatenAdmin = array_values(array_unique($kabupatenAdmin));
    sort($kabupatenAdmin, SORT_NATURAL | SORT_FLAG_CASE);

    // Admin tidak menggunakan parameter dapil dari URL.
    $dapil = 0;
}

/*
|--------------------------------------------------------------------------
| TOTAL RELAWAN
|--------------------------------------------------------------------------
*/

if ($role === 'superadmin') {
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM profiles
        WHERE type = 'relawan'
    ");

    $totalRelawan = (int) $stmt->fetchColumn();
} else {
    $totalRelawan = hitung_relawan_kabupaten(
        $pdo,
        $kabupatenAdmin
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI DAPIL SUPERADMIN
|--------------------------------------------------------------------------
*/

$namaDapil = '';
$kabupatenDapil = [];

if ($role === 'superadmin' && $dapil > 0) {
    $stmt = $pdo->prepare("
        SELECT daerah_pemilihan
        FROM dapil
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$dapil]);

    $namaDapil = (string) $stmt->fetchColumn();

    if ($namaDapil === '') {
        $dapil = 0;
        $kab = '';
        $kec = '';
        $desa = '';
    } else {
        $kabupatenDapil = ambil_kabupaten_dapil(
            $pdo,
            $dapil
        );
    }
}

/*
|--------------------------------------------------------------------------
| VALIDASI AKSES WILAYAH
|--------------------------------------------------------------------------
*/

if ($role === 'superadmin') {
    // Superadmin harus memilih dapil sebelum memilih kabupaten/kota.
    if ($dapil === 0) {
        $kab = '';
        $kec = '';
        $desa = '';
    } elseif (
        $kab !== ''
        && !in_array($kab, $kabupatenDapil, true)
    ) {
        $kab = '';
        $kec = '';
        $desa = '';
    }
} else {
    // Admin hanya boleh membuka kabupaten/kota sesuai dapilnya.
    if (
        $kab !== ''
        && !in_array($kab, $kabupatenAdmin, true)
    ) {
        $kab = '';
        $kec = '';
        $desa = '';
    }
}

/*
|--------------------------------------------------------------------------
| DATA YANG DITAMPILKAN
|--------------------------------------------------------------------------
*/

$dataTampil = [];
$levelAktif = $role === 'superadmin'
    ? 'dapil'
    : 'kabupaten';

/*
|--------------------------------------------------------------------------
| LEVEL 1 SUPERADMIN: SEMUA DAPIL
|--------------------------------------------------------------------------
*/

if ($role === 'superadmin' && $dapil === 0) {
    $stmt = $pdo->query("
        SELECT
            id,
            daerah_pemilihan,
            kab_kota
        FROM dapil
        ORDER BY id
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $daftarKabupaten = normalisasi_daftar_wilayah(
            $row['kab_kota']
        );

        $dataTampil[] = [
            'nama'  => $row['daerah_pemilihan'],
            'total' => hitung_relawan_kabupaten(
                $pdo,
                $daftarKabupaten
            ),
            'url'   => buat_url([
                'dapil' => $row['id']
            ])
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL KABUPATEN/KOTA
|--------------------------------------------------------------------------
*/

elseif ($kab === '') {
    $levelAktif = 'kabupaten';

    $daftarKabupaten = $role === 'superadmin'
        ? $kabupatenDapil
        : $kabupatenAdmin;

    foreach ($daftarKabupaten as $namaKabupaten) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE
                type = 'relawan'
                AND kab_kota = ?
        ");
        $stmt->execute([$namaKabupaten]);

        $parameterUrl = [
            'kab' => $namaKabupaten
        ];

        if ($role === 'superadmin') {
            $parameterUrl = [
                'dapil' => $dapil,
                'kab'   => $namaKabupaten
            ];
        }

        $dataTampil[] = [
            'nama'  => $namaKabupaten,
            'total' => (int) $stmt->fetchColumn(),
            'url'   => buat_url($parameterUrl)
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL KECAMATAN
|--------------------------------------------------------------------------
*/

elseif ($kec === '') {
    $levelAktif = 'kecamatan';

    $stmt = $pdo->prepare("
        SELECT
            t.kecamatan,
            COUNT(DISTINCT p.id) AS total
        FROM (
            SELECT DISTINCT kecamatan
            FROM tps_kalsel
            WHERE kabupaten = ?
        ) t

        LEFT JOIN profiles p
            ON p.type = 'relawan'
            AND p.kab_kota = ?
            AND p.kecamatan = t.kecamatan

        GROUP BY t.kecamatan
        ORDER BY t.kecamatan
    ");

    $stmt->execute([
        $kab,
        $kab
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $parameterUrl = [
            'kab' => $kab,
            'kec' => $row['kecamatan']
        ];

        if ($role === 'superadmin') {
            $parameterUrl['dapil'] = $dapil;
            $parameterUrl = [
                'dapil' => $dapil,
                'kab'   => $kab,
                'kec'   => $row['kecamatan']
            ];
        }

        $dataTampil[] = [
            'nama'  => $row['kecamatan'],
            'total' => (int) $row['total'],
            'url'   => buat_url($parameterUrl)
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL DESA/KELURAHAN
|--------------------------------------------------------------------------
*/

elseif ($desa === '') {
    $levelAktif = 'kelurahan';

    $stmt = $pdo->prepare("
        SELECT
            t.kelurahan,
            COUNT(DISTINCT p.id) AS total
        FROM (
            SELECT DISTINCT kelurahan
            FROM tps_kalsel
            WHERE
                kabupaten = ?
                AND kecamatan = ?
        ) t

        LEFT JOIN profiles p
            ON p.type = 'relawan'
            AND p.kab_kota = ?
            AND p.kecamatan = ?
            AND p.desa_kelurahan = t.kelurahan

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
        $parameterUrl = [
            'kab'  => $kab,
            'kec'  => $kec,
            'desa' => $row['kelurahan']
        ];

        if ($role === 'superadmin') {
            $parameterUrl = [
                'dapil' => $dapil,
                'kab'   => $kab,
                'kec'   => $kec,
                'desa'  => $row['kelurahan']
            ];
        }

        $dataTampil[] = [
            'nama'  => $row['kelurahan'],
            'total' => (int) $row['total'],
            'url'   => buat_url($parameterUrl)
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LEVEL TPS
|--------------------------------------------------------------------------
*/

else {
    $levelAktif = 'tps';

    $stmt = $pdo->prepare("
        SELECT
            t.no_tps,
            COUNT(DISTINCT p.id) AS total
        FROM tps_kalsel t

        LEFT JOIN profiles p
            ON p.type = 'relawan'
            AND p.tps = t.no_tps
            AND p.kab_kota = t.kabupaten
            AND p.kecamatan = t.kecamatan
            AND p.desa_kelurahan = t.kelurahan

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
        $dataTampil[] = [
            'nama'  => 'TPS ' . str_pad(
                (string) $row['no_tps'],
                3,
                '0',
                STR_PAD_LEFT
            ),
            'total' => (int) $row['total'],
            'url'   => ''
        ];
    }
}

/*
|--------------------------------------------------------------------------
| BREADCRUMB
|--------------------------------------------------------------------------
*/

if ($role === 'superadmin') {
    $breadcrumb = [
        [
            'nama' => 'Semua Dapil',
            'url'  => buat_url()
        ]
    ];

    if ($dapil > 0) {
        $breadcrumb[] = [
            'nama' => $namaDapil,
            'url'  => buat_url([
                'dapil' => $dapil
            ])
        ];
    }
} else {
    $breadcrumb = [
        [
            'nama' => 'Kota/Kabupaten (sesuai dapil admin)',
            'url'  => buat_url()
        ]
    ];
}

if ($kab !== '') {
    $parameterUrl = [
        'kab' => $kab
    ];

    if ($role === 'superadmin') {
        $parameterUrl = [
            'dapil' => $dapil,
            'kab'   => $kab
        ];
    }

    $breadcrumb[] = [
        'nama' => $kab,
        'url'  => buat_url($parameterUrl)
    ];
}

if ($kec !== '') {
    $parameterUrl = [
        'kab' => $kab,
        'kec' => $kec
    ];

    if ($role === 'superadmin') {
        $parameterUrl = [
            'dapil' => $dapil,
            'kab'   => $kab,
            'kec'   => $kec
        ];
    }

    $breadcrumb[] = [
        'nama' => $kec,
        'url'  => buat_url($parameterUrl)
    ];
}

if ($desa !== '') {
    $breadcrumb[] = [
        'nama' => $desa,
        'url'  => ''
    ];
}

$labelLevel = [
    'dapil'     => 'Daerah Pemilihan',
    'kabupaten' => 'Kota/Kabupaten',
    'kecamatan' => 'Kecamatan',
    'kelurahan' => 'Desa/Kelurahan',
    'tps'       => 'TPS'
];

$judulRole = $role === 'superadmin'
    ? 'Superadmin'
    : 'Admin';

$keteranganTotal = $role === 'superadmin'
    ? '*total keseluruhan relawan'
    : '*total sesuai dengan dapil';

?>

<style>
    .statistik-wilayah-wrapper {
        padding-bottom: 30px;
    }

    .total-relawan-card {
        width: 100%;
        max-width: 310px;
        margin: 0 auto;
        border: 0;
        border-radius: 4px;
        background: #56b3e9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .total-relawan-card .card-body {
        padding: 18px 20px;
    }

    .total-relawan-card .judul-total {
        margin: 0;
        font-size: 21px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .total-relawan-card .angka-total {
        margin: 5px 0 0;
        font-size: 38px;
        font-weight: 700;
        line-height: 1;
    }

    .total-relawan-card .keterangan-total {
        margin: 6px 0 0;
        font-size: 13px;
    }

    .info-dapil-admin {
        max-width: 720px;
        margin: 12px auto 0;
        color: #6c757d;
        font-size: 13px;
        text-align: center;
    }

    .breadcrumb-wilayah {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        margin: 24px 0 14px;
        font-size: 17px;
        font-weight: 600;
    }

    .breadcrumb-wilayah a {
        color: #343a40;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .breadcrumb-wilayah a:hover {
        color: #007bff;
    }

    .breadcrumb-wilayah .aktif {
        color: #343a40;
    }

    .breadcrumb-wilayah .pemisah {
        color: #6c757d;
    }

    .judul-level {
        margin-bottom: 14px;
        color: #6c757d;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.7px;
    }

    .grid-wilayah {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .kartu-wilayah {
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 3px;
        background: #9ac6ee;
        color: #111;
        text-decoration: none !important;
        text-align: center;
        box-shadow: 0 3px 9px rgba(0, 0, 0, 0.08);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .kartu-wilayah:hover {
        color: #111;
        transform: translateY(-3px);
        box-shadow: 0 7px 17px rgba(0, 0, 0, 0.14);
    }

    .kartu-wilayah.kosong {
        background: #d7d7d7;
    }

    .kartu-wilayah.tidak-aktif:hover {
        transform: none;
        box-shadow: 0 3px 9px rgba(0, 0, 0, 0.08);
    }

    .kartu-wilayah .isi-kartu {
        width: 100%;
        padding: 18px 14px;
    }

    .kartu-wilayah .nama-wilayah {
        margin: 0 0 8px;
        font-size: 20px;
        font-weight: 500;
        word-break: break-word;
    }

    .kartu-wilayah .jumlah-relawan {
        margin: 0;
        font-size: 40px;
        font-weight: 500;
        line-height: 1;
    }

    .kartu-wilayah .label-relawan {
        margin: 8px 0 0;
        font-size: 18px;
    }

    .data-kosong {
        padding: 50px 20px;
        border-radius: 4px;
        background: #f2f2f2;
        color: #6c757d;
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .grid-wilayah {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .grid-wilayah {
            grid-template-columns: 1fr;
        }

        .breadcrumb-wilayah {
            font-size: 15px;
        }

        .kartu-wilayah {
            min-height: 135px;
        }
    }
</style>

<div class="container-fluid statistik-wilayah-wrapper">

    <!-- JUDUL -->
    <div class="text-center mb-4">
        <h3 class="font-weight-bold mb-0">
            Statistik Wilayah Relawan
            (Role <?= e($judulRole) ?>)
        </h3>
    </div>

    <!-- TOTAL RELAWAN -->
    <div class="total-relawan-card">
        <div class="card-body text-center">
            <p class="judul-total">TOTAL RELAWAN</p>

            <p class="angka-total">
                <?= number_format($totalRelawan, 0, ',', '.') ?>
            </p>

            <p class="keterangan-total">
                <?= e($keteranganTotal) ?>
            </p>
        </div>
    </div>

    <?php if ($role === 'admin' && !empty($namaDapilAdmin)): ?>
        <div class="info-dapil-admin">
            Dapil admin:
            <?= e(implode(', ', $namaDapilAdmin)) ?>
        </div>
    <?php endif; ?>

    <!-- BREADCRUMB -->
    <div class="breadcrumb-wilayah">
        <?php foreach ($breadcrumb as $index => $item): ?>

            <?php if ($index > 0): ?>
                <span class="pemisah">&gt;</span>
            <?php endif; ?>

            <?php
            $itemTerakhir = $index === count($breadcrumb) - 1;
            ?>

            <?php if (!$itemTerakhir && $item['url'] !== ''): ?>
                <a href="<?= e($item['url']) ?>">
                    <?= e($item['nama']) ?>
                </a>
            <?php else: ?>
                <span class="aktif">
                    <?= e($item['nama']) ?>
                </span>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>

    <div class="judul-level">
        Daftar <?= e($labelLevel[$levelAktif] ?? 'Wilayah') ?>
    </div>

    <!-- KARTU DATA -->
    <?php if (empty($dataTampil)): ?>

        <div class="data-kosong">
            <?php if ($role === 'admin' && empty($kabupatenAdmin)): ?>
                Admin belum memiliki dapil atau wilayah yang terhubung.
            <?php else: ?>
                Tidak ada data wilayah yang dapat ditampilkan.
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="grid-wilayah">

            <?php foreach ($dataTampil as $row): ?>

                <?php
                $totalKosong = (int) $row['total'] === 0;
                $punyaUrl = !empty($row['url']);

                $classKartu = 'kartu-wilayah';

                if ($totalKosong) {
                    $classKartu .= ' kosong';
                }

                if (!$punyaUrl) {
                    $classKartu .= ' tidak-aktif';
                }
                ?>

                <?php if ($punyaUrl): ?>

                    <a
                        href="<?= e($row['url']) ?>"
                        class="<?= e($classKartu) ?>"
                    >
                        <div class="isi-kartu">
                            <h5 class="nama-wilayah">
                                <?= e($row['nama']) ?>
                            </h5>

                            <p class="jumlah-relawan">
                                <?= number_format(
                                    (int) $row['total'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </p>

                            <p class="label-relawan">
                                Relawan
                            </p>
                        </div>
                    </a>

                <?php else: ?>

                    <div class="<?= e($classKartu) ?>">
                        <div class="isi-kartu">
                            <h5 class="nama-wilayah">
                                <?= e($row['nama']) ?>
                            </h5>

                            <p class="jumlah-relawan">
                                <?= number_format(
                                    (int) $row['total'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </p>

                            <p class="label-relawan">
                                Relawan
                            </p>
                        </div>
                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>