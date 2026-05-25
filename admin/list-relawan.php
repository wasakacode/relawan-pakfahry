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
?>

<h1 class="h3 mb-4 text-gray-800">Data Relawan</h1>

<div class="card shadow mb-4">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Kecamatan</th>
                        <th>Desa</th>
                        <th>TPS</th>
                        <th>Status</th>
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