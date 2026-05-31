<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role('superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users 
            (name, username, password, role, kecamatan) 
            VALUES (?, ?, ?, 'admin', ?)");

        $stmt->execute([
            $_POST['nama_lengkap'],
            $_POST['username'],
            $hash,
            $_POST['kecamatan'] ?: null
        ]);

        $userId = $pdo->lastInsertId();

        create_profile($pdo, 'admin', $userId);

        $pdo->commit();

        flash('success', 'Admin Kecamatan berhasil dibuat.');
        redirect('admin/create-admin.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal membuat admin: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Buat Admin (Koordinator Kecamatan)</h1>

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
        <i class="fas fa-save"></i> Simpan Admin
    </button>

</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>