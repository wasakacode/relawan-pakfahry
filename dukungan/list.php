<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$where = "WHERE p.type = 'dukungan'";
$params = [];

if (current_user()['role'] === 'relawan') {
    $where .= " AND p.created_by = ?";
    $params[] = current_user()['id'];
}

$stmt = $pdo->prepare("SELECT p.*, u.name pembuat 
                       FROM profiles p 
                       LEFT JOIN users u ON p.created_by = u.id 
                       $where 
                       ORDER BY p.created_at DESC");

$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<h1 class="h3 mb-4 text-gray-800">Data Dukungan</h1>

<div class="card shadow mb-4">
    <div class="card-body">

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