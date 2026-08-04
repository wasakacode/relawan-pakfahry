<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <?php
    $profileComplete = true;
    $profileActive = true;
    $statusVerifikasi = 'terdaftar';

    if (current_user()['role'] === 'relawan') {

        $profile = current_profile($pdo);

        if ($profile) {

            $profileComplete = (bool)$profile['profile_complete'];
            $profileActive = (bool)$profile['profile_active'];
            $statusVerifikasi = $profile['status_verifikasi'];
        }
    }
    ?>

    <?php

    $profileComplete = true;
    $profileActive = true;
    $statusVerifikasi = 'terdaftar';

    if (current_user()['role'] === 'relawan') {

        $profile = current_profile($pdo);

        if ($profile) {

            $profileComplete = (bool)$profile['profile_complete'];
            $profileActive = (bool)$profile['profile_active'];
            $statusVerifikasi = $profile['status_verifikasi'];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AKSES RELAWAN
    |--------------------------------------------------------------------------
    */

    $alasan = [];

    if (!$profileComplete) {
        $alasan[] = "melengkapi profil";
    }

    if (!$profileActive) {
        $alasan[] = "mengaktifkan profil";
    }

    if ($statusVerifikasi !== 'terdaftar') {
        $alasan[] = "memverifikasi akun";
    }

    $bolehAkses = empty($alasan);

    $pesanAkses = '';

    if (!$bolehAkses) {

        $pesanAkses =
            "Silakan hubungi Admin untuk "
            . implode(", ", $alasan) . ".";
    }

    ?>

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= url('dashboard/index.php') ?>">
        <div class="sidebar-brand-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="sidebar-brand-text mx-3">
            Relawan App
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="<?= url('dashboard/index.php') ?>">
            <i class="fas fa-home fa-fw"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- ========================= -->
    <!-- INPUT DATA -->
    <!-- ========================= -->
    <div class="sidebar-heading">
        Input Data
    </div>

    <?php if (current_user()['role'] === 'superadmin'): ?>
        <!-- Buat Admin -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/create-admin.php') ?>">
                <i class="fas fa-user-shield fa-fw"></i>
                <span>Buat Admin</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Tambah Relawan -->
    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/create-relawan.php') ?>">
                <i class="fas fa-user-plus fa-fw"></i>
                <span>Buat Relawan</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Tambah Dukungan -->
    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('dukungan/create.php') ?>">
                <i class="fas fa-hand-holding-heart fa-fw"></i>
                <span>Tambah Dukungan</span>
            </a>
        </li>
    <?php elseif (current_user()['role'] === 'relawan'): ?>

        <li class="nav-item">

            <a
                class="nav-link"
                href="<?= $bolehAkses ? url('dukungan/create.php') : '#' ?>"
                <?= !$bolehAkses
                    ? 'onclick="alert(\'' . htmlspecialchars($pesanAkses, ENT_QUOTES) . '\'); return false;"'
                    : '' ?>>

                <i class="fas <?= $bolehAkses
                                    ? 'fa-hand-holding-heart'
                                    : 'fa-lock' ?> fa-fw"></i>

                <span>Tambah Dukungan</span>

            </a>

        </li>

    <?php endif; ?>

    <?php if (current_user()['role'] === 'superadmin'): ?>
        <!-- Buat Daerah Pemilihan -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/create-dapil.php') ?>">
                <i class="fas fa-map fa-fw"></i>
                <span>Buat Daerah Pemilihan</span>
            </a>
        </li>
    <?php endif; ?>

    <hr class="sidebar-divider">

    <!-- ========================= -->
    <!-- MANAJEMEN DATA -->
    <!-- ========================= -->
    <div class="sidebar-heading">
        Manajemen Data
    </div>

    <?php if (current_user()['role'] === 'superadmin'): ?>
        <!-- Data Admin -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-admin.php') ?>">
                <i class="fas fa-users-cog fa-fw"></i>
                <span>Data Admin</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>
        <!-- Data Relawan -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-relawan.php') ?>">
                <i class="fas fa-id-badge fa-fw"></i>
                <span>Data Relawan</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>
        <!-- Data Dukungan -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('dukungan/list.php') ?>">
                <i class="fas fa-hand-holding-heart fa-fw"></i>
                <span>Data Dukungan</span>
            </a>
        </li>
    <?php elseif (current_user()['role'] === 'relawan'): ?>

        <li class="nav-item">

            <a
                class="nav-link"
                href="<?= $bolehAkses ? url('dukungan/list.php') : '#' ?>"
                <?= !$bolehAkses
                    ? 'onclick="alert(\'' . htmlspecialchars($pesanAkses, ENT_QUOTES) . '\'); return false;"'
                    : '' ?>>

                <i class="fas <?= $bolehAkses
                                    ? 'fa-hand-holding-heart'
                                    : 'fa-lock' ?> fa-fw"></i>

                <span>Data Dukungan</span>

            </a>

        </li>

    <?php endif; ?>

    <?php if (current_user()['role'] === 'relawan'): ?>
        <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Profil
        </div>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/detail-relawan.php') ?>">
                <i class="fas fa-id-card fa-fw"></i>
                <span>Profil Saya</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (current_user()['role'] === 'superadmin'): ?>
        <!-- Data Dapil -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-dapil.php') ?>">
                <i class="fas fa-globe fa-fw"></i>
                <span>Data Dapil</span>
            </a>
        </li>
        <!-- Data TPS -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-tps.php') ?>">
                <i class="fas fa-map-pin fa-fw"></i>
                <span>Data TPS</span>
            </a>
        </li>
        <!-- Data Users -->
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-users.php') ?>">
                <i class="fas fa-users fa-fw"></i>
                <span>Data Users</span>
            </a>
        </li>
    <?php endif; ?>

    <hr class="sidebar-divider">

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>

        <div class="sidebar-heading">
            Statistik
        </div>

        <li class="nav-item">
            <a class="nav-link" href="<?= url('statistik/statistik_wilayah.php') ?>">
                <i class="fas fa-address-book fa-fw"></i>
                <span>Statistik Wilayah</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('statistik/keterisian_tps.php') ?>">
                <i class="fas fa-address-book fa-fw"></i>
                <span>Statistik Keterisian TPS</span>
            </a>
        </li>
    <?php endif; ?>

    <hr class="sidebar-divider">

    <!-- ========================= -->
    <!-- AKUN -->
    <!-- ========================= -->
    <div class="sidebar-heading">
        Akun
    </div>

    <li class="nav-item">
        <a class="nav-link" href="<?= url('logout.php') ?>">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span>Logout</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>