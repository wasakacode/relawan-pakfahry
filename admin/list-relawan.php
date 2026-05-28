<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$stmt = $pdo->query("SELECT p.*, u.username 
                    FROM profiles p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE p.type = 'relawan' 
                    ORDER BY p.created_at DESC");

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

$search = '';

if(isset($_GET['search'])){
    $search = $_GET['search'];
}

echo $search;
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
            <table class="table table-bordered table-hover" width="100%">

                <thead style="background:#f1faff;">
                    <tr>
                        <th>No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Detail</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($rows) > 0): ?>
                        <?php foreach ($rows as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <b><?= e($r['nik']) ?></b>
                                </td>
                                <td><?= e($r['nama_lengkap']) ?></td>
                                <td>
                                    <a 
                                        href="<?= url('admin/detail-relawan.php?id=' . $r['id']) ?>" 
                                        class="btn btn-sm btn-info"
                                    >
                                        <i class="fas fa-eye"></i> Lihat Data
                                    </a>
                                </td>
                                <td><?= e($r['status_verifikasi']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Belum ada data relawan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>