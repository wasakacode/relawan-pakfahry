<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

$id = $_GET['id'] ?? null;

if (!$id) {
    flash('error', 'Data dukungan tidak ditemukan.');
    redirect('dukungan/list.php');
}

if (current_user()['role'] === 'relawan') {
    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE id = ? 
                           AND type = 'dukungan'
                           AND created_by = ?
                           LIMIT 1");
    $stmt->execute([$id, current_user()['id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE id = ? 
                           AND type = 'dukungan'
                           LIMIT 1");
    $stmt->execute([$id]);
}

$data = $stmt->fetch();
$familyStmt = $pdo->prepare("
    SELECT *
    FROM family_members
    WHERE profile_id = ?
    ORDER BY id ASC
");
$familyStmt->execute([$id]);

$familyMembers = $familyStmt->fetchAll(PDO::FETCH_ASSOC);


if (!$data) {
    flash('error', 'Data dukungan tidak ditemukan atau Anda tidak memiliki akses.');
    redirect('dukungan/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // VALIDASI FORMAT
        $errors = [];

        if (!empty($_POST['nik']) && !preg_match('/^[0-9]{16}$/', $_POST['nik'])) {
            $errors[] = 'NIK harus terdiri dari 16 digit angka';
        }

        if (!empty($_POST['nomor_kk']) && !preg_match('/^[0-9]{16}$/', $_POST['nomor_kk'])) {
            $errors[] = 'Nomor KK harus terdiri dari 16 digit angka';
        }

        if (!empty($_POST['rt']) && !preg_match('/^[0-9]{3}$/', $_POST['rt'])) {
            $errors[] = 'RT harus terdiri dari 3 digit angka';
        }

        if (!empty($_POST['rw']) && !preg_match('/^[0-9]{3}$/', $_POST['rw'])) {
            $errors[] = 'RW harus terdiri dari 3 digit angka';
        }

        if (!empty($_POST['tps']) && !preg_match('/^[0-9]{3}$/', $_POST['tps'])) {
            $errors[] = 'TPS harus terdiri dari 3 digit angka';
        }

        if (!empty($_POST['keluarga_nik'])) {
            foreach ($_POST['keluarga_nik'] as $i => $nik) {

                if (!empty($nik) && !preg_match('/^[0-9]{16}$/', $nik)) {
                    $errors[] = 'NIK Anggota Keluarga #' . ($i + 1) . ' harus terdiri dari 16 digit angka';
                }
            }
        }

        if (!empty($errors)) {
            flash(
                'error',
                'Kolom berikut tidak sesuai format: ' . implode(', ', $errors)
            );

            redirect('dukungan/edit.php?id=' . $data['id']);
            exit;
        }

        $stmtUpdate = $pdo->prepare("UPDATE profiles SET
            nik = ?,
            nama_lengkap = ?,
            tempat_lahir = ?,
            tanggal_lahir = ?,
            jenis_kelamin = ?,
            golongan_darah = ?,
            status_pernikahan = ?,
            agama = ?,
            pekerjaan = ?,
            alamat = ?,
            provinsi = ?,
            kab_kota = ?,
            kecamatan = ?,
            desa_kelurahan = ?,
            rt = ?,
            rw = ?,
            tps = ?,
            nomor_kk = ?,
            nomor_telepon = ?,
            nomor_whatsapp = ?,
            status_verifikasi = ?
            WHERE id = ?
        ");

        $stmtUpdate->execute([
            $_POST['nik'],
            $_POST['nama_lengkap'],
            $_POST['tempat_lahir'] ?: null,
            $_POST['tanggal_lahir'] ?: null,
            $_POST['jenis_kelamin'] ?: null,
            $_POST['golongan_darah'] ?: null,
            $_POST['status_pernikahan'] ?: null,
            $_POST['agama'] ?: null,
            $_POST['pekerjaan'] ?: null,
            $_POST['alamat'] ?: null,
            $_POST['provinsi'] ?: null,
            $_POST['kab_kota'] ?: null,
            $_POST['kecamatan'] ?: null,
            $_POST['desa_kelurahan'] ?: null,
            $_POST['rt'] ?: null,
            $_POST['rw'] ?: null,
            $_POST['tps'] ?: null,
            $_POST['nomor_kk'] ?: null,
            $_POST['nomor_telepon'] ?: null,
            $_POST['nomor_whatsapp'] ?: null,
            $_POST['status_verifikasi'] ?: 'terdaftar',
            $data['id']
        ]);
        $deleteFamily = $pdo->prepare("
            DELETE FROM family_members
            WHERE profile_id = ?
        ");
        $deleteFamily->execute([$data['id']]);

        $insertFamily = $pdo->prepare("
            INSERT INTO family_members (
                profile_id,
                hubungan_keluarga,
                nik,
                nama_lengkap,
                tempat_lahir,
                tanggal_lahir,
                jenis_kelamin,
                agama,
                pekerjaan
            )
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        // lanjut simpan ke database

        if (!empty($_POST['keluarga_nik'])) {

            foreach ($_POST['keluarga_nik'] as $i => $nik) {

                if (empty($nik)) {
                    continue;
                }

                $insertFamily->execute([
                    $data['id'],
                    $_POST['keluarga_hubungan_keluarga'][$i] ?? null,
                    $_POST['keluarga_nik'][$i] ?? null,
                    $_POST['keluarga_nama'][$i] ?? null,
                    $_POST['keluarga_tempat_lahir'][$i] ?? null,
                    $_POST['keluarga_tanggal_lahir'][$i] ?? null,
                    $_POST['keluarga_jenis_kelamin'][$i] ?? null,
                    $_POST['keluarga_agama'][$i] ?? null,
                    $_POST['keluarga_pekerjaan'][$i] ?? null
                ]);
            }
        }

        flash('success', 'Data dukungan berhasil diperbarui.');
        redirect('dukungan/detail.php?id=' . $data['id']);
    } catch (Exception $e) {
        flash('error', 'Gagal memperbarui data dukungan: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800 mb-0">Edit Data Dukungan</h1>

    <a href="<?= url('dukungan/detail.php?id=' . $data['id']) ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<form method="POST">

    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-check-circle mr-2" style="color:#3db7ee;"></i>
                Status Data
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Status Verifikasi</label>
                <select name="status_verifikasi" class="form-control">
                    <option value="terdaftar" <?= $data['status_verifikasi'] === 'terdaftar' ? 'selected' : '' ?>>Terdaftar</option>
                    <option value="pending" <?= $data['status_verifikasi'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="ditolak" <?= $data['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
            </div>

        </div>
    </div>


    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-id-card mr-2" style="color:#3db7ee;"></i>
                Data Kependudukan
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">
                <label>NIK</label>
                <input
                    type="text"
                    id="nik"
                    name="nik"
                    class="form-control"
                    value="<?= e($data['nik']) ?>"
                    maxlength="16"
                    required
                    oninput="validasiNIK()">

                <small id="errorNIK" class="text-danger"></small>
            </div>

            <script>
                function validasiNIK() {

                    let input = document.getElementById("nik");
                    let error = document.getElementById("errorNIK");

                    // Hanya boleh angka
                    input.value = input.value.replace(/[^0-9]/g, '');

                    let regex = /^[0-9]{16}$/;

                    if (input.value == "") {
                        error.innerHTML = "";
                        input.classList.remove("is-valid");
                        input.classList.remove("is-invalid");
                    } else if (regex.test(input.value)) {
                        error.innerHTML = "";
                        input.classList.remove("is-invalid");
                        input.classList.add("is-valid");
                    } else {
                        error.innerHTML = "NIK harus terdiri dari 16 digit angka";
                        input.classList.remove("is-valid");
                        input.classList.add("is-invalid");
                    }
                }
            </script>

            <div class="form-group col-md-8">
                <label>Nama Lengkap</label>
                <input name="nama_lengkap" class="form-control" value="<?= e($data['nama_lengkap']) ?>" required>
            </div>

            <div class="form-group col-md-4">
                <label>Tempat Lahir</label>
                <input name="tempat_lahir" class="form-control" value="<?= e($data['tempat_lahir']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control" value="<?= e($data['tanggal_lahir']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                    <option value="">Pilih</option>
                    <option value="Laki-laki" <?= $data['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= $data['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    <option value="laki-laki" <?= $data['jenis_kelamin'] === 'laki-laki' ? 'selected' : '' ?>>laki-laki</option>
                    <option value="perempuan" <?= $data['jenis_kelamin'] === 'perempuan' ? 'selected' : '' ?>>perempuan</option>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label>Golongan Darah</label>
                <select name="golongan_darah" class="form-control">
                    <option value="">Pilih</option>
                    <option value="A" <?= $data['golongan_darah'] === 'A' ? 'selected' : '' ?>>A</option>
                    <option value="B" <?= $data['golongan_darah'] === 'B' ? 'selected' : '' ?>>B</option>
                    <option value="AB" <?= $data['golongan_darah'] === 'AB' ? 'selected' : '' ?>>AB</option>
                    <option value="O" <?= $data['golongan_darah'] === 'O' ? 'selected' : '' ?>>O</option>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label>Status Pernikahan</label>
                <input name="status_pernikahan" class="form-control" value="<?= e($data['status_pernikahan']) ?>">
            </div>

            <div class="form-group col-md-3">
                <label>Agama</label>
                <input name="agama" class="form-control" value="<?= e($data['agama']) ?>">
            </div>

            <div class="form-group col-md-3">
                <label>Pekerjaan</label>
                <input name="pekerjaan" class="form-control" value="<?= e($data['pekerjaan']) ?>">
            </div>

            <div class="form-group col-md-12">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control"><?= e($data['alamat']) ?></textarea>
            </div>

        </div>
    </div>


    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-map-marker-alt mr-2" style="color:#3db7ee;"></i>
                Pemetaan Wilayah
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Provinsi</label>
                <input name="provinsi" class="form-control" value="<?= e($data['provinsi']) ?>">
            </div>

            <div class="form-group col-md-6">
                <label>Kabupaten/Kota</label>
                <input name="kab_kota" class="form-control" value="<?= e($data['kab_kota']) ?>">
            </div>

            <div class="form-group col-md-6">
                <label>Kecamatan</label>
                <input name="kecamatan" class="form-control" value="<?= e($data['kecamatan']) ?>">
            </div>

            <div class="form-group col-md-6">
                <label>Desa/Kelurahan</label>
                <input name="desa_kelurahan" class="form-control" value="<?= e($data['desa_kelurahan']) ?>">
            </div>

            <!-- RT -->
            <div class="form-group col-md-4">
                <label>RT</label>
                <input
                    type="text"
                    id="rt"
                    name="rt"
                    class="form-control"
                    value="<?= e($data['rt'] ?? '') ?>"
                    placeholder="Contoh: 001"
                    maxlength="3"
                    oninput="validasiRT()">

                <small id="errorRT" class="text-danger"></small>
            </div>

            <script>
                function validasiRT() {
                    let input = document.getElementById("rt");
                    let error = document.getElementById("errorRT");

                    // Hanya boleh angka
                    input.value = input.value.replace(/[^0-9]/g, '');

                    let regex = /^[0-9]{3}$/;

                    if (input.value == "") {
                        error.innerHTML = "";
                        input.classList.remove("is-valid");
                        input.classList.remove("is-invalid");
                    } else if (regex.test(input.value)) {
                        error.innerHTML = "";
                        input.classList.remove("is-invalid");
                        input.classList.add("is-valid");
                    } else {
                        error.innerHTML = "RT harus terdiri dari 3 digit angka. Contoh: 001";
                        input.classList.remove("is-valid");
                        input.classList.add("is-invalid");
                    }
                }
            </script>

            <!-- RW -->
            <div class="form-group col-md-4">
                <label>RW</label>
                <input
                    type="text"
                    id="rw"
                    name="rw"
                    class="form-control"
                    value="<?= e($data['rw'] ?? '') ?>"
                    placeholder="Contoh: 001"
                    maxlength="3"
                    oninput="validasiRW()">

                <small id="errorRW" class="text-danger"></small>
            </div>

            <script>
                function validasiRW() {
                    let input = document.getElementById("rw");
                    let error = document.getElementById("errorRW");

                    // Hanya boleh angka
                    input.value = input.value.replace(/[^0-9]/g, '');

                    let regex = /^[0-9]{3}$/;

                    if (input.value == "") {
                        error.innerHTML = "";
                        input.classList.remove("is-valid");
                        input.classList.remove("is-invalid");
                    } else if (regex.test(input.value)) {
                        error.innerHTML = "";
                        input.classList.remove("is-invalid");
                        input.classList.add("is-valid");
                    } else {
                        error.innerHTML = "RW harus terdiri dari 3 digit angka. Contoh: 001";
                        input.classList.remove("is-valid");
                        input.classList.add("is-invalid");
                    }
                }
            </script>

            <!-- TPS -->
            <div class="form-group col-md-4">
                <label>TPS</label>
                <input
                    type="text"
                    id="tps"
                    name="tps"
                    class="form-control"
                    value="<?= e($data['tps'] ?? '') ?>"
                    placeholder="Contoh: 001"
                    maxlength="3"
                    oninput="validasiTPS()">

                <small id="errorTPS" class="text-danger"></small>
            </div>

            <script>
                function validasiTPS() {
                    let input = document.getElementById("tps");
                    let error = document.getElementById("errorTPS");

                    // Hanya boleh angka
                    input.value = input.value.replace(/[^0-9]/g, '');

                    let regex = /^[0-9]{3}$/;

                    if (input.value == "") {
                        error.innerHTML = "";
                        input.classList.remove("is-valid");
                        input.classList.remove("is-invalid");
                    } else if (regex.test(input.value)) {
                        error.innerHTML = "";
                        input.classList.remove("is-invalid");
                        input.classList.add("is-valid");
                    } else {
                        error.innerHTML = "TPS harus terdiri dari 3 digit angka. Contoh: 001";
                        input.classList.remove("is-valid");
                        input.classList.add("is-invalid");
                    }
                }
            </script>

        </div>
    </div>


    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-phone mr-2" style="color:#3db7ee;"></i>
                Kontak dan KK
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">
                <label>Nomor KK</label>
                <input
                    type="text"
                    id="nomor_kk"
                    name="nomor_kk"
                    class="form-control"
                    value="<?= e($data['nomor_kk']) ?>"
                    maxlength="16"
                    oninput="validasiKK()">

                <small id="errorKK" class="text-danger"></small>
            </div>

            <script>
                function validasiKK() {

                    let input = document.getElementById("nomor_kk");
                    let error = document.getElementById("errorKK");

                    // hanya angka
                    input.value = input.value.replace(/[^0-9]/g, '');

                    let regex = /^[0-9]{16}$/;

                    if (input.value == "") {
                        error.innerHTML = "";
                        input.classList.remove("is-valid");
                        input.classList.remove("is-invalid");
                    } else if (regex.test(input.value)) {
                        error.innerHTML = "";
                        input.classList.remove("is-invalid");
                        input.classList.add("is-valid");
                    } else {
                        error.innerHTML = "Nomor KK harus terdiri dari 16 digit angka";
                        input.classList.remove("is-valid");
                        input.classList.add("is-invalid");
                    }
                }
            </script>

            <div class="form-group col-md-4">
                <label>Nomor Telepon</label>
                <input name="nomor_telepon" class="form-control" value="<?= e($data['nomor_telepon']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Nomor WhatsApp</label>
                <input name="nomor_whatsapp" class="form-control" value="<?= e($data['nomor_whatsapp']) ?>">
            </div>

        </div>
    </div>

    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
                Data Anggota Keluarga
            </h6>
        </div>

        <div class="card-body">

            <div id="anggotaKeluargaContainer">

                <?php foreach ($familyMembers as $index => $fam): ?>

                    <div class="border rounded p-3 mb-3 anggota-item">

                        <div class="d-flex justify-content-between mb-3">
                            <h6>Anggota Keluarga <?= $index + 1 ?></h6>

                            <button type="button"
                                class="btn btn-danger btn-sm btnHapus">
                                Hapus
                            </button>
                        </div>

                        <div class="row">

                            <div class="form-group col-md-4">
                                <label>Hubungan Keluarga</label>
                                <select name="keluarga_hubungan_keluarga[]" class="form-control">
                                    <option value="">Pilih Hubungan</option>
                                    <option value="Suami" <?= $fam['hubungan_keluarga'] == 'Suami' ? 'selected' : '' ?>>Suami</option>
                                    <option value="Istri" <?= $fam['hubungan_keluarga'] == 'Istri' ? 'selected' : '' ?>>Istri</option>
                                    <option value="Anak" <?= $fam['hubungan_keluarga'] == 'Anak' ? 'selected' : '' ?>>Anak</option>
                                    <option value="Orang Tua" <?= $fam['hubungan_keluarga'] == 'Orang Tua' ? 'selected' : '' ?>>Orang Tua</option>
                                    <option value="Lainnya" <?= $fam['hubungan_keluarga'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Jenis Kelamin</label>
                                <select
                                    name="keluarga_jenis_kelamin[]"
                                    class="form-control">

                                    <option value="">Pilih</option>

                                    <option value="Laki-laki"
                                        <?= $fam['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>
                                        Laki-laki
                                    </option>

                                    <option value="Perempuan"
                                        <?= $fam['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>
                                        Perempuan
                                    </option>

                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label>NIK</label>
                                <input
                                    type="text"
                                    name="keluarga_nik[]"
                                    class="form-control keluarga-nik"
                                    value="<?= e($fam['nik']) ?>"
                                    maxlength="16"
                                    oninput="validasiNIKKeluarga(this)">

                                <small class="text-danger error-keluarga-nik"></small>
                            </div>

                            <script>
                                function validasiNIKKeluarga(input) {

                                    let error = input.parentElement.querySelector('.error-keluarga-nik');

                                    // hanya angka
                                    input.value = input.value.replace(/[^0-9]/g, '');

                                    if (input.value === "") {
                                        error.innerHTML = "";
                                        input.classList.remove("is-valid");
                                        input.classList.remove("is-invalid");
                                    } else if (input.value.length === 16) {
                                        error.innerHTML = "";
                                        input.classList.remove("is-invalid");
                                        input.classList.add("is-valid");
                                    } else {
                                        error.innerHTML = "NIK harus terdiri dari 16 digit angka";
                                        input.classList.remove("is-valid");
                                        input.classList.add("is-invalid");
                                    }
                                }
                            </script>

                            <div class="form-group col-md-4">
                                <label>Nama</label>
                                <input
                                    name="keluarga_nama[]"
                                    class="form-control"
                                    value="<?= e($fam['nama_lengkap']) ?>">
                            </div>

                            <div class="form-group col-md-4">
                                <label>Tempat Lahir</label>
                                <input
                                    name="keluarga_tempat_lahir[]"
                                    class="form-control"
                                    value="<?= e($fam['tempat_lahir']) ?>">
                            </div>

                            <div class="form-group col-md-4">
                                <label>Tanggal Lahir</label>
                                <input
                                    type="date"
                                    name="keluarga_tanggal_lahir[]"
                                    class="form-control"
                                    value="<?= $fam['tanggal_lahir'] ?>">
                            </div>

                            <div class="form-group col-md-4">
                                <label>Agama</label>
                                <select name="keluarga_agama[]" class="form-control">
                                    <option value="">Pilih Agama</option>
                                    <option value="Islam" <?= $fam['agama'] == 'Islam' ? 'selected' : '' ?>>Islam</option>
                                    <option value="Kristen" <?= $fam['agama'] == 'Kristen' ? 'selected' : '' ?>>Kristen</option>
                                    <option value="Katolik" <?= $fam['agama'] == 'Katolik' ? 'selected' : '' ?>>Katolik</option>
                                    <option value="Hindu" <?= $fam['agama'] == 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                                    <option value="Buddha" <?= $fam['agama'] == 'Buddha' ? 'selected' : '' ?>>Buddha</option>
                                    <option value="Konghucu" <?= $fam['agama'] == 'Konghucu' ? 'selected' : '' ?>>Konghucu</option>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Pekerjaan</label>
                                <input
                                    name="keluarga_pekerjaan[]"
                                    class="form-control"
                                    value="<?= e($fam['pekerjaan']) ?>">
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <button type="button"
                id="btnTambahAnggota"
                class="btn btn-success">
                <i class="fas fa-plus"></i>
                Tambah Anggota Keluarga
            </button>

        </div>
    </div>
    <button type="submit" class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Simpan Perubahan
    </button>

</form>

<script>
    let anggotaIndex = <?= count($familyMembers) ?>;

    document.getElementById('btnTambahAnggota').addEventListener('click', function() {

        anggotaIndex++;

        const html = `
    <div class="border rounded p-3 mb-3 anggota-item">

        <div class="d-flex justify-content-between mb-3">
            <h6>Anggota Keluarga ${anggotaIndex}</h6>

            <button type="button"
                    class="btn btn-danger btn-sm btnHapus">
                Hapus
            </button>
        </div>

        <div class="row">
            <div class="form-group col-md-4">
                <label>Hubungan Keluarga</label>
                <select name="keluarga_hubungan_keluarga[]" class="form-control">
                    <option value="">Pilih Hubungan</option>
                    <option value="Suami">Suami</option>
                    <option value="Istri">Istri</option>
                    <option value="Anak">Anak</option>
                    <option value="Orang Tua">Orang Tua</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="form-group col-md-4">
                <label>Jenis Kelamin</label>
                <select name="keluarga_jenis_kelamin[]" class="form-control">
                    <option value="">Pilih</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>

            <div class="form-group col-md-4">
                <label>NIK</label>
                <input
                type="text"
                name="keluarga_nik[]"
                class="form-control keluarga-nik"
                maxlength="16"
                oninput="validasiNIKKeluarga(this)">

            <small class="text-danger error-keluarga-nik"></small>
            </div>

            <div class="form-group col-md-4">
                <label>Nama</label>
                <input name="keluarga_nama[]" class="form-control">
            </div>

            <div class="form-group col-md-4">
                <label>Tempat Lahir</label>
                <input name="keluarga_tempat_lahir[]" class="form-control">
            </div>

            <div class="form-group col-md-4">
                <label>Tanggal Lahir</label>
                <input type="date" name="keluarga_tanggal_lahir[]" class="form-control">
            </div>

            <div class="form-group col-md-4">
                <label>Agama</label>
                <select name="keluarga_agama[]" class="form-control">
                    <option value="">Pilih Agama</option>
                    <option value="Islam">Islam</option>
                    <option value="Kristen">Kristen</option>
                    <option value="Katolik">Katolik</option>
                    <option value="Hindu">Hindu</option>
                    <option value="Buddha">Buddha</option>
                    <option value="Konghucu">Konghucu</option>
                </select>
            </div>

            <div class="form-group col-md-4">
                <label>Pekerjaan</label>
                <input name="keluarga_pekerjaan[]" class="form-control">
            </div>

        </div>

    </div>`;

        document.getElementById('anggotaKeluargaContainer')
            .insertAdjacentHTML('beforeend', html);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btnHapus')) {
            e.target.closest('.anggota-item').remove();
        }
    });
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>