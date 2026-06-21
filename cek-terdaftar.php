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
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .page-wrapper {
            width: 900px;
            max-width: 95%;
            min-height: 550px;
            background: rgba(255, 255, 255, .18);
            border-radius: 30px;
            position: relative;
            overflow: hidden;
            padding: 40px 0;
            box-shadow: 0 30px 80px rgba(20, 80, 120, .25);
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
        }

        .shape-bottom {
            position: absolute;
            left: 160px;
            bottom: 30px;
            width: 350px;
            height: 90px;
            background: #fff;
            transform: skewY(-8deg);
            border-radius: 25px;
        }

        .shape-right {
            position: absolute;
            right: -120px;
            top: 170px;
            width: 330px;
            height: 260px;
            background: linear-gradient(135deg, #fff 0%, #fff 45%, #4eb7e7 46%, #229cda 100%);
            border-radius: 180px 0 0 180px;
        }

        .shape-top {
            display: none;
        }

        .check-card {
            width: 430px;
            max-width: 90%;
            margin: auto;
            background: #fff;
            border-radius: 25px;
            padding: 40px;
            position: relative;
            z-index: 5;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .15);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            color: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .check-card h2 {
            margin: 0;
            text-align: center;
            color: #1f3b57;
            font-size: 28px;
        }

        .check-card p {
            text-align: center;
            color: #8a9bad;
            line-height: 1.6;
            margin: 10px 0 25px;
        }

        .form-group {
            position: relative;
            margin-bottom: 18px;
        }

        .form-group input {
            width: 100%;
            height: 55px;
            border: none;
            background: #f3f8fc;
            border-radius: 15px;
            padding: 0 50px 0 18px;
            outline: none;
        }

        .form-group i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #9cb2c5;
        }

        .btn-check {
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .result-box {
            margin-top: 25px;
            padding: 18px;
            border-radius: 18px;
            line-height: 1.8;
        }

        .result-success {
            background: #e8f8ef;
            border: 1px solid #c7efd7;
        }

        .result-warning {
            background: #fff7e6;
            border: 1px solid #ffe2a3;
            text-align: center;
        }

        .result-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 50px;
            background: #d8f1ff;
            color: #168ed0;
            font-weight: bold;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 25px 0;
            color: #b0bdc8;
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
            text-align: center;
            text-decoration: none;
            height: 52px;
            line-height: 52px;
            border: 1px solid #cbdce9;
            border-radius: 15px;
            color: #3e5870;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #f4fbff;
        }

        @media (max-width:768px) {

            body {
                padding: 20px;
                align-items: center;
            }

            .page-wrapper {
                width: 100%;
                min-height: auto;
                padding: 40px 0;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .check-card {
                width: 90%;
                max-width: 420px;
                padding: 30px 25px;
                margin: auto;
            }

            .shape-left {
                left: -130px;
                bottom: -100px;
                opacity: .3;
            }

            .shape-right {
                right: -170px;
                top: 60%;
                opacity: .3;
            }

            .shape-bottom {
                opacity: .2;
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
                        required>
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