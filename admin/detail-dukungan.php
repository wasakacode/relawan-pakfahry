<?php
require_once __DIR__ . '/../auth/auth.php';

require_role(['superadmin', 'admin']);

$id = $_GET['id'] ?? null;

if (!$id) {
    flash('error', 'Data relawan tidak ditemukan.');
    redirect('admin/list-relawan.php');
}

if (current_user()['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.name AS nama_akun, u.is_active
                           FROM profiles p
                           LEFT JOIN users u ON p.user_id = u.id
                           WHERE p.id = ? 
                           AND p.type = 'relawan'
                           AND p.created_by = ?
                           LIMIT 1");
    $stmt->execute([$id, current_user()['id']]);
} else {
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.name AS nama_akun, u.is_active
                           FROM profiles p
                           LEFT JOIN users u ON p.user_id = u.id
                           WHERE p.id = ? 
                           AND p.type = 'relawan'
                           LIMIT 1");
    $stmt->execute([$id]);
}

$data = $stmt->fetch();

if (!$data) {
    flash('error', 'Data relawan tidak ditemukan atau Anda tidak memiliki akses.');
    redirect('admin/list-relawan.php');
}

$stmtFamily = $pdo->prepare("SELECT * FROM family_members WHERE profile_id = ?");
$stmtFamily->execute([$data['id']]);
$families = $stmtFamily->fetchAll();

function tampilkanFileDokumentasi($file, $label) {
    if (empty($file)) {
        echo '<div class="doc-card">';
        echo '<div class="doc-label">' . e($label) . '</div>';
        echo '<div class="doc-empty">Belum ada file</div>';
        echo '</div>';
        return;
    }

    $url = url($file);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    echo '<div class="doc-card">';
    echo '<div class="doc-label">' . e($label) . '</div>';

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        echo '<a href="' . $url . '" target="_blank">';
        echo '<img src="' . $url . '" class="doc-image" alt="' . e($label) . '">';
        echo '</a>';
    } elseif ($ext === 'pdf') {
        echo '<div class="doc-pdf">';
        echo '<i class="fas fa-file-pdf"></i>';
        echo '<p>File PDF</p>';
        echo '</div>';
    } else {
        echo '<div class="doc-pdf">';
        echo '<i class="fas fa-file"></i>';
        echo '<p>File Dokumen</p>';
        echo '</div>';
    }

    echo '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-outline-primary mt-2">';
    echo '<i class="fas fa-eye"></i> Buka File';
    echo '</a>';

    echo '</div>';
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<style>
    .profile-photo-box {
        width: 110px;
        height: 110px;
        border-radius: 28px;
        overflow: hidden;
        margin: 0 auto 18px;
        background: linear-gradient(135deg,#3db7ee,#118dd0);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 42px;
        box-shadow: 0 16px 30px rgba(17,141,208,.25);
    }

    .profile-photo-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .doc-card {
        background: #f8fcff;
        border: 1px solid #dff3fc;
        border-radius: 18px;
        padding: 14px;
        height: 100%;
        text-align: center;
    }

    .doc-label {
        font-weight: 800;
        color: #1f3b57;
        margin-bottom: 12px;
    }

    .doc-image {
        width: 100%;
        height: 230px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid #dceff8;
        transition: 0.2s;
        background: #ffffff;
    }

    .doc-image:hover {
        transform: scale(1.02);
        box-shadow: 0 12px 28px rgba(17,141,208,.18);
    }

    .doc-pdf {
        height: 230px;
        border-radius: 14px;
        border: 1px dashed #b9dff0;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .doc-pdf i {
        font-size: 52px;
        color: #e74a3b;
        margin-bottom: 10px;
    }

    .doc-pdf p {
        margin: 0;
        color: #7890a6;
        font-weight: 700;
    }

    .doc-empty {
        height: 230px;
        border-radius: 14px;
        background: #f3f8fc;
        color: #7890a6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800 mb-0">Detail Data Relawan</h1>

    <a href="<?= url('admin/list-relawan.php') ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="row">

    <div class="col-lg-4 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-body text-center">

                <div class="profile-photo-box">
                    <?php if (!empty($data['foto_diri'])): ?>
                        <img src="<?= url($data['foto_diri']) ?>" alt="Foto Diri">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>

                <h4 class="font-weight-bold mb-1">
                    <?= e($data['nama_lengkap']) ?>
                </h4>

                <p class="text-muted mb-2">
                    NIK: <?= e($data['nik']) ?>
                </p>

                <span class="badge badge-success px-3 py-2">
                    <?= e($data['status_verifikasi']) ?>
                </span>

                <hr>

                <p class="mb-1">
                    <b>Username:</b> <?= e($data['username'] ?? '-') ?>
                </p>

                <p class="mb-1">
                    <b>Password:</b>
                    <span class="text-muted">Terenkripsi / tidak dapat ditampilkan</span>
                </p>

                <small class="text-muted d-block mb-3">
                    Password dapat diganti melalui tombol Edit Data.
                </small>

                <p class="mb-1">
                    <b>Status Akun:</b>
                    <?php if ((int)($data['is_active'] ?? 0) === 1): ?>
                        <span class="badge badge-primary">Aktif</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Tidak Aktif</span>
                    <?php endif; ?>
                </p>

                <hr>

                <a href="<?= url('admin/edit-relawan.php?id=' . $data['id']) ?>" class="btn btn-warning btn-sm btn-block mb-2">
                    <i class="fas fa-edit"></i> Edit Data
                </a>

                <form action="<?= url('admin/delete-relawan.php') ?>" method="POST" 
                      onsubmit="return confirm('Yakin ingin menghapus data relawan ini? Data yang dihapus tidak bisa dikembalikan.');">
                    <input type="hidden" name="id" value="<?= e($data['id']) ?>">

                    <button type="submit" class="btn btn-danger btn-sm btn-block">
                        <i class="fas fa-trash"></i> Hapus Data
                    </button>
                </form>

            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-id-card mr-2" style="color:#3db7ee;"></i>
                    Data Kependudukan
                </h6>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 35%;">NIK</th>
                        <td><?= e($data['nik']) ?></td>
                    </tr>
                    <tr>
                        <th>Nama Lengkap</th>
                        <td><?= e($data['nama_lengkap']) ?></td>
                    </tr>
                    <tr>
                        <th>Tempat/Tanggal Lahir</th>
                        <td><?= e($data['tempat_lahir']) ?>, <?= e($data['tanggal_lahir']) ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Kelamin</th>
                        <td><?= e($data['jenis_kelamin']) ?></td>
                    </tr>
                    <tr>
                        <th>Golongan Darah</th>
                        <td><?= e($data['golongan_darah']) ?></td>
                    </tr>
                    <tr>
                        <th>Status Pernikahan</th>
                        <td><?= e($data['status_pernikahan']) ?></td>
                    </tr>
                    <tr>
                        <th>Agama</th>
                        <td><?= e($data['agama']) ?></td>
                    </tr>
                    <tr>
                        <th>Pekerjaan</th>
                        <td><?= e($data['pekerjaan']) ?></td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td><?= e($data['alamat']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-lg-6 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-map-marker-alt mr-2" style="color:#3db7ee;"></i>
                    Pemetaan Wilayah
                </h6>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 35%;">Provinsi</th>
                        <td><?= e($data['provinsi']) ?></td>
                    </tr>
                    <tr>
                        <th>Kabupaten/Kota</th>
                        <td><?= e($data['kab_kota']) ?></td>
                    </tr>
                    <tr>
                        <th>Kecamatan</th>
                        <td><?= e($data['kecamatan']) ?></td>
                    </tr>
                    <tr>
                        <th>Desa/Kelurahan</th>
                        <td><?= e($data['desa_kelurahan']) ?></td>
                    </tr>
                    <tr>
                        <th>RT/RW</th>
                        <td><?= e($data['rt']) ?> / <?= e($data['rw']) ?></td>
                    </tr>
                    <tr>
                        <th>TPS</th>
                        <td><?= e($data['tps']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-phone mr-2" style="color:#3db7ee;"></i>
                    Kontak dan Kartu Keluarga
                </h6>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 35%;">Nomor KK</th>
                        <td><?= e($data['nomor_kk']) ?></td>
                    </tr>
                    <tr>
                        <th>Nomor Telepon</th>
                        <td><?= e($data['nomor_telepon']) ?></td>
                    </tr>
                    <tr>
                        <th>Nomor WhatsApp</th>
                        <td><?= e($data['nomor_whatsapp']) ?></td>
                    </tr>
                    <tr>
                        <th>Dibuat Pada</th>
                        <td><?= e($data['created_at']) ?></td>
                    </tr>
                    <tr>
                        <th>Diperbarui Pada</th>
                        <td><?= e($data['updated_at']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="card content-card shadow mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users mr-2" style="color:#3db7ee;"></i>
            Data Keluarga
        </h6>
    </div>

    <div class="card-body">

        <?php if (count($families) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead style="background:#f1faff;">
                        <tr>
                            <th>No</th>
                            <th>Hubungan</th>
                            <th>NIK</th>
                            <th>Nama Lengkap</th>
                            <th>Tempat/Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>Agama</th>
                            <th>Pekerjaan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($families as $i => $f): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($f['hubungan_keluarga']) ?></td>
                                <td><?= e($f['nik']) ?></td>
                                <td><?= e($f['nama_lengkap']) ?></td>
                                <td><?= e($f['tempat_lahir']) ?>, <?= e($f['tanggal_lahir']) ?></td>
                                <td><?= e($f['jenis_kelamin']) ?></td>
                                <td><?= e($f['agama']) ?></td>
                                <td><?= e($f['pekerjaan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                Belum ada data keluarga yang tercatat.
            </div>
        <?php endif; ?>

    </div>
</div>

<div class="card content-card shadow mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-file-image mr-2" style="color:#3db7ee;"></i>
            Dokumentasi
        </h6>
    </div>

    <div class="card-body row">

        <div class="col-md-4 mb-3">
            <?php tampilkanFileDokumentasi($data['foto_ktp'] ?? null, 'Foto KTP'); ?>
        </div>

        <div class="col-md-4 mb-3">
            <?php tampilkanFileDokumentasi($data['foto_diri'] ?? null, 'Foto Diri'); ?>
        </div>

        <div class="col-md-4 mb-3">
            <?php tampilkanFileDokumentasi($data['foto_bukti_rekrut'] ?? null, 'Foto Bukti Rekrut'); ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>