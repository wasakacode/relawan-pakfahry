<?php
require_once __DIR__ . '/../auth/auth.php';

require_role('superadmin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Metode tidak valid.');
    redirect('admin/list-admin.php');
}

$id = $_POST['id'] ?? null;

if (!$id) {
    flash('error', 'Data admin tidak ditemukan.');
    redirect('admin/list-admin.php');
}

$stmt = $pdo->prepare("SELECT * FROM profiles 
                       WHERE id = ? 
                       AND type = 'admin'
                       LIMIT 1");
$stmt->execute([$id]);

$data = $stmt->fetch();

if (!$data) {
    flash('error', 'Data admin tidak ditemukan.');
    redirect('admin/list-admin.php');
}

try {
    $pdo->beginTransaction();

    $userId = $data['user_id'];

    $files = [
        $data['foto_ktp'] ?? null,
        $data['foto_diri'] ?? null,
        $data['foto_bukti_rekrut'] ?? null
    ];

    foreach ($files as $file) {
        if ($file) {
            $path = __DIR__ . '/../' . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    $deleteProfile = $pdo->prepare("DELETE FROM profiles WHERE id = ? AND type = 'admin'");
    $deleteProfile->execute([$data['id']]);

    if ($userId) {
        $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
        $deleteUser->execute([$userId]);
    }

    $pdo->commit();

    flash('success', 'Data admin berhasil dihapus.');
    redirect('admin/list-admin.php');

} catch (Exception $e) {
    $pdo->rollBack();

    flash('error', 'Gagal menghapus data admin: ' . $e->getMessage());
    redirect('admin/detail-admin.php?id=' . $id);
}