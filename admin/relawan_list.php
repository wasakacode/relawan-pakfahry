<?php

require_once __DIR__ . '/../auth/auth.php';

require_role('superadmin');

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';


$adminProfileId = (int)($_GET['admin'] ?? 0);

if ($adminProfileId <= 0) {
    die('Admin tidak ditemukan.');
}

$sql = "
SELECT
    p.*,
    u.username,
    u.is_active
FROM profiles p

INNER JOIN profile_admin pa
    ON pa.profile_id = p.id

LEFT JOIN users u
    ON u.id = p.user_id

WHERE
    p.type = 'relawan'
    AND pa.admin_profile_id = ?

ORDER BY p.nama_lengkap ASC
";

$stmt = $pdo->prepare("
    SELECT nama_lengkap
    FROM profiles
    WHERE id = ?
    AND type='admin'
");

$stmt->execute([$adminProfileId]);

$namaAdmin = $stmt->fetchColumn();

$stmt = $pdo->prepare($sql);
$stmt->execute([$adminProfileId]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<h1 class="h3 mb-4 text-gray-800">RELAWAN DARI <strong><?= e($namaAdmin) ?></strong> </h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Daftar Relawan
        </h6>
    </div>

    <div class="card-body">

        <?php if (empty($rows)) : ?>

            <div class="alert alert-warning mb-0">
                Belum ada relawan yang dinaungi admin ini.
            </div>

        <?php else : ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">

                    <thead class="thead-light">
                        <tr>
                            <th width="60">No</th>
                            <th>Nama Lengkap</th>
                            <th>detail</th>
                            <th>Status Akun</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php $no = 1; ?>

                        <?php foreach ($rows as $row) : ?>

                            <tr>

                                <td><?= $no++ ?></td>

                                <td><?= e($row['nama_lengkap']) ?></td>
                                
                                <td class="text-center">
                                    <a
                                        href="<?= url('admin/detail-relawan.php?relawan=' . $row['id']) ?>"
                                        class="btn btn-sm btn-info">

                                        <i class="fas fa-eye"></i> Lihat Data

                                    </a>
                                </td>

                                <td>
                                    <?php if ($row['is_active']) : ?>
                                        <span class="badge badge-success">
                                            Aktif
                                        </span>
                                    <?php else : ?>
                                        <span class="badge badge-danger">
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

