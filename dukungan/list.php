<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$params = [];
$where = "WHERE p.type = 'dukungan'";

if (current_user()['role'] === 'relawan') {
    $where .= " AND p.created_by = ?";
    $params[] = current_user()['id'];
}

$stmt = $pdo->prepare("SELECT p.*, u.name AS pembuat
                       FROM profiles p
                       LEFT JOIN users u ON p.created_by = u.id
                       $where
                       ORDER BY p.created_at DESC");

$stmt->execute($params);
$rows = $stmt->fetchAll();
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

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">

                <thead style="background:#f1faff;">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 30%;">NIK</th>
                        <th>Nama Lengkap</th>
                        <th style="width: 20%;">Keterangan</th>
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
                                        href="<?= url('dukungan/detail.php?id=' . $r['id']) ?>" 
                                        class="btn btn-sm btn-info"
                                    >
                                        <i class="fas fa-eye"></i> Lihat Data
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Belum ada data dukungan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>