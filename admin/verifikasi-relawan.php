<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

/*
|--------------------------------------------------------------------------
| Ambil Data
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? null;
    $statusVerifikasi = $_POST['status_verifikasi'] ?? '';
    $catatanVerifikasi = trim($_POST['catatan_verifikasi'] ?? '');

} else {

    $id = $_GET['id'] ?? null;
    $statusVerifikasi = $_GET['status_verifikasi'] ?? '';
    $catatanVerifikasi = '';

}

if (!$id || !in_array($statusVerifikasi, ['terdaftar', 'ditolak'])) {

    flash('error', 'Permintaan tidak valid.');
    redirect('admin/list-relawan.php');

}

try {

    /*
    |--------------------------------------------------------------------------
    | Hak Akses Admin
    |--------------------------------------------------------------------------
    */

    if (current_user()['role'] === 'admin') {

        $stmt = $pdo->prepare("
            SELECT id
            FROM profiles
            WHERE
                id = ?
                AND type='relawan'
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        if (!$stmt->fetch()) {

            flash(
                'error',
                'Anda tidak memiliki akses.'
            );

            redirect('admin/list-relawan.php');

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Validasi Penolakan
    |--------------------------------------------------------------------------
    */

    if ($statusVerifikasi == 'ditolak' && $catatanVerifikasi == '') {

        flash(
            'error',
            'Alasan penolakan wajib diisi.'
        );

        redirect('admin/list-relawan.php');

    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE profiles
        SET
            status_verifikasi = ?,
            catatan_verifikasi = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $statusVerifikasi,
        $catatanVerifikasi,
        $id
    ]);

    /*
    |--------------------------------------------------------------------------
    | Flash Message
    |--------------------------------------------------------------------------
    */

    if ($statusVerifikasi == 'terdaftar') {

        flash(
            'success',
            'Relawan berhasil diverifikasi.'
        );

    } else {

        flash(
            'success',
            'Relawan berhasil ditolak.'
        );

    }

} catch (Exception $e) {

    flash(
        'error',
        'Gagal memperbarui status: ' .
        $e->getMessage()
    );

}

redirect('admin/list-relawan.php');