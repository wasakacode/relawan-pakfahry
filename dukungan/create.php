<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin', 'relawan']);
require_profile_complete($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (!empty($_POST['wa_sama_telepon'])) {
            $_POST['nomor_whatsapp'] = $_POST['nomor_telepon'] ?? null;
        }
        $profileId = create_profile($pdo, 'dukungan', null);

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
            $_POST['provinsi'] ?? null,
            $_POST['kab_kota'] ?? null,
            $_POST['kecamatan'] ?? null,
            $_POST['desa_kelurahan'] ?? null,
            $_POST['tps'] ?? null
        ]);

        $tpsId = $stmt->fetchColumn();

        if (!$tpsId) {
            throw new Exception('TPS tidak ditemukan.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO profiles_tps (
                profile_id,
                tps_id
            ) VALUES (?, ?)
        ");

        $stmt->execute([
            $profileId,
            $tpsId
        ]);

        flash(
            'success',
            'Data dukungan berhasil disimpan. Dukungan tidak dibuatkan akun login.'
        );

        redirect('dukungan/list.php');
        exit;

    } catch (Exception $e) {
        flash(
            'error',
            'Gagal menyimpan dukungan. ' . $e->getMessage()
        );
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';

?>

<h1 class="h3 mb-4 text-gray-800">
    Tambah Data Dukungan
</h1>

<form method="POST" enctype="multipart/form-data">

    <?php include __DIR__ . '/../partials/form-fields.php'; ?>

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Langkah 5 - Dokumentasi
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">

                <label>
                    Foto KTP
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="file"
                    name="foto_ktp"
                    class="form-control-file"
                    accept=".pdf,.jpg,.jpeg,.png,image/*"
                    required>

                <small class="text-danger">
                    Wajib upload file PDF atau gambar JPG, JPEG, atau PNG.
                </small>

            </div>

            <div class="form-group col-md-4">

                <label>
                    Foto Diri
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="file"
                    name="foto_diri"
                    class="form-control-file"
                    accept=".pdf,.jpg,.jpeg,.png,image/*"
                    required>

                <small class="text-danger">
                    Wajib upload file PDF atau gambar JPG, JPEG, atau PNG.
                </small>

            </div>

            <div class="form-group col-md-4">

                <label>
                    Foto Kartu Keluarga
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="file"
                    name="foto_kartu_keluarga"
                    class="form-control-file"
                    accept=".pdf,.jpg,.jpeg,.png,image/*"
                    required>

                <small class="text-danger">
                    Wajib upload file PDF atau gambar JPG, JPEG, atau PNG.
                </small>

            </div>

        </div>
    </div>

    <button type="submit" class="btn btn-primary mb-4">
        <i class="fas fa-save"></i>
        Simpan Dukungan
    </button>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const nomorTelepon = document.getElementById('nomor_telepon');
    const nomorWhatsApp = document.getElementById('nomor_whatsapp');
    const waSamaTelepon = document.getElementById('wa_sama_telepon');

    if (!nomorTelepon || !nomorWhatsApp || !waSamaTelepon) {
        return;
    }

    function aturNomorWhatsApp() {

        if (waSamaTelepon.checked) {

            nomorWhatsApp.value = nomorTelepon.value;
            nomorWhatsApp.readOnly = true;

        } else {

            nomorWhatsApp.readOnly = false;
        }
    }

    waSamaTelepon.addEventListener('change', function () {
        aturNomorWhatsApp();
    });

    nomorTelepon.addEventListener('input', function () {

        if (waSamaTelepon.checked) {
            nomorWhatsApp.value = nomorTelepon.value;
        }
    });

    aturNomorWhatsApp();
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>