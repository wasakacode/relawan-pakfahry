<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$where = "WHERE p.type = 'dukungan'";

// Relawan hanya lihat datanya sendiri
if (current_user()['role'] === 'relawan') {
    $where .= " AND p.created_by = ?";
    $params[] = current_user()['id'];
}

$stmt = $pdo->prepare("SELECT p.*, u.name pembuat 
                       FROM profiles p 
                       LEFT JOIN users u ON p.created_by = u.id 
                       $where 
                       ORDER BY p.created_at DESC");

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

// Ambil Kecamatan
$kecamatanStmt = $pdo->query("
    SELECT DISTINCT kecamatan
    FROM profiles
    WHERE type = 'dukungan'
    ORDER BY kecamatan ASC
");

$kecamatanList = $kecamatanStmt->fetchAll();

// Ambil Desa
$desaStmt = $pdo->query("
    SELECT DISTINCT desa_kelurahan
    FROM profiles
    WHERE type = 'dukungan'
    ORDER BY desa_kelurahan ASC
");

$desaList = $desaStmt->fetchAll();

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
        <form method="GET" class="mb-4">

            <div class="row">

                <!-- Search -->
                <div class="col-md-4 mb-2">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nama atau NIK..."
                        value="<?= e($search) ?>">
                </div>

                <!-- Kecamatan -->
                <div class="col-md-3 mb-2">
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
                <div class="col-md-2 mb-2">
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
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Kecamatan</th>
                        <th>Desa</th>
                        <th>TPS</th>
                        <th>Diinput Oleh</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($r['nik']) ?></td>
                            <td><?= e($r['nama_lengkap']) ?></td>
                            <td><?= e($r['kecamatan']) ?></td>
                            <td><?= e($r['desa_kelurahan']) ?></td>
                            <td><?= e($r['tps']) ?></td>
                            <td><?= e($r['pembuat']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>