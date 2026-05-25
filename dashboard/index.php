<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$totalRelawan = $pdo->query("SELECT COUNT(*) AS total FROM profiles WHERE type='relawan'")->fetch()['total'];
$totalDukungan = $pdo->query("SELECT COUNT(*) AS total FROM profiles WHERE type='dukungan'")->fetch()['total'];
$totalAdmin = $pdo->query("SELECT COUNT(*) AS total FROM profiles WHERE type='admin'")->fetch()['total'];
$totalUser = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE is_active=1")->fetch()['total'];

$role = current_user()['role'];

if ($role === 'superadmin') {
    $welcomeRole = 'Superadmin';
    $welcomeText = 'Anda memiliki akses penuh untuk mengelola admin kecamatan, relawan, dukungan, serta akun pengguna dalam sistem.';
} elseif ($role === 'admin') {
    $welcomeRole = 'Admin Kecamatan';
    $welcomeText = 'Anda dapat membuat akun relawan, menginput dukungan, serta mengelola data pada wilayah yang menjadi tanggung jawab Anda.';
} else {
    $welcomeRole = 'Relawan';
    $welcomeText = 'Anda dapat melihat profil pendaftaran diri dan menambahkan data dukungan yang berhasil dikumpulkan.';
}
?>

<div class="dashboard-hero">
    <div class="hero-content">
        <span class="hero-badge">
            <i class="fas fa-sparkles"></i> Selamat Datang
        </span>

        <h1 class="hero-title">
            Halo, <?= e(current_user()['name']) ?> 👋
        </h1>

        <p class="hero-desc">
            Anda masuk sebagai <b><?= e($welcomeRole) ?></b>. 
            <?= e($welcomeText) ?>
        </p>
    </div>
</div>

<div class="row">

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Admin Kecamatan</div>
                        <div class="stat-number"><?= $totalAdmin ?></div>
                    </div>

                    <div class="stat-icon primary">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Relawan</div>
                        <div class="stat-number"><?= $totalRelawan ?></div>
                    </div>

                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Dukungan</div>
                        <div class="stat-number"><?= $totalDukungan ?></div>
                    </div>

                    <div class="stat-icon info">
                        <i class="fas fa-handshake"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Akun Aktif</div>
                        <div class="stat-number"><?= $totalUser ?></div>
                    </div>

                    <div class="stat-icon warning">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-lg-8 mb-4">
        <div class="card content-card h-100">

            <div class="card-header">
                <h6 class="m-0">
                    <i class="fas fa-layer-group mr-2" style="color:#3db7ee;"></i>
                    Ringkasan Hak Akses Sistem
                </h6>
            </div>

            <div class="card-body">

                <p style="color:#5f788f; line-height:1.7;">
                    Sistem ini digunakan untuk membantu proses pencatatan dan pengelolaan data 
                    <b>admin kecamatan</b>, <b>relawan</b>, dan <b>dukungan</b>. 
                    Setiap pengguna memiliki hak akses yang berbeda agar pengelolaan data lebih tertata.
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Role</th>
                                <th>Hak Akses</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>
                                    <span class="role-pill">Superadmin</span>
                                </td>
                                <td>
                                    Mengelola seluruh data, membuat admin kecamatan, membuat relawan, dan melihat data dukungan.
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="role-pill">Admin Kecamatan</span>
                                </td>
                                <td>
                                    Membuat akun relawan, menginput dukungan, serta melihat data relawan dan dukungan.
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="role-pill">Relawan</span>
                                </td>
                                <td>
                                    Melihat profil dirinya dan menginput data dukungan yang diperoleh.
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="role-pill">Dukungan</span>
                                </td>
                                <td>
                                    Tidak memiliki akun login. Data dukungan hanya dicatat oleh admin atau relawan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card content-card h-100">

            <div class="card-header">
                <h6 class="m-0">
                    <i class="fas fa-user-circle mr-2" style="color:#3db7ee;"></i>
                    Profil Pengguna
                </h6>
            </div>

            <div class="card-body">

                <div class="profile-box">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="profile-name">
                        <?= e(current_user()['name']) ?>
                    </div>

                    <div class="profile-role">
                        <?= e(ucfirst(current_user()['role'])) ?>
                    </div>

                    <p style="color:#7890a6; font-size:14px; margin-bottom:20px;">
                        Kecamatan:
                        <b><?= e(current_user()['kecamatan'] ?? '-') ?></b>
                    </p>
                </div>

                <a href="<?= url('dashboard/index.php') ?>" class="quick-action">
                    <i class="fas fa-home"></i> Dashboard
                </a>

                <?php if (in_array(current_user()['role'], ['superadmin','admin'])): ?>
                    <a href="<?= url('admin/create-relawan.php') ?>" class="quick-action">
                        <i class="fas fa-user-plus"></i> Tambah Relawan
                    </a>
                <?php endif; ?>

                <?php if (in_array(current_user()['role'], ['superadmin','admin','relawan'])): ?>
                    <a href="<?= url('dukungan/create.php') ?>" class="quick-action">
                        <i class="fas fa-hand-holding-heart"></i> Tambah Dukungan
                    </a>
                <?php endif; ?>

                <a href="<?= url('logout.php') ?>" class="quick-action">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>