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

$search = $_GET['search'] ?? '';
$role   = $_GET['role'] ?? '';

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
    'id',
    'name',
    'username',
    'role',
    'created_at',
    'updated_at'
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
    SELECT 
        id,
        name,
        username,
        role,
        created_at,
        updated_at
    FROM users
    WHERE role IN ('superadmin', 'admin', 'relawan')
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
            name LIKE :search
            OR username LIKE :search
        )
    ";

    $params['search'] = "%$search%";
}

/*
|--------------------------------------------------------------------------
| Filter Role
|--------------------------------------------------------------------------
*/

if (!empty($role)) {

    $sql .= " AND role = :role ";

    $params['role'] = $role;
}

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY $sortBy $order ";

/*
|--------------------------------------------------------------------------
| Execute Query
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Ambil daftar role unik
|--------------------------------------------------------------------------
*/

$roleStmt = $pdo->query("
    SELECT DISTINCT role
    FROM users
    WHERE role IN ('admin', 'relawan')
    ORDER BY role ASC
");

$roleList = $roleStmt->fetchAll();

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

<h1 class="h3 mb-4 text-gray-800">Data Pengguna</h1>

<div class="card content-card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
            Daftar Pengguna
        </h6>
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
                        placeholder="Cari nama atau username..."
                        value="<?= e($search) ?>">
                </div>

                <!-- Role -->
                <div class="col-md-3 mb-2">
                    <select name="role" class="form-control">

                        <option value="">-- Semua Role --</option>

                        <?php foreach ($roleList as $r): ?>

                            <option
                                value="<?= e($r['role']) ?>"
                                <?= $role == $r['role'] ? 'selected' : '' ?>>

                                <?= e(ucfirst($r['role'])) ?>

                            </option>

                        <?php endforeach; ?>

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

                        <th>
                            <?= sortLink('id', 'No') ?>
                        </th>

                        <th>
                            <?= sortLink('name', 'Nama') ?>
                        </th>

                        <th>
                            <?= sortLink('username', 'Username') ?>
                        </th>

                        <th>
                            <?= sortLink('role', 'Role') ?>
                        </th>

                        <th>
                            <?= sortLink('created_at', 'Tanggal Dibuat') ?>
                        </th>

                        <th>
                            <?= sortLink('updated_at', 'Tanggal Diubah') ?>
                        </th>

                    </tr>
                </thead>

                <tbody>

                    <?php if (count($rows) > 0): ?>

                        <?php foreach ($rows as $i => $r): ?>

                            <tr>

                                <td><?= $i + 1 ?></td>

                                <td><?= e($r['name']) ?></td>

                                <td><?= e($r['username']) ?></td>
                                
                                <td><?= e($r['role']) ?></td>

                                <td><?= e($r['created_at']) ?></td>

                                <td><?= e($r['updated_at']) ?></td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada data.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>