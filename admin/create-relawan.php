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
                <input type="password" name="password" class="form-control" required>
            </div>

        </div>
    </div>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Simpan Relawan
    </button>

</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>