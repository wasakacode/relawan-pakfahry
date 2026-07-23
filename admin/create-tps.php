<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $provinsi  = trim($_POST['provinsi'] ?? '');
        $kabupaten = trim($_POST['kabupaten'] ?? '');
        $kecamatan = trim($_POST['kecamatan'] ?? '');
        $kelurahan = trim($_POST['kelurahan'] ?? '');
        $noTps     = trim($_POST['no_tps'] ?? '');

        if ($provinsi === '') {
            throw new Exception('Provinsi wajib dipilih.');
        }

        if ($kabupaten === '') {
            throw new Exception('Kabupaten/Kota wajib dipilih.');
        }

        if ($kecamatan === '') {
            throw new Exception('Kecamatan wajib dipilih.');
        }

        if ($kelurahan === '') {
            throw new Exception('Kelurahan wajib dipilih.');
        }

        if ($noTps === '') {
            throw new Exception('Nomor TPS wajib diisi.');
        }

        if (!preg_match('/^\d{3}$/', $noTps)) {
            throw new Exception('Nomor TPS harus terdiri dari 3 digit. Contoh: 001.');
        }

        $cek = $pdo->prepare("
            SELECT COUNT(*)
            FROM tps_kalsel
            WHERE
                provinsi = ?
                AND kabupaten = ?
                AND kecamatan = ?
                AND kelurahan = ?
                AND no_tps = ?
        ");

        $cek->execute([
            $provinsi,
            $kabupaten,
            $kecamatan,
            $kelurahan,
            $noTps
        ]);

        if ($cek->fetchColumn() > 0) {
            throw new Exception('TPS tersebut sudah ada.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO tps_kalsel
            (
                provinsi,
                kabupaten,
                kecamatan,
                kelurahan,
                no_tps
            )
            VALUES
            (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $provinsi,
            $kabupaten,
            $kecamatan,
            $kelurahan,
            $noTps
        ]);

        flash('success', 'Data TPS berhasil ditambahkan.');
        redirect('admin/create-tps.php');
        exit;
    } catch (Exception $e) {

        flash('error', $e->getMessage());
        redirect('admin/create-tps.php');
        exit;
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Tambah Data TPS</h1>

<form method="POST">

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Data TPS
            </h6>
        </div>

        <div class="card-body">

            <div class="row">

                <!-- Provinsi -->
                <div class="form-group col-md-6">
                    <label>Provinsi</label>

                    <select
                        name="provinsi"
                        id="provinsi"
                        class="form-control"
                        required>

                        <option value="">Memuat data provinsi...</option>

                    </select>
                </div>

                <!-- Kabupaten -->
                <div class="form-group col-md-6">
                    <label>Kabupaten/Kota</label>

                    <select
                        name="kabupaten"
                        id="kabupaten"
                        class="form-control"
                        required>

                        <option value="">Pilih provinsi terlebih dahulu</option>

                    </select>
                </div>

                <!-- Kecamatan -->
                <div class="form-group col-md-6">
                    <label>Kecamatan</label>

                    <select
                        name="kecamatan"
                        id="kecamatan"
                        class="form-control"
                        required>

                        <option value="">Pilih kabupaten terlebih dahulu</option>

                    </select>
                </div>

                <!-- Kelurahan -->
                <div class="form-group col-md-6">
                    <label>Kelurahan/Desa</label>

                    <select
                        name="kelurahan"
                        id="kelurahan"
                        class="form-control"
                        required>

                        <option value="">Pilih kecamatan terlebih dahulu</option>

                    </select>
                </div>

                <!-- Nomor TPS -->
                <div class="form-group col-md-6">

                    <label>Nomor TPS</label>

                    <input
                        type="text"
                        name="no_tps"
                        class="form-control"
                        placeholder="Contoh: 001"
                        maxlength="3"
                        pattern="[0-9]{3}"
                        inputmode="numeric"
                        required>

                    <small class="text-muted">
                        Masukkan nomor TPS dalam format 3 digit, contoh: 001, 015, 120.
                    </small>

                </div>

            </div>

        </div>

    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i>
        Simpan
    </button>

    <a href="<?= url('admin/list-tps.php') ?>" class="btn btn-secondary">
        Kembali
    </a>

</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const API_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        const provinsi = document.getElementById('provinsi');
        const kabupaten = document.getElementById('kabupaten');
        const kecamatan = document.getElementById('kecamatan');
        const kelurahan = document.getElementById('kelurahan');

        function resetSelect(select, text) {
            select.innerHTML = `<option value="">${text}</option>`;
        }

        async function fetchWilayah(url) {

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Gagal mengambil data wilayah');
            }

            return await response.json();
        }

        // ==========================
        // Load Provinsi
        // ==========================
        async function loadProvinsi() {

            try {

                resetSelect(provinsi, 'Memuat provinsi...');

                const data = await fetchWilayah(`${API_URL}/provinces.json`);

                resetSelect(provinsi, 'Pilih Provinsi');

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    provinsi.appendChild(option);

                });

            } catch (error) {

                console.error(error);

                resetSelect(provinsi, 'Gagal memuat provinsi');

            }

        }

        // ==========================
        // Load Kabupaten
        // ==========================
        async function loadKabupaten(provinsiId) {

            try {

                resetSelect(kabupaten, 'Memuat kabupaten...');

                const data = await fetchWilayah(`${API_URL}/regencies/${provinsiId}.json`);

                resetSelect(kabupaten, 'Pilih Kabupaten/Kota');

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    kabupaten.appendChild(option);

                });

            } catch (error) {

                console.error(error);

                resetSelect(kabupaten, 'Gagal memuat kabupaten');

            }

        }

        // ==========================
        // Load Kecamatan
        // ==========================
        async function loadKecamatan(kabupatenId) {

            try {

                resetSelect(kecamatan, 'Memuat kecamatan...');

                const data = await fetchWilayah(`${API_URL}/districts/${kabupatenId}.json`);

                resetSelect(kecamatan, 'Pilih Kecamatan');

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    kecamatan.appendChild(option);

                });

            } catch (error) {

                console.error(error);

                resetSelect(kecamatan, 'Gagal memuat kecamatan');

            }

        }

        // ==========================
        // Load Kelurahan
        // ==========================
        async function loadKelurahan(kecamatanId) {

            try {

                resetSelect(kelurahan, 'Memuat kelurahan...');

                const data = await fetchWilayah(`${API_URL}/villages/${kecamatanId}.json`);

                resetSelect(kelurahan, 'Pilih Kelurahan');

                data.forEach(item => {

                    const option = document.createElement('option');

                    option.value = item.name;
                    option.textContent = item.name;

                    kelurahan.appendChild(option);

                });

            } catch (error) {

                console.error(error);

                resetSelect(kelurahan, 'Gagal memuat kelurahan');

            }

        }

        // ==========================
        // Event Provinsi
        // ==========================
        provinsi.addEventListener('change', function() {

            const id = this.options[this.selectedIndex].dataset.id;

            resetSelect(kabupaten, 'Pilih Provinsi terlebih dahulu');
            resetSelect(kecamatan, 'Pilih Kabupaten terlebih dahulu');
            resetSelect(kelurahan, 'Pilih Kecamatan terlebih dahulu');

            if (id) {
                loadKabupaten(id);
            }

        });

        // ==========================
        // Event Kabupaten
        // ==========================
        kabupaten.addEventListener('change', function() {

            const id = this.options[this.selectedIndex].dataset.id;

            resetSelect(kecamatan, 'Pilih Kabupaten terlebih dahulu');
            resetSelect(kelurahan, 'Pilih Kecamatan terlebih dahulu');

            if (id) {
                loadKecamatan(id);
            }

        });

        // ==========================
        // Event Kecamatan
        // ==========================
        kecamatan.addEventListener('change', function() {

            const id = this.options[this.selectedIndex].dataset.id;

            resetSelect(kelurahan, 'Pilih Kecamatan terlebih dahulu');

            if (id) {
                loadKelurahan(id);
            }

        });

        loadProvinsi();

    });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>