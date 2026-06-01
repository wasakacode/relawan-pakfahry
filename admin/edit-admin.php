<?php
require_once __DIR__ . '/../auth/auth.php';

require_role('superadmin');

$id = $_GET['id'] ?? null;

if (!$id) {
    flash('error', 'Data admin tidak ditemukan.');
    redirect('admin/list-admin.php');
}

$stmt = $pdo->prepare("SELECT p.*, u.username, u.is_active, u.id AS akun_id
                       FROM profiles p
                       LEFT JOIN users u ON p.user_id = u.id
                       WHERE p.id = ? 
                       AND p.type = 'admin'
                       LIMIT 1");

$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    flash('error', 'Data admin tidak ditemukan.');
    redirect('admin/list-admin.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $username = trim($_POST['username'] ?? '');
        $passwordBaru = trim($_POST['password'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($passwordBaru !== '') {
            $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

            $stmtUser = $pdo->prepare("UPDATE users 
                                       SET username = ?, password = ?, is_active = ?, name = ?, kecamatan = ?
                                       WHERE id = ?");
            $stmtUser->execute([
                $username,
                $hash,
                $isActive,
                $_POST['nama_lengkap'],
                $_POST['kecamatan'],
                $data['akun_id']
            ]);
        } else {
            $stmtUser = $pdo->prepare("UPDATE users 
                                       SET username = ?, is_active = ?, name = ?, kecamatan = ?
                                       WHERE id = ?");
            $stmtUser->execute([
                $username,
                $isActive,
                $_POST['nama_lengkap'],
                $_POST['kecamatan'],
                $data['akun_id']
            ]);
        }

        $stmtProfile = $pdo->prepare("UPDATE profiles SET
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

        $stmtProfile->execute([
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

        $pdo->commit();

        flash('success', 'Data admin berhasil diperbarui.');
        redirect('admin/detail-admin.php?id=' . $data['id']);

    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal memperbarui data admin: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800 mb-0">Edit Data Admin (Koordinator Kecamatan)</h1>

    <a href="<?= url('admin/detail-admin.php?id=' . $data['id']) ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<form method="POST">

    <div class="card content-card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-user-lock mr-2" style="color:#3db7ee;"></i>
                Data Akun Admin
            </h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= e($data['username']) ?>" required>
            </div>

            <div class="form-group col-md-6">
                <label>Password Baru</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                <small class="text-muted">Password lama tidak dapat ditampilkan. Isi kolom ini hanya jika ingin mengganti password.</small>
            </div>

            <div class="form-group col-md-6">
                <label>Status Akun</label><br>
                <div class="custom-control custom-switch">
                    <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" 
                           <?= ((int)$data['is_active'] === 1) ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="is_active">Akun Aktif</label>
                </div>
            </div>

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