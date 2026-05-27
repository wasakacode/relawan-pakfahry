<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Metode tidak valid.');
    redirect('admin/list-relawan.php');
}

$id = $_POST['id'] ?? null;

if (!$id) {
    flash('error', 'Data relawan tidak ditemukan.');
    redirect('admin/list-relawan.php');
}

if (current_user()['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE id = ? 
                           AND type = 'relawan'
                           AND created_by = ?
                           LIMIT 1");
    $stmt->execute([$id, current_user()['id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE id = ? 
                           AND type = 'relawan'
                           LIMIT 1");
    $stmt->execute([$id]);
}

$data = $stmt->fetch();

if (!$data) {
    flash('error', 'Data relawan tidak ditemukan atau Anda tidak memiliki akses.');
    redirect('admin/list-relawan.php');
}

try {
    $pdo->beginTransaction();

    $userId = $data['user_id'];

    // Hapus file dokumentasi jika ada
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

    // Hapus profile. Data family_members akan ikut terhapus karena ON DELETE CASCADE.
    $deleteProfile = $pdo->prepare("DELETE FROM profiles WHERE id = ?");
    $deleteProfile->execute([$data['id']]);

    // Hapus akun user relawan
    if ($userId) {
        $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'relawan'");
        $deleteUser->execute([$userId]);
    }

    $pdo->commit();

    flash('success', 'Data relawan berhasil dihapus.');
    redirect('admin/list-relawan.php');

} catch (Exception $e) {
    $pdo->rollBack();

    flash('error', 'Gagal menghapus data: ' . $e->getMessage());
    redirect('admin/detail-relawan.php?id=' . $id);
}