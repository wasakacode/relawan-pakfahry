<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role([
    'superadmin',
    'admin',
    'relawan'
]);



if (current_user()['role'] === 'relawan') {

    // Relawan hanya boleh mengedit profil miliknya sendiri
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            u.username,
            u.is_active,
            u.id AS akun_id
        FROM profiles p
        LEFT JOIN users u
            ON p.user_id = u.id
        WHERE
            p.user_id = ?
            AND p.type = 'relawan'
        LIMIT 1
    ");

    $stmt->execute([
        current_user()['id']
    ]);
} else {

    // Admin & Superadmin menggunakan parameter id
    $id = $_GET['id'] ?? null;

    if (!$id) {
        flash('error', 'Data relawan tidak ditemukan.');
        redirect('admin/list-relawan.php');
    }

    if (current_user()['role'] === 'admin') {

    // ambil profile admin yang login
    $stmtAdmin = $pdo->prepare("
        SELECT id
        FROM profiles
        WHERE user_id = ?
        AND type='admin'
        LIMIT 1
    ");

    $stmtAdmin->execute([
        current_user()['id']
    ]);

    $adminProfileId = $stmtAdmin->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            u.username,
            u.is_active,
            u.id AS akun_id
        FROM profiles p
        LEFT JOIN users u
            ON p.user_id = u.id
        INNER JOIN profile_admin pa
            ON pa.profile_id = p.id
        WHERE
            p.id = ?
            AND p.type = 'relawan'
            AND pa.admin_profile_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $id,
        $adminProfileId
    ]);
} else {

        $stmt = $pdo->prepare("
            SELECT
                p.*,
                u.username,
                u.is_active,
                u.id AS akun_id
            FROM profiles p
            LEFT JOIN users u
                ON p.user_id = u.id
            WHERE
                p.id = ?
                AND p.type = 'relawan'
            LIMIT 1
        ");

        $stmt->execute([$id]);
    }
}


$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {

    flash(
        'error',
        'Data relawan tidak ditemukan atau Anda tidak memiliki akses.'
    );

    if (current_user()['role'] === 'relawan') {
        redirect('dashboard/index.php');
    } else {
        redirect('admin/list-relawan.php');
    }
}

$id = $data['id'];

$familyStmt = $pdo->prepare("
    SELECT *
    FROM family_members
    WHERE profile_id = ?
    ORDER BY id ASC
");

$familyStmt->execute([$id]);

$familyMembers = $familyStmt->fetchAll(PDO::FETCH_ASSOC);

$stmtAdmin = $pdo->query("
    SELECT
        p.id,
        p.nama_lengkap,
        GROUP_CONCAT(
            d.daerah_pemilihan
            ORDER BY d.daerah_pemilihan
            SEPARATOR ', '
        ) AS dapil
    FROM profiles p
    LEFT JOIN profile_dapil pd
        ON pd.profile_id = p.id
    LEFT JOIN dapil d
        ON d.id = pd.dapil_id
    WHERE
        p.type='admin'
        AND p.profile_active=1
    GROUP BY
        p.id,
        p.nama_lengkap
    ORDER BY
        p.nama_lengkap
");

$adminList = $stmtAdmin->fetchAll(PDO::FETCH_ASSOC);

$stmtAdmin = $pdo->prepare("
    SELECT admin_profile_id
    FROM profile_admin
    WHERE profile_id = ?
");

$stmtAdmin->execute([$id]);

$selectedAdmin = $stmtAdmin->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction(); 
        
        if (!empty($_POST['wa_sama_telepon'])) {
            $_POST['nomor_whatsapp'] =
                $_POST['nomor_telepon'] ?? null;
        }
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

                    $errors[] =
                        'NIK Anggota Keluarga #' .
                        ($i + 1) .
                        ' harus terdiri dari 16 digit angka';
                }
            }
        }

        if (!empty($errors)) {

            $pdo->rollBack();

            flash(
                'error',
                "Kolom berikut tidak sesuai format:\n• " .
                    implode("\n• ", $errors)
            );

            if (current_user()['role'] === 'relawan') {

                redirect('admin/edit-relawan.php');
            } else {

                redirect('admin/edit-relawan.php?id=' . $data['id']);
            }
        }

        $username      = trim($_POST['username'] ?? '');
        $passwordBaru  = trim($_POST['password'] ?? '');

        if (current_user()['role'] !== 'relawan') {

            $isActive = isset($_POST['is_active']) ? 1 : 0;
        } else {

            $isActive = $data['is_active'];
        }

        $stmtCekUsername = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            AND id != ?
            LIMIT 1
        ");

        $stmtCekUsername->execute([
            $username,
            $data['akun_id']
        ]);

        if ($stmtCekUsername->fetch()) {

            $pdo->rollBack();

            flash(
                'error',
                'Username sudah digunakan oleh akun lain.'
            );

            if (current_user()['role'] === 'relawan') {

                redirect('admin/edit-relawan.php');
            } else {

                redirect('admin/edit-relawan.php?id=' . $data['id']);
            }
        }

        if ($passwordBaru !== '') {

            $hash = password_hash(
                $passwordBaru,
                PASSWORD_DEFAULT
            );

            $stmtUser = $pdo->prepare("
                UPDATE users
                SET
                    username = ?,
                    password = ?,
                    is_active = ?,
                    name = ?,
                    kecamatan = ?
                WHERE id = ?
            ");

            $stmtUser->execute([
                $username,
                $hash,
                $isActive,
                $_POST['nama_lengkap'],
                $_POST['kecamatan'],
                $data['akun_id']
            ]);
        } else {

            $stmtUser = $pdo->prepare("
                UPDATE users
                SET
                    username = ?,
                    is_active = ?,
                    name = ?,
                    kecamatan = ?
                WHERE id = ?
            ");

            $stmtUser->execute([
                $username,
                $isActive,
                $_POST['nama_lengkap'],
                $_POST['kecamatan'],
                $data['akun_id']
            ]);
        }

        $profileComplete = $data['profile_complete'];
        $statusVerifikasi = $data['status_verifikasi'];
        $catatanVerifikasi = $data['catatan_verifikasi'];

        if (current_user()['role'] === 'relawan') {

            $profileComplete = '1';
            $statusVerifikasi = 'pending';
            // Hapus alasan verifikasi lama
            $catatanVerifikasi = null;
        }

        $stmtProfile = $pdo->prepare("
            UPDATE profiles SET
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
                profile_complete = ?,
                status_verifikasi = ?,
                catatan_verifikasi = ?
            WHERE id = ?
        ");

        $stmtProfile->execute([
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
            $profileComplete,
            $statusVerifikasi,
            $catatanVerifikasi,
            $data['id']
        ]);

        $deleteFamily = $pdo->prepare("
            DELETE FROM family_members
            WHERE profile_id = ?
        ");

        $deleteFamily->execute([$data['id']]);

        $insertFamily = $pdo->prepare("
            INSERT INTO family_members
            (
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

        $foto_ktp = $data['foto_ktp'];

        if (!empty($_FILES['foto_ktp']['name'])) {

            // upload file baru
            $foto_ktp = upload_file('foto_ktp', 'ktp');

            // hapus file lama
            if (
                $foto_ktp &&
                !empty($data['foto_ktp']) &&
                file_exists(__DIR__ . '/../' . $data['foto_ktp'])
            ) {
                unlink(__DIR__ . '/../' . $data['foto_ktp']);
            }
        }

        $foto_diri = $data['foto_diri'];

        if (!empty($_FILES['foto_diri']['name'])) {

            $foto_diri = upload_file('foto_diri', 'diri');

            if (
                $foto_diri &&
                !empty($data['foto_diri']) &&
                file_exists(__DIR__ . '/../' . $data['foto_diri'])
            ) {
                unlink(__DIR__ . '/../' . $data['foto_diri']);
            }
        }

        $foto_kartu_keluarga = $data['foto_kartu_keluarga'];

        if (!empty($_FILES['foto_kartu_keluarga']['name'])) {

            $foto_kartu_keluarga = upload_file('foto_kartu_keluarga', 'kk');

            if (
                $foto_kartu_keluarga &&
                !empty($data['foto_kartu_keluarga']) &&
                file_exists(__DIR__ . '/../' . $data['foto_kartu_keluarga'])
            ) {
                unlink(__DIR__ . '/../' . $data['foto_kartu_keluarga']);
            }
        }

        $stmtfoto = $pdo->prepare("
                UPDATE profiles SET
                    foto_ktp = ?,
                    foto_diri = ?,
                    foto_kartu_keluarga = ?
                WHERE id = ?
                ");

        $stmtfoto->execute([
            $foto_ktp,
            $foto_diri,
            $foto_kartu_keluarga,
            $id
        ]);

        if (current_user()['role'] !== 'relawan') {

            $stmtAdmin = $pdo->prepare("
                DELETE FROM profile_admin
                WHERE profile_id = ?
            ");

            $stmtAdmin->execute([
                $data['id']
            ]);

            if (!empty($_POST['admin_id'])) {

                $stmtAdmin = $pdo->prepare("
                    INSERT INTO profile_admin
                    (
                        profile_id,
                        admin_profile_id
                    )
                    VALUES (?,?)
                ");

                foreach ($_POST['admin_id'] as $adminId) {

                    $stmtAdmin->execute([
                        $data['id'],
                        $adminId
                    ]);
                }
            }
        }
        $pdo->commit();

        if (current_user()['role'] === 'relawan') {

            flash(
                'success',
                'Profil berhasil diperbarui. Data Anda telah dikirim kembali dan sedang menunggu verifikasi Admin.'
            );
        } else {

            flash(
                'success',
                'Data relawan berhasil diperbarui.'
            );
        }

        if (current_user()['role'] === 'relawan') {

            redirect('admin/detail-relawan.php');
        } else {

            redirect('admin/detail-relawan.php?id=' . $data['id']);
        }
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            'error',
            'Gagal memperbarui data: ' . $e->getMessage()
        );

        if (current_user()['role'] === 'relawan') {

            redirect('admin/edit-relawan.php');
        } else {

            redirect('admin/edit-relawan.php?id=' . $data['id']);
        }
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800 mb-0">Edit Data Relawan</h1>

    <a href="<?= url('admin/detail-relawan.php?id=' . $data['id']) ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<form method="POST" enctype="multipart/form-data">

    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-user-lock mr-2" style="color:#3db7ee;"></i>
                Data Akun
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= e($data['username']) ?>" required>
            </div>

            <div class="form-group col-md-6">
                <label>Password Baru</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                <small class="text-muted">Password lama tidak dapat ditampilkan. Isi kolom ini hanya jika ingin mengganti password.</small>
            </div>

            <?php if (current_user()['role'] != 'relawan'): ?>
                <div class="form-group col-md-6">
                    <label>Status Akun</label><br>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" name="is_active" class="custom-control-input" id="is_active"
                            <?= ((int)$data['is_active'] === 1) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="is_active">Akun Aktif</label>
                    </div>
                </div>
            <?php endif; ?>

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
                <select name="status_pernikahan" class="form-control">
                    <option value="">Pilih</option>

                    <option value="Belum Menikah"
                        <?= ($data['status_pernikahan'] == 'Belum Menikah') ? 'selected' : '' ?>>
                        Belum Menikah
                    </option>

                    <option value="Sudah Menikah"
                        <?= ($data['status_pernikahan'] == 'Sudah Menikah') ? 'selected' : '' ?>>
                        Sudah Menikah
                    </option>

                    <option value="Pernah Menikah"
                        <?= ($data['status_pernikahan'] == 'Pernah Menikah') ? 'selected' : '' ?>>
                        Pernah Menikah
                    </option>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label>Agama</label>
                <select name="agama" class="form-control">
                    <option value="">Pilih</option>

                    <option value="Islam"
                        <?= ($data['agama'] == 'Islam') ? 'selected' : '' ?>>
                        Islam
                    </option>

                    <option value="Kristen Protestan"
                        <?= ($data['agama'] == 'Kristen Protestan') ? 'selected' : '' ?>>
                        Kristen Protestan
                    </option>

                    <option value="Katolik"
                        <?= ($data['agama'] == 'Katolik') ? 'selected' : '' ?>>
                        Katolik
                    </option>

                    <option value="Hindu"
                        <?= ($data['agama'] == 'Hindu') ? 'selected' : '' ?>>
                        Hindu
                    </option>

                    <option value="Budha"
                        <?= ($data['agama'] == 'Budha') ? 'selected' : '' ?>>
                        Budha
                    </option>

                    <option value="Konghuchu"
                        <?= ($data['agama'] == 'Konghuchu') ? 'selected' : '' ?>>
                        Konghuchu
                    </option>
                </select>
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
                <select
                    id="provinsi"
                    name="provinsi"
                    class="form-control"

                    data-selected="<?= e($data['provinsi']) ?>">

                    <option value="">Memuat Provinsi...</option>

                </select>
            </div>

            <div class="form-group col-md-6">
                <label>Kabupaten/Kota</label>
                <select
                    id="kab_kota"
                    name="kab_kota"
                    class="form-control"

                    data-selected="<?= e($data['kab_kota']) ?>">

                    <option value="">Pilih Provinsi dahulu</option>

                </select>
            </div>

            <div class="form-group col-md-6">
                <label>Kecamatan</label>
                <select
                    id="kecamatan"
                    name="kecamatan"
                    class="form-control"

                    data-selected="<?= e($data['kecamatan']) ?>">

                    <option value="">Pilih Kabupaten dahulu</option>

                </select>
            </div>

            <div class="form-group col-md-6">
                <label>Desa/Kelurahan</label>
                <select
                    id="desa_kelurahan"
                    name="desa_kelurahan"
                    class="form-control"

                    data-selected="<?= e($data['desa_kelurahan']) ?>">

                    <option value="">Pilih Kecamatan dahulu</option>

                </select>
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


    <?php

$nomorTeleponSaatIni = $data['nomor_telepon'] ?? '';
$nomorWhatsAppSaatIni = $data['nomor_whatsapp'] ?? '';

$waSamaTelepon =
    $nomorTeleponSaatIni !== '' &&
    $nomorTeleponSaatIni === $nomorWhatsAppSaatIni;

?>

<div class="card content-card shadow mb-4">

    <div class="card-header">
        <h6 class="m-0 font-weight-bold">
            <i
                class="fas fa-phone mr-2"
                style="color:#3db7ee;">
            </i>

            Kontak
        </h6>
    </div>

    <div class="card-body">

        <div class="row">

            <!-- Nomor Telepon -->
            <div class="form-group col-md-6">

                <label for="nomor_telepon">
                    Nomor Telepon
                </label>

                <input
                    type="tel"
                    name="nomor_telepon"
                    id="nomor_telepon"
                    class="form-control"
                    value="<?= e($nomorTeleponSaatIni) ?>"
                    placeholder="Contoh: 081234567890"
                    inputmode="numeric"
                    maxlength="15">

                <!-- Checkbox di bawah nomor telepon -->
                <div class="custom-control custom-checkbox mt-3">

                    <input
                        type="checkbox"
                        name="wa_sama_telepon"
                        id="wa_sama_telepon"
                        class="custom-control-input"
                        value="1"
                        <?= $waSamaTelepon ? 'checked' : '' ?>>

                    <label
                        class="custom-control-label"
                        for="wa_sama_telepon">

                        Nomor WhatsApp sama dengan nomor telepon

                    </label>

                </div>

            </div>

            <!-- Nomor WhatsApp -->
            <div class="form-group col-md-6">

                <label for="nomor_whatsapp">
                    Nomor WhatsApp
                </label>

                <input
                    type="tel"
                    name="nomor_whatsapp"
                    id="nomor_whatsapp"
                    class="form-control"
                    value="<?= e($nomorWhatsAppSaatIni) ?>"
                    placeholder="Contoh: 081234567890"
                    inputmode="numeric"
                    maxlength="15">

                <small class="form-text text-muted mt-2">
                    Centang pilihan di sebelah kiri jika nomor WhatsApp sama.
                </small>

            </div>

        </div>

    </div>

</div>

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

    function hanyaAngka(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
    }

    function sinkronkanWhatsApp() {

        if (waSamaTelepon.checked) {

            nomorWhatsApp.value = nomorTelepon.value;
            nomorWhatsApp.readOnly = true;
            nomorWhatsApp.classList.add('bg-light');

        } else {

            nomorWhatsApp.readOnly = false;
            nomorWhatsApp.classList.remove('bg-light');
        }
    }

    nomorTelepon.addEventListener('input', function () {

        hanyaAngka(nomorTelepon);

        if (waSamaTelepon.checked) {
            nomorWhatsApp.value = nomorTelepon.value;
        }
    });

    nomorWhatsApp.addEventListener('input', function () {
        hanyaAngka(nomorWhatsApp);
    });

    waSamaTelepon.addEventListener('change', function () {
        sinkronkanWhatsApp();
    });

    sinkronkanWhatsApp();

});
</script>

    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
                Data Anggota Keluarga
            </h6>
        </div>

        <div class="card-body">

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

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Admin yang menaungi
            </h6>
        </div>

        <div class="card-body row">

            <?php foreach ($adminList as $admin): ?>

                <div class="col-md-6 mb-3">

                    <div class="custom-control custom-checkbox">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="admin_<?= $admin['id'] ?>"
                            name="admin_id[]"
                            value="<?= $admin['id'] ?>"

                            <?= in_array($admin['id'], $selectedAdmin) ? 'checked' : '' ?>>

                        <label
                            class="custom-control-label"
                            for="admin_<?= $admin['id'] ?>">

                            <strong><?= e($admin['nama_lengkap']) ?></strong>

                            <br>

                            <small class="text-muted">
                                <?= e($admin['dapil']) ?>
                            </small>

                        </label>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Dokumentasi</h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">
                <label>Foto KTP <span class="text-danger">*</span></label>
                <?php if (!empty($data['foto_ktp'])): ?>
                    <img src="../<?= e($data['foto_ktp']) ?>"
                        class="img-thumbnail"
                        style="max-height:180px;">
                <?php endif; ?>
                <input type="file"
                    name="foto_ktp"
                    class="form-control-file"
                    accept=".pdf,image/*">
                <small class="text-muted">
                    note : Kosongkan jika tidak ingin mengganti.
                </small>
            </div>

            <div class="form-group col-md-4">
                <label>Foto Diri <span class="text-danger">*</span></label>
                <?php if (!empty($data['foto_diri'])): ?>
                    <img src="../<?= e($data['foto_diri']) ?>"
                        class="img-thumbnail"
                        style="max-height:180px;">
                <?php endif; ?>
                <input type="file"
                    name="foto_diri"
                    class="form-control-file"
                    accept=".pdf,image/*">
                <small class="text-muted">
                    note : Kosongkan jika tidak ingin mengganti.
                </small>
            </div>

            <!-- Role Relawan -->
            <div class="form-group col-md-4">
                <label>Foto Kartu Keluarga <span class="text-danger">*</span></label>
                <?php if (!empty($data['foto_kartu_keluarga'])): ?>
                    <img src="../<?= e($data['foto_kartu_keluarga']) ?>"
                        class="img-thumbnail"
                        style="max-height:180px;">
                <?php endif; ?>
                <input type="file"
                    name="foto_kartu_keluarga"
                    class="form-control-file"
                    accept=".pdf,image/*">
                <small class="text-muted">
                    note : Kosongkan jika tidak ingin mengganti.
                </small>
            </div>

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const provinsiSelect = document.getElementById('provinsi');
        const kabKotaSelect = document.getElementById('kab_kota');
        const kecamatanSelect = document.getElementById('kecamatan');
        const desaSelect = document.getElementById('desa_kelurahan');

        const API_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        function resetSelect(select, text) {
            select.innerHTML = `<option value="">${text}</option>`;
        }

        function setLoading(select, text = 'Memuat data...') {
            select.innerHTML = `<option value="">${text}</option>`;
        }

        async function fetchWilayah(url) {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Gagal mengambil data wilayah');
            }

            return await response.json();
        }

        async function loadProvinsi() {
            try {
                setLoading(provinsiSelect, 'Memuat data provinsi...');

                const data = await fetchWilayah(`${API_URL}/provinces.json`);

                provinsiSelect.innerHTML = '<option value="">Pilih Provinsi</option>';

                const selectedProvinsi = provinsiSelect.dataset.selected;

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    if (item.name === selectedProvinsi) {

                        option.selected = true;

                        loadKabKota(item.id);

                    }

                    provinsiSelect.appendChild(option);

                });

            } catch (error) {
                resetSelect(provinsiSelect, 'Gagal memuat provinsi');
                console.error(error);
            }
        }

        async function loadKabKota(provinsiId) {
            try {
                setLoading(kabKotaSelect, 'Memuat kabupaten/kota...');
                resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
                resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

                const data = await fetchWilayah(`${API_URL}/regencies/${provinsiId}.json`);

                kabKotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';

                const selectedKab = kabKotaSelect.dataset.selected;

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    if (item.name === selectedKab) {

                        option.selected = true;

                        loadKecamatan(item.id);

                    }

                    kabKotaSelect.appendChild(option);

                });

            } catch (error) {
                resetSelect(kabKotaSelect, 'Gagal memuat kabupaten/kota');
                console.error(error);
            }
        }

        async function loadKecamatan(kabKotaId) {
            try {
                setLoading(kecamatanSelect, 'Memuat kecamatan...');
                resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

                const data = await fetchWilayah(`${API_URL}/districts/${kabKotaId}.json`);

                kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';

                const selectedKecamatan = kecamatanSelect.dataset.selected;

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    if (item.name === selectedKecamatan) {

                        option.selected = true;

                        loadDesa(item.id);

                    }

                    kecamatanSelect.appendChild(option);

                });

            } catch (error) {
                resetSelect(kecamatanSelect, 'Gagal memuat kecamatan');
                console.error(error);
            }
        }

        async function loadDesa(kecamatanId) {
            try {
                setLoading(desaSelect, 'Memuat desa/kelurahan...');

                const data = await fetchWilayah(`${API_URL}/villages/${kecamatanId}.json`);

                desaSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';

                const selectedDesa = desaSelect.dataset.selected;

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;

                    if (item.name === selectedDesa) {

                        option.selected = true;

                    }

                    desaSelect.appendChild(option);

                });

            } catch (error) {
                resetSelect(desaSelect, 'Gagal memuat desa/kelurahan');
                console.error(error);
            }
        }

        provinsiSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const provinsiId = selected.dataset.id;

            resetSelect(kabKotaSelect, 'Pilih provinsi terlebih dahulu');
            resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            if (provinsiId) {
                loadKabKota(provinsiId);
            }
        });

        kabKotaSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const kabKotaId = selected.dataset.id;

            resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            if (kabKotaId) {
                loadKecamatan(kabKotaId);
            }
        });

        kecamatanSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const kecamatanId = selected.dataset.id;

            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            if (kecamatanId) {
                loadDesa(kecamatanId);
            }
        });

        loadProvinsi();
    });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>