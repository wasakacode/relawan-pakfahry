<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Data Dapil tidak valid.');
    redirect('admin/list-dapil.php');
    exit;
}

// Cek data
$stmt = $pdo->prepare("
    SELECT id
    FROM dapil
    WHERE id = ?
");

$stmt->execute([$id]);

if (!$stmt->fetch()) {
    flash('error', 'Data Dapil tidak ditemukan.');
    redirect('admin/list-dapil.php');
    exit;
}

// Hapus data
$stmt = $pdo->prepare("
    DELETE FROM dapil
    WHERE id = ?
");

$stmt->execute([$id]);

flash('success', 'Data Dapil berhasil dihapus.');
redirect('admin/list-dapil.php');
exit;