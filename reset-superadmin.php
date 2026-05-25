<?php
require_once __DIR__ . '/config/database.php';

$username = 'superadmin';
$password = 'superadmin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$cek = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$cek->execute([$username]);
$user = $cek->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $update = $pdo->prepare("UPDATE users 
                             SET password = ?, role = 'superadmin', is_active = 1 
                             WHERE username = ?");
    $update->execute([$hash, $username]);

    echo "<h2>Password superadmin berhasil di-reset.</h2>";
} else {
    $insert = $pdo->prepare("INSERT INTO users 
        (name, username, password, role, kecamatan, is_active) 
        VALUES (?, ?, ?, ?, ?, ?)");

    $insert->execute([
        'Superadmin',
        'superadmin',
        $hash,
        'superadmin',
        null,
        1
    ]);

    echo "<h2>Akun superadmin berhasil dibuat.</h2>";
}

echo "<p>Username: <b>superadmin</b></p>";
echo "<p>Password: <b>superadmin123</b></p>";
echo "<p><a href='login.php'>Login sekarang</a></p>";