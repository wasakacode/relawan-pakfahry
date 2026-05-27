<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin', 'relawan']);

$id = $_GET['id'] ?? null;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmtUpdate = $pdo->prepare("UPDATE profiles SET
            nik = ?,
            nama_lengkap = ?,
            tempat_lahir = ?,
            tanggal_lahir = ?,
            jenis_kelamin = ?,
            golongan_darah = ?,
            status_pernikahan = ?,
            agama = ?,
            pekerjaan = ?,
            alamat = ?,
            provinsi = ?,
            kab_kota = ?,
            kecamatan = ?,
            desa_kelurahan = ?,
            rt = ?,
            rw = ?,
            tps = ?,
            nomor_kk = ?,
            nomor_telepon = ?,
            nomor_whatsapp = ?,
            status_verifikasi = ?
            WHERE id = ?
        ");

        $stmtUpdate->execute([
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
            $_POST['status_verifikasi'] ?: 'terdaftar',
            $data['id']
        ]);

        flash('success', 'Data dukungan berhasil diperbarui.');
        redirect('dukungan/detail.php?id=' . $data['id']);

    } catch (Exception $e) {
        flash('error', 'Gagal memperbarui data dukungan: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800 mb-0">Edit Data Dukungan</h1>

    <a href="<?= url('dukungan/detail.php?id=' . $data['id']) ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<form method="POST">

    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-check-circle mr-2" style="color:#3db7ee;"></i>
                Status Data
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Status Verifikasi</label>
                <select name="status_verifikasi" class="form-control">
                    <option value="terdaftar" <?= $data['status_verifikasi'] === 'terdaftar' ? 'selected' : '' ?>>Terdaftar</option>
                    <option value="pending" <?= $data['status_verifikasi'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="ditolak" <?= $data['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
            </div>

        </div>
    </div>


    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-id-card mr-2" style="color:#3db7ee;"></i>
                Data Kependudukan
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">
                <label>NIK</label>
                <input name="nik" class="form-control" value="<?= e($data['nik']) ?>" required>
            </div>

            <div class="form-group col-md-8">
                <label>Nama Lengkap</label>
                <input name="nama_lengkap" class="form-control" value="<?= e($data['nama_lengkap']) ?>" required>
            </div>

            <div class="form-group col-md-4">
                <label>Tempat Lahir</label>
                <input name="tempat_lahir" class="form-control" value="<?= e($data['tempat_lahir']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control" value="<?= e($data['tanggal_lahir']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                    <option value="">Pilih</option>
                    <option value="Laki-laki" <?= $data['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= $data['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    <option value="laki-laki" <?= $data['jenis_kelamin'] === 'laki-laki' ? 'selected' : '' ?>>laki-laki</option>
                    <option value="perempuan" <?= $data['jenis_kelamin'] === 'perempuan' ? 'selected' : '' ?>>perempuan</option>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label>Golongan Darah</label>
                <select name="golongan_darah" class="form-control">
                    <option value="">Pilih</option>
                    <option value="A" <?= $data['golongan_darah'] === 'A' ? 'selected' : '' ?>>A</option>
                    <option value="B" <?= $data['golongan_darah'] === 'B' ? 'selected' : '' ?>>B</option>
                    <option value="AB" <?= $data['golongan_darah'] === 'AB' ? 'selected' : '' ?>>AB</option>
                    <option value="O" <?= $data['golongan_darah'] === 'O' ? 'selected' : '' ?>>O</option>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label>Status Pernikahan</label>
                <input name="status_pernikahan" class="form-control" value="<?= e($data['status_pernikahan']) ?>">
            </div>

            <div class="form-group col-md-3">
                <label>Agama</label>
                <input name="agama" class="form-control" value="<?= e($data['agama']) ?>">
            </div>

            <div class="form-group col-md-3">
                <label>Pekerjaan</label>
                <input name="pekerjaan" class="form-control" value="<?= e($data['pekerjaan']) ?>">
            </div>

            <div class="form-group col-md-12">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control"><?= e($data['alamat']) ?></textarea>
            </div>

        </div>
    </div>


    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-map-marker-alt mr-2" style="color:#3db7ee;"></i>
                Pemetaan Wilayah
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Provinsi</label>
                <input name="provinsi" class="form-control" value="<?= e($data['provinsi']) ?>">
            </div>

            <div class="form-group col-md-6">
                <label>Kabupaten/Kota</label>
                <input name="kab_kota" class="form-control" value="<?= e($data['kab_kota']) ?>">
            </div>

            <div class="form-group col-md-6">
                <label>Kecamatan</label>
                <input name="kecamatan" class="form-control" value="<?= e($data['kecamatan']) ?>">
            </div>

            <div class="form-group col-md-6">
                <label>Desa/Kelurahan</label>
                <input name="desa_kelurahan" class="form-control" value="<?= e($data['desa_kelurahan']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>RT</label>
                <input name="rt" class="form-control" value="<?= e($data['rt']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>RW</label>
                <input name="rw" class="form-control" value="<?= e($data['rw']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>TPS</label>
                <input name="tps" class="form-control" value="<?= e($data['tps']) ?>">
            </div>

        </div>
    </div>


    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-phone mr-2" style="color:#3db7ee;"></i>
                Kontak dan KK
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">
                <label>Nomor KK</label>
                <input name="nomor_kk" class="form-control" value="<?= e($data['nomor_kk']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Nomor Telepon</label>
                <input name="nomor_telepon" class="form-control" value="<?= e($data['nomor_telepon']) ?>">
            </div>

            <div class="form-group col-md-4">
                <label>Nomor WhatsApp</label>
                <input name="nomor_whatsapp" class="form-control" value="<?= e($data['nomor_whatsapp']) ?>">
            </div>

        </div>
    </div>

    <button type="submit" class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Simpan Perubahan
    </button>

</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>