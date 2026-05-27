<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

// Filter & Search
$search    = $_GET['search'] ?? '';
$kecamatan = $_GET['kecamatan'] ?? '';
$status    = $_GET['status'] ?? '';

// Sorting
$sortBy = $_GET['sort_by'] ?? 'created_at';
$order  = $_GET['order'] ?? 'DESC';

// Validasi Sorting
$allowedColumns = [
    'nik',
    'nama_lengkap',
    'username',
    'kecamatan',
    'desa_kelurahan',
    'tps',
    'status_verifikasi',
    'created_at'
];

if (!in_array($sortBy, $allowedColumns)) {
    $sortBy = 'created_at';
}

$allowedOrder = ['ASC', 'DESC'];

if (!in_array($order, $allowedOrder)) {
    $order = 'DESC';
}
// Query
$sql = "
    SELECT p.*, u.username
    FROM profiles p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.type = 'relawan'
";

$params = [];

// Search
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

// Filter Kecamatan
if (!empty($kecamatan)) {

    $sql .= " AND p.kecamatan = :kecamatan ";

    $params['kecamatan'] = $kecamatan;
}

// Filter Status
if (!empty($status)) {

    $sql .= " AND p.status_verifikasi = :status ";

    $params['status'] = $status;
}

// Sorting
$sql .= " ORDER BY $sortBy $order ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

// Ambil daftar kecamatan unik
$kecamatanStmt = $pdo->query("
    SELECT DISTINCT kecamatan
    FROM profiles
    WHERE type = 'relawan'
    ORDER BY kecamatan ASC
");

$kecamatanList = $kecamatanStmt->fetchAll();

// Function Sort Link
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

<div class="card shadow mb-4">
    <div class="card-body">

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

                        <option value="verified"
                            <?= $status == 'verified' ? 'selected' : '' ?>>
                            Verified
                        </option>

                        <option value="pending"
                            <?= $status == 'pending' ? 'selected' : '' ?>>
                            Pending
                        </option>

                    </select>
                </div>

                <!-- Button -->
                <div class="col-md-3 mb-2">
                    <div class="d-flex">

                        <button type="submit"
                            class="btn btn-primary flex-fill mr-2">

                            <i class="fas fa-filter"></i> Filter

                        </button>

                        <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>"
                            class="btn btn-secondary flex-fill">

                            <i class="fas fa-sync-alt"></i> Reset

                        </a>

                    </div>
                </div>

            </div>

        </form>

        <div class="table-responsive">
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th><?= sortLink('nik', 'NIK') ?></th>
                        <th><?= sortLink('nama_lengkap', 'Nama') ?></th>
                        <th><?= sortLink('username', 'Username') ?></th>
                        <th><?= sortLink('kecamatan', 'Kecamatan') ?></th>
                        <th><?= sortLink('desa_kelurahan', 'Desa') ?></th>
                        <th><?= sortLink('tps', 'TPS') ?></th>
                        <th><?= sortLink('status_verifikasi', 'Status') ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($r['nik']) ?></td>
                            <td><?= e($r['nama_lengkap']) ?></td>
                            <td><?= e($r['username']) ?></td>
                            <td><?= e($r['kecamatan']) ?></td>
                            <td><?= e($r['desa_kelurahan']) ?></td>
                            <td><?= e($r['tps']) ?></td>
                            <td>
                                <span class="badge badge-success">
                                    <?= e($r['status_verifikasi']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>