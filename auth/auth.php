<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_login() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_login()) {
        redirect('login.php');
    }
}

function require_role($roles) {
    require_login();

    $roles = is_array($roles) ? $roles : [$roles];

    if (!in_array(current_user()['role'], $roles)) {
        http_response_code(403);
        die('Akses ditolak. Role Anda tidak berhak membuka halaman ini.');
    }
}

function can_manage_data() {
    $role = current_user()['role'] ?? null;
    return in_array($role, ['superadmin', 'admin', 'relawan']);
}

function can_create_relawan() {
    $role = current_user()['role'] ?? null;
    return in_array($role, ['superadmin', 'admin']);
}
?>