<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user'])) {
    header("Location: " . url('dashboard/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = 'Username tidak ditemukan.';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'Akun tidak aktif.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Password salah.';
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'role' => $user['role'],
                'kecamatan' => $user['kecamatan']
            ];

            header("Location: " . url('dashboard/index.php'));
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #c8efff, #86d7f5, #4bb6e8);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-wrapper {
            width: 900px;
            max-width: 92%;
            height: 520px;
            background: rgba(255, 255, 255, 0.22);
            border-radius: 26px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(20, 80, 120, 0.25);
            backdrop-filter: blur(10px);
        }

        .shape-left {
            position: absolute;
            left: -60px;
            bottom: -70px;
            width: 340px;
            height: 260px;
            background: #77ccef;
            border-radius: 0 180px 0 0;
            opacity: 0.95;
        }

        .shape-bottom {
            position: absolute;
            left: 170px;
            bottom: 105px;
            width: 370px;
            height: 90px;
            background: #ffffff;
            transform: skewY(-8deg);
            border-radius: 22px;
        }

        .shape-right {
            position: absolute;
            right: -110px;
            top: 120px;
            width: 410px;
            height: 320px;
            background: linear-gradient(135deg, #ffffff 0%, #ffffff 45%, #4eb7e7 46%, #229cda 100%);
            border-radius: 180px 0 0 180px;
        }

        .shape-top {
            position: absolute;
            top: -70px;
            left: 330px;
            width: 240px;
            height: 150px;
            background: #ffffff;
            border-radius: 0 0 120px 120px;
            transform: rotate(-8deg);
        }

        .login-card {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 360px;
            background: #ffffff;
            padding: 36px 34px 30px;
            border-radius: 22px;
            transform: translate(-50%, -50%);
            box-shadow: 0 22px 55px rgba(18, 83, 130, 0.22);
            z-index: 5;
        }

        .login-card h2 {
            margin: 0;
            color: #1f3b57;
            text-align: center;
            font-size: 30px;
            font-weight: 800;
        }

        .login-card p {
            text-align: center;
            color: #8a9bad;
            font-size: 13px;
            margin: 8px 0 26px;
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            height: 48px;
            border: none;
            outline: none;
            background: #f3f8fc;
            border-radius: 12px;
            padding: 0 44px 0 16px;
            color: #2b3f52;
            font-size: 14px;
            transition: 0.2s;
        }

        .form-group input:focus {
            background: #eef8ff;
            box-shadow: 0 0 0 3px rgba(95, 190, 235, 0.22);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9fb4c7;
        }

        .forgot {
            display: block;
            text-align: left;
            color: #7b92a8;
            font-size: 13px;
            margin-bottom: 18px;
            text-decoration: none;
        }

        .forgot:hover {
            color: #1b99d4;
        }

        .btn-login {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            color: white;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(17, 141, 208, 0.28);
            transition: 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(17, 141, 208, 0.34);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 22px 0;
            color: #b0bdc8;
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e4edf4;
        }

        .btn-check {
            display: block;
            width: 100%;
            height: 44px;
            line-height: 44px;
            text-align: center;
            border: 1px solid #cbdce9;
            border-radius: 12px;
            color: #3e5870;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-check:hover {
            background: #f4fbff;
            color: #168ed0;
        }

        .alert {
            background: #ffe8e8;
            color: #c0392b;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            text-align: center;
        }

        .small-info {
            margin-top: 18px;
            text-align: center;
            font-size: 12px;
            color: #9aabba;
        }

        .brand-text {
            position: absolute;
            left: 42px;
            top: 35px;
            color: white;
            z-index: 4;
        }

        .brand-text h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
        }

        .brand-text span {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            opacity: 0.88;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                height: 620px;
            }

            .login-card {
                width: 86%;
            }

            .brand-text {
                left: 28px;
                top: 28px;
            }

            .brand-text h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <!-- <div class="brand-text">
        <h1>Sistem Relawan</h1>
        <span>Kelola relawan dan dukungan dengan mudah</span>
    </div> -->

    <div class="shape-left"></div>
    <div class="shape-bottom"></div>
    <div class="shape-right"></div>
    <div class="shape-top"></div>

    <div class="login-card">

        <h2>SELAMAT DATANG</h2>
        <p>Masuk menggunakan akun yang telah terdaftar</p>

        <?php if ($error): ?>
            <div class="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <input 
                    type="text" 
                    name="username" 
                    placeholder="Username"
                    required
                >
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Password"
                    required
                >
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="btn-login">
                Sign in
            </button>

        </form>

        <div class="divider">atau</div>

        <a href="cek-terdaftar.php" class="btn-check">
            Cek status relawan
        </a>

    </div>

</div>

</body>
</html>