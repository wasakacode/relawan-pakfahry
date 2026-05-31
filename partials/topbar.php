<div id="content-wrapper" class="d-flex flex-column">

<div id="content">

    <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
        </button>

        <div class="d-none d-sm-inline-block mr-auto ml-md-3 my-2 my-md-0">
            <div style="font-weight: 800; color: #1f3b57;">
                <?= APP_NAME ?>
            </div>
            <small style="color: #7890a6;">
                Dashboard pengelolaan relawan dan dukungan
            </small>
        </div>

        <ul class="navbar-nav ml-auto">

            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                    <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                        <?= e(current_user()['name']) ?> 
                        |
                        <?= e(ucfirst(current_user()['role'])) ?>
                    </span>

                    <i class="fas fa-user-circle fa-2x" style="color:#3db7ee;"></i>
                </a>

                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                     aria-labelledby="userDropdown">

                    <a class="dropdown-item" href="<?= url('dashboard/index.php') ?>">
                        <i class="fas fa-home fa-sm fa-fw mr-2 text-gray-400"></i>
                        Dashboard
                    </a>

                    <a class="dropdown-item" href="<?= url('auth/reset_password.php') ?>">
                        <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                        Reset Password
                    </a>

                    <div class="dropdown-divider"></div>

                    <a class="dropdown-item" href="<?= url('logout.php') ?>">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Logout
                    </a>

                </div>
            </li>

        </ul>

    </nav>

    <div class="container-fluid">

        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-danger">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>