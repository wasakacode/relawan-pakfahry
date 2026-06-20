<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role('superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users 
            (name, username, password, role, kecamatan) 
            VALUES (?, ?, ?, 'admin', ?)");

        $stmt->execute([
            $_POST['nama_lengkap'],
            $_POST['username'],
            $hash,
            $_POST['kecamatan'] ?: null
        ]);

        $userId = $pdo->lastInsertId();

        create_profile($pdo, 'admin', $userId);

        $pdo->commit();

        flash('success', 'Admin Kecamatan berhasil dibuat.');
        redirect('admin/create-admin.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal membuat admin: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Buat Admin (Koordinator Kecamatan)</h1>

<form method="POST" enctype="multipart/form-data">

    <?php include __DIR__ . '/../partials/form-fields.php'; ?>

    <!-- <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Langkah 6 - Daerah Pemilihan (Dapil)</h6>
        </div>
        <div class="card-body row">
            <div class="form-group col-md-6">
                <label>Daerah Pemilihan (Dapil)</label>
                <select name="dapil" class="form-control">
                <option value="">Pilih</option>
                <option value="Kalsel I">Kalsel I</option>
                <option value="Kalsel II">Kalsel II</option>
            </select>
            </div>
        </div>

    </div> -->

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

            <!-- Role Admin -->
            <div class="form-group col-md-4">
                <label>Foto Surat Persetujuan <span class="text-danger">*</span></label>
                <input type="file"
                    name="foto_surat_persetujuan"
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

        $files = ['foto_ktp', 'foto_diri', 'foto_surat_persetujuan'];
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
        <i class="fas fa-save"></i> Simpan Admin
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