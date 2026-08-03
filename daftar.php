<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();

        $nik           = trim($_POST['nik'] ?? '');
        $namaLengkap   = trim($_POST['nama_lengkap'] ?? '');
        $username      = trim($_POST['username'] ?? '');
        $password      = $_POST['password'] ?? '';
        $konfirmasi    = $_POST['konfirmasi_password'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            throw new Exception('NIK harus terdiri dari 16 digit angka.');
        }

        if (strlen($namaLengkap) < 3) {
            throw new Exception('Nama lengkap minimal 3 karakter.');
        }

        if (strlen($username) < 4) {
            throw new Exception('Username minimal 4 karakter.');
        }

        if (!preg_match('/^[A-Za-z0-9._]+$/', $username)) {
            throw new Exception('Username hanya boleh berisi huruf, angka, titik (.) dan underscore (_).');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter.');
        }

        if ($password !== $konfirmasi) {
            throw new Exception('Konfirmasi password tidak sesuai.');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK NIK
        |--------------------------------------------------------------------------
        */

        $cek = $pdo->prepare("
            SELECT COUNT(*)
            FROM profiles
            WHERE nik = ?
        ");

        $cek->execute([$nik]);

        if ($cek->fetchColumn() > 0) {
            throw new Exception('NIK sudah terdaftar.');
        }

        /*
        |--------------------------------------------------------------------------
        | CEK USERNAME
        |--------------------------------------------------------------------------
        */

        $cek = $pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE username = ?
        ");

        $cek->execute([$username]);

        if ($cek->fetchColumn() > 0) {
            throw new Exception('Username sudah digunakan.');
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN USER
        |--------------------------------------------------------------------------
        */

        $hash = password_hash($password, PASSWORD_DEFAULT);

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
                ?, ?, ?, 'relawan', 1
            )
        ");

        $stmt->execute([
            $namaLengkap,
            $username,
            $hash
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
                nik,
                nama_lengkap,
                status_verifikasi,
                profile_active,
                profile_complete
            )
            VALUES
            (
                ?, 'relawan', ?, ?, 'pending', 1, 0
            )
        ");

        $stmt->execute([
            $userId,
            $nik,
            $namaLengkap
        ]);

        $pdo->commit();

        redirect('login.php');
        exit;
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
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

            <form method="POST" id="registerForm">

                <!-- =========================
                    WIZARD HEADER
                ========================== -->

                <div class="wizard-header">

                    <div class="progress">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>

                    <div class="step-indicator">

                        <div class="step-circle active">
                            <span>1</span>
                            <small>Data</small>
                        </div>

                        <div class="step-circle">
                            <span>2</span>
                            <small>Wilayah</small>
                        </div>

                        <div class="step-circle">
                            <span>3</span>
                            <small>Kontak</small>
                        </div>

                        <div class="step-circle">
                            <span>4</span>
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
                                maxlength="16"
                                placeholder="Masukkan NIK">

                        </div>

                        <div class="form-group col-md-8">
                            <label>Nama Lengkap</label>

                            <input
                                type="text"
                                class="form-control"
                                name="nama_lengkap"
                                placeholder="Nama Lengkap">

                        </div>

                        <div class="form-group col-md-4">
                            <label>Tempat Lahir</label>

                            <input
                                type="text"
                                class="form-control"
                                name="tempat_lahir">

                        </div>

                        <div class="form-group col-md-4">
                            <label>Tanggal Lahir</label>

                            <input
                                type="date"
                                class="form-control"
                                name="tanggal_lahir">

                        </div>

                        <div class="form-group col-md-4">

                            <label>Jenis Kelamin</label>

                            <select
                                class="form-control"
                                name="jenis_kelamin">

                                <option value="">Pilih</option>
                                <option>Laki-laki</option>
                                <option>Perempuan</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Golongan Darah</label>

                            <select class="form-control">

                                <option>Pilih</option>
                                <option>A</option>
                                <option>B</option>
                                <option>AB</option>
                                <option>O</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Status Pernikahan</label>

                            <select class="form-control">

                                <option>Pilih</option>
                                <option>Belum Menikah</option>
                                <option>Sudah Menikah</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Agama</label>

                            <select class="form-control">

                                <option>Pilih</option>
                                <option>Islam</option>
                                <option>Kristen</option>
                                <option>Katolik</option>
                                <option>Hindu</option>
                                <option>Budha</option>

                            </select>

                        </div>

                        <div class="form-group col-md-3">

                            <label>Pekerjaan</label>

                            <input
                                class="form-control"
                                name="pekerjaan">

                        </div>

                        <div class="form-group col-md-12">

                            <label>Alamat</label>

                            <textarea
                                class="form-control"
                                name="alamat"></textarea>

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
                                class="form-control">

                                <option value="">Pilih Provinsi</option>

                            </select>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Kabupaten / Kota</label>

                            <select
                                name="kab_kota"
                                id="kab_kota"
                                class="form-control">

                                <option value="">
                                    Pilih Kabupaten / Kota
                                </option>

                            </select>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Kecamatan</label>

                            <select
                                name="kecamatan"
                                id="kecamatan"
                                class="form-control">

                                <option value="">
                                    Pilih Kecamatan
                                </option>

                            </select>

                        </div>

                        <div class="form-group col-md-6">

                            <label>Desa / Kelurahan</label>

                            <select
                                name="desa_kelurahan"
                                id="desa_kelurahan"
                                class="form-control">

                                <option value="">
                                    Pilih Desa / Kelurahan
                                </option>

                            </select>

                        </div>

                        <div class="form-group col-md-4">

                            <label>RT</label>

                            <input
                                type="text"
                                name="rt"
                                maxlength="3"
                                class="form-control"
                                placeholder="001">

                        </div>

                        <div class="form-group col-md-4">

                            <label>RW</label>

                            <input
                                type="text"
                                name="rw"
                                maxlength="3"
                                class="form-control"
                                placeholder="001">

                        </div>

                        <div class="form-group col-md-4">

                            <label>TPS</label>

                            <input
                                type="text"
                                name="tps"
                                maxlength="3"
                                class="form-control"
                                placeholder="001">

                        </div>

                    </div>

                </div>

                <!-- =========================
        STEP 3
========================== -->

                <div class="step">

                    <h4 class="step-title">
                        Kontak & Dokumen
                    </h4>

                    <div class="row">

                        <div class="form-group col-md-6">

                            <label>Nomor KK</label>

                            <input
                                type="text"
                                name="nomor_kk"
                                maxlength="16"
                                class="form-control"
                                placeholder="Masukkan Nomor KK">

                        </div>

                        <div class="form-group col-md-6">

                            <label>Nomor Handphone</label>

                            <input
                                type="text"
                                name="nomor_hp"
                                class="form-control"
                                placeholder="08xxxxxxxxxx">

                        </div>

                        <div class="form-group col-md-6">

                            <label>Nomor WhatsApp</label>

                            <input
                                type="text"
                                name="nomor_wa"
                                class="form-control"
                                placeholder="08xxxxxxxxxx">

                        </div>

                        <div class="form-group col-md-6">

                            <label>Upload Foto KTP</label>

                            <input
                                type="file"
                                name="foto_ktp"
                                class="form-control">

                        </div>

                        <div class="form-group col-md-6">

                            <label>Upload Foto Diri</label>

                            <input
                                type="file"
                                name="foto_diri"
                                class="form-control">

                        </div>

                        <div class="form-group col-md-6">

                            <label>Upload Foto KK</label>

                            <input
                                type="file"
                                name="foto_kk"
                                class="form-control">

                        </div>

                    </div>

                </div>

                <!-- =========================
                        STEP 4
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
</script>

<script>
    const btn = document.getElementById('togglePassword');

    btn.addEventListener('click', () => {

        const input = document.getElementById('password');

        const icon = btn.querySelector('i');

        if (input.type === "password") {

            input.type = "text";

            icon.classList.replace("fa-eye", "fa-eye-slash");

        } else {

            input.type = "password";

            icon.classList.replace("fa-eye-slash", "fa-eye");

        }

    });
</script>

</html>