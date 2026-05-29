<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

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
            <i class="fas fa-home"></i>
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
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/create-admin.php') ?>">
                <i class="fas fa-user-shield"></i>
                <span>Buat Admin</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/create-relawan.php') ?>">
                <i class="fas fa-user-plus"></i>
                <span>Buat Relawan</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin', 'relawan'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('dukungan/create.php') ?>">
                <i class="fas fa-hand-holding-heart"></i>
                <span>Tambah Dukungan</span>
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
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-users.php') ?>">
                <i class="fas fa-users"></i>
                <span>Data Users</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (current_user()['role'] === 'superadmin'): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-admin.php') ?>">
                <i class="fas fa-users-cog"></i>
                <span>Data Admin</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('admin/list-relawan.php') ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Data Relawan</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if (in_array(current_user()['role'], ['superadmin', 'admin', 'relawan'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= url('dukungan/list.php') ?>">
                <i class="fas fa-address-book"></i>
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
            <a class="nav-link" href="<?= url('relawan/profil.php') ?>">
                <i class="fas fa-id-card"></i>
                <span>Profil Saya</span>
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
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>