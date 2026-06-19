<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

$user = current_user();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi    = $_POST['konfirmasi'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();

    if (!password_verify($password_lama, $dbUser['password'])) {

        $error = 'Password lama tidak sesuai.';

    } elseif ($password_baru !== $konfirmasi) {

        $error = 'Konfirmasi password tidak cocok.';

    } elseif (strlen($password_baru) < 6) {

        $error = 'Password minimal 6 karakter.';

    } else {

        $hash = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $stmt->execute([$hash, $user['id']]);

        $success = 'Password berhasil diperbarui.';
    }
}
?>

<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Ubah Password</h5>
    </div>

    <div class="card-body">
        <?php if ($error): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Password Lama</label>
                <div class="input-group">
                <input type="password"
                       name="password_lama"
                       id="password_lama"
                       class="form-control"
                       required>

                       <div class="input-group-append">
                            <button type="button"
                                class="btn btn-outline-secondary toggle-password"
                                data-target="password_lama">
                            <i class="fas fa-eye"></i>
                        </button>
                       </div>
                </div>
            </div>

            <div class="form-group">
                <label>Password Baru</label>
                <div class="input-group">
                        <input type="password"
                       name="password_baru"
                       id="password_baru"
                       class="form-control"
                       required>

                       <div class="input-group-append">
                            <button type="button"
                                    class="btn btn-outline-secondary toggle-password"
                                    data-target="password_baru">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                </div>      
            </div>

            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <div class="input-group">
                        <input type="password"
                       name="konfirmasi"
                       id="konfirmasi"
                       class="form-control"
                       required>

                       <div class="input-group-append">
                            <button type="button"
                                    class="btn btn-outline-secondary toggle-password"
                                    data-target="konfirmasi">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Simpan Password
            </button>

        </form>

    </div>
</div>
<script>
document.querySelectorAll('.toggle-password').forEach(function(btn){

    btn.addEventListener('click', function(){

        const target = document.getElementById(
            this.dataset.target
        );

        const icon = this.querySelector('i');

        if(target.type === 'password'){
            target.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }

    });

});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>