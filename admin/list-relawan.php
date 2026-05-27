<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

if (current_user()['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT p.*, u.username 
                           FROM profiles p 
                           LEFT JOIN users u ON p.user_id = u.id 
                           WHERE p.type = 'relawan' 
                           AND p.created_by = ?
                           ORDER BY p.created_at DESC");
    $stmt->execute([current_user()['id']]);
} else {
    $stmt = $pdo->query("SELECT p.*, u.username 
                         FROM profiles p 
                         LEFT JOIN users u ON p.user_id = u.id 
                         WHERE p.type = 'relawan' 
                         ORDER BY p.created_at DESC");
}

$rows = $stmt->fetchAll();
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
                                        href="<?= url('admin/detail-relawan.php?id=' . $r['id']) ?>" 
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