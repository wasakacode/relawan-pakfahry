<?php
require_once __DIR__ . '/../auth/auth.php';

require_role('superadmin');

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$stmt = $pdo->query("SELECT p.*, u.username, u.is_active
                     FROM profiles p
                     LEFT JOIN users u ON p.user_id = u.id
                     WHERE p.type = 'admin'
                     ORDER BY p.created_at DESC");

$rows = $stmt->fetchAll();
?>

<h1 class="h3 mb-4 text-gray-800">Data Admin</h1>

<div class="card content-card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users-cog mr-2" style="color:#3db7ee;"></i>
            Daftar Admin / Koordinator Kecamatan
        </h6>

        <a href="<?= url('admin/create-admin.php') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-user-shield"></i> Tambah Admin
        </a>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">

                <thead style="background:#f1faff;">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 28%;">NIK</th>
                        <th>Nama Lengkap</th>
                        <th>Kecamatan</th>
                        <th style="width: 18%;">Keterangan</th>
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

                                <td>
                                    <?= e($r['nama_lengkap']) ?>
                                </td>

                                <td>
                                    <?= e($r['kecamatan'] ?? '-') ?>
                                </td>

                                <td>
                                    <a 
                                        href="<?= url('admin/detail-admin.php?id=' . $r['id']) ?>" 
                                        class="btn btn-sm btn-info"
                                    >
                                        <i class="fas fa-eye"></i> Lihat Data
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                Belum ada data admin.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>