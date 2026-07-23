<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin', 'relawan']);

require_profile_complete($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $profileId = create_profile($pdo, 'dukungan', null);

            // cari id TPS
            $stmt = $pdo->prepare("
                SELECT id
                FROM tps_kalsel
                WHERE provinsi = ?
                AND kabupaten = ?
                AND kecamatan = ?
                AND kelurahan = ?
                AND no_tps = ?
                LIMIT 1
            ");

            $stmt->execute([
                $_POST['provinsi'],
                $_POST['kab_kota'],
                $_POST['kecamatan'],
                $_POST['desa_kelurahan'],
                $_POST['tps']
            ]);

            $tpsId = $stmt->fetchColumn();

            if (!$tpsId) {
                throw new Exception('TPS tidak ditemukan.');
            }

            // simpan relasi
            $stmt = $pdo->prepare("
                INSERT INTO profiles_tps
                (profile_id, tps_id)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $profileId,
                $tpsId
            ]);

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