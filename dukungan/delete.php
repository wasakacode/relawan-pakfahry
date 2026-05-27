<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Metode tidak valid.');
    redirect('dukungan/list.php');
}

$id = $_POST['id'] ?? null;

if (!$id) {
    flash('error', 'Data dukungan tidak ditemukan.');
    redirect('dukungan/list.php');
}

if (current_user()['role'] === 'relawan') {
    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE id = ? 
                           AND type = 'dukungan'
                           AND created_by = ?
                           LIMIT 1");
    $stmt->execute([$id, current_user()['id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM profiles 
                           WHERE id = ? 
                           AND type = 'dukungan'
                           LIMIT 1");
    $stmt->execute([$id]);
}

$data = $stmt->fetch();

if (!$data) {
    flash('error', 'Data dukungan tidak ditemukan atau Anda tidak memiliki akses.');
    redirect('dukungan/list.php');
}

try {
    $pdo->beginTransaction();

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

    // Data keluarga ikut terhapus karena family_members profile_id memakai ON DELETE CASCADE.
    $deleteProfile = $pdo->prepare("DELETE FROM profiles WHERE id = ? AND type = 'dukungan'");
    $deleteProfile->execute([$data['id']]);

    $pdo->commit();

    flash('success', 'Data dukungan berhasil dihapus.');
    redirect('dukungan/list.php');

} catch (Exception $e) {
    $pdo->rollBack();

    flash('error', 'Gagal menghapus data dukungan: ' . $e->getMessage());
    redirect('dukungan/detail.php?id=' . $id);
}