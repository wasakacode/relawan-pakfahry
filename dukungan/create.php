<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin', 'relawan']);

require_profile_complete($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        create_profile($pdo, 'dukungan', null);

        flash('success', 'Data dukungan berhasil disimpan. Dukungan tidak dibuatkan akun login.');
        redirect('dukungan/list.php');

    } catch (Exception $e) {
        flash('error', 'Gagal menyimpan dukungan. ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Tambah Data Dukungan</h1>

<form method="POST" enctype="multipart/form-data">

    <?php include __DIR__ . '/../partials/form-fields.php'; ?>

    <button class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Simpan Dukungan
    </button>

</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>