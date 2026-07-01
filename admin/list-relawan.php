<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

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
$status    = $_GET['status'] ?? '';

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

$allowedColumns = [
    'nik',
    'nama_lengkap',
    'status_verifikasi',
    'is_active',
    'created_at'
];

if (!in_array($sortBy, $allowedColumns)) {
    $sortBy = 'created_at';
}

$allowedOrder = ['ASC', 'DESC'];

if (!in_array($order, $allowedOrder)) {
    $order = 'DESC';
}

/*
|--------------------------------------------------------------------------
| Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT p.*, u.username, u.is_active
    FROM profiles p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.type = 'relawan'
";

$params = [];

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if (!empty($search)) {

    $sql .= "
        AND (
            p.nama_lengkap LIKE :search
            OR u.username LIKE :search
            OR p.nik LIKE :search
        )
    ";

    $params['search'] = "%$search%";
}

/*
|--------------------------------------------------------------------------
| Filter Kecamatan
|--------------------------------------------------------------------------
*/

if (!empty($kecamatan)) {

    $sql .= " AND p.kecamatan = :kecamatan ";

    $params['kecamatan'] = $kecamatan;
}

/*
|--------------------------------------------------------------------------
| Filter Status
|--------------------------------------------------------------------------
*/

if (isset($_GET['status']) && $_GET['status'] !== '') {

    $sql .= " AND u.is_active = :status ";

    $params['status'] = $_GET['status'];
}

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY $sortBy $order ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Ambil daftar kecamatan unik
|--------------------------------------------------------------------------
*/

$kecamatanStmt = $pdo->query("
    SELECT DISTINCT kecamatan
    FROM profiles
    WHERE type = 'relawan'
    ORDER BY kecamatan ASC
");

$kecamatanList = $kecamatanStmt->fetchAll();

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
?>

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
        <form method="GET" class="mb-4">

            <div class="row">

                <!-- Search -->
                <div class="col-md-4 mb-2">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nama, username, atau NIK..."
                        value="<?= e($search) ?>">
                </div>

                <!-- Kecamatan -->
                <div class="col-md-3 mb-2">
                    <select name="kecamatan" class="form-control">

                        <option value="">-- Semua Kecamatan --</option>

                        <?php foreach ($kecamatanList as $k): ?>

                            <option
                                value="<?= e($k['kecamatan']) ?>"
                                <?= $kecamatan == $k['kecamatan'] ? 'selected' : '' ?>>

                                <?= e($k['kecamatan']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control">

                        <option value="">-- Status --</option>

                        <option value="1"
                            <?= $status == '1' ? 'selected' : '' ?>>
                            Aktif
                        </option>

                        <option value="0"
                            <?= $status == '0' ? 'selected' : '' ?>>
                            Nonaktif
                        </option>

                    </select>
                </div>

                <!-- Button -->
                <div class="col-md-3 mb-2">
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

                <thead style="background:#f1faff;">
                    <tr>

                        <th>No</th>

                        <th>
                            <?= sortLink('nik', 'NIK') ?>
                        </th>

                        <th>
                            <?= sortLink('nama_lengkap', 'Nama') ?>
                        </th>

                        <th>Detail</th>

                        <th>
                            <?= sortLink('is_active', 'Status Aktif') ?>
                        </th>

                        <th>Status Verifikasi</th>

                    </tr>
                </thead>

                <tbody>

                    <?php if (count($rows) > 0): ?>

                        <?php foreach ($rows as $i => $r): ?>

                            <tr>
                                <!-- No -->
                                <td><?= $i + 1 ?></td>

                                <!-- NIK -->
                                <td>
                                    <b><?= e($r['nik']) ?></b>
                                </td>

                                <!-- Nama -->
                                <td><?= e($r['nama_lengkap']) ?></td>

                                <!-- Detail -->
                                <td>
                                    <a
                                        href="<?= url('admin/detail-relawan.php?id=' . $r['id']) ?>"
                                        class="btn btn-sm btn-info">

                                        <i class="fas fa-eye"></i> Lihat Data

                                    </a>
                                </td>

                                <!-- Status Aktif -->
                                <td>
                                    <?php if ($r['is_active'] == '1'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status Verifikasi -->
                                <td class="text-center">

                                    <?php if ($r['status_verifikasi'] == 'pending'): ?>

                                        <!-- Verifikasi -->
                                        <a href="<?= url('admin/verifikasi-relawan.php?id=' . $r['id'] . '&status_verifikasi=terdaftar') ?>"
                                            class="btn btn-success btn-circle btn-sm"
                                            onclick="return confirm('Yakin ingin memverifikasi relawan ini?')">
                                            <i class="fas fa-check"></i>
                                        </a>

                                        <!-- Tolak -->
                                        <button
                                            class="btn btn-danger btn-circle btn-sm"
                                            data-toggle="modal"
                                            data-target="#modalTolak"
                                            data-id="<?= $r['id'] ?>">
                                            <i class="fas fa-times"></i>
                                        </button>

                                    <?php elseif ($r['status_verifikasi'] == 'terdaftar'): ?>

                                        <span class="badge badge-success px-3 py-2">
                                            <i class="fas fa-check-circle"></i>
                                            Terdaftar
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-danger px-3 py-2">
                                            <i class="fas fa-times-circle"></i>
                                            Ditolak
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                Belum ada data relawan.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak">

    <div class="modal-dialog">

        <form
            method="POST"
            action="<?= url('admin/verifikasi-relawan.php') ?>">

            <div class="modal-content">

                <div class="modal-header bg-danger text-white">

                    <h5 class="modal-title">
                        Tolak Relawan
                    </h5>

                    <button
                        type="button"
                        class="close text-white"
                        data-dismiss="modal">
                        <span>&times;</span>
                    </button>

                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="id"
                        id="tolakId">
                    <input
                        type="hidden"
                        name="status_verifikasi"
                        value="ditolak">

                    <div class="form-group">

                        <label>
                            Alasan Penolakan
                        </label>

                        <textarea
                            name="catatan_verifikasi"
                            class="form-control"
                            rows="5"
                            required></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Batal
                    </button>

                    <button
                        class="btn btn-danger">
                        Tolak Relawan
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

<!-- Script Tolak -->
<script>
    $('#modalTolak').on('show.bs.modal', function(e) {
        let button = $(e.relatedTarget);
        let id = button.data('id');
        $('#tolakId').val(id);
    });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>