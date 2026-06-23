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

$search   = $_GET['search'] ?? '';
$provinsi = $_GET['provinsi'] ?? '';


/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$sortBy = $_GET['sort_by'] ?? 'daerah_pemilihan';
$order  = $_GET['order'] ?? 'ASC';

/*
|--------------------------------------------------------------------------
| Validasi Sorting
|--------------------------------------------------------------------------
*/

$allowedColumns = [
    'daerah_pemilihan',
    'provinsi',
    'created_at'
];

if (!in_array($sortBy, $allowedColumns, true)) {
    $sortBy = 'daerah_pemilihan';
}

$allowedOrder = ['ASC', 'DESC'];

if (!in_array($order, $allowedOrder, true)) {
    $order = 'ASC';
}

//tombol
// if (isset($_POST['toggle_id'])) {

//     $id = $_POST['toggle_id'];

//     // kalau checkbox dicentang = 1, kalau tidak = 0
//     $is_active = isset($_POST['is_active']) ? 1 : 0;

//     $stmt = $pdo->prepare("
//         UPDATE dapil
//         SET is_active = ?
//         WHERE id = ?
//     ");

//     $stmt->execute([$is_active, $id]);
// }
/*
|--------------------------------------------------------------------------
| Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT 
        id,
        daerah_pemilihan,
        provinsi,
        kab_kota,
        is_active
    FROM dapil
    WHERE 1=1
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
            daerah_pemilihan LIKE :search
            OR provinsi LIKE :search
            OR kab_kota LIKE :search
        )
    ";

    $params['search'] = "%{$search}%";
}

if (!empty($provinsi)) {
    $sql .= " AND provinsi = :provinsi ";
    $params['provinsi'] = $provinsi;
}

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$sortMap = [
    'daerah_pemilihan' => 'daerah_pemilihan',
    'provinsi'         => 'provinsi',
    'created_at'       => 'created_at'
];

$sortColumn = $sortMap[$sortBy];

$sql .= " ORDER BY $sortColumn $order";

/*
|--------------------------------------------------------------------------
| Execute
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();
?>

<h1 class="h3 mb-4 text-gray-800">Data Daerah Pemilihan (Dapil)</h1>

<div class="card content-card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
            Daftar Dapil
        </h6>

        <a href="<?= url('admin/create-dapil.php') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus"></i> Tambah Dapil
        </a>
    </div>

    <div class="card-body">

        <!-- FILTER -->
        <form method="GET" class="mb-4">
    <div class="row">

        <!-- Search -->
        <div class="col-md-4 mb-2">
            <input type="text"
                name="search"
                class="form-control"
                placeholder="Cari dapil"
                value="<?= e($search) ?>">
        </div>

        <!-- Provinsi -->
        <div class="col-md-4 mb-2">
            <select name="provinsi" class="form-control">
                <option value="">Semua Provinsi</option>

                <?php foreach ($provinsiList as $p): ?>
                    <option value="<?= e($p) ?>"
                        <?= ($provinsi == $p) ? 'selected' : '' ?>>
                        <?= e($p) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Button -->
        <div class="col-md-4 mb-2">
            <div class="d-flex">

                <button type="submit" class="btn btn-primary flex-fill mr-2">
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

        <!-- TABLE -->
        <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th width="60">No</th>
                <th>Daerah Pemilihan</th>
                <th>Provinsi</th>
                <th>Kabupaten/Kota</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($rows): ?>

            <?php foreach ($rows as $i => $row): ?>

                <tr>
                    <td><?= $i + 1 ?></td>

                    <td><?= e($row['daerah_pemilihan']) ?></td>

                    <td><?= e($row['provinsi']) ?></td>

                    <td>
                        <?php
                        $kabupaten = json_decode($row['kab_kota'], true);

                        if (is_array($kabupaten) && count($kabupaten) > 0) {
                            foreach ($kabupaten as $kab) {
                                echo '<span class="badge badge-primary mr-1 mb-1">' . e($kab) . '</span>';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="<?= url('admin/edit-dapil.php?id=' . $row['id']) ?>"
                            class="btn btn-warning btn-sm p-1"
                            title="Edit Data">
                                <i class="fas fa-pen"></i>
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="4" class="text-center">
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