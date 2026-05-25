<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$result = null;
$checked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checked = true;
    $nik = trim($_POST['nik'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE nik = ? AND type = 'relawan' 
                           LIMIT 1");

    $stmt->execute([$nik]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cek Status Relawan - <?= APP_NAME ?></title>
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

        .page-wrapper {
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

        .check-card {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 390px;
            background: #ffffff;
            padding: 34px 34px 30px;
            border-radius: 22px;
            transform: translate(-50%, -50%);
            box-shadow: 0 22px 55px rgba(18, 83, 130, 0.22);
            z-index: 5;
        }

        .icon-circle {
            width: 62px;
            height: 62px;
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            color: #ffffff;
            border-radius: 50%;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            box-shadow: 0 12px 24px rgba(17, 141, 208, 0.28);
        }

        .check-card h2 {
            margin: 0;
            color: #1f3b57;
            text-align: center;
            font-size: 26px;
            font-weight: 800;
        }

        .check-card p {
            text-align: center;
            color: #8a9bad;
            font-size: 13px;
            margin: 8px 0 24px;
            line-height: 1.5;
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

        .btn-check {
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

        .btn-check:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(17, 141, 208, 0.34);
        }

        .result-box {
            margin-top: 20px;
            padding: 16px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.6;
        }

        .result-success {
            background: #e8f8ef;
            color: #216b3a;
            border: 1px solid #c7efd7;
        }

        .result-warning {
            background: #fff7e6;
            color: #8a5a00;
            border: 1px solid #ffe2a3;
            text-align: center;
        }

        .result-name {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #1f3b57;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #d8f1ff;
            color: #168ed0;
            font-size: 12px;
            font-weight: 700;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 22px 0 16px;
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

        .btn-login {
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

        .btn-login:hover {
            background: #f4fbff;
            color: #3295cb;
        }

        @media (max-width: 768px) {
            .page-wrapper {
                height: 640px;
            }

            .check-card {
                width: 86%;
                padding: 30px 24px;
            }

            .brand-text {
                left: 28px;
                top: 28px;
            }

            .brand-text h1 {
                font-size: 22px;
            }

            .brand-text span {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<div class="page-wrapper">


    <div class="shape-left"></div>
    <div class="shape-bottom"></div>
    <div class="shape-right"></div>
    <div class="shape-top"></div>

    <div class="check-card">

        <div class="icon-circle">
            <i class="fas fa-id-card"></i>
        </div>

        <h2>Cek Status</h2>
        <p>Masukkan NIK untuk mengetahui apakah data relawan sudah terdaftar di sistem.</p>

        <form method="POST">
            <div class="form-group">
                <input 
                    type="text" 
                    name="nik" 
                    placeholder="Masukkan NIK"
                    value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                    required
                >
                <i class="fas fa-search"></i>
            </div>

            <button type="submit" class="btn-check">
                Cek Sekarang
            </button>
        </form>

        <?php if ($checked): ?>

            <?php if ($result): ?>
                <div class="result-box result-success">
                    <div class="result-name">
                        <?= htmlspecialchars($result['nama_lengkap']) ?>
                    </div>

                    <div>
                        <b>NIK:</b> <?= htmlspecialchars($result['nik']) ?>
                    </div>

                    <div>
                        <b>Kecamatan:</b> <?= htmlspecialchars($result['kecamatan'] ?? '-') ?>
                    </div>

                    <div>
                        <b>Desa/Kelurahan:</b> <?= htmlspecialchars($result['desa_kelurahan'] ?? '-') ?>
                    </div>

                    <div>
                        <b>TPS:</b> <?= htmlspecialchars($result['tps'] ?? '-') ?>
                    </div>

                    <div style="margin-top: 10px;">
                        <span class="badge">
                            <?= htmlspecialchars($result['status_verifikasi']) ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="result-box result-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    NIK belum terdaftar sebagai relawan.
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="divider">atau</div>

        <a href="login.php" class="btn-login">
            Kembali ke halaman login
        </a>

    </div>

</div>

</body>
</html>