<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users 
            (name, username, password, role, kecamatan) 
            VALUES (?, ?, ?, 'relawan', ?)");

        $stmt->execute([
            $_POST['nama_lengkap'],
            $_POST['username'],
            $hash,
            $_POST['kecamatan'] ?: null
        ]);

        $userId = $pdo->lastInsertId();

        create_profile($pdo, 'relawan', $userId);

        $pdo->commit();

        flash('success', 'Akun relawan berhasil dibuat.');
        redirect('admin/list-relawan.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal membuat relawan: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Buat Akun Relawan</h1>

<form method="POST" enctype="multipart/form-data">

    <?php include __DIR__ . '/../partials/form-fields.php'; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Langkah 6 - Informasi Akun</h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <div class="form-group col-md-6">
                <label>Password</label>

                <div class="input-group">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required>

                    <div class="input-group-append">
                        <button
                            class="btn btn-outline-secondary"
                            type="button"
                            id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <button class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Simpan Relawan
    </button>

</form>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {

    const password = document.getElementById('password');
    const icon = this.querySelector('i');

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }

});
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>