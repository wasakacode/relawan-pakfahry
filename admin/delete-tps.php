<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Data TPS tidak valid.');
    redirect('admin/list-tps.php');
    exit;
}

// Cek data
$stmt = $pdo->prepare("
    SELECT id
    FROM tps_kalsel
    WHERE id = ?
");

$stmt->execute([$id]);

if (!$stmt->fetch()) {
    flash('error', 'Data TPS tidak ditemukan.');
    redirect('admin/list-tps.php');
    exit;
}

// Hapus data
$stmt = $pdo->prepare("
    DELETE FROM tps_kalsel
    WHERE id = ?
");

$stmt->execute([$id]);

flash('success', 'Data TPS berhasil dihapus.');
redirect('admin/list-tps.php');
exit;