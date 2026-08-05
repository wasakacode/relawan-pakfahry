<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <?php
    $profileComplete = true;
    $isActive = true;
    $statusVerifikasi = 'terdaftar';

    if (current_user()['role'] === 'relawan') {

        $profile = current_profile($pdo);

        if ($profile) {
            $profileComplete = (bool) $profile['profile_complete'];
            $statusVerifikasi = $profile['status_verifikasi'];
        }

        $isActive = current_user()['is_active'];
    }

    /*
|--------------------------------------------------------------------------
| AKSES RELAWAN
|--------------------------------------------------------------------------
*/

    $bolehAkses =
        $profileComplete &&
        $isActive &&
        $statusVerifikasi === 'terdaftar';

    $modalTitle = '';
    $modalMessage = '';
    $modalIcon = 'fas fa-exclamation-triangle';
    $modalColor = '#f39c12';

    if (!$profileComplete) {

        $modalTitle = 'Profil Belum Lengkap';
        $modalMessage = 'Silakan lengkapi profil terlebih dahulu agar dapat menggunakan menu ini.';
    } elseif ($statusVerifikasi === 'pending') {

        $modalTitle = 'Menunggu Verifikasi';
        $modalMessage = 'Akun Anda sedang menunggu proses verifikasi oleh Admin Dapil.';
        $modalIcon = 'fas fa-clock';
        $modalColor = '#3498db';
    } elseif ($statusVerifikasi === 'ditolak') {

        $modalTitle = 'Verifikasi Ditolak';
        $modalTitle = 'Verifikasi Ditolak';
        $modalMessage = 'Verifikasi akun Anda ditolak. Silakan lihat catatan penolakan pada halaman Profil, perbaiki data yang diperlukan, lalu hubungi Admin Dapil.';
        $modalIcon = 'fas fa-times';
        $modalColor = '#e74c3c';
    } elseif (!$isActive) {

        $modalTitle = 'Profil Belum Aktif';
        $modalMessage = 'Profil Anda belum aktif. Silakan hubungi Admin Dapil.';
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
                    ? 'onclick="showBlockedModal(); return false;"'
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
                    ? 'onclick="showBlockedModal(); return false;"'
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

<!-- Modal Notifikasi -->
<?php if (current_user()['role'] === 'relawan' && !$bolehAkses): ?>

    <div id="blockedAccountModal" class="success-modal-overlay" style="display:none;">

        <div class="success-modal-card">

            <div class="success-modal-icon" style="background: <?= $modalColor ?>;">
                <i class="<?= $modalIcon ?>"></i>
            </div>

            <h3><?= htmlspecialchars($modalTitle) ?></h3>

            <p><?= htmlspecialchars($modalMessage) ?></p>

            <button
                type="button"
                class="success-modal-button"
                onclick="closeBlockedModal()">
                OK
            </button>

        </div>

    </div>

    <script>
        function showBlockedModal() {

            document.getElementById('blockedAccountModal').style.display = 'flex';

        }

        function closeBlockedModal() {

            document.getElementById('blockedAccountModal').style.display = 'none';

        }
    </script>

<?php endif; ?>