<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$search     = $_GET['search'] ?? '';
$kabupaten   = $_GET['kabupaten'] ?? '';
$kecamatan   = $_GET['kecamatan'] ?? '';
$kelurahan   = $_GET['kelurahan'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'provinsi';
$order  = $_GET['order'] ?? 'ASC';
$allowedColumns = [
    'provinsi',
    'kabupaten',
    'kecamatan',
    'kelurahan',
    'no_tps'
];

if (!in_array($sortBy, $allowedColumns, true)) {
    $sortBy = 'provinsi';
}

$allowedOrder = ['ASC', 'DESC'];

if (!in_array($order, $allowedOrder, true)) {
    $order = 'ASC';
}


$sql = "
    SELECT 
        id,
        provinsi,
        kabupaten,
        kecamatan,
        kelurahan,
        no_tps
    FROM tps_kalsel
    WHERE 1=1
";

$params = [];

if (!empty($search)) {

    $sql .= "
        AND (
            kabupaten LIKE :search
            OR kecamatan LIKE :search
            OR kelurahan LIKE :search
            OR CAST(no_tps AS CHAR) LIKE :search
        )
    ";

    $params['search'] = "%{$search}%";
}


if (!empty($kabupaten)) {

    $sql .= " AND kabupaten = :kabupaten";

    $params['kabupaten'] = $kabupaten;
}


if (!empty($kecamatan)) {

    $sql .= " AND kecamatan = :kecamatan";

    $params['kecamatan'] = $kecamatan;
}


if (!empty($kelurahan)) {

    $sql .= " AND kelurahan = :kelurahan";

    $params['kelurahan'] = $kelurahan;
}
$sortMap = [
    'provinsi'         => 'provinsi',
    'kabupaten'       => 'kabupaten',
    'kecamatan'       => 'kecamatan',
    'kelurahan'       => 'kelurahan',
    'no_tps'          => 'no_tps'
];

$sortColumn = $sortMap[$sortBy];
$sql .= " ORDER BY $sortColumn $order";

function sortLink($column, $label)
{
    $currentSortBy = $_GET['sort_by'] ?? 'id';
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

$perPage = 25;

$page = max(1, (int)($_GET['page'] ?? 1));

$offset = ($page - 1) * $perPage;

$no = $offset + 1;
$countSql = "
    SELECT COUNT(*)
    FROM tps_kalsel
    WHERE 1=1
";

$countParams = [];

if (!empty($search)) {
    $countSql .= "
        AND (
            kabupaten LIKE :search
            OR kecamatan LIKE :search
            OR kelurahan LIKE :search
            OR CAST(no_tps AS CHAR) LIKE :search
        )
    ";

    $countParams['search'] = "%{$search}%";
}

if (!empty($kabupaten)) {
    $countSql .= " AND kabupaten = :kabupaten";
    $countParams['kabupaten'] = $kabupaten;
}

if (!empty($kecamatan)) {
    $countSql .= " AND kecamatan = :kecamatan";
    $countParams['kecamatan'] = $kecamatan;
}

if (!empty($kelurahan)) {
    $countSql .= " AND kelurahan = :kelurahan";
    $countParams['kelurahan'] = $kelurahan;
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);

$totalData = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalData / $perPage);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    $no = $offset + 1;
}

// Pagination Query String
$queryParams = $_GET;
unset($queryParams['page']);

$sql .= " LIMIT :limit OFFSET :offset";

$stmtKabupaten = $pdo->query("
    SELECT DISTINCT kabupaten
    FROM tps_kalsel
    ORDER BY kabupaten
");

$kabupatenList = $stmtKabupaten->fetchAll(PDO::FETCH_COLUMN);
$kecamatanList = [];

if (!empty($kabupaten)) {

    $stmtKecamatan = $pdo->prepare("
        SELECT DISTINCT kecamatan
        FROM tps_kalsel
        WHERE kabupaten = :kabupaten
        ORDER BY kecamatan
    ");

    $stmtKecamatan->execute([
        'kabupaten' => $kabupaten
    ]);
} else {

    $stmtKecamatan = $pdo->query("
        SELECT DISTINCT kecamatan
        FROM tps_kalsel
        ORDER BY kecamatan
    ");
}

$kecamatanList = $stmtKecamatan->fetchAll(PDO::FETCH_COLUMN);
$kelurahanList = [];

if (!empty($kabupaten) && !empty($kecamatan)) {

    $stmtKelurahan = $pdo->prepare("
        SELECT DISTINCT kelurahan
        FROM tps_kalsel
        WHERE
            kabupaten = :kabupaten
            AND kecamatan = :kecamatan
        ORDER BY kelurahan
    ");

    $stmtKelurahan->execute([
        'kabupaten' => $kabupaten,
        'kecamatan' => $kecamatan
    ]);
} elseif (!empty($kabupaten)) {

    $stmtKelurahan = $pdo->prepare("
        SELECT DISTINCT kelurahan
        FROM tps_kalsel
        WHERE kabupaten = :kabupaten
        ORDER BY kelurahan
    ");

    $stmtKelurahan->execute([
        'kabupaten' => $kabupaten
    ]);
} else {

    $stmtKelurahan = $pdo->query("
        SELECT DISTINCT kelurahan
        FROM tps_kalsel
        ORDER BY kelurahan
    ");
}

$kelurahanList = $stmtKelurahan->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="h3 mb-4 text-gray-800">Data TPS</h1>

<div class="card content-card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
            Daftar TPS
        </h6>

        <a href="<?= url('admin/create-tps.php') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus"></i> Tambah TPS
        </a>
    </div>

    <div class="card-body">

        <!-- FILTER -->
        <form method="GET" class="mb-4">

            <!-- Search -->
            <div class="row mb-3">

                <div class="col-md-12">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari kabupaten, kecamatan, kelurahan atau nomor TPS..."
                        value="<?= e($search) ?>">

                </div>

            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Kabupaten/Kota</label>
                    <select name="kabupaten" class="form-control">
                        <option value="">Semua Kabupaten/Kota</option>
                        <?php foreach ($kabupatenList as $kab): ?>
                            <option
                                value="<?= e($kab) ?>"
                                <?= ($kabupaten == $kab) ? 'selected' : '' ?>>
                                <?= e($kab) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">

                    <label>Kecamatan</label>

                    <select name="kecamatan" class="form-control">

                        <option value="">Semua Kecamatan</option>

                        <?php foreach ($kecamatanList as $kec): ?>

                            <option
                                value="<?= e($kec) ?>"
                                <?= ($kecamatan == $kec) ? 'selected' : '' ?>>

                                <?= e($kec) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>
                <div class="col-md-4 mb-3">

                    <label>Kelurahan/Desa</label>

                    <select name="kelurahan" class="form-control">

                        <option value="">Semua Kelurahan</option>

                        <?php foreach ($kelurahanList as $kel): ?>

                            <option
                                value="<?= e($kel) ?>"
                                <?= ($kelurahan == $kel) ? 'selected' : '' ?>>

                                <?= e($kel) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>
            <div class="text-right">

                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>

                <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>"
                    class="btn btn-secondary">

                    <i class="fas fa-sync-alt"></i>
                    Reset
                </a>

            </div>

        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">

                <thead style="background:#f1faff;">
                    <tr>
                        <th width="60">No</th>
                        <th><?= sortLink('provinsi', 'Provinsi') ?></th>
                        <th><?= sortLink('kabupaten', 'Kabupaten/Kota') ?></th>
                        <th><?= sortLink('kecamatan', 'Kecamatan') ?></th>
                        <th><?= sortLink('kelurahan', 'Kelurahan/Desa') ?></th>
                        <th><?= sortLink('no_tps', 'No. TPS') ?></th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($rows): ?>

                        <?php foreach ($rows as $i => $row): ?>

                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($row['provinsi']) ?></td>
                                <td><?= e($row['kabupaten']) ?></td>
                                <td><?= e($row['kecamatan']) ?></td>
                                <td><?= e($row['kelurahan']) ?></td>
                                <td><?= e($row['no_tps']) ?></td>
                                <td>
                                    <a href="<?= url('admin/edit-tps.php?id=' . $row['id']) ?>"
                                        class="btn btn-warning btn-sm p-1"
                                        title="Edit Data">

                                        <i class="fas fa-pen"></i>

                                    </a>

                                    <a href="<?= url('admin/delete-tps.php?id=' . $row['id']) ?>"
                                        class="btn btn-danger btn-sm p-1"
                                        title="Hapus Data"
                                        onclick="return confirm('Yakin ingin menghapus data TPS ini?')">

                                        <i class="fas fa-trash"></i>

                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center">
                                Belum ada data.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">

            <small class="text-muted">
                Menampilkan
                <strong><?= $offset + 1 ?></strong>
                -
                <strong><?= min($offset + $perPage, $totalData) ?></strong>
                dari
                <strong><?= number_format($totalData) ?></strong>
                data
            </small>

        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage   = min($totalPages, $page + 2);
            ?>

            <nav class="mt-4">
                <ul class="pagination justify-content-center">

                    <!-- Previous -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link"
                            href="?<?= http_build_query(array_merge($queryParams, ['page' => $page - 1])) ?>">
                            <i class="fas fa-angle-left mr-1"></i>
                            Previous
                        </a>
                    </li>
                    <?php if ($startPage > 1): ?>

                        <li class="page-item">
                            <a class="page-link"
                                href="?<?= http_build_query(array_merge($queryParams, ['page' => 1])) ?>">
                                1
                            </a>
                        </li>

                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>

                    <?php endif; ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>

                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>

                    <?php endfor; ?>

                    <!-- Halaman Terakhir -->
                    <?php if ($endPage < $totalPages): ?>

                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>

                        <li class="page-item">
                            <a class="page-link"
                                href="?<?= http_build_query(array_merge($queryParams, ['page' => $totalPages])) ?>">
                                <?= $totalPages ?>
                            </a>
                        </li>

                    <?php endif; ?>

                    <!-- Next -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link"
                            href="?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])) ?>">
                            Next
                            <i class="fas fa-angle-right ml-1"></i>
                        </a>
                    </li>

                </ul>
            </nav>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>