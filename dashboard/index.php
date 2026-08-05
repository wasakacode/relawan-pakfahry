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

if ($role === 'superadmin') {

    // Superadmin melihat statistik PER DAPIL
    $stmt = $pdo->query("
        SELECT
    d.daerah_pemilihan,

    COUNT(DISTINCT r.id) AS total_relawan,

    0 AS total_dukungan

FROM dapil d

LEFT JOIN profile_dapil pd
    ON pd.dapil_id = d.id

LEFT JOIN profiles a
    ON a.id = pd.profile_id
   AND a.type='admin'

LEFT JOIN profile_admin pa
    ON pa.admin_profile_id = a.id

LEFT JOIN profiles r
    ON r.id = pa.profile_id
   AND r.type='relawan'

GROUP BY
    d.id,
    d.daerah_pemilihan

ORDER BY
    d.daerah_pemilihan;
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {

    // Admin tetap berdasarkan kabupaten/kota
    if ($isStatLimited && empty($allowedKabKota)) {

        $rows = [];
    } elseif ($isStatLimited) {

        $placeholders = $makePlaceholders(count($allowedKabKota));

        $stmt = $pdo->prepare("
            SELECT
                kab_kota,
                SUM(CASE WHEN type='relawan' THEN 1 ELSE 0 END) AS total_relawan,
                SUM(CASE WHEN type='dukungan' THEN 1 ELSE 0 END) AS total_dukungan
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
                SUM(CASE WHEN type='relawan' THEN 1 ELSE 0 END) AS total_relawan,
                SUM(CASE WHEN type='dukungan' THEN 1 ELSE 0 END) AS total_dukungan
            FROM profiles
            GROUP BY kab_kota
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
/*
|--------------------------------------------------------------------------
| Susun data chart
|--------------------------------------------------------------------------
*/
$dataMap = [];

foreach ($rows as $row) {

    if ($role === 'superadmin') {

        $label = trim($row['daerah_pemilihan']);
    } else {

        $label = trim($row['kab_kota']);
    }

    $dataMap[$label] = [
        'relawan'  => (int)$row['total_relawan'],
        'dukungan' => (int)$row['total_dukungan']
    ];
}

/*
|--------------------------------------------------------------------------
| Label chart
|--------------------------------------------------------------------------
| Superadmin : tampilkan semua kab/kota, termasuk data luar Kalsel jika ada.
| Admin      : hanya tampilkan kab/kota dalam dapilnya.
*/
if ($role === 'superadmin') {

    $chartLabels = array_keys($dataMap);
} else {

    if ($isStatLimited) {

        $chartLabels = [];

        foreach ($allKabKota as $kab) {

            if (in_array($kab, $allowedKabKota)) {
                $chartLabels[] = $kab;
            }
        }
    } else {

        $chartLabels = $allKabKota;

        foreach ($rows as $row) {

            if (!in_array($row['kab_kota'], $chartLabels)) {
                $chartLabels[] = $row['kab_kota'];
            }
        }
    }
}

$labels = [];
$relawanData = [];
$dukunganData = [];

foreach ($chartLabels as $label) {

    $labels[] = $label;

    $relawanData[] = $dataMap[$label]['relawan'] ?? 0;

    $dukunganData[] = $dataMap[$label]['dukungan'] ?? 0;
}

?>


<style>
    .support-box {
        margin-top: 6px;
        padding: 20px;
        background: linear-gradient(135deg, #eef7ff, #f7fbff);
        border: 1.5px solid #b9dcff;
        border-radius: 18px;
        color: #1f3550;
    }

    .support-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        font-size: 18px;
        font-weight: 800;
        color: #17283f;
    }

    .support-title i {
        color: #2faee5;
        font-size: 22px;
    }

    .support-description {
        margin: 0 0 14px;
        color: #526b82;
        font-size: 14px;
        line-height: 1.6;
    }

    .support-contact {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .support-contact a {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #17283f;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .support-contact a i {
        width: 20px;
        color: #25a66a;
        font-size: 18px;
        text-align: center;
    }

    .support-contact a:hover {
        color: #168ed0;
        transform: translateX(3px);
        text-decoration: none;
    }

    .support-note {
        margin: 12px 0 0;
        color: #71869b;
        font-size: 13px;
    }

    .support-logout {
        margin-top: 14px;
    }

    @media (max-width: 576px) {
        .support-box {
            padding: 16px;
        }

        .support-contact a {
            align-items: flex-start;
            font-size: 14px;
        }
    }

    /* =========================================================
       DASHBOARD ADMIN & SUPERADMIN
       ========================================================= */
    .management-stat-row {
        margin-left: -8px;
        margin-right: -8px;
    }

    .management-stat-row > [class*="col-"] {
        padding-left: 8px;
        padding-right: 8px;
    }

    .management-stat-card {
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(190, 224, 244, 0.9);
        border-radius: 20px;
        box-shadow: 0 12px 28px rgba(36, 132, 181, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .management-stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--stat-accent, #36b9ec);
    }

    .management-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 34px rgba(36, 132, 181, 0.13);
    }

    .management-stat-card .card-body {
        padding: 20px 21px;
    }

    .management-stat-card.stat-primary {
        --stat-accent: #36a9e8;
    }

    .management-stat-card.stat-success {
        --stat-accent: #35bea8;
    }

    .management-stat-card.stat-info {
        --stat-accent: #61bff1;
    }

    .management-stat-card.stat-warning {
        --stat-accent: #ffbd52;
    }

    .management-stat-label {
        margin-bottom: 7px;
        color: #72899e;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.55px;
        text-transform: uppercase;
    }

    .management-stat-number {
        color: #1b354f;
        font-size: 27px;
        font-weight: 800;
        line-height: 1;
    }

    .management-stat-caption {
        margin-top: 7px;
        color: #8aa0b2;
        font-size: 11px;
    }

    .management-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 15px;
        font-size: 18px;
        box-shadow: 0 9px 18px rgba(33, 142, 196, 0.17);
    }

    .management-stat-icon.primary {
        background: linear-gradient(145deg, #46b9ef, #2798d5);
        color: #fff;
    }

    .management-stat-icon.success {
        background: linear-gradient(145deg, #48ccb8, #25aa94);
        color: #fff;
    }

    .management-stat-icon.info {
        background: linear-gradient(145deg, #75cdf3, #43aee2);
        color: #fff;
    }

    .management-stat-icon.warning {
        background: linear-gradient(145deg, #ffd06d, #f4ad37);
        color: #fff;
    }

    .management-dashboard {
        margin-top: 2px;
    }

    .management-main-card,
    .management-profile-card,
    .management-help-card {
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(184, 220, 241, 0.85);
        border-radius: 24px;
        box-shadow: 0 15px 36px rgba(33, 129, 178, 0.09);
    }

    .management-main-card,
    .management-side-stack {
        height: 100%;
    }

    .management-main-card::before,
    .management-profile-card::before,
    .management-help-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #2aaae6, #6bd1f2);
    }

    .management-main-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        padding: 25px 27px 20px;
        border-bottom: 1px solid #e7f1f7;
    }

    .management-eyebrow {
        display: block;
        margin-bottom: 5px;
        color: #2aa7df;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.7px;
        text-transform: uppercase;
    }

    .management-main-header h2 {
        margin: 0;
        color: #19344e;
        font-size: 21px;
        font-weight: 800;
    }

    .management-access-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex-shrink: 0;
        padding: 8px 12px;
        background: #eaf8ff;
        border: 1px solid #cceafa;
        border-radius: 999px;
        color: #168fc9;
        font-size: 12px;
        font-weight: 800;
    }

    .management-main-body {
        padding: 24px 27px 27px;
    }

    .management-intro {
        display: flex;
        align-items: center;
        gap: 17px;
        margin-bottom: 21px;
        padding: 17px;
        background: linear-gradient(135deg, #f6fcff, #eef9ff);
        border: 1px solid #dceff8;
        border-radius: 18px;
    }

    .management-intro-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        background: linear-gradient(145deg, #3db9ed, #1e98d5);
        border-radius: 16px;
        color: #fff;
        font-size: 20px;
        box-shadow: 0 10px 22px rgba(29, 151, 210, 0.22);
    }

    .management-intro h3 {
        margin: 0 0 5px;
        color: #223e57;
        font-size: 16px;
        font-weight: 800;
    }

    .management-intro p {
        margin: 0;
        color: #698298;
        font-size: 13px;
        line-height: 1.6;
    }

    .management-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 12px;
    }

    .management-section-title h4 {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0;
        color: #243f58;
        font-size: 14px;
        font-weight: 800;
    }

    .management-section-title h4 i {
        color: #2ca8df;
    }

    .management-area-count {
        color: #8096aa;
        font-size: 12px;
        font-weight: 700;
    }

    .management-area-box {
        margin-bottom: 21px;
        padding: 16px;
        background: #f8fcff;
        border: 1px solid #e0eff7;
        border-radius: 17px;
    }

    .management-area-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .management-area-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        background: #fff;
        border: 1px solid #d6eaf5;
        border-radius: 11px;
        color: #38556e;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.35;
    }

    .management-area-pill i {
        color: #2aa9e1;
    }

    .management-all-access {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 13px;
        background: #edf9ff;
        border: 1px solid #d3edf9;
        border-radius: 14px;
        color: #36546c;
        font-size: 13px;
        line-height: 1.55;
    }

    .management-all-access i {
        color: #269fd8;
        font-size: 22px;
    }

    .management-work-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 11px;
    }

    .management-work-item {
        padding: 15px;
        background: #f9fcfe;
        border: 1px solid #e1edf5;
        border-radius: 16px;
    }

    .management-work-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        margin-bottom: 11px;
        background: #e1f5ff;
        border-radius: 12px;
        color: #209fd9;
        font-size: 15px;
    }

    .management-work-item strong {
        display: block;
        margin-bottom: 4px;
        color: #2a465e;
        font-size: 13px;
    }

    .management-work-item p {
        margin: 0;
        color: #7b91a4;
        font-size: 11px;
        line-height: 1.5;
    }

    .management-side-stack {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .management-profile-card {
        flex: 1 1 auto;
        padding: 24px;
        text-align: center;
    }

    .management-profile-heading,
    .management-help-heading {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 19px;
        color: #1f3b55;
        font-size: 15px;
        font-weight: 800;
        text-align: left;
    }

    .management-profile-heading i,
    .management-help-heading i {
        color: #2aa9e1;
        font-size: 18px;
    }

    .management-profile-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 76px;
        height: 76px;
        margin: 2px auto 13px;
        background: linear-gradient(145deg, #37b8ee, #168fd0);
        border: 6px solid #eaf8ff;
        border-radius: 23px;
        color: #fff;
        font-size: 28px;
        box-shadow: 0 12px 25px rgba(28, 148, 207, 0.22);
    }

    .management-profile-name {
        margin-bottom: 7px;
        color: #1b354e;
        font-size: 17px;
        font-weight: 800;
    }

    .management-profile-role {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 17px;
        padding: 7px 11px;
        background: #e7f6ff;
        border-radius: 999px;
        color: #178fca;
        font-size: 11px;
        font-weight: 800;
    }

    .management-profile-info {
        display: grid;
        gap: 9px;
        margin-bottom: 16px;
        text-align: left;
    }

    .management-profile-info-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 10px 12px;
        background: #f7fbfe;
        border: 1px solid #e3eef5;
        border-radius: 12px;
        color: #71889c;
        font-size: 11px;
    }

    .management-profile-info-item strong {
        max-width: 62%;
        overflow: hidden;
        color: #2c485f;
        font-size: 11px;
        text-align: right;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .management-logout-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 14px;
        background: #f3f8fb;
        border: 1px solid #dce9f1;
        border-radius: 13px;
        color: #3e5a71;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .management-logout-btn:hover {
        background: #fff0f0;
        border-color: #ffd3d3;
        color: #d34a4a;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .management-help-card {
        flex: 0 0 auto;
        padding: 22px;
        background: linear-gradient(145deg, #eff9ff, #ffffff);
    }

    .management-help-card p {
        margin: 0 0 13px;
        color: #688096;
        font-size: 12px;
        line-height: 1.55;
    }

    .management-help-links {
        display: grid;
        gap: 8px;
    }

    .management-help-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 11px;
        background: #fff;
        border: 1px solid #dcebf4;
        border-radius: 12px;
        color: #29465e;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .management-help-link i {
        color: #24a767;
        font-size: 16px;
    }

    .management-help-link:hover {
        border-color: #b8deef;
        color: #178fc8;
        text-decoration: none;
        transform: translateX(2px);
    }

    @media (max-width: 1199.98px) {
        .management-work-grid {
            grid-template-columns: 1fr;
        }

        .management-work-item {
            display: grid;
            grid-template-columns: 38px 1fr;
            column-gap: 11px;
            align-items: center;
        }

        .management-work-icon {
            grid-row: 1 / span 2;
            margin-bottom: 0;
        }
    }

    @media (max-width: 991.98px) {
        .management-main-card,
        .management-side-stack {
            height: auto;
        }
    }

    @media (max-width: 575.98px) {
        .management-main-header {
            align-items: center;
            padding: 21px 19px 17px;
        }

        .management-main-header h2 {
            font-size: 18px;
        }

        .management-main-body {
            padding: 20px 19px 21px;
        }

        .management-intro {
            align-items: flex-start;
        }

        .management-area-list {
            display: grid;
        }

        .management-area-pill {
            width: 100%;
        }
    }

    /* =========================================================
       DASHBOARD KHUSUS ROLE RELAWAN
       ========================================================= */
    .volunteer-dashboard {
        margin-top: 4px;
    }

    .volunteer-dashboard .row {
        align-items: stretch;
    }

    .volunteer-main-card,
    .volunteer-side-card {
        position: relative;
        overflow: hidden;
        height: 100%;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(181, 220, 244, 0.75);
        border-radius: 24px;
        box-shadow: 0 16px 38px rgba(39, 139, 190, 0.10);
    }

    .volunteer-main-card::before,
    .volunteer-side-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #24a9e8, #72d4f4);
    }

    .volunteer-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 26px 28px 18px;
        border-bottom: 1px solid #e6f1f8;
    }

    .volunteer-eyebrow {
        display: block;
        margin-bottom: 5px;
        color: #2aa7df;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.7px;
        text-transform: uppercase;
    }

    .volunteer-card-header h2 {
        margin: 0;
        color: #18324d;
        font-size: 21px;
        font-weight: 800;
    }

    .volunteer-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex-shrink: 0;
        padding: 8px 12px;
        background: #ebfbf3;
        border: 1px solid #bcebd3;
        border-radius: 999px;
        color: #168b56;
        font-size: 12px;
        font-weight: 800;
    }

    .volunteer-status i {
        font-size: 8px;
    }

    .volunteer-profile-content {
        display: flex;
        align-items: center;
        gap: 22px;
        padding: 26px 28px 20px;
    }

    .volunteer-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 92px;
        height: 92px;
        flex: 0 0 92px;
        background: linear-gradient(145deg, #36b8ef, #148fd3);
        border: 7px solid #eaf8ff;
        border-radius: 26px;
        color: #fff;
        box-shadow: 0 12px 25px rgba(31, 157, 216, 0.25);
        font-size: 36px;
    }

    .volunteer-identity {
        min-width: 0;
    }

    .volunteer-identity h3 {
        margin: 0 0 8px;
        color: #172e47;
        font-size: 25px;
        font-weight: 800;
        word-break: break-word;
    }

    .volunteer-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
        padding: 7px 12px;
        background: #e7f6ff;
        border-radius: 999px;
        color: #178fca;
        font-size: 12px;
        font-weight: 800;
    }

    .volunteer-identity p {
        margin: 0;
        color: #6e879d;
        font-size: 14px;
        line-height: 1.65;
    }

    .volunteer-detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        padding: 0 28px 22px;
    }

    .volunteer-detail-item {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        padding: 14px;
        background: #f5fbff;
        border: 1px solid #dceef8;
        border-radius: 16px;
    }

    .volunteer-detail-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        background: #dff4ff;
        border-radius: 12px;
        color: #209fd8;
        font-size: 15px;
    }

    .volunteer-detail-text {
        min-width: 0;
    }

    .volunteer-detail-text span {
        display: block;
        margin-bottom: 2px;
        color: #7b91a5;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .volunteer-detail-text strong {
        display: block;
        overflow: hidden;
        color: #243d55;
        font-size: 13px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .volunteer-permission-box {
        margin: 0 28px 24px;
        padding: 18px;
        background: linear-gradient(135deg, #f7fcff, #edf8ff);
        border: 1px solid #d5eaf7;
        border-radius: 18px;
    }

    .volunteer-permission-box h4 {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0 0 13px;
        color: #1e3952;
        font-size: 15px;
        font-weight: 800;
    }

    .volunteer-permission-box h4 i {
        color: #2ba8df;
    }

    .volunteer-permission-list {
        display: grid;
        gap: 10px;
    }

    .volunteer-permission-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #536f86;
        font-size: 13px;
        line-height: 1.55;
    }

    .volunteer-permission-item i {
        margin-top: 3px;
        color: #22a36a;
    }

    .volunteer-card-footer {
        padding: 0 28px 28px;
    }

    .volunteer-logout-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        width: 100%;
        padding: 13px 16px;
        background: #f4f9fc;
        border: 1px solid #dceaf3;
        border-radius: 14px;
        color: #35536b;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .volunteer-logout-btn:hover {
        background: #fff0f0;
        border-color: #ffd2d2;
        color: #d34a4a;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .volunteer-side-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
        height: 100%;
    }

    .volunteer-side-card {
        height: auto;
        padding: 25px;
    }

    .volunteer-side-card.guide-card {
        flex: 1 1 auto;
    }

    .volunteer-side-card.help-card {
        flex: 0 0 auto;
        background: linear-gradient(145deg, #f0f9ff, #ffffff);
    }

    .volunteer-side-title {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-bottom: 16px;
        color: #1c344e;
        font-size: 18px;
        font-weight: 800;
    }

    .volunteer-side-title .title-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        background: #e2f5ff;
        border-radius: 13px;
        color: #239fd9;
        font-size: 18px;
    }

    .volunteer-guide-list {
        display: grid;
        gap: 12px;
    }

    .volunteer-guide-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        background: #f8fcff;
        border: 1px solid #e2eff7;
        border-radius: 14px;
    }

    .volunteer-guide-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 29px;
        height: 29px;
        flex: 0 0 29px;
        background: linear-gradient(145deg, #39b9ed, #179bd8);
        border-radius: 10px;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
    }

    .volunteer-guide-item strong {
        display: block;
        margin-bottom: 3px;
        color: #29455d;
        font-size: 13px;
    }

    .volunteer-guide-item p {
        margin: 0;
        color: #71899e;
        font-size: 12px;
        line-height: 1.5;
    }

    .volunteer-help-description {
        margin: 0 0 15px;
        color: #637d93;
        font-size: 13px;
        line-height: 1.65;
    }

    .volunteer-help-contacts {
        display: grid;
        gap: 10px;
    }

    .volunteer-help-link {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 12px 13px;
        background: #ffffff;
        border: 1px solid #dcecf5;
        border-radius: 13px;
        color: #233e56;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .volunteer-help-link i {
        color: #24a866;
        font-size: 18px;
    }

    .volunteer-help-link:hover {
        border-color: #b9dfef;
        color: #168fc9;
        text-decoration: none;
        transform: translateX(2px);
    }

    .volunteer-help-note {
        margin: 13px 0 0;
        color: #7d92a5;
        font-size: 12px;
    }

    @media (max-width: 1199.98px) {
        .volunteer-detail-grid {
            grid-template-columns: 1fr 1fr;
        }

        .volunteer-detail-item:last-child {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 767.98px) {
        .volunteer-card-header,
        .volunteer-profile-content {
            padding-left: 20px;
            padding-right: 20px;
        }

        .volunteer-profile-content {
            align-items: flex-start;
        }

        .volunteer-detail-grid {
            grid-template-columns: 1fr;
            padding-left: 20px;
            padding-right: 20px;
        }

        .volunteer-detail-item:last-child {
            grid-column: auto;
        }

        .volunteer-permission-box {
            margin-left: 20px;
            margin-right: 20px;
        }

        .volunteer-card-footer {
            padding-left: 20px;
            padding-right: 20px;
        }
    }

    @media (max-width: 575.98px) {
        .volunteer-card-header {
            align-items: center;
        }

        .volunteer-status {
            padding: 7px 10px;
        }

        .volunteer-profile-content {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .volunteer-identity h3 {
            font-size: 22px;
        }

        .volunteer-side-card {
            padding: 20px;
        }
    }

</style>

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

<?php if ($role === 'admin' || $role === 'superadmin'): ?>
    <!-- KARTU STATISTIK -->
    <div class="row management-stat-row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card management-stat-card stat-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="management-stat-label">
                                <?= $role === 'admin' ? 'Admin di Dapil' : 'Admin' ?>
                            </div>
                            <div class="management-stat-number"><?= e($totalAdmin) ?></div>
                            <div class="management-stat-caption">Akun admin terdata</div>
                        </div>
                        <div class="management-stat-icon primary">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card management-stat-card stat-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="management-stat-label">Relawan</div>
                            <div class="management-stat-number"><?= e($totalRelawan) ?></div>
                            <div class="management-stat-caption">Relawan dalam cakupan</div>
                        </div>
                        <div class="management-stat-icon success">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card management-stat-card stat-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="management-stat-label">Dukungan</div>
                            <div class="management-stat-number"><?= e($totalDukungan) ?></div>
                            <div class="management-stat-caption">Data dukungan tercatat</div>
                        </div>
                        <div class="management-stat-icon info">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card management-stat-card stat-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="management-stat-label">Akun Aktif</div>
                            <div class="management-stat-number"><?= e($totalUser) ?></div>
                            <div class="management-stat-caption">Pengguna aktif saat ini</div>
                        </div>
                        <div class="management-stat-icon warning">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RINGKASAN PENGELOLAAN ADMIN -->
    <section class="management-dashboard">
        <div class="row">
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="management-main-card">
                    <div class="management-main-header">
                        <div>
                            <span class="management-eyebrow">
                                <?= $role === 'admin' ? 'Dashboard Admin Dapil' : 'Dashboard Superadmin' ?>
                            </span>
                            <h2>Ringkasan Pengelolaan Wilayah</h2>
                        </div>

                        <span class="management-access-badge">
                            <i class="fas fa-shield-alt"></i>
                            <?= $role === 'admin' ? 'Akses Dapil' : 'Akses Penuh' ?>
                        </span>
                    </div>

                    <div class="management-main-body">
                        <div class="management-intro">
                            <div class="management-intro-icon">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div>
                                <h3>
                                    <?= $role === 'admin'
                                        ? 'Wilayah yang menjadi tanggung jawab Anda'
                                        : 'Pengelolaan seluruh wilayah sistem' ?>
                                </h3>
                                <p>
                                    <?= $role === 'admin'
                                        ? 'Data relawan, dukungan, dan statistik yang tampil telah dibatasi sesuai daerah pemilihan yang terhubung dengan akun Anda.'
                                        : 'Anda dapat memantau dan mengelola seluruh admin, relawan, dukungan, serta statistik wilayah di dalam sistem.' ?>
                                </p>
                            </div>
                        </div>

                        <div class="management-section-title">
                            <h4>
                                <i class="fas fa-map-marker-alt"></i>
                                <?= $role === 'admin' ? 'Wilayah Dapil' : 'Cakupan Akses' ?>
                            </h4>

                            <?php if ($role === 'admin'): ?>
                                <span class="management-area-count">
                                    <?= count($allowedKabKota) ?> wilayah
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="management-area-box">
                            <?php if ($role === 'superadmin'): ?>
                                <div class="management-all-access">
                                    <i class="fas fa-globe-asia"></i>
                                    <span>
                                        Akun ini mempunyai akses ke seluruh kabupaten/kota,
                                        data pengguna, dan statistik yang tersedia pada sistem.
                                    </span>
                                </div>
                            <?php elseif (!empty($allowedKabKota)): ?>
                                <div class="management-area-list">
                                    <?php foreach ($allowedKabKota as $kabKota): ?>
                                        <span class="management-area-pill">
                                            <i class="fas fa-map-pin"></i>
                                            <?= e($kabKota) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Belum ada wilayah dapil yang dihubungkan dengan akun admin ini.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="management-section-title">
                            <h4>
                                <i class="fas fa-th-large"></i>
                                Ruang Kerja Pengelolaan
                            </h4>
                        </div>

                        <div class="management-work-grid">
                            <div class="management-work-item">
                                <div class="management-work-icon">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <strong>Data Relawan</strong>
                                <p>Kelola relawan yang berada dalam cakupan wilayah akun Anda.</p>
                            </div>

                            <div class="management-work-item">
                                <div class="management-work-icon">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <strong>Data Dukungan</strong>
                                <p>Catat dan periksa data dukungan yang telah dihimpun.</p>
                            </div>

                            <div class="management-work-item">
                                <div class="management-work-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <strong>Statistik Wilayah</strong>
                                <p>Pantau perkembangan data berdasarkan wilayah dan TPS.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="management-side-stack">
                    <div class="management-profile-card">
                        <div class="management-profile-heading">
                            <i class="fas fa-user-circle"></i>
                            <span>Profil Pengguna</span>
                        </div>

                        <div class="management-profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>

                        <div class="management-profile-name">
                            <?= e($currentUser['name'] ?? '-') ?>
                        </div>

                        <span class="management-profile-role">
                            <i class="fas fa-user-shield"></i>
                            <?= e($welcomeRole) ?>
                        </span>

                        <div class="management-profile-info">
                            <div class="management-profile-info-item">
                                <span>Kecamatan</span>
                                <strong title="<?= e($adminProfile['kecamatan'] ?? ($currentUser['kecamatan'] ?? '-')) ?>">
                                    <?= e($adminProfile['kecamatan'] ?? ($currentUser['kecamatan'] ?? '-')) ?>
                                </strong>
                            </div>

                            <div class="management-profile-info-item">
                                <span>Cakupan Wilayah</span>
                                <strong>
                                    <?= $role === 'admin'
                                        ? count($allowedKabKota) . ' wilayah'
                                        : 'Seluruh wilayah' ?>
                                </strong>
                            </div>

                            <div class="management-profile-info-item">
                                <span>Status Akun</span>
                                <strong>Aktif</strong>
                            </div>
                        </div>

                        <a href="<?= url('logout.php') ?>" class="management-logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            Keluar dari Akun
                        </a>
                    </div>

                    <div class="management-help-card">
                        <div class="management-help-heading">
                            <i class="fas fa-headset"></i>
                            <span>Pusat Bantuan</span>
                        </div>

                        <p>
                            Mengalami kendala teknis atau membutuhkan panduan?
                            Hubungi tim support melalui WhatsApp atau telepon.
                        </p>

                        <div class="management-help-links">
                            <a
                                href="https://wa.me/628871278297"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="management-help-link">
                                <i class="fab fa-whatsapp"></i>
                                <span>+62 887-1278-297 (Faza)</span>
                            </a>

                            <a
                                href="https://wa.me/628871278298"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="management-help-link">
                                <i class="fab fa-whatsapp"></i>
                                <span>+62 887-1278-298 (Besta)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

    <?php if ($role === 'relawan'): ?>
        <section class="volunteer-dashboard">
            <div class="row">
                <!-- PROFIL UTAMA RELAWAN -->
                <div class="col-xl-7 col-lg-7 mb-4">
                    <div class="volunteer-main-card">
                        <div class="volunteer-card-header">
                            <div>
                                <span class="volunteer-eyebrow">Akun Relawan</span>
                                <h2>Profil Pengguna</h2>
                            </div>

                            <span class="volunteer-status">
                                <i class="fas fa-circle"></i>
                                Aktif
                            </span>
                        </div>

                        <div class="volunteer-profile-content">
                            <div class="volunteer-avatar">
                                <i class="fas fa-user"></i>
                            </div>

                            <div class="volunteer-identity">
                                <h3><?= e($currentUser['name'] ?? '-') ?></h3>

                                <span class="volunteer-role-badge">
                                    <i class="fas fa-hands-helping"></i>
                                    Relawan
                                </span>

                                <p>
                                    Terima kasih telah menjadi bagian dari tim relawan.
                                    Gunakan menu di sebelah kiri untuk mengelola profil dan mencatat data dukungan.
                                </p>
                            </div>
                        </div>

                        <div class="volunteer-detail-grid">
                            <div class="volunteer-detail-item">
                                <div class="volunteer-detail-icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <div class="volunteer-detail-text">
                                    <span>Role</span>
                                    <strong>Relawan</strong>
                                </div>
                            </div>

                            <div class="volunteer-detail-item">
                                <div class="volunteer-detail-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="volunteer-detail-text">
                                    <span>Kecamatan</span>
                                    <strong title="<?= e($currentUser['kecamatan'] ?? '-') ?>">
                                        <?= e($currentUser['kecamatan'] ?? '-') ?>
                                    </strong>
                                </div>
                            </div>

                            <div class="volunteer-detail-item">
                                <div class="volunteer-detail-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="volunteer-detail-text">
                                    <span>Status Akun</span>
                                    <strong>Aktif</strong>
                                </div>
                            </div>
                        </div>

                        <div class="volunteer-permission-box">
                            <h4>
                                <i class="fas fa-shield-alt"></i>
                                Hak Akses Anda
                            </h4>

                            <div class="volunteer-permission-list">
                                <div class="volunteer-permission-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Melihat dan memperbarui informasi profil pribadi.</span>
                                </div>

                                <div class="volunteer-permission-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Menambahkan data dukungan yang berhasil dikumpulkan.</span>
                                </div>

                                <div class="volunteer-permission-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Melihat kembali data dukungan yang telah dimasukkan.</span>
                                </div>
                            </div>
                        </div>

                        <div class="volunteer-card-footer">
                            <a href="<?= url('logout.php') ?>" class="volunteer-logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                Keluar dari Akun
                            </a>
                        </div>
                    </div>
                </div>

                <!-- INFORMASI DAN BANTUAN -->
                <div class="col-xl-5 col-lg-5 mb-4">
                    <div class="volunteer-side-stack">
                        <div class="volunteer-side-card guide-card">
                            <div class="volunteer-side-title">
                                <span class="title-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </span>
                                <span>Panduan Singkat</span>
                            </div>

                            <div class="volunteer-guide-list">
                                <div class="volunteer-guide-item">
                                    <span class="volunteer-guide-number">1</span>
                                    <div>
                                        <strong>Lengkapi profil</strong>
                                        <p>Pastikan identitas dan wilayah kecamatan sudah sesuai.</p>
                                    </div>
                                </div>

                                <div class="volunteer-guide-item">
                                    <span class="volunteer-guide-number">2</span>
                                    <div>
                                        <strong>Tambahkan dukungan</strong>
                                        <p>Masukkan data dukungan melalui menu Tambah Dukungan.</p>
                                    </div>
                                </div>

                                <div class="volunteer-guide-item">
                                    <span class="volunteer-guide-number">3</span>
                                    <div>
                                        <strong>Periksa kembali data</strong>
                                        <p>Buka menu Data Dukungan untuk memastikan data sudah tercatat.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="volunteer-side-card help-card">
                            <div class="volunteer-side-title">
                                <span class="title-icon">
                                    <i class="fas fa-headset"></i>
                                </span>
                                <span>Pusat Bantuan</span>
                            </div>

                            <p class="volunteer-help-description">
                                Mengalami kendala teknis atau membutuhkan panduan?
                                Hubungi tim support melalui WhatsApp atau telepon.
                            </p>

                            <div class="volunteer-help-contacts">
                                <a
                                    class="volunteer-help-link"
                                    href="https://wa.me/628871278297"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Hubungi Faza melalui WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                    <span>+62 887-1278-297 (Faza)</span>
                                </a>

                                <a
                                    class="volunteer-help-link"
                                    href="https://wa.me/628871278298"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Hubungi Besta melalui WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                    <span>+62 887-1278-298 (Besta)</span>
                                </a>
                            </div>

                            <p class="volunteer-help-note">
                                <i class="fas fa-clock mr-1"></i>
                                Hubungi support ketika mengalami kendala penggunaan sistem.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($role === 'admin' || $role === 'superadmin'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('kabupatenChart');

        if (ctx) {
            new Chart(ctx, {
            type: 'bar',

            data: {
                labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,

                datasets: [{
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
        }

        const pieCtx = document.getElementById('pieChart');

        if (pieCtx) {
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
        }
    </script>
    <?php endif; ?>

    <?php require_once __DIR__ . '/../partials/footer.php'; ?>