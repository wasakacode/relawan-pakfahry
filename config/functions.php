<?php
function upload_file($field, $folder)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];

    if (!in_array($_FILES[$field]['type'], $allowed)) {
        return null;
    }

    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
    $name = uniqid($field . '_') . '.' . $ext;

    $targetDir = __DIR__ . '/../uploads/' . $folder;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $target = $targetDir . '/' . $name;
    move_uploaded_file($_FILES[$field]['tmp_name'], $target);

    return 'uploads/' . $folder . '/' . $name;
}

function create_profile($pdo, $type, $userId = null)
{
    $stmt = $pdo->prepare("INSERT INTO profiles (
        type, user_id, created_by, nik, nama_lengkap, tempat_lahir, tanggal_lahir,
        jenis_kelamin, golongan_darah, status_pernikahan, agama, pekerjaan, alamat,
        provinsi, kab_kota, kecamatan, desa_kelurahan, rt, rw, tps, nomor_kk,
        nomor_telepon, nomor_whatsapp, foto_ktp, foto_diri, foto_bukti_rekrut
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $fotoKtp = upload_file('foto_ktp', 'ktp');
    $fotoDiri = upload_file('foto_diri', 'diri');
    $fotoBukti = upload_file('foto_bukti_rekrut', 'bukti');

    $createdBy = current_user()['id'] ?? null;

    $stmt->execute([
        $type,
        $userId,
        $createdBy,
        $_POST['nik'],
        $_POST['nama_lengkap'],
        $_POST['tempat_lahir'] ?: null,
        $_POST['tanggal_lahir'] ?: null,
        $_POST['jenis_kelamin'] ?: null,
        $_POST['golongan_darah'] ?: null,
        $_POST['status_pernikahan'] ?: null,
        $_POST['agama'] ?: null,
        $_POST['pekerjaan'] ?: null,
        $_POST['alamat'] ?: null,
        $_POST['provinsi'] ?: null,
        $_POST['kab_kota'] ?: null,
        $_POST['kecamatan'] ?: null,
        $_POST['desa_kelurahan'] ?: null,
        $_POST['rt'] ?: null,
        $_POST['rw'] ?: null,
        $_POST['tps'] ?: null,
        $_POST['nomor_kk'] ?: null,
        $_POST['nomor_telepon'] ?: null,
        $_POST['nomor_whatsapp'] ?: null,
        $fotoKtp,
        $fotoDiri,
        $fotoBukti
    ]);

    $profileId = $pdo->lastInsertId();

    if (!empty($_POST['keluarga_nik'])) {

        $fam = $pdo->prepare("
        INSERT INTO family_members
        (
            profile_id,
            hubungan_keluarga,
            nik,
            nama_lengkap,
            tempat_lahir,
            tanggal_lahir,
            jenis_kelamin,
            agama,
            pekerjaan
        )
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

        foreach ($_POST['keluarga_nik'] as $i => $nik) {

            // Skip jika kosong
            if (empty($nik)) {
                continue;
            }

            $fam->execute([
                $profileId,
                $_POST['keluarga_hubungan_keluarga'][$i] ?? null,
                $_POST['keluarga_nik'][$i] ?? null,
                $_POST['keluarga_nama'][$i] ?? null,
                $_POST['keluarga_tempat_lahir'][$i] ?? null,
                $_POST['keluarga_tanggal_lahir'][$i] ?? null,
                $_POST['keluarga_jenis_kelamin'][$i] ?? null,
                $_POST['keluarga_agama'][$i] ?? null,
                $_POST['keluarga_pekerjaan'][$i] ?? null
            ]);
        }
    }

    return $profileId;
}
