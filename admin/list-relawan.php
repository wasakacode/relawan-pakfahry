<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$search       = trim($_GET['search'] ?? '');
$kecamatan    = trim($_GET['kecamatan'] ?? '');
$status       = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$kelengkapan  = isset($_GET['kelengkapan']) ? trim((string)$_GET['kelengkapan']) : '';

$sortBy = $_GET['sort_by'] ?? 'created_at';
$order  = strtoupper($_GET['order'] ?? 'DESC');

$sortColumns = [
    'nik'                 => 'p.nik',
    'nama_lengkap'        => 'p.nama_lengkap',
    'status_verifikasi'   => 'p.status_verifikasi',
    'is_active'           => 'u.is_active',
    'profile_complete'    => 'p.profile_complete',
    'catatan_verifikasi'  => 'p.catatan_verifikasi',
    'created_at'          => 'p.created_at'
];

if (!array_key_exists($sortBy, $sortColumns)) {
    $sortBy = 'created_at';
}

$allowedOrder = ['ASC', 'DESC'];

if (!in_array($order, $allowedOrder, true)) {
    $order = 'DESC';
}

$orderByColumn = $sortColumns[$sortBy];

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? '';
$currentUserId = (int)($currentUser['id'] ?? 0);
$adminProfileId = 0;

$sql = "
    SELECT DISTINCT
        p.*,
        u.username,
        u.is_active,
        creator.name AS input_by_name,
        creator.username AS input_by_username,
        creator.role AS input_by_role
    FROM profiles p
    LEFT JOIN users u
        ON u.id = p.user_id
    LEFT JOIN users creator
        ON creator.id = p.created_by
";

$params = [];

if ($currentRole === 'admin') {

    $stmtAdmin = $pdo->prepare("
        SELECT id
        FROM profiles
        WHERE user_id = ?
          AND type = 'admin'
        LIMIT 1
    ");

    $stmtAdmin->execute([$currentUserId]);
    $adminProfileId = (int)$stmtAdmin->fetchColumn();

    $sql .= "
        INNER JOIN profile_admin pa
            ON pa.profile_id = p.id
        WHERE p.type = 'relawan'
          AND pa.admin_profile_id = :admin_profile_id
    ";

    $params['admin_profile_id'] = $adminProfileId;

} else {

    // Superadmin dapat melihat seluruh relawan
    $sql .= "
        WHERE p.type = 'relawan'
    ";
}

if ($search !== '') {
    $sql .= "
        AND (
            p.nama_lengkap LIKE :search
            OR u.username LIKE :search
            OR p.nik LIKE :search
        )
    ";

    $params['search'] = '%' . $search . '%';
}

if ($kecamatan !== '') {
    $sql .= " AND p.kecamatan = :kecamatan ";
    $params['kecamatan'] = $kecamatan;
}

if ($status !== '') {
    $sql .= " AND u.is_active = :status ";
    $params['status'] = (int)$status;
}


if ($kelengkapan !== '') {
    $sql .= " AND COALESCE(p.profile_complete, 0) = :kelengkapan ";
    $params['kelengkapan'] = (int)$kelengkapan;
}

$sql .= " ORDER BY {$orderByColumn} {$order} ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($currentRole === 'admin') {
    $kecamatanStmt = $pdo->prepare("
        SELECT DISTINCT p.kecamatan
        FROM profiles p
        INNER JOIN profile_admin pa
            ON pa.profile_id = p.id
        WHERE p.type = 'relawan'
          AND pa.admin_profile_id = ?
          AND p.kecamatan IS NOT NULL
          AND p.kecamatan <> ''
        ORDER BY p.kecamatan ASC
    ");

    $kecamatanStmt->execute([$adminProfileId]);
} else {
    $kecamatanStmt = $pdo->query("
        SELECT DISTINCT kecamatan
        FROM profiles
        WHERE type = 'relawan'
          AND kecamatan IS NOT NULL
          AND kecamatan <> ''
        ORDER BY kecamatan ASC
    ");
}

$kecamatanList = $kecamatanStmt->fetchAll(PDO::FETCH_ASSOC);

function sortLink($column, $label)
{
    $currentSortBy = $_GET['sort_by'] ?? 'created_at';
    $currentOrder  = strtoupper($_GET['order'] ?? 'DESC');

    $newOrder = 'ASC';

    if ($currentSortBy === $column && $currentOrder === 'ASC') {
        $newOrder = 'DESC';
    }

    $query = $_GET;
    $query['sort_by'] = $column;
    $query['order'] = $newOrder;

    $url = '?' . http_build_query($query);

    $icon = '<i class="fas fa-sort text-muted ml-1"></i>';

    if ($currentSortBy === $column) {
        if ($currentOrder === 'ASC') {
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

<h1 class="h3 mb-4 text-gray-800">Data Relawan</h1>

<div class="card content-card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
            Daftar Relawan
        </h6>

        <a href="<?= url('admin/create-relawan.php') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus"></i> Tambah Relawan
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
                        placeholder="Cari nama, username, atau NIK..."
                        value="<?= e($search) ?>">
                </div>

                <!-- Kecamatan -->
                <div class="col-xl-3 col-lg-6 mb-2">
                    <label class="small font-weight-bold text-muted">Kecamatan</label>
                    <select name="kecamatan" class="form-control">
                        <option value="">-- Semua Kecamatan --</option>

                        <?php foreach ($kecamatanList as $k): ?>
                            <option
                                value="<?= e($k['kecamatan']) ?>"
                                <?= $kecamatan === $k['kecamatan'] ? 'selected' : '' ?>>
                                <?= e($k['kecamatan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Aktif -->
                <div class="col-xl-2 col-lg-4 mb-2">
                    <label class="small font-weight-bold text-muted">Status Akun</label>
                    <select name="status" class="form-control">
                        <option value="">-- Semua Status --</option>
                        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <!-- Kelengkapan Data -->
                <div class="col-xl-2 col-lg-4 mb-2">
                    <label class="small font-weight-bold text-muted">Kelengkapan Data</label>
                    <select name="kelengkapan" class="form-control">
                        <option value="">-- Semua Data --</option>
                        <option value="1" <?= $kelengkapan === '1' ? 'selected' : '' ?>>Lengkap</option>
                        <option value="0" <?= $kelengkapan === '0' ? 'selected' : '' ?>>Belum Lengkap</option>
                    </select>
                </div>

                <!-- Button -->
                <div class="col-xl-2 col-lg-4 mb-2">
                    <label class="small font-weight-bold text-muted d-block">Aksi</label>
                    <div class="d-flex">
                        <button type="submit" class="btn btn-primary flex-fill mr-2">
                            <i class="fas fa-filter"></i>
                            <span class="d-none d-sm-inline">Filter</span>
                        </button>

                        <a
                            href="<?= e(strtok($_SERVER['REQUEST_URI'], '?')) ?>"
                            class="btn btn-secondary flex-fill">
                            <i class="fas fa-sync-alt"></i>
                            <span class="d-none d-sm-inline">Reset</span>
                        </a>
                    </div>
                </div>

            </div>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover relawan-table" width="100%">

                <thead style="background:#f1faff;">
                    <tr class="text-center">
                        <th style="width:55px;">No</th>

                        <th>
                            <?= sortLink('nama_lengkap', 'Nama') ?>
                        </th>

                        <th style="min-width:125px;">Detail</th>

                        <th style="min-width:125px;">
                            <?= sortLink('is_active', 'Status Aktif') ?>
                        </th>

                        <th style="min-width:165px;">
                            <?= sortLink('profile_complete', 'Kelengkapan Data') ?>
                        </th>

                        <th style="min-width:170px;">Status Verifikasi</th>

                        <th style="min-width:185px;">Diinput Oleh</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($rows) > 0): ?>

                        <?php foreach ($rows as $i => $r): ?>
                            <?php $inputBy = inputByDisplay($r); ?>

                            <tr>
                                <!-- No -->
                                <td class="text-center"><?= $i + 1 ?></td>

                                <!-- Nama -->
                                <td><?= e($r['nama_lengkap']) ?></td>

                                <!-- Detail -->
                                <td class="text-center">
                                    <a
                                        href="<?= url('admin/detail-relawan.php?id=' . $r['id']) ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Lihat Data
                                    </a>
                                </td>

                                <!-- Status Aktif -->
                                <td class="text-center">
                                    <?php if ((int)($r['is_active'] ?? 0) === 1): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Kelengkapan Data -->
                                <td class="text-center">
                                    <?php if ((int)($r['profile_complete'] ?? 0) === 1): ?>
                                        <span class="badge badge-success">Lengkap</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Belum Lengkap</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status Verifikasi -->
                                <td class="text-center">

                                    <?php if (($r['status_verifikasi'] ?? '') === 'pending'): ?>

                                        <a
                                            href="<?= url('admin/verifikasi-relawan.php?id=' . $r['id'] . '&status_verifikasi=terdaftar') ?>"
                                            class="btn btn-success btn-circle btn-sm"
                                            title="Verifikasi relawan"
                                            onclick="return confirm('Yakin ingin memverifikasi relawan ini?')">
                                            <i class="fas fa-check"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-danger btn-circle btn-sm"
                                            title="Tolak relawan"
                                            data-toggle="modal"
                                            data-target="#modalTolak"
                                            data-id="<?= e($r['id']) ?>">
                                            <i class="fas fa-times"></i>
                                        </button>

                                    <?php elseif (($r['status_verifikasi'] ?? '') === 'terdaftar'): ?>

                                        <span class="badge badge-success px-3 py-2">
                                            <i class="fas fa-check-circle"></i>
                                            Terdaftar
                                        </span>

                                    <?php elseif (($r['status_verifikasi'] ?? '') === 'ditolak'): ?>

                                        <span
                                            class="badge badge-danger px-3 py-2"
                                            title="<?= e($r['catatan_verifikasi'] ?? '') ?>">
                                            <i class="fas fa-times-circle"></i>
                                            Ditolak
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-secondary px-3 py-2">
                                            <i class="fas fa-question-circle"></i>
                                            Belum Ada Status
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Diinput Oleh -->
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

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle mr-1"></i>
                                Belum ada data relawan yang sesuai dengan filter.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1" role="dialog" aria-hidden="true">

    <div class="modal-dialog" role="document">

        <form method="POST" action="<?= url('admin/verifikasi-relawan.php') ?>">

            <div class="modal-content">

                <div class="modal-header bg-danger text-white">

                    <h5 class="modal-title">
                        Tolak Relawan
                    </h5>

                    <button
                        type="button"
                        class="close text-white"
                        data-dismiss="modal"
                        aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>

                </div>

                <div class="modal-body">

                    <input type="hidden" name="id" id="tolakId">
                    <input type="hidden" name="status_verifikasi" value="ditolak">

                    <div class="form-group">

                        <label for="catatanVerifikasi">
                            Alasan Penolakan
                        </label>

                        <textarea
                            id="catatanVerifikasi"
                            name="catatan_verifikasi"
                            class="form-control"
                            rows="5"
                            required></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-danger">
                        Tolak Relawan
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
$(function () {
    $('#modalTolak').on('show.bs.modal', function (e) {
        const button = $(e.relatedTarget);
        const id = button.data('id');

        $('#tolakId').val(id);
    });
});
</script>
