<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);
require_profile_complete($pdo);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

/*
|--------------------------------------------------------------------------
| Filter & Search
|--------------------------------------------------------------------------
*/

$search    = $_GET['search'] ?? '';
$kecamatan = $_GET['kecamatan'] ?? '';
$desa      = $_GET['desa'] ?? '';

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$sortBy = $_GET['sort_by'] ?? 'created_at';
$order  = $_GET['order'] ?? 'DESC';

/*
|--------------------------------------------------------------------------
| Validasi Sorting
|--------------------------------------------------------------------------
*/

$where = "WHERE p.type = 'dukungan'";
$params = [];

$allowedKabKota = [];

/*
|--------------------------------------------------------------------------
| Ambil wilayah yang boleh dilihat
|--------------------------------------------------------------------------
*/

if (current_user()['role'] == 'admin') {

    // profile admin
    $stmt = $pdo->prepare("
        SELECT id
        FROM profiles
        WHERE user_id = ?
        AND type='admin'
        LIMIT 1
    ");
    $stmt->execute([current_user()['id']]);

    $adminProfileId = $stmt->fetchColumn();
} elseif (current_user()['role'] == 'relawan') {

    // cari admin yang menaungi relawan
    $stmt = $pdo->prepare("
        SELECT pa.admin_profile_id
        FROM profile_admin pa
        JOIN profiles p
            ON p.id = pa.profile_id
        WHERE p.user_id = ?
        LIMIT 1
    ");

    $stmt->execute([current_user()['id']]);

    $adminProfileId = $stmt->fetchColumn();
}

if (current_user()['role'] != 'superadmin') {

    $stmt = $pdo->prepare("
        SELECT d.kab_kota
        FROM profile_dapil pd
        JOIN dapil d
            ON d.id = pd.dapil_id
        WHERE pd.profile_id = ?
    ");

    $stmt->execute([$adminProfileId]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $kab = json_decode($row['kab_kota'], true);

        if (is_array($kab)) {
            foreach ($kab as $k) {
                $allowedKabKota[] = trim($k);
            }
        }
    }

    $allowedKabKota = array_unique($allowedKabKota);

    if (!empty($allowedKabKota)) {

        $placeholders = implode(',', array_fill(0, count($allowedKabKota), '?'));

        $where .= " AND p.kab_kota IN ($placeholders)";

        $params = array_merge($params, $allowedKabKota);
    } else {

        // jika tidak punya dapil
        $where .= " AND 1=0";
    }
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if (!empty($search)) {

    $where .= "
        AND (
            p.nama_lengkap LIKE ?
            OR p.nik LIKE ?
        )
    ";

    $params[] = "%$search%";
    $params[] = "%$search%";
}

/*
|--------------------------------------------------------------------------
| Filter Kecamatan
|--------------------------------------------------------------------------
*/

if (!empty($kecamatan)) {

    $where .= " AND p.kecamatan = ? ";
    $params[] = $kecamatan;
}

/*
|--------------------------------------------------------------------------
| Filter Desa
|--------------------------------------------------------------------------
*/

if (!empty($desa)) {

    $where .= " AND p.desa_kelurahan = ? ";
    $params[] = $desa;
}

/*
|--------------------------------------------------------------------------
| Query Final
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.*,
        creator.name AS input_by_name,
        creator.username AS input_by_username,
        creator.role AS input_by_role
    FROM profiles p
    LEFT JOIN users creator
        ON creator.id = p.created_by
    $where
    ORDER BY $sortBy $order
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Ambil Kecamatan
|--------------------------------------------------------------------------
*/

$sql = "
SELECT DISTINCT kecamatan
FROM profiles
WHERE type='dukungan'
";

$params = [];

if (!empty($allowedKabKota)) {

    $placeholders = implode(',', array_fill(0, count($allowedKabKota), '?'));

    $sql .= " AND kab_kota IN ($placeholders)";

    $params = $allowedKabKota;
}

$sql .= " ORDER BY kecamatan";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$kecamatanList = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Ambil Desa
|--------------------------------------------------------------------------
*/

$sql = "
SELECT DISTINCT desa_kelurahan
FROM profiles
WHERE type='dukungan'
";

$params = [];

if (!empty($allowedKabKota)) {

    $placeholders = implode(',', array_fill(0, count($allowedKabKota), '?'));

    $sql .= " AND kab_kota IN ($placeholders)";

    $params = $allowedKabKota;
}

$sql .= " ORDER BY desa_kelurahan";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$desaList = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Function Sort Link
|--------------------------------------------------------------------------
*/

function sortLink($column, $label)
{
    $currentSortBy = $_GET['sort_by'] ?? 'created_at';
    $currentOrder  = $_GET['order'] ?? 'DESC';

    $newOrder = 'ASC';

    if ($currentSortBy == $column && $currentOrder == 'ASC') {
        $newOrder = 'DESC';
    }

    $query = $_GET;

    $query['sort_by'] = $column;
    $query['order']   = $newOrder;

    $url = '?' . http_build_query($query);

    // Default icon
    $icon = '<i class="fas fa-sort text-muted ml-1"></i>';

    // Active icon
    if ($currentSortBy == $column) {

        if ($currentOrder == 'ASC') {
            $icon = '<i class="fas fa-sort-up ml-1"></i>';
        } else {
            $icon = '<i class="fas fa-sort-down ml-1"></i>';
        }
    }

    return '
        <a href="' . $url . '" class="text-dark text-decoration-none">
            ' . $label . ' ' . $icon . '
        </a>
    ';
}

function inputByDisplay(array $row): array
{
    $role = strtolower(trim((string)($row['input_by_role'] ?? '')));
    $name = trim((string)($row['input_by_name'] ?? ''));

    if ($name === '') {
        $name = trim((string)($row['input_by_username'] ?? ''));
    }

    switch ($role) {
        case 'superadmin':
            return [
                'label' => 'Superadmin',
                'class' => 'primary',
                'icon'  => 'fa-user-shield',
                'name'  => $name !== '' ? $name : 'Superadmin'
            ];

        case 'admin':
            return [
                'label' => 'Admin',
                'class' => 'info',
                'icon'  => 'fa-user-cog',
                'name'  => $name !== '' ? $name : 'Admin'
            ];

        case 'relawan':
            return [
                'label' => 'Relawan',
                'class' => 'success',
                'icon'  => 'fa-hands-helping',
                'name'  => $name !== '' ? $name : 'Relawan'
            ];

        default:
            return [
                'label' => 'Tidak tercatat',
                'class' => 'secondary',
                'icon'  => 'fa-question-circle',
                'name'  => '-'
            ];
    }
}
?>

<style>
    .relawan-filter-box {
        padding: 18px;
        margin-bottom: 24px;
        border: 1px solid #dfeef7;
        border-radius: 16px;
        background: #f8fcff;
    }

    .relawan-filter-box .form-control,
    .relawan-filter-box .btn {
        min-height: 42px;
        border-radius: 9px;
    }

    .input-by-cell {
        min-width: 175px;
    }

    .input-by-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .input-by-wrap .badge {
        padding: 6px 10px;
        font-size: 12px;
        border-radius: 20px;
    }

    .input-by-name {
        color: #526b82;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
        text-align: center;
        overflow-wrap: anywhere;
    }

    .relawan-table th,
    .relawan-table td {
        vertical-align: middle !important;
    }

    @media (max-width: 767.98px) {
        .relawan-filter-box {
            padding: 14px;
        }
    }
</style>

<h1 class="h3 mb-4 text-gray-800">Data Dukungan</h1>

<div class="card content-card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-hand-holding-heart mr-2" style="color:#3db7ee;"></i>
            Daftar Dukungan
        </h6>

        <a href="<?= url('dukungan/create.php') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Tambah Dukungan
        </a>
    </div>

    <div class="card-body">

        <!-- FILTER -->
        <form method="GET" class="relawan-filter-box">

            <div class="row align-items-end">

                <!-- Search -->
                <div class="col-xl-3 col-lg-6 mb-2">
                    <label class="small font-weight-bold text-muted">Pencarian</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nama atau NIK..."
                        value="<?= e($search) ?>">
                </div>

                <!-- Kecamatan -->
                <div class="col-xl-3 col-lg-6 mb-2">
                    <label class="small font-weight-bold text-muted">Kecamatan</label>
                    <select name="kecamatan" class="form-control">

                        <option value="">
                            -- Semua Kecamatan --
                        </option>

                        <?php foreach ($kecamatanList as $k): ?>

                            <option
                                value="<?= e($k['kecamatan']) ?>"
                                <?= $kecamatan == $k['kecamatan'] ? 'selected' : '' ?>>

                                <?= e($k['kecamatan']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Desa -->
                <div class="col-xl-3 col-lg-6 mb-2">
                    <label class="small font-weight-bold text-muted">Desa</label>
                    <select name="desa" class="form-control">

                        <option value="">
                            -- Semua Desa --
                        </option>

                        <?php foreach ($desaList as $d): ?>

                            <option
                                value="<?= e($d['desa_kelurahan']) ?>"
                                <?= $desa == $d['desa_kelurahan'] ? 'selected' : '' ?>>

                                <?= e($d['desa_kelurahan']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Button -->
                <div class="col-xl-2 col-lg-4 mb-2">
                    <label class="small font-weight-bold text-muted d-block">Aksi</label>
                    <div class="d-flex">

                        <!-- Filter -->
                        <button type="submit"
                            class="btn btn-primary flex-fill mr-2">

                            <i class="fas fa-filter"></i> Filter

                        </button>

                        <!-- Reset -->
                        <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>"
                            class="btn btn-secondary flex-fill">

                            <i class="fas fa-sync-alt"></i> Reset

                        </a>

                    </div>
                </div>

            </div>

        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">

                <thead style="background:#f1faff;" class="text-center">
                    <tr>

                        <th style="width:55px;">No</th>

                        <th>
                            <?= sortLink('nama_lengkap', 'Nama') ?>
                        </th>

                        <th>
                            <?= sortLink('kecamatan', 'Kecamatan') ?>
                        </th>

                        <th>
                            <?= sortLink('desa_kelurahan', 'Desa') ?>
                        </th>

                        <th>
                            <?= sortLink('tps', 'TPS') ?>
                        </th>

                        <th style="min-width:185px;">Diinput Oleh</th>

                        <th style="min-width:125px;">Detail</th>

                    </tr>
                </thead>

                <tbody>

                    <?php if (count($rows) > 0): ?>

                        <?php foreach ($rows as $i => $r): ?>
                            <?php $inputBy = inputByDisplay($r); ?>

                            <tr>

                                <td class="text-center"><?= $i + 1 ?></td>

                                <td><?= e($r['nama_lengkap']) ?></td>

                                <td><?= e($r['kecamatan']) ?></td>

                                <td><?= e($r['desa_kelurahan']) ?></td>

                                <td class="text-center"><?= e($r['tps']) ?></td>

                                <td class="text-center input-by-cell">
                                    <div class="input-by-wrap">
                                        <span class="badge badge-<?= e($inputBy['class']) ?>">
                                            <i class="fas <?= e($inputBy['icon']) ?> mr-1"></i>
                                            <?= e($inputBy['label']) ?>
                                        </span>

                                        <span class="input-by-name">
                                            <?= e($inputBy['name']) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <a
                                        href="<?= url('dukungan/detail.php?id=' . $r['id']) ?>"
                                        class="btn btn-sm btn-info">

                                        <i class="fas fa-eye"></i> Lihat Data

                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                Data dukungan tidak ditemukan.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>