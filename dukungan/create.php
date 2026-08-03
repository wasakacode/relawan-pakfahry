<?php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin', 'relawan']);
require_profile_complete($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!empty($_POST['wa_sama_telepon'])) {
            $_POST['nomor_whatsapp'] =
                $_POST['nomor_telepon'] ?? null;
        }

        $nik = trim($_POST['nik'] ?? '');

        if ($nik === '') {
            throw new Exception('NIK wajib diisi.');
        }

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            throw new Exception(
                'NIK harus terdiri dari 16 digit angka.'
            );
        }

        $cekNik = $pdo->prepare("
            SELECT id
            FROM profiles
            WHERE nik = ?
            LIMIT 1
        ");

        $cekNik->execute([$nik]);

        if ($cekNik->fetchColumn()) {
            throw new Exception(
                'NIK sudah terdaftar. Gunakan NIK yang berbeda.'
            );
        }

        $requiredFiles = [
            'foto_ktp' => 'Foto KTP',
            'foto_diri' => 'Foto Diri',
            'foto_kartu_keluarga' => 'Foto Kartu Keluarga'
        ];

        foreach ($requiredFiles as $field => $label) {
            if (
                !isset($_FILES[$field]) ||
                $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
            ) {
                throw new Exception(
                    $label . ' wajib diunggah.'
                );
            }

            if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception(
                    'Terjadi kesalahan saat mengunggah ' . $label . '.'
                );
            }

            $extension = strtolower(
                pathinfo(
                    $_FILES[$field]['name'],
                    PATHINFO_EXTENSION
                )
            );

            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'pdf'
            ];

            if (!in_array($extension, $allowedExtensions, true)) {
                throw new Exception(
                    $label .
                    ' harus berupa JPG, JPEG, PNG, atau PDF.'
                );
            }

            if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
                throw new Exception(
                    'Ukuran ' . $label . ' maksimal 5 MB.'
                );
            }
        }

        $provinsi = trim($_POST['provinsi'] ?? '');
        $kabKota = trim($_POST['kab_kota'] ?? '');
        $kecamatan = trim($_POST['kecamatan'] ?? '');
        $desaKelurahan = trim($_POST['desa_kelurahan'] ?? '');
        $nomorTps = trim($_POST['tps'] ?? '');

        if (
            $provinsi === '' ||
            $kabKota === '' ||
            $kecamatan === '' ||
            $desaKelurahan === '' ||
            $nomorTps === ''
        ) {
            throw new Exception(
                'Data wilayah dan TPS wajib diisi lengkap.'
            );
        }

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
            $provinsi,
            $kabKota,
            $kecamatan,
            $desaKelurahan,
            $nomorTps
        ]);

        $tpsId = $stmt->fetchColumn();

        if (!$tpsId) {
            throw new Exception(
                'TPS tidak ditemukan. Periksa kembali data wilayah dan nomor TPS.'
            );
        }

        $pdo->beginTransaction();
        $profileId = create_profile(
            $pdo,
            'dukungan',
            null
        );

        if (!$profileId) {
            throw new Exception(
                'Data profile dukungan gagal dibuat.'
            );
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

        $pdo->commit();

        flash(
            'success',
            'Data dukungan berhasil disimpan. Dukungan tidak dibuatkan akun login.'
        );

        redirect('dukungan/list.php');
        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

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

        <div class="card-body">

            <div class="row">

                <!-- Foto KTP -->
                <div class="form-group col-md-4">

                    <label for="foto_ktp">
                        Foto KTP
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="file"
                        name="foto_ktp"
                        id="foto_ktp"
                        class="form-control-file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        required>

                    <small class="form-text text-danger">
                        Wajib mengunggah JPG, JPEG, PNG, atau PDF.
                        Maksimal 5 MB.
                    </small>

                </div>

                <!-- Foto Diri -->
                <div class="form-group col-md-4">

                    <label for="foto_diri">
                        Foto Diri
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="file"
                        name="foto_diri"
                        id="foto_diri"
                        class="form-control-file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        required>

                    <small class="form-text text-danger">
                        Wajib mengunggah JPG, JPEG, PNG, atau PDF.
                        Maksimal 5 MB.
                    </small>

                </div>

                <!-- Foto Kartu Keluarga -->
                <div class="form-group col-md-4">

                    <label for="foto_kartu_keluarga">
                        Foto Kartu Keluarga
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="file"
                        name="foto_kartu_keluarga"
                        id="foto_kartu_keluarga"
                        class="form-control-file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        required>

                    <small class="form-text text-danger">
                        Wajib mengunggah JPG, JPEG, PNG, atau PDF.
                        Maksimal 5 MB.
                    </small>

                </div>

            </div>

        </div>
    </div>

    <button
        type="submit"
        class="btn btn-primary mb-4">

        <i class="fas fa-save"></i>
        Simpan Dukungan

    </button>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const nomorTelepon =
        document.getElementById('nomor_telepon');

    const nomorWhatsApp =
        document.getElementById('nomor_whatsapp');

    const waSamaTelepon =
        document.getElementById('wa_sama_telepon');

    if (
        !nomorTelepon ||
        !nomorWhatsApp ||
        !waSamaTelepon
    ) {
        return;
    }

    function aturNomorWhatsApp() {

        if (waSamaTelepon.checked) {
            nomorWhatsApp.value = nomorTelepon.value;
            nomorWhatsApp.readOnly = true;
            nomorWhatsApp.classList.add('bg-light');
        } else {
            nomorWhatsApp.readOnly = false;
            nomorWhatsApp.classList.remove('bg-light');
        }
    }

    waSamaTelepon.addEventListener(
        'change',
        aturNomorWhatsApp
    );

    nomorTelepon.addEventListener('input', function () {

        nomorTelepon.value =
            nomorTelepon.value.replace(/[^0-9]/g, '');

        if (waSamaTelepon.checked) {
            nomorWhatsApp.value = nomorTelepon.value;
        }
    });

    nomorWhatsApp.addEventListener('input', function () {
        nomorWhatsApp.value =
            nomorWhatsApp.value.replace(/[^0-9]/g, '');
    });

    aturNomorWhatsApp();
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>