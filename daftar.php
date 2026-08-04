<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

/*
|--------------------------------------------------------------------------
| JIKA SUDAH LOGIN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user'])) {

    redirect('dashboard/index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function input_value($name)
{
    return e($_POST[$name] ?? '');
}

/*
|--------------------------------------------------------------------------
| VARIABEL
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| HELPER UPLOAD FILE
|--------------------------------------------------------------------------
*/

function uploadFile($inputName, $folder)
{

    if (
        !isset($_FILES[$inputName]) ||
        $_FILES[$inputName]['error'] == UPLOAD_ERR_NO_FILE
    ) {

        throw new Exception("{$inputName} wajib diupload");
    }

    if ($_FILES[$inputName]['error'] != UPLOAD_ERR_OK) {

        throw new Exception("Gagal upload file {$inputName}");
    }

    $ext = strtolower(
        pathinfo(
            $_FILES[$inputName]['name'],
            PATHINFO_EXTENSION
        )
    );

    $allowed = [
        'jpg',
        'jpeg',
        'png',
        'pdf'
    ];

    if (!in_array($ext, $allowed)) {

        throw new Exception(
            "Format file {$inputName} tidak didukung."
        );
    }

    $filename = uniqid() . "." . $ext;

    $destination =
        __DIR__ .
        "/uploads/{$folder}/" .
        $filename;

    if (
        !move_uploaded_file(
            $_FILES[$inputName]['tmp_name'],
            $destination
        )
    ) {

        throw new Exception(
            "Gagal menyimpan file {$inputName}"
        );
    }

    return $filename;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA DAPIL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        provinsi,
        kab_kota
    FROM dapil
");

$dapilData = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $provinsi = trim($row['provinsi']);

    if (!isset($dapilData[$provinsi])) {

        $dapilData[$provinsi] = [];
    }

    $kabupaten = json_decode(
        $row['kab_kota'],
        true
    );

    if (is_array($kabupaten)) {

        foreach ($kabupaten as $kab) {

            $kab = trim($kab);

            if (!in_array($kab, $dapilData[$provinsi])) {

                $dapilData[$provinsi][] = $kab;
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA ADMIN DAPIL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.nama_lengkap,
        d.provinsi,
        d.kab_kota,
        d.daerah_pemilihan

    FROM profiles p

    JOIN profile_dapil pd
        ON pd.profile_id = p.id

    JOIN dapil d
        ON d.id = pd.dapil_id

    WHERE
        p.type = 'admin'
        AND p.profile_active = 1

    ORDER BY
        p.nama_lengkap
");

$adminList = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $row['kab_kota'] = json_decode(
        $row['kab_kota'],
        true
    );

    $adminList[] = $row;
}

/*
|--------------------------------------------------------------------------
| PROSES SUBMIT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();

        /*
|--------------------------------------------------------------------------
| AMBIL DATA FORM
|--------------------------------------------------------------------------
*/

        $nik                = trim($_POST['nik'] ?? '');
        $namaLengkap        = trim($_POST['nama_lengkap'] ?? '');

        $tempatLahir        = trim($_POST['tempat_lahir'] ?? '');
        $tanggalLahir       = trim($_POST['tanggal_lahir'] ?? '');

        $jenisKelamin       = trim($_POST['jenis_kelamin'] ?? '');
        $golonganDarah      = trim($_POST['golongan_darah'] ?? '');

        $agama              = trim($_POST['agama'] ?? '');
        $statusPernikahan   = trim($_POST['status_pernikahan'] ?? '');

        $pekerjaan          = trim($_POST['pekerjaan'] ?? '');
        $alamat             = trim($_POST['alamat'] ?? '');

        $provinsi           = trim($_POST['provinsi'] ?? '');
        $kabKota            = trim($_POST['kab_kota'] ?? '');
        $kecamatan          = trim($_POST['kecamatan'] ?? '');
        $desaKelurahan      = trim($_POST['desa_kelurahan'] ?? '');

        $rt                 = trim($_POST['rt'] ?? '');
        $rw                 = trim($_POST['rw'] ?? '');
        $tps                = trim($_POST['tps'] ?? '');

        $nomorKK            = trim($_POST['nomor_kk'] ?? '');

        $nomorHP            = trim($_POST['nomor_telepon'] ?? '');
        $nomorWA            = trim($_POST['nomor_whatsapp'] ?? '');

        $username           = trim($_POST['username'] ?? '');

        $password           = $_POST['password'] ?? '';
        $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

        $adminId            = $_POST['admin_id'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            throw new Exception('NIK harus terdiri dari 16 digit.');
        }

        if (strlen($namaLengkap) < 3) {
            throw new Exception('Nama lengkap minimal 3 karakter.');
        }

        if (empty($tempatLahir)) {
            throw new Exception('Tempat lahir wajib diisi.');
        }

        if (empty($tanggalLahir)) {
            throw new Exception('Tanggal lahir wajib diisi.');
        }

        if (empty($jenisKelamin)) {
            throw new Exception('Jenis kelamin wajib dipilih.');
        }

        if (empty($agama)) {
            throw new Exception('Agama wajib dipilih.');
        }

        if (empty($statusPernikahan)) {
            throw new Exception('Status pernikahan wajib dipilih.');
        }

        if (empty($alamat)) {
            throw new Exception('Alamat wajib diisi.');
        }

        if (empty($provinsi)) {
            throw new Exception('Provinsi wajib dipilih.');
        }

        if (empty($kabKota)) {
            throw new Exception('Kabupaten/Kota wajib dipilih.');
        }

        if (empty($kecamatan)) {
            throw new Exception('Kecamatan wajib dipilih.');
        }

        if (empty($desaKelurahan)) {
            throw new Exception('Desa/Kelurahan wajib dipilih.');
        }

        if (!preg_match('/^[0-9]{3}$/', $rt)) {
            throw new Exception('RT harus terdiri dari 3 digit.');
        }

        if (!preg_match('/^[0-9]{3}$/', $rw)) {
            throw new Exception('RW harus terdiri dari 3 digit.');
        }

        if (!preg_match('/^[0-9]{3}$/', $tps)) {
            throw new Exception('TPS harus terdiri dari 3 digit.');
        }

        if (!preg_match('/^[0-9]{16}$/', $nomorKK)) {
            throw new Exception('Nomor KK harus terdiri dari 16 digit.');
        }

        if (!preg_match('/^[0-9]{10,15}$/', $nomorHP)) {
            throw new Exception('Nomor Handphone tidak valid.');
        }

        if (!preg_match('/^[0-9]{10,15}$/', $nomorWA)) {
            throw new Exception('Nomor WhatsApp tidak valid.');
        }

        if (strlen($username) < 4) {
            throw new Exception('Username minimal 4 karakter.');
        }

        if (!preg_match('/^[A-Za-z0-9._]+$/', $username)) {
            throw new Exception('Username hanya boleh huruf, angka, titik (.) dan underscore (_).');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter.');
        }

        if ($password !== $konfirmasiPassword) {
            throw new Exception('Konfirmasi password tidak sesuai.');
        }

        if (empty($adminId)) {
            throw new Exception('Pilih minimal satu Admin Dapil.');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK NIK
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE nik = ?
        ");

        $stmt->execute([$nik]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('NIK sudah terdaftar.');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK USERNAME
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE username = ?
        ");

        $stmt->execute([$username]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Username sudah digunakan.');
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD
        |--------------------------------------------------------------------------
        */

        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter.');
        }

        if ($password !== $konfirmasiPassword) {
            throw new Exception('Konfirmasi password tidak sesuai.');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK NIK
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE nik = ?
        ");

        $stmt->execute([$nik]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('NIK sudah terdaftar.');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK USERNAME
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE username = ?
        ");

        $stmt->execute([$username]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Username sudah digunakan.');
        }

        /*
        |--------------------------------------------------------------------------
        | UPLOAD FILE
        |--------------------------------------------------------------------------
        */

        $fotoKTP  = uploadFile('foto_ktp', 'ktp');
        $fotoKK   = uploadFile('foto_kartu_keluarga', 'kk');
        $fotoDiri = uploadFile('foto_diri', 'diri');

        /*
        |--------------------------------------------------------------------------
        | SIMPAN USER
        |--------------------------------------------------------------------------
        */

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            INSERT INTO users
            (
                name,
                username,
                password,
                role,
                is_active
            )
            VALUES
            (
                ?, ?, ?, 'relawan', 0
            )
        ");

        $stmt->execute([
            $namaLengkap,
            $username,
            $passwordHash
        ]);

        $userId = $pdo->lastInsertId();

        /*
        |--------------------------------------------------------------------------
        | SIMPAN PROFILE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO profiles
            (
                user_id,
                type,
                created_by,

                nik,
                nama_lengkap,

                tempat_lahir,
                tanggal_lahir,

                jenis_kelamin,
                golongan_darah,

                agama,
                status_pernikahan,

                pekerjaan,
                alamat,

                provinsi,
                kab_kota,
                kecamatan,
                desa_kelurahan,

                rt,
                rw,
                tps,

                nomor_kk,
                nomor_telepon,
                nomor_whatsapp,

                foto_ktp,
                foto_kartu_keluarga,
                foto_diri,

                status_verifikasi,
                profile_active,
                profile_complete
            )
            VALUES
            (
                ?, ?, ?,

                ?, ?,

                ?, ?,

                ?, ?,

                ?, ?,

                ?, ?,

                ?, ?, ?, ?,

                ?, ?, ?,

                ?, ?, ?,

                ?, ?, ?,

                'pending',
                1,
                1
            )
        ");

        $stmt->execute([
            $userId,
            'relawan',
            $userId,

            $nik,
            $namaLengkap,

            $tempatLahir,
            $tanggalLahir,

            $jenisKelamin,
            $golonganDarah,

            $agama,
            $statusPernikahan,

            $pekerjaan,
            $alamat,

            $provinsi,
            $kabKota,
            $kecamatan,
            $desaKelurahan,

            $rt,
            $rw,
            $tps,

            $nomorKK,
            $nomorHP,
            $nomorWA,

            $fotoKTP,
            $fotoKK,
            $fotoDiri
        ]);


        /*
        |--------------------------------------------------------------------------
        | SIMPAN ADMIN YANG DIPILIH
        |--------------------------------------------------------------------------
        */
        $profileId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO profile_admin
            (
                profile_id,
                admin_profile_id
            )
            VALUES
            (?, ?)
        ");

        foreach ($adminId as $admin) {

            $stmt->execute([
                $profileId,
                $admin
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        $_SESSION['success'] =
            "Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi Admin Dapil.";

        redirect('login.php');

        exit;
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();

        // sementara untuk debugging
        // echo "<pre>".$e->getMessage()."</pre>";

    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar - <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #c8efff, #86d7f5, #4bb6e8);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 30px;
        }

        /* ===========================
        WRAPPER
        =========================== */

        .signup-wrapper {
            width: 1100px;
            max-width: 95%;
            min-height: 750px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 0;
            background: rgba(255, 255, 255, .18);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(20, 80, 120, .25);
        }

        /* ===========================
        BACKGROUND SHAPE
        =========================== */

        .shape-left {
            position: absolute;
            left: -60px;
            bottom: -70px;
            width: 340px;
            height: 260px;
            background: #77ccef;
            border-radius: 0 180px 0 0;
        }

        .shape-bottom {
            position: absolute;
            left: 170px;
            bottom: 105px;
            width: 370px;
            height: 90px;
            background: #fff;
            border-radius: 22px;
            transform: skewY(-8deg);
        }

        .shape-right {
            position: absolute;
            right: -110px;
            top: 120px;
            width: 410px;
            height: 320px;
            background: linear-gradient(135deg, #fff 0%, #fff 45%, #4eb7e7 46%, #229cda 100%);
            border-radius: 180px 0 0 180px;
        }

        .shape-top {
            position: absolute;
            top: -70px;
            left: 330px;
            width: 240px;
            height: 150px;
            background: #fff;
            border-radius: 0 0 120px 120px;
            transform: rotate(-8deg);
        }

        /* ===========================
        CARD
        =========================== */

        .signup-card {
            position: relative;
            width: 700px;
            max-width: 95%;
            min-height: 760px;
            background: #fff;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 22px 55px rgba(18, 83, 130, .20);
            z-index: 5;
        }

        .signup-card h2 {
            text-align: center;
            color: #1f3b57;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .signup-card p {
            text-align: center;
            color: #8a9bad;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 28px;
        }

        /* ===========================
        PROGRESS BAR
        =========================== */

        .progress {
            height: 8px;
            border-radius: 20px;
            overflow: hidden;
            background: #e9ecef;
            margin-bottom: 25px;
        }

        .progress-bar {
            width: 25%;
            height: 100%;
            background: linear-gradient(90deg, #3db7ee, #118dd0);
            transition: .35s;
        }

        /* ===========================
        STEP INDICATOR
        =========================== */

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
        }

        .step-circle {
            flex: 1;
            position: relative;
            text-align: center;
        }

        .step-circle:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 20px;
            left: 60%;
            width: 80%;
            height: 3px;
            background: #dfe6ec;
            z-index: 0;
        }

        .step-circle span {
            width: 42px;
            height: 42px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background: #dfe6ec;
            color: #6c757d;
            font-weight: bold;
            position: relative;
            z-index: 2;
            transition: .3s;
        }

        .step-circle.active span {
            background: #118dd0;
            color: #fff;
        }

        .step-circle.done span {
            background: #2ecc71;
            color: #fff;
        }

        .step-circle small {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: #6c757d;
        }

        /* ===========================
       STEP CONTENT
    =========================== */

        .step {
            display: none;
            animation: fade .35s ease;
        }

        .step.active {
            display: block;
        }

        @keyframes fade {
            from {
                opacity: 0;
                transform: translateX(25px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ===========================
        FORM
        =========================== */

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #324a5f;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            height: 50px;
            border: none;
            outline: none;
            border-radius: 12px;
            background: #f3f8fc;
            padding: 0 16px;
            font-size: 14px;
            transition: .25s;
        }

        textarea.form-control {
            min-height: 100px;
            padding-top: 12px;
            resize: vertical;
        }

        .form-control:focus {
            background: #eef8ff;
            box-shadow: 0 0 0 3px rgba(95, 190, 235, .25);
        }

        /* Nomor KK */

        .kk-header {

            display: flex;
            gap: 15px;
            align-items: center;

        }

        .kk-header .form-control {

            flex: 1;

        }

        .btn-tambah {

            min-width: 220px;
            height: 50px;

            border: none;
            border-radius: 12px;

            background: #28a745;
            color: #fff;

            font-weight: 600;

            cursor: pointer;

            transition: .25s;

        }

        .btn-tambah:hover {

            background: #218838;

        }

        /* ======================================
        KELUARGA
        ====================================== */

        .anggota-card {

            background: #f8fbff;

            border: 1px solid #d8e8f4;

            border-radius: 16px;

            padding: 20px;

            margin-top: 20px;

            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);

        }

        .anggota-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

            padding-bottom: 12px;

            border-bottom: 1px solid #e5eef6;

        }

        .anggota-title {

            display: flex;

            align-items: center;

            gap: 10px;

            font-weight: 700;

            color: #234;

            font-size: 16px;

        }

        .anggota-title i {

            width: 36px;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #3db7ee;

            color: #fff;

        }

        .btnHapus {

            border: none;

            background: #dc3545;

            color: #fff;

            width: 38px;

            height: 38px;

            border-radius: 10px;

            cursor: pointer;

            transition: .2s;

        }

        .btnHapus:hover {

            background: #bb2d3b;

            transform: scale(1.05);

        }

        /* ===================================
        CHECKBOX
        =================================== */

        .custom-check {

            display: flex;

            align-items: center;

            margin-top: 14px;

        }

        .custom-check .form-check-input {

            width: 18px;

            height: 18px;

            margin-top: 0;

            margin-right: 10px;

            cursor: pointer;

        }

        .custom-check .form-check-label {

            font-size: 14px;

            color: #42566b;

            cursor: pointer;

            user-select: none;

            line-height: 1.5;

        }

        /* ===============================
   ADMIN GRID
================================ */

        .admin-grid {

            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));

            gap: 15px;

        }

        /* ===============================
        ADMIN CARD
        ================================ */

        .admin-card {

            position: relative;

            border: 2px solid #e5edf5;

            border-radius: 15px;

            padding: 18px;

            cursor: pointer;

            transition: .25s;

            background: #fff;

        }

        .admin-card:hover {

            border-color: #3db7ee;

            transform: translateY(-2px);

            box-shadow: 0 10px 22px rgba(61, 183, 238, .12);

        }

        .admin-card input {

            position: absolute;

            top: 15px;

            right: 15px;

            width: 18px;

            height: 18px;

        }

        .admin-name {

            font-size: 16px;

            font-weight: 700;

            color: #29445d;

            margin-bottom: 8px;

        }

        .admin-dapil {

            display: inline-block;

            background: #eef8ff;

            color: #118dd0;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }

        /* ======================================
        UPLOAD CARD
        ====================================== */

        .upload-card {

            background: #f8fbff;

            border: 1px solid #d9e8f4;

            border-radius: 18px;

            padding: 25px;

            text-align: center;

            height: 100%;

            transition: .25s;

        }

        .upload-card:hover {

            border-color: #3db7ee;

            transform: translateY(-3px);

            box-shadow: 0 10px 25px rgba(61, 183, 238, .15);

        }

        .upload-icon {

            width: 65px;
            height: 65px;

            margin: auto auto 15px;

            border-radius: 50%;

            background: #e8f6fd;

            display: flex;

            justify-content: center;

            align-items: center;

            font-size: 28px;

            color: #2da8e2;

        }

        .upload-card h6 {

            font-weight: 700;

            color: #22384d;

            margin-bottom: 8px;

        }

        .upload-card p {

            font-size: 13px;

            color: #7a8b9c;

            margin-bottom: 18px;

        }

        .upload-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 10px 18px;

            border-radius: 10px;

            background: #3db7ee;

            color: #fff;

            cursor: pointer;

            transition: .2s;

            font-weight: 600;

        }

        .upload-btn:hover {

            background: #259ad1;

        }

        /* ===========================
        PASSWORD BUTTON
        =========================== */

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 40px;
            border: none;
            background: none;
            cursor: pointer;
            color: #9fb4c7;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #118dd0;
        }

        /* ===========================
        VALIDATION
        =========================== */

        .text-danger {
            display: block;
            margin-top: 6px;
            color: #e74c3c;
            font-size: 12px;
        }

        .is-valid {
            border: 2px solid #2ecc71 !important;
        }

        .is-invalid {
            border: 2px solid #e74c3c !important;
        }

        /* ===========================
        ALERT
        =========================== */

        .alert {
            background: #ffe8e8;
            color: #c0392b;
            border-radius: 12px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* ===========================
        BUTTON WIZARD
        =========================== */

        .wizard-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 35px;
        }

        #prevBtn,
        #nextBtn,
        #submitBtn {
            height: 50px;
            border: none;
            border-radius: 12px;
            padding: 0 28px;
            font-weight: 700;
            cursor: pointer;
            transition: .25s;
        }

        #prevBtn {
            background: #e9ecef;
            color: #555;
        }

        #prevBtn:hover {
            background: #d6d8db;
        }

        #nextBtn,
        #submitBtn {
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            color: white;
            box-shadow: 0 12px 24px rgba(17, 141, 208, .25);
        }

        #nextBtn:hover,
        #submitBtn:hover {
            transform: translateY(-2px);
        }

        #submitBtn {
            display: none;
        }

        /* ===========================
        LOGIN LINK
        =========================== */

        .signup-text {
            margin: 25px 0 12px;
            text-align: center;
            color: #8a9bad;
            font-size: 13px;
        }

        .btn-secondary {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            width: 100%;
            height: 48px;
            border: 2px solid #118dd0;
            border-radius: 12px;
            color: #118dd0;
            background: #fff;
            text-decoration: none;
            font-weight: 700;
            transition: .25s;
        }

        .btn-secondary:hover {
            background: #118dd0;
            color: #fff;
            text-decoration: none;
        }

        /* ===========================
        RESPONSIVE
        =========================== */

        @media (max-width:768px) {

            body {
                padding: 20px;
            }

            .signup-wrapper {
                width: 100%;
                min-height: 100vh;
                border-radius: 20px;
            }

            .signup-card {
                width: 100%;
                min-height: auto;
                padding: 25px;
            }

            .step-indicator small {
                font-size: 10px;
            }

            .step-circle span {
                width: 34px;
                height: 34px;
            }

            .wizard-buttons {
                flex-direction: column;
            }

            #prevBtn,
            #nextBtn,
            #submitBtn {
                width: 100%;
            }

            .shape-bottom {
                display: none;
            }

            .shape-left {
                width: 250px;
                height: 180px;
            }

            .shape-right {
                width: 250px;
                height: 220px;
            }
        }
    </style>

</head>

<body>

    <div class="signup-wrapper">

        <div class="shape-left"></div>
        <div class="shape-bottom"></div>
        <div class="shape-right"></div>
        <div class="shape-top"></div>

        <div class="signup-card">

            <h2>DAFTAR RELAWAN</h2>
            <p>
                Buat akun terlebih dahulu untuk bergabung
                sebagai relawan.
            </p>

            <?php if ($error): ?>
                <div class="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" enctype="multipart/form-data">

                <!-- =========================
                    WIZARD HEADER
                ========================== -->

                <div class="wizard-header">

                    <div class="progress">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>

                    <div class="step-indicator">

                        <div class="step-circle active">
                            <span><i class="fas fa-id-card"></i></span>
                            <small>Identitas</small>
                        </div>

                        <div class="step-circle">
                            <span><i class="fas fa-map-marker-alt"></i></span>
                            <small>Wilayah</small>
                        </div>

                        <div class="step-circle">
                            <span><i class="fas fa-users"></i></span>
                            <small>Keluarga</small>
                        </div>

                        <div class="step-circle">
                            <span><i class="fas fa-phone-alt"></i></span>
                            <small>Kontak</small>
                        </div>

                        <div class="step-circle">
                            <span><i class="fas fa-folder-open"></i></span>
                            <small>Dokumen</small>
                        </div>

                        <div class="step-circle">
                            <span><i class="fas fa-user-lock"></i></span>
                            <small>Akun</small>
                        </div>

                    </div>

                </div>

                <!-- =========================
                        STEP 1
                ========================== -->

                <div class="step active">

                    <h4 class="step-title">
                        Data Kependudukan
                    </h4>

                    <div class="row">

                        <div class="form-group col-md-4">
                            <label>NIK</label>

                            <input
                                type="text"
                                class="form-control"
                                name="nik"
                                id="nik"
                                maxlength="16"
                                placeholder="Masukkan NIK"
                                oninput="validasiNIK()"
                                required>

                            <small id="errorNIK" class="text-danger"></small>
                        </div>

                        <div class="form-group col-md-8">
                            <label>Nama Lengkap</label>

                            <input
                                type="text"
                                class="form-control"
                                name="nama_lengkap"
                                id="nama_lengkap"
                                placeholder="Masukkan Nama Lengkap"
                                required>

                        </div>

                        <div class="form-group col-md-4">
                            <label>Tempat Lahir</label>

                            <input
                                type="text"
                                class="form-control"
                                name="tempat_lahir"
                                id="tempat_lahir"
                                placeholder="Masukkan Tempat Lahir"
                                required>

                        </div>

                        <div class="form-group col-md-4">
                            <label>Tanggal Lahir</label>

                            <input
                                type="date"
                                class="form-control"
                                name="tanggal_lahir"
                                id="tanggal_lahir"
                                required>

                        </div>

                        <div class="form-group col-md-4">

                            <label>Jenis Kelamin</label>

                            <select
                                class="form-control"
                                name="jenis_kelamin"
                                id="jenis_kelamin"
                                required>

                                <option value="">Pilih</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Golongan Darah</label>

                            <select class="form-control" name="golongan_darah" id="golongan_darah" required>

                                <option value="">Pilih</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Status Pernikahan</label>

                            <select class="form-control" name="status_pernikahan" id="status_pernikahan" required>

                                <option value="Belum Menikah">Belum Menikah</option>
                                <option value="Sudah Menikah">Sudah Menikah</option>
                                <option value="Pernah Menikah">Pernah Menikah</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Agama</label>

                            <select class="form-control" name="agama" id="agama" required>

                                <option value="">Pilih</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen Protestan">Kristen Protestan</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Budha">Budha</option>
                                <option value="Konghuchu">Konghuchu</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Pekerjaan</label>

                            <input
                                class="form-control"
                                name="pekerjaan"
                                id="pekerjaan"
                                placeholder="Masukkan Pekerjaan"
                                required>

                        </div>

                        <div class="form-group col-md-12">

                            <label>Alamat</label>

                            <textarea
                                class="form-control"
                                name="alamat"
                                id="alamat"
                                placeholder="Masukkan Alamat"
                                required>
                            </textarea>

                        </div>

                    </div>

                </div>

                <!-- =========================
                        STEP 2
                ========================== -->

                <div class="step">

                    <h4 class="step-title">
                        Pemetaan Wilayah
                    </h4>

                    <div class="row">

                        <div class="form-group col-md-6">

                            <label>Provinsi</label>

                            <select
                                name="provinsi"
                                id="provinsi"
                                class="form-control"
                                required>

                                <option value="">Memuat data provinsi...</option>

                            </select>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Kabupaten / Kota</label>

                            <select
                                name="kab_kota"
                                id="kab_kota"
                                class="form-control"
                                required>

                                <option value="">
                                    Pilih provinsi terlebih dahulu
                                </option>

                            </select>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Kecamatan</label>

                            <select
                                name="kecamatan"
                                id="kecamatan"
                                class="form-control"
                                required>

                                <option value="">
                                    Pilih kabupaten/kota terlebih dahulu
                                </option>

                            </select>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Desa / Kelurahan</label>

                            <select
                                name="desa_kelurahan"
                                id="desa_kelurahan"
                                class="form-control"
                                required>

                                <option value="">
                                    Pilih kecamatan terlebih dahulu
                                </option>

                            </select>

                        </div>

                        <div class="form-group col-md-4">

                            <label>RT</label>

                            <input
                                type="text"
                                name="rt"
                                id="rt"
                                maxlength="3"
                                class="form-control"
                                placeholder="Contoh: 001, 015, 100"
                                oninput="validasiRT()"
                                required>

                            <small id="pesanRT" class="text-danger"></small>
                        </div>

                        <div class="form-group col-md-4">

                            <label>RW</label>

                            <input
                                type="text"
                                name="rw"
                                id="rw"
                                maxlength="3"
                                class="form-control"
                                placeholder="Contoh: 001, 015, 100"
                                oninput="validasiRW()"
                                required>

                            <small id="pesanRW" class="text-danger"></small>
                        </div>

                        <div class="form-group col-md-4">

                            <label>TPS</label>

                            <input
                                type="text"
                                name="tps"
                                id="tps"
                                maxlength="3"
                                class="form-control"
                                placeholder="Contoh: 001, 015, 100"
                                oninput="validasiTPS()"
                                required>

                            <small id="pesanTPS" class="text-danger"></small>
                        </div>


                        <div class="form-group col-md-12">

                            <label>Pilih Admin Dapil</label>

                            <div id="adminContainer" class="admin-grid">

                                <div class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Pilih kabupaten terlebih dahulu.
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

                <!-- =========================
                        STEP 3
                ========================== -->

                <div class="step">

                    <h4 class="step-title">
                        Keluarga
                    </h4>

                    <div class="row">

                        <div class="form-group col-md-12">

                            <label>Nomor KK</label>

                            <div class="kk-header">

                                <input
                                    type="text"
                                    name="nomor_kk"
                                    id="nomor_kk"
                                    maxlength="16"
                                    class="form-control"
                                    placeholder="Masukkan Nomor KK"
                                    oninput="validasiKK()"
                                    required>

                                <button
                                    type="button"
                                    id="btnTambahAnggota"
                                    class="btn-tambah">

                                    <i class="fas fa-plus"></i>
                                    Tambah Anggota

                                </button>

                            </div>

                            <small
                                id="errorKK"
                                class="text-danger">
                            </small>

                        </div>

                        <div class="form-group col-md-12">

                            <div id="anggotaContainer"></div>

                        </div>

                    </div>

                </div>

                <!-- =========================
                        STEP 4
                ========================== -->

                <div class="step">

                    <h4 class="step-title">
                        Informasi Kontak
                    </h4>

                    <div class="row">

                        <div class="form-group col-md-6">

                            <label>Nomor Handphone</label>

                            <input
                                type="text"
                                name="nomor_telepon"
                                id="nomor_telepon"
                                class="form-control"
                                placeholder="08xxxxxxxxxx"
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                required>

                            <!-- Checkbox di bawah nomor telepon -->
                            <div class="form-check custom-check mt-3">

                                <input
                                    type="checkbox"
                                    name="wa_sama_telepon"
                                    id="wa_sama_telepon"
                                    class="form-check-input"
                                    value="1"
                                    <?= !empty($_POST['wa_sama_telepon']) ? 'checked' : '' ?>>

                                <label
                                    class="form-check-label"
                                    for="wa_sama_telepon">

                                    Gunakan nomor Handphone sebagai nomor WhatsApp

                                </label>

                            </div>

                        </div>

                        <div class="form-group col-md-6">

                            <label for="nomor_whatsapp">Nomor WhatsApp</label>

                            <input
                                type="text"
                                name="nomor_whatsapp"
                                id="nomor_whatsapp"
                                class="form-control"
                                placeholder="08xxxxxxxxxx"
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">

                            <small class="form-text text-muted mt-2">
                                Centang pilihan di atas jika nomor WhatsApp sama.
                            </small>
                        </div>

                    </div>

                </div>

                <!-- =========================
                        STEP 5
                ========================== -->
                <div class="step">

                    <h4 class="step-title">Upload Dokumen</h4>

                    <div class="document-note">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Periksa dokumen sebelum melanjutkan</strong>
                            <span>Format JPG, PNG, WEBP, atau PDF maksimal 5 MB. Foto diri wajib berupa gambar.</span>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-4">
                            <div class="upload-card" data-upload-card>
                                <div class="upload-icon"><i class="fas fa-id-card"></i></div>
                                <h6>Foto KTP</h6>
                                <p>Pastikan seluruh bagian KTP terlihat jelas.</p>

                                <div class="file-preview" id="preview_foto_ktp">
                                    <div class="file-preview-empty">
                                        <i class="far fa-image"></i>
                                        <span>Belum ada file</span>
                                    </div>
                                </div>

                                <label class="upload-btn" for="foto_ktp">
                                    <i class="fas fa-upload"></i>
                                    <span>Pilih File</span>
                                </label>

                                <input
                                    type="file"
                                    id="foto_ktp"
                                    name="foto_ktp"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                    data-label="Foto KTP"
                                    data-preview="preview_foto_ktp"
                                    required
                                    hidden>

                                <div class="file-meta" id="meta_foto_ktp"></div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="upload-card" data-upload-card>
                                <div class="upload-icon"><i class="fas fa-user"></i></div>
                                <h6>Foto Diri</h6>
                                <p>Gunakan foto terbaru dengan wajah terlihat jelas.</p>

                                <div class="file-preview file-preview-portrait" id="preview_foto_diri">
                                    <div class="file-preview-empty">
                                        <i class="far fa-user-circle"></i>
                                        <span>Belum ada file</span>
                                    </div>
                                </div>

                                <label class="upload-btn" for="foto_diri">
                                    <i class="fas fa-upload"></i>
                                    <span>Pilih File</span>
                                </label>

                                <input
                                    type="file"
                                    id="foto_diri"
                                    name="foto_diri"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    data-label="Foto Diri"
                                    data-preview="preview_foto_diri"
                                    required
                                    hidden>

                                <div class="file-meta" id="meta_foto_diri"></div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="upload-card" data-upload-card>
                                <div class="upload-icon"><i class="fas fa-users"></i></div>
                                <h6>Kartu Keluarga</h6>
                                <p>Pastikan nomor dan anggota keluarga dapat terbaca.</p>

                                <div class="file-preview" id="preview_foto_kk">
                                    <div class="file-preview-empty">
                                        <i class="far fa-file-alt"></i>
                                        <span>Belum ada file</span>
                                    </div>
                                </div>

                                <label class="upload-btn" for="foto_kk">
                                    <i class="fas fa-upload"></i>
                                    <span>Pilih File</span>
                                </label>

                                <input
                                    type="file"
                                    id="foto_kk"
                                    name="foto_kk"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                    data-label="Foto Kartu Keluarga"
                                    data-preview="preview_foto_kk"
                                    required
                                    hidden>

                                <div class="file-meta" id="meta_foto_kk"></div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- =========================
                        STEP 6
                ========================== -->

                <div class="step">

                    <h4 class="step-title">
                        Akun Relawan
                    </h4>

                    <div class="row">

                        <div class="form-group col-md-12">

                            <label>Username</label>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Minimal 4 karakter">

                        </div>

                        <div class="form-group col-md-6">

                            <label>Password</label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Minimal 6 karakter">

                                <button
                                    type="button"
                                    class="toggle-password"
                                    data-target="password">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Konfirmasi Password</label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    id="konfirmasi_password"
                                    name="konfirmasi_password"
                                    class="form-control"
                                    placeholder="Ulangi Password">

                                <button
                                    type="button"
                                    class="toggle-password"
                                    data-target="konfirmasi_password">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                        </div>

                        <div class="form-group col-md-12">

                            <label class="checkbox-container">

                                <input
                                    type="checkbox"
                                    id="setuju">

                                <span>
                                    Saya menyatakan bahwa seluruh data yang saya isi adalah benar.
                                </span>

                            </label>

                        </div>

                    </div>

                </div>

                <div class="wizard-buttons">

                    <button
                        type="button"
                        id="prevBtn"
                        class="btn-secondary">

                        <i class="fas fa-arrow-left"></i>
                        Kembali

                    </button>

                    <button
                        type="button"
                        id="nextBtn"
                        class="btn-login">

                        Selanjutnya
                        <i class="fas fa-arrow-right"></i>

                    </button>

                    <button
                        href="login.php"
                        type="submit"
                        id="submitBtn"
                        class="btn-login">

                        <i class="fas fa-user-plus"></i>
                        Daftar Relawan

                    </button>

                </div>

                <div class="signup-text">

                    Sudah mempunyai akun?

                </div>

                <a
                    href="login.php"
                    class="btn-secondary">

                    <i class="fas fa-sign-in-alt"></i>

                    Masuk

                </a>

            </form>

        </div>

    </div>

</body>


<script>
    // WIZARD
    document.addEventListener("DOMContentLoaded", function() {

        let currentStep = 0;

        const steps = document.querySelectorAll(".step");
        const circles = document.querySelectorAll(".step-circle");

        const prevBtn = document.getElementById("prevBtn");
        const nextBtn = document.getElementById("nextBtn");
        const submitBtn = document.getElementById("submitBtn");
        const progressBar = document.getElementById("progressBar");

        function showStep(index) {

            // Tampilkan step aktif
            steps.forEach(step => step.classList.remove("active"));
            steps[index].classList.add("active");

            // Update lingkaran step
            circles.forEach((circle, i) => {

                circle.classList.remove("active", "done");

                if (i < index) {
                    circle.classList.add("done");
                } else if (i === index) {
                    circle.classList.add("active");
                }

            });

            // Update progress bar
            let percent = ((index + 1) / steps.length) * 100;
            progressBar.style.width = percent + "%";

            // Tombol
            prevBtn.style.display = index === 0 ? "none" : "inline-block";
            nextBtn.style.display = index === steps.length - 1 ? "none" : "inline-block";
            submitBtn.style.display = index === steps.length - 1 ? "inline-block" : "none";

        }

        // Tampilkan step pertama
        showStep(currentStep);

        nextBtn.addEventListener("click", function() {

            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }

        });

        prevBtn.addEventListener("click", function() {

            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }

        });

    });
    // ---------------------------------------------------------------------

    // VALIDASI FORMULIR
    function validasiNIK() {

        let input = document.getElementById("nik");
        let error = document.getElementById("errorNIK");

        // Hanya boleh angka
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

    function validasiTPS() {
        let input = document.getElementById("tps");
        let pesan = document.getElementById("pesanTPS");

        // Hanya angka
        input.value = input.value.replace(/[^0-9]/g, '');

        let regex = /^[0-9]{3}$/;

        if (input.value === "") {
            pesan.innerHTML = "";
        } else if (regex.test(input.value)) {
            pesan.innerHTML = "";
        } else {
            pesan.innerHTML = "TPS harus terdiri dari 3 digit angka, contoh: 001";
        }
    }

    function validasiRT() {
        let input = document.getElementById("rt");
        let pesan = document.getElementById("pesanRT");

        // Hanya angka
        input.value = input.value.replace(/[^0-9]/g, '');

        let regex = /^[0-9]{3}$/;

        if (input.value === "") {
            pesan.innerHTML = "";
        } else if (regex.test(input.value)) {
            pesan.innerHTML = "";
        } else {
            pesan.innerHTML = "RT harus terdiri dari 3 digit angka, contoh: 001";
        }
    }

    function validasiRW() {
        let input = document.getElementById("rw");
        let pesan = document.getElementById("pesanRW");

        // Hanya angka
        input.value = input.value.replace(/[^0-9]/g, '');

        let regex = /^[0-9]{3}$/;

        if (input.value === "") {
            pesan.innerHTML = "";
        } else if (regex.test(input.value)) {
            pesan.innerHTML = "";
        } else {
            pesan.innerHTML = "RW harus terdiri dari 3 digit angka, contoh: 001";
        }
    }

    function validasiKK() {

        let input = document.getElementById("nomor_kk");
        let error = document.getElementById("errorKK");

        // Hanya angka
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
            error.innerHTML = "Nomor KK harus terdiri dari 16 digit angka";
            input.classList.remove("is-valid");
            input.classList.add("is-invalid");
        }
    }

    function validasiNIKKeluarga(input) {

        let error = input.parentElement.querySelector('.error-keluarga-nik');

        // Hanya boleh angka
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
    // ------------------------------------------------------------------------------------------------

    // TOGGLE PASSWORD
    document.querySelectorAll(".toggle-password").forEach(button => {

        button.addEventListener("click", function() {

            const target = this.dataset.target;

            const input = document.getElementById(target);

            const icon = this.querySelector("i");

            if (input.type === "password") {

                input.type = "text";

                icon.classList.replace(
                    "fa-eye",
                    "fa-eye-slash"
                );

            } else {

                input.type = "password";

                icon.classList.replace(
                    "fa-eye-slash",
                    "fa-eye"
                );

            }

        });

    });
    // ------------------------------------------------------------------------------------------------

    // API WILAYAH INDONESIA
    document.addEventListener("DOMContentLoaded", async function() {

        const provinsiSelect = document.getElementById("provinsi");
        const kabKotaSelect = document.getElementById("kab_kota");
        const kecamatanSelect = document.getElementById("kecamatan");
        const desaSelect = document.getElementById("desa_kelurahan");

        const API_URL = "https://www.emsifa.com/api-wilayah-indonesia/api";

        // Data dari database (PHP)
        const DAPIL = <?= json_encode($dapilData) ?>;

        const ADMIN_LIST = <?= json_encode($adminList) ?>;

        // Cache supaya API tidak dipanggil berkali-kali
        let cacheProvinsi = [];

        /*==================================================
          HELPER
        ==================================================*/

        function resetSelect(select, text) {

            select.innerHTML = `<option value="">${text}</option>`;

        }

        function setLoading(select, text = "Memuat data...") {

            select.innerHTML = `<option value="">${text}</option>`;

        }

        async function fetchWilayah(url) {

            const response = await fetch(url);

            if (!response.ok) {

                throw new Error("Gagal mengambil data wilayah");

            }

            return await response.json();

        }

        /*==================================================
          LOAD PROVINSI
        ==================================================*/

        async function loadProvinsi() {

            resetSelect(provinsiSelect, "Pilih Provinsi");

            Object.keys(DAPIL).forEach(provinsi => {

                const option = document.createElement("option");

                option.value = provinsi;
                option.textContent = provinsi;

                provinsiSelect.appendChild(option);

            });

            // Cache daftar provinsi API
            cacheProvinsi = await fetchWilayah(
                `${API_URL}/provinces.json`
            );

        }

        /*==================================================
          LOAD KABUPATEN
        ==================================================*/

        function loadKabKota() {

            resetSelect(kabKotaSelect, "Pilih Kabupaten/Kota");
            resetSelect(kecamatanSelect, "Pilih Kecamatan");
            resetSelect(desaSelect, "Pilih Desa/Kelurahan");

            const provinsi = provinsiSelect.value;

            if (!provinsi) return;

            DAPIL[provinsi].forEach(kab => {

                const option = document.createElement("option");

                option.value = kab;
                option.textContent = kab;

                kabKotaSelect.appendChild(option);

            });

        }

        /*==================================================
        NORMALISASI NAMA WILAYAH
        ==================================================*/

        function normalizeWilayah(nama) {

            return nama
                .toUpperCase()
                .replace("KABUPATEN", "KAB.")
                .replace(/\s+/g, " ")
                .trim();

        }

        /*==================================================
          CARI ID KABUPATEN
        ==================================================*/

        async function getKabupatenId() {

            const provinsi = provinsiSelect.value;
            const kabupaten = kabKotaSelect.value;

            if (!provinsi || !kabupaten) {
                return null;
            }

            // Cari ID Provinsi dari cache
            const provinsiItem = cacheProvinsi.find(item =>
                item.name.toUpperCase() === provinsi.toUpperCase()
            );

            if (!provinsiItem) {
                return null;
            }

            // Ambil daftar kabupaten dari API
            const kabupatenData = await fetchWilayah(
                `${API_URL}/regencies/${provinsiItem.id}.json`
            );

            // Cocokkan nama kabupaten
            const kabupatenItem = kabupatenData.find(item =>
                normalizeWilayah(item.name) === normalizeWilayah(kabupaten)
            );

            return kabupatenItem ? kabupatenItem.id : null;

        }

        /*==================================================
          LOAD KECAMATAN
        ==================================================*/

        async function loadKecamatan() {

            try {

                setLoading(kecamatanSelect, "Memuat Kecamatan...");
                resetSelect(desaSelect, "Pilih Kecamatan Terlebih Dahulu");

                const kabupatenId = await getKabupatenId();

                if (!kabupatenId) {

                    resetSelect(
                        kecamatanSelect,
                        "Kabupaten tidak ditemukan"
                    );

                    return;

                }

                const data = await fetchWilayah(
                    `${API_URL}/districts/${kabupatenId}.json`
                );

                resetSelect(
                    kecamatanSelect,
                    "Pilih Kecamatan"
                );

                data.forEach(item => {

                    const option = document.createElement("option");

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    kecamatanSelect.appendChild(option);

                });

            } catch (err) {

                console.error(err);

                resetSelect(
                    kecamatanSelect,
                    "Gagal memuat Kecamatan"
                );

            }

        }

        /*==================================================
        DESA TAMBAHAN
        ==================================================*/

        const tambahanKelurahan = {

            "KABUPATEN TANAH LAUT": {
                "BATU AMPAR": [
                    "BLURU"
                ]
            },

            "KABUPATEN KOTABARU": {
                "PULAU LAUT BARAT": [
                    "GEMURUH"
                ],
                "PULAU LAUT TIMUR": [
                    "BETUNG"
                ],
                "PAMUKAN UTARA": [
                    "BETUNG"
                ],
                "PULAU SEBUKU": [
                    "UJUNG"
                ],
                "PAMUKAN SELATAN": [
                    "SESULUNG"
                ]
            },

            "KABUPATEN TANAH BUMBU": {
                "KUSAN HILIR": [
                    "BETUNG",
                    "GUSUNGE",
                    "SEPUNGGUR"
                ],
                "KUSAN HULU": [
                    "GUNTUNG"
                ],
                "SIMPANG EMPAT": [
                    "BERSUJUD"
                ]
            }

        };

        /*==================================================
          LOAD DESA
        ==================================================*/

        async function loadDesa(kecamatanId) {

            try {

                setLoading(
                    desaSelect,
                    "Memuat Desa/Kelurahan..."
                );

                const data = await fetchWilayah(
                    `${API_URL}/villages/${kecamatanId}.json`
                );

                resetSelect(
                    desaSelect,
                    "Pilih Desa/Kelurahan"
                );

                data.forEach(item => {

                    const option = document.createElement("option");

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    desaSelect.appendChild(option);

                });

                /*==========================================
                    TAMBAHAN DESA
                ==========================================*/

                const kabupaten = kabKotaSelect.value.toUpperCase();
                const kecamatan = kecamatanSelect.value.toUpperCase();

                if (
                    tambahanKelurahan[kabupaten] &&
                    tambahanKelurahan[kabupaten][kecamatan]
                ) {

                    tambahanKelurahan[kabupaten][kecamatan].forEach(nama => {

                        const sudahAda = [...desaSelect.options].some(
                            option =>
                            option.value.toUpperCase() === nama
                        );

                        if (!sudahAda) {

                            const option =
                                document.createElement("option");

                            option.value = nama;
                            option.textContent = nama;

                            desaSelect.appendChild(option);

                        }

                    });

                }

            } catch (err) {

                console.error(err);

                resetSelect(
                    desaSelect,
                    "Gagal Memuat Desa"
                );

            }

        }

        /*==================================================
          LOAD ADMIN
        ==================================================*/
        function loadAdmin() {

            const provinsi = provinsiSelect.value;
            const kabupaten = kabKotaSelect.value;

            const container = document.getElementById("adminContainer");

            container.innerHTML = "";

            if (!provinsi || !kabupaten) {

                container.innerHTML =
                    "<small class='text-muted'>Pilih kabupaten terlebih dahulu.</small>";

                return;
            }

            const admin = ADMIN_LIST.filter(item => {

                return item.provinsi === provinsi &&
                    item.kab_kota.includes(kabupaten);

            });

            if (admin.length === 0) {

                container.innerHTML =
                    "<div class='alert alert-warning'>Belum ada Admin Dapil.</div>";

                return;

            }

            admin.forEach(item => {

                container.innerHTML += `

                <label class="admin-card">

                    <input
                        type="checkbox"
                        name="admin_id[]"
                        value="${item.id}">

                    <div class="admin-name">

                        <i class="fas fa-user-tie"></i>

                        ${item.nama_lengkap}

                    </div>

                    <div class="admin-dapil">

                        <i class="fas fa-map-marker-alt"></i>

                        ${item.daerah_pemilihan}

                    </div>

                </label>

                `;

            });

        }

        /*==================================================
          EVENT
        ==================================================*/

        provinsiSelect.addEventListener("change", function() {

            loadKabKota();

        });

        kabKotaSelect.addEventListener("change", function() {

            if (this.value) {

                // Load kecamatan
                loadKecamatan();

                // Load admin sesuai kabupaten
                loadAdmin();

            } else {

                resetSelect(
                    kecamatanSelect,
                    "Pilih Kecamatan"
                );

                resetSelect(
                    desaSelect,
                    "Pilih Desa/Kelurahan"
                );

                document.getElementById("adminContainer").innerHTML =
                    "<small class='text-muted'>Pilih kabupaten terlebih dahulu.</small>";

            }

        });

        kecamatanSelect.addEventListener("change", function() {

            const selected =
                this.options[this.selectedIndex];

            const kecamatanId =
                selected.dataset.id;

            if (kecamatanId) {

                loadDesa(kecamatanId);

            } else {

                resetSelect(
                    desaSelect,
                    "Pilih Desa/Kelurahan"
                );

            }

        });

        /*==================================================
          INIT
        ==================================================*/
        await loadProvinsi();

    });
    // ------------------------------------------------------------------------------------------------

    // TAMBAH ANGGOTA KELUARGA
    let anggotaIndex = 0;

    document
        .getElementById('btnTambahAnggota')
        .addEventListener('click', function() {

            anggotaIndex++;

            const html = `

            <div class="anggota-card">

                <div class="anggota-header">

                    <div class="anggota-title">

                        <i class="fas fa-user"></i>

                        Anggota Keluarga ${anggotaIndex}

                    </div>

                    <button
                        type="button"
                        class="btnHapus">

                        <i class="fas fa-trash"></i>

                    </button>

                </div>

                <div class="row">

                    <div class="form-group col-md-6">

                        <label>Hubungan Keluarga</label>

                        <select
                            name="keluarga_hubungan_keluarga[]"
                            class="form-control">

                            <option value="">Pilih Hubungan</option>
                            <option>Suami</option>
                            <option>Istri</option>
                            <option>Anak</option>
                            <option>Orang Tua</option>
                            <option>Lainnya</option>

                        </select>

                    </div>

                    <div class="form-group col-md-6">

                        <label>Jenis Kelamin</label>

                        <select
                            name="keluarga_jenis_kelamin[]"
                            class="form-control">

                            <option value="">Pilih Jenis Kelamin</option>
                            <option>Laki-laki</option>
                            <option>Perempuan</option>

                        </select>

                    </div>

                    <div class="form-group col-md-6">

                        <label>NIK</label>

                        <input
                            type="text"
                            maxlength="16"
                            class="form-control keluarga-nik"
                            name="keluarga_nik[]"
                            oninput="validasiNIKKeluarga(this)">

                        <small class="text-danger error-keluarga-nik"></small>

                    </div>

                    <div class="form-group col-md-6">

                        <label>Nama</label>

                        <input
                            type="text"
                            class="form-control"
                            name="keluarga_nama[]">

                    </div>

                    <div class="form-group col-md-6">

                        <label>Tempat Lahir</label>

                        <input
                            type="text"
                            class="form-control"
                            name="keluarga_tempat_lahir[]">

                    </div>

                    <div class="form-group col-md-6">

                        <label>Tanggal Lahir</label>

                        <input
                            type="date"
                            class="form-control"
                            name="keluarga_tanggal_lahir[]">

                    </div>

                    <div class="form-group col-md-6">

                        <label>Agama</label>

                        <select
                            class="form-control"
                            name="keluarga_agama[]">

                            <option value="">Pilih Agama</option>
                            <option>Islam</option>
                            <option>Kristen</option>
                            <option>Katolik</option>
                            <option>Hindu</option>
                            <option>Buddha</option>

                        </select>

                    </div>

                    <div class="form-group col-md-6">

                        <label>Pekerjaan</label>

                        <input
                            type="text"
                            class="form-control"
                            name="keluarga_pekerjaan[]">

                    </div>

                </div>

            </div>
        `;

            document
                .getElementById('anggotaContainer')
                .insertAdjacentHTML('beforeend', html);
        });

    document.addEventListener("click", function(e) {

        const btn = e.target.closest(".btnHapus");

        if (btn) {

            btn.closest(".anggota-card").remove();

        }

    });
    // ------------------------------------------------------------------------------------------------

    // SINKRONISASI NOMOR TELEPON DAN WHATSAPP
    document.addEventListener('DOMContentLoaded', function() {

        const nomorTelepon = document.getElementById('nomor_telepon');
        const nomorWhatsApp = document.getElementById('nomor_whatsapp');
        const checkboxWa = document.getElementById('wa_sama_telepon');

        if (!nomorTelepon || !nomorWhatsApp || !checkboxWa) {
            return;
        }

        function sinkronkanNomorWhatsApp() {
            if (checkboxWa.checked) {
                nomorWhatsApp.value = nomorTelepon.value;
                nomorWhatsApp.readOnly = true;

            } else {
                nomorWhatsApp.readOnly = false;
            }
        }

        checkboxWa.addEventListener('change', function() {
            sinkronkanNomorWhatsApp();
        });

        nomorTelepon.addEventListener('input', function() {
            if (checkboxWa.checked) {
                nomorWhatsApp.value = nomorTelepon.value;
            }
        });

        sinkronkanNomorWhatsApp();
    });
    // ------------------------------------------------------------------------------------------------

    
    document.addEventListener('DOMContentLoaded', function() {
        const maxSize = 5 * 1024 * 1024;

        function formatBytes(bytes) {
            if (!bytes) return '0 KB';
            const units = ['B', 'KB', 'MB', 'GB'];
            const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
            return `${(bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 2)} ${units[index]}`;
        }

        function toast(type, title, message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-info-circle'
            };
            const el = document.createElement('div');
            el.className = `app-toast ${type}`;
            el.innerHTML = `<i class="fas ${icons[type] || icons.warning}"></i><div><strong>${title}</strong><span>${message}</span></div>`;
            container.appendChild(el);
            setTimeout(() => el.remove(), 4200);
        }

        document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
            input.addEventListener('change', function() {
                const preview = document.getElementById(this.dataset.preview);
                const meta = document.getElementById(`meta_${this.id}`);
                const card = this.closest('[data-upload-card]');
                const label = this.dataset.label || 'File';
                const file = this.files && this.files[0];

                if (!file) {
                    preview.innerHTML = '<div class="file-preview-empty"><i class="far fa-image"></i><span>Belum ada file</span></div>';
                    meta.innerHTML = '';
                    card.classList.remove('has-file');
                    return;
                }

                if (file.size > maxSize) {
                    this.value = '';
                    preview.innerHTML = '<div class="file-preview-empty"><i class="fas fa-exclamation-circle"></i><span>File terlalu besar</span></div>';
                    meta.innerHTML = '<span style="color:#d64545">Maksimal 5 MB</span>';
                    card.classList.remove('has-file');
                    toast('error', `${label} ditolak`, 'Ukuran file maksimal 5 MB.');
                    return;
                }

                const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                const isImage = file.type.startsWith('image/');

                if (!isPdf && !isImage) {
                    this.value = '';
                    toast('error', `${label} ditolak`, 'Format file tidak didukung.');
                    return;
                }
                if (this.id === 'foto_diri' && !isImage) {
                    this.value = '';
                    toast('error', 'Foto Diri ditolak', 'Foto diri harus berupa JPG, PNG, atau WEBP.');
                    return;
                }

                if (isPdf) {
                    preview.innerHTML = '<div class="pdf-preview"><i class="fas fa-file-pdf"></i><span>Dokumen PDF siap diunggah</span></div>';
                } else {
                    const reader = new FileReader();
                    reader.onload = event => {
                        preview.innerHTML = `<img src="${event.target.result}" alt="Preview ${label}">`;
                    };
                    reader.readAsDataURL(file);
                }

                meta.innerHTML = `
                    <span class="file-success"><i class="fas fa-check-circle"></i> File telah dipilih</span>
                    <span>${file.name}</span>
                    <span class="file-size">${formatBytes(file.size)}</span>
                `;
                card.classList.add('has-file');
                toast('success', `${label} siap`, `${file.name} berhasil dipilih.`);
            });
        });
    });
</script>

</html>