<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function is_login()
{
    return isset($_SESSION['user']);
}

function require_login()
{
    if (!is_login()) {
        redirect('login.php');
        exit;
    }
}

function require_role($roles)
{
    require_login();

    $roles = is_array($roles) ? $roles : [$roles];

    if (!in_array(current_user()['role'], $roles)) {
        http_response_code(403);
        die('Akses ditolak. Role Anda tidak berhak membuka halaman ini.');
    }
}

function can_manage_data()
{
    $role = current_user()['role'] ?? null;

    return in_array($role, [
        'superadmin',
        'admin',
        'relawan'
    ]);
}

function can_create_relawan()
{
    $role = current_user()['role'] ?? null;

    return in_array($role, [
        'superadmin',
        'admin'
    ]);
}

/*
|--------------------------------------------------------------------------
| PROFILE RELAWAN
|--------------------------------------------------------------------------
*/

function current_profile(PDO $pdo)
{
    if (!current_user()) {
        return null;
    }

    static $profile = null;

    if ($profile !== null) {
        return $profile;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        current_user()['id']
    ]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    return $profile;
}

function profile_completed(PDO $pdo)
{
    $profile = current_profile($pdo);

    return $profile && (int)$profile['profile_complete'] === 1;
}

function require_profile_complete(PDO $pdo)
{
    if (current_user()['role'] !== 'relawan') {
        return;
    }

    $profile = current_profile($pdo);

    if (!$profile) {

        flash(
            'error',
            'Profil relawan tidak ditemukan.'
        );

        redirect('login.php');
        exit;
    }

    if ((int)$profile['profile_complete'] !== 1) {

        flash(
            'error',
            'Profil Anda belum lengkap. Silakan lengkapi profil terlebih dahulu sebelum menggunakan fitur ini.'
        );

        redirect('relawan/profil.php');
        exit;
    }
}
?>