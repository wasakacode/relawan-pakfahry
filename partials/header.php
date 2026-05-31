<?php 
require_once __DIR__ . '/../auth/auth.php'; 
require_login(); 
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>

    <link href="<?= url('vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= url('css/sb-admin-2.min.css') ?>" rel="stylesheet">

    <style>
        :root {
            --baby-blue: #c8efff;
            --soft-blue: #86d7f5;
            --main-blue: #3db7ee;
            --deep-blue: #118dd0;
            --dark-text: #1f3b57;
            --muted-text: #7890a6;
            --white-soft: rgba(255, 255, 255, 0.78);
        }

        body {
            background: linear-gradient(135deg, #eaf9ff, #c8efff, #f6fcff);
            color: var(--dark-text);
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        #wrapper {
            background: transparent;
        }

        #content-wrapper {
            background: transparent !important;
        }

        .container-fluid {
            padding-left: 2rem;
            padding-right: 2rem;
        }

        .sidebar {
            background: linear-gradient(180deg, #4bb6e8 0%, #3db7ee 45%, #118dd0 100%) !important;
            box-shadow: 12px 0 35px rgba(17, 141, 208, 0.18);
        }

        .sidebar .sidebar-brand {
            height: 5.5rem;
        }

        .sidebar .sidebar-brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar .sidebar-brand-text {
            font-size: 15px;
            letter-spacing: .5px;
        }

        .sidebar .nav-item .nav-link {
            margin: 4px 14px;
            border-radius: 14px;
            transition: 0.2s;
        }

        .sidebar .nav-item .nav-link:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateX(3px);
        }

        .sidebar .nav-item .nav-link i,
        .sidebar .nav-item .nav-link span {
            color: #ffffff !important;
        }

        .sidebar-heading {
            color: rgba(255,255,255,.72) !important;
            font-size: 11px;
            letter-spacing: .8px;
        }

        .topbar {
            background: rgba(255, 255, 255, 0.82) !important;
            backdrop-filter: blur(12px);
            border-radius: 0 0 26px 26px;
            box-shadow: 0 16px 35px rgba(49, 130, 170, 0.12) !important;
            margin-left: 1.2rem;
            margin-right: 1.2rem;
        }

        .dashboard-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #eaf9ff 50%, #bdeeff 100%);
            border-radius: 28px;
            padding: 34px;
            box-shadow: 0 24px 55px rgba(17, 141, 208, 0.12);
            margin-bottom: 28px;
        }

        .dashboard-hero::before {
            content: "";
            position: absolute;
            right: -80px;
            top: -90px;
            width: 280px;
            height: 280px;
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            border-radius: 50%;
            opacity: 0.20;
        }

        .dashboard-hero::after {
            content: "";
            position: absolute;
            right: 110px;
            bottom: -70px;
            width: 180px;
            height: 180px;
            background: #ffffff;
            border-radius: 50%;
            opacity: 0.70;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-block;
            background: #dff5ff;
            color: #168ed0;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .hero-title {
            font-size: 30px;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .hero-desc {
            color: var(--muted-text);
            max-width: 720px;
            margin-bottom: 0;
            line-height: 1.7;
        }

        .stat-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 18px 42px rgba(17, 141, 208, 0.10);
            overflow: hidden;
            transition: 0.25s;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 50px rgba(17, 141, 208, 0.16);
        }

        .stat-card .card-body {
            padding: 24px;
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #ffffff;
            box-shadow: 0 14px 28px rgba(17, 141, 208, 0.22);
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, #3db7ee, #118dd0);
        }

        .stat-icon.success {
            background: linear-gradient(135deg, #5bd6c9, #23a69a);
        }

        .stat-icon.info {
            background: linear-gradient(135deg, #7ed7ff, #299be6);
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, #ffd36e, #f4a62a);
        }

        .stat-label {
            font-size: 12px;
            color: #7890a6;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 850;
            color: var(--dark-text);
            line-height: 1;
        }

        .content-card {
            border: none;
            border-radius: 24px;
            background: rgba(255,255,255,0.88);
            box-shadow: 0 18px 42px rgba(17, 141, 208, 0.10);
            overflow: hidden;
        }

        .content-card .card-header {
            background: transparent;
            border-bottom: 1px solid #e4f4fb;
            padding: 20px 24px;
        }

        .content-card .card-header h6 {
            color: var(--dark-text) !important;
            font-weight: 800;
        }

        .content-card .card-body {
            padding: 24px;
        }

        .role-table {
            margin-bottom: 0;
        }

        .role-table th {
            background: #f1faff;
            color: var(--dark-text);
            border-color: #dff3fc !important;
        }

        .role-table td {
            border-color: #dff3fc !important;
            vertical-align: middle;
            color: #4c647a;
        }

        .role-pill {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            background: #dff5ff;
            color: #168ed0;
        }

        .profile-box {
            text-align: center;
            padding: 8px 0;
        }

        .profile-avatar {
            width: 78px;
            height: 78px;
            border-radius: 24px;
            background: linear-gradient(135deg, #3db7ee, #118dd0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 34px;
            box-shadow: 0 16px 30px rgba(17, 141, 208, 0.25);
        }

        .profile-name {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 4px;
        }

        .profile-role {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 999px;
            background: #dff5ff;
            color: #168ed0;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .quick-action {
            display: block;
            text-decoration: none;
            background: #f1faff;
            color: #315a78;
            padding: 13px 15px;
            border-radius: 16px;
            margin-bottom: 10px;
            font-weight: 700;
            transition: 0.2s;
        }

        .quick-action:hover {
            text-decoration: none;
            background: #dff5ff;
            color: #118dd0;
            transform: translateX(3px);
        }

        .quick-action i {
            margin-right: 8px;
        }

        .alert {
            border-radius: 16px;
            border: none;
        }

        @media (max-width: 768px) {
            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .topbar {
                margin-left: .7rem;
                margin-right: .7rem;
            }

            .dashboard-hero {
                padding: 26px;
            }

            .hero-title {
                font-size: 24px;
            }
        }

        #wrapper {
    display: flex !important;
}

#content-wrapper {
    flex: 1 !important;
}

.topbar {
    position: relative;
    z-index: 1050;
}

.dropdown-menu {
    z-index: 99999 !important;
}
    </style>
</head>

<body id="page-top">

<div id="wrapper">