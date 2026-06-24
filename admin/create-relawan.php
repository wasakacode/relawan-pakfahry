<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users 
            (name, username, password, role, kecamatan) 
            VALUES (?, ?, ?, 'relawan', ?)");

        $stmt->execute([
            $_POST['nama_lengkap'],
            $_POST['username'],
            $hash,
            $_POST['kecamatan'] ?: null
        ]);

        $userId = $pdo->lastInsertId();

        create_profile($pdo, 'relawan', $userId);

        // ambil id profile relawan
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM profiles
                    WHERE user_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$userId]);

                $profileId = $stmt->fetchColumn();

                // simpan admin yang dipilih
                if (empty($_POST['admin_id'])) {
                        throw new Exception('Pilih minimal satu admin.');
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO profile_admin
                        (profile_id, admin_profile_id)
                        VALUES (?, ?)
                    ");

                    foreach ($_POST['admin_id'] as $adminId) {
                        $stmt->execute([
                            $profileId,
                            $adminId
                        ]);
                }
        $pdo->commit();

        flash('success', 'Akun relawan berhasil dibuat.');
        redirect('admin/list-relawan.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal membuat relawan: ' . $e->getMessage());
    }
}

            $stmt = $pdo->query("
                SELECT
                    p.id,
                    p.nama_lengkap,
                    GROUP_CONCAT(
                        d.daerah_pemilihan
                        ORDER BY d.daerah_pemilihan
                        SEPARATOR ', '
                    ) AS dapil
                FROM profiles p

                LEFT JOIN profile_dapil pd
                    ON pd.profile_id = p.id

                LEFT JOIN dapil d
                    ON d.id = pd.dapil_id

                WHERE
                    p.type='admin'
                    AND p.profile_active=1

                GROUP BY
                    p.id,
                    p.nama_lengkap

                ORDER BY
                    p.nama_lengkap
                ");

                $adminList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dapilList = $stmt->fetchAll();
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Buat Akun Relawan</h1>

<form method="POST" enctype="multipart/form-data">

    <?php include __DIR__ . '/../partials/form-fields.php'; ?>

<div class="card shadow mb-4">

    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Pilih Admin
        </h6>
    </div>

    <div class="card-body row">

        <?php foreach($adminList as $admin): ?>

            <div class="col-md-6 mb-3">

                <div class="custom-control custom-checkbox">

                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="admin_<?= $admin['id'] ?>"
                        name="admin_id[]"
                        value="<?= $admin['id'] ?>">

                    <label
                        class="custom-control-label"
                        for="admin_<?= $admin['id'] ?>">

                        <strong><?= e($admin['nama_lengkap']) ?></strong>

                        <br>

                        <small class="text-muted">

                            <?= e($admin['dapil']) ?>

                        </small>

                    </label>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Langkah 5 - Dokumentasi</h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-4">
                <label>Foto KTP <span class="text-danger">*</span></label>
                <input type="file"
                    name="foto_ktp"
                    class="form-control-file"
                    accept=".pdf,image/*"
                    required>
                <small class="text-danger">
                    Wajib upload file PDF atau gambar (JPG, JPEG, PNG).
                </small>
            </div>

            <div class="form-group col-md-4">
                <label>Foto Diri <span class="text-danger">*</span></label>
                <input type="file"
                    name="foto_diri"
                    class="form-control-file"
                    accept=".pdf,image/*"
                    required>
                <small class="text-danger">
                    Wajib upload file PDF atau gambar (JPG, JPEG, PNG).
                </small>
            </div>

            <!-- Role Relawan -->
            <div class="form-group col-md-4">
                <label>Foto Kartu Keluarga <span class="text-danger">*</span></label>
                <input type="file"
                    name="foto_kartu_keluarga"
                    class="form-control-file"
                    accept=".pdf,image/*"
                    required>
                <small class="text-danger">
                    Wajib upload file PDF atau gambar (JPG, JPEG, PNG).
                </small>
            </div>

        </div>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $files = ['foto_ktp', 'foto_diri', 'foto_kartu_keluarga'];
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        foreach ($files as $file) {

            if (isset($_FILES[$file]) && !empty($_FILES[$file]['name'])) {

                $ext = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    echo "<div class='alert alert-danger'>
                        File $file harus berupa PDF atau gambar.
                      </div>";
                }
            } else {

                echo "<div class='alert alert-danger'>
                    File $file wajib diupload.
                  </div>";
            }
        }
    }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Langkah 6 - Informasi Akun</h6>
        </div>

        <div class="card-body row">

            <div class="form-group col-md-6">
                <label>Username</label>
                <input name="username" class="form-control" required>
            </div>

            <div class="form-group col-md-6">
                <label>Password</label>

                <div class="input-group">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required>

                    <div class="input-group-append">
                        <button
                            class="btn btn-outline-secondary"
                            type="button"
                            id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <button class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Simpan Relawan
    </button>

</form>

<script>
    document.getElementById('togglePassword').addEventListener('click', function() {

        const password = document.getElementById('password');
        const icon = this.querySelector('i');

        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }

    });
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>