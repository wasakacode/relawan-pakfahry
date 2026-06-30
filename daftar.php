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
            width: 1000px;
            max-width: 95%;
            min-height: 720px;

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

            background: linear-gradient(135deg,
                    #ffffff 0%,
                    #ffffff 45%,
                    #4eb7e7 46%,
                    #229cda 100%);

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

            width: 430px;
            max-width: 90%;

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
   FORM
=========================== */

        .form-group {

            margin-bottom: 18px;
        }

        .form-group label {

            display: block;

            font-size: 14px;

            font-weight: 600;

            color: #324a5f;

            margin-bottom: 8px;
        }

        .form-group {

            position: relative;
        }

        .form-control {

            width: 100%;

            height: 50px;

            border: none;

            outline: none;

            border-radius: 12px;

            background: #f3f8fc;

            padding: 0 50px 0 16px;

            font-size: 14px;

            color: #2b3f52;

            transition: .2s;
        }

        .form-control:focus {

            background: #eef8ff;

            box-shadow: 0 0 0 3px rgba(95, 190, 235, .22);
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

            color: #9fb4c7;

            cursor: pointer;

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

            border: 2px solid #2ecc71;
        }

        .is-invalid {

            border: 2px solid #e74c3c;
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

            margin-bottom: 18px;

            text-align: center;
        }

        /* ===========================
   BUTTON
=========================== */

        .btn-login {

            width: 100%;

            height: 50px;

            border: none;

            border-radius: 12px;

            background: linear-gradient(135deg,
                    #3db7ee,
                    #118dd0);

            color: #fff;

            font-weight: 700;

            font-size: 15px;

            cursor: pointer;

            transition: .25s;

            box-shadow: 0 12px 24px rgba(17, 141, 208, .28);
        }

        .btn-login:hover {

            transform: translateY(-2px);

            box-shadow: 0 16px 28px rgba(17, 141, 208, .34);
        }

        /* ===========================
   LOGIN LINK
=========================== */

        .signup-text {

            margin: 22px 0 12px;

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
   MOBILE
=========================== */

        @media(max-width:768px) {

            body {
                padding: 20px;
            }


            .signup-wrapper {

                min-height: 100vh;

                padding: 40px 0;

                border-radius: 20px;
            }

            .signup-card {

                width: 92%;
                padding: 28px;

            }

            .signup-card h2 {

                font-size: 28px;
            }

            .shape-left {

                width: 250px;
                height: 180px;
            }

            .shape-right {

                width: 250px;
                height: 220px;
            }

            .shape-bottom {

                display: none;
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

            <form method="POST">

                <div class="form-group">
                    <label>NIK</label>
                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                        placeholder="Masukkan NIK"
                        maxlength="16"
                        required>

                    <small id="errorNIK" class="text-danger"></small>
                </div>
                <script>
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
                </script>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input
                        name="nama_lengkap"
                        class="form-control"
                        placeholder="Masukkan Nama Lengkap"
                        value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input
                        name="username"
                        class="form-control"
                        placeholder="Masukkan Username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Minimal 6 karakter"
                        oninput="validasiPassword()"
                        required>

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>

                    <small id="errorPassword" class="text-danger"></small>
                </div>

                <script>
                    function validasiPassword() {

                        let input = document.getElementById("password");
                        let error = document.getElementById("errorPassword");

                        if (input.value === "") {

                            error.innerHTML = "";
                            input.classList.remove("is-valid");
                            input.classList.remove("is-invalid");

                        } else if (input.value.length >= 6) {

                            error.innerHTML = "";
                            input.classList.remove("is-invalid");
                            input.classList.add("is-valid");

                        } else {

                            error.innerHTML = "Password minimal 6 karakter";
                            input.classList.remove("is-valid");
                            input.classList.add("is-invalid");

                        }
                    }
                </script>

                <div class="form-group">
                    <label>Konfirmasi Password</label>

                    <input
                        type="password"
                        name="konfirmasi_password"
                        id="konfirmasi_password"
                        class="form-control"
                        placeholder="Ulangi Password"
                        oninput="validasiKonfirmasi()"
                        required>

                    <button
                        type="button"
                        class="toggle-password"
                        id="toggleKonfirmasi">
                        <i class="fas fa-eye"></i>
                    </button>

                    <small id="errorKonfirmasi" class="text-danger"></small>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-user-plus"></i>
                    Daftar Relawan
                </button>

                <p class="signup-text">
                    Sudah memiliki akun?
                </p>

                <a href="login.php" class="btn-secondary">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk
                </a>

            </form>

        </div>

    </div>

</body>

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