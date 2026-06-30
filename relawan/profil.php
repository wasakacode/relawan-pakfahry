<?php
require_once __DIR__ . '/../auth/auth.php';

require_role('relawan');

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$stmt = $pdo->prepare("SELECT * FROM profiles 
                       WHERE user_id = ? AND type = 'relawan' 
                       LIMIT 1");

$stmt->execute([current_user()['id']]);
$p = $stmt->fetch();
?>

<h1 class="h3 mb-4 text-gray-800">Profil Saya</h1>

<?php if ($p): ?>

    <div class="card shadow mb-4">
        <div class="card-body">

            <!-- <div class="alert alert-success">
                <b>Status:</b> Anda sudah terdaftar sebagai relawan.
            </div> -->

            <table class="table table-bordered">
                <tr>
                    <th>NIK</th>
                    <td><?= e($p['nik']) ?></td>
                </tr>

                <tr>
                    <th>Nama Lengkap</th>
                    <td><?= e($p['nama_lengkap']) ?></td>
                </tr>

                <tr>
                    <th>Kecamatan</th>
                    <td><?= e($p['kecamatan']) ?></td>
                </tr>

                <tr>
                    <th>Desa/Kelurahan</th>
                    <td><?= e($p['desa_kelurahan']) ?></td>
                </tr>

                <tr>
                    <th>TPS</th>
                    <td><?= e($p['tps']) ?></td>
                </tr>

                <tr>
                    <th>Status Verifikasi</th>
                    <td><?= e($p['status_verifikasi']) ?></td>
                </tr>
            </table>

        </div>
    </div>

<?php else: ?>

    <div class="alert alert-warning">
        Data profil belum ditemukan.
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>