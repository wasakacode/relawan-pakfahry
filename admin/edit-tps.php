<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role(['superadmin', 'admin']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM tps_kalsel
    WHERE id = ?
");

$stmt->execute([$id]);

$tps = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tps) {
    flash('error', 'Data TPS tidak ditemukan.');
    redirect('admin/list-tps.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $provinsi  = trim($_POST['provinsi'] ?? '');
        $kabupaten = trim($_POST['kabupaten'] ?? '');
        $kecamatan = trim($_POST['kecamatan'] ?? '');
        $kelurahan = trim($_POST['kelurahan'] ?? '');
        $noTps     = trim($_POST['no_tps'] ?? '');

        $noTps = str_pad($noTps, 3, '0', STR_PAD_LEFT);

        if ($provinsi === '') {
            throw new Exception('Provinsi wajib dipilih.');
        }

        if ($kabupaten === '') {
            throw new Exception('Kabupaten wajib dipilih.');
        }

        if ($kecamatan === '') {
            throw new Exception('Kecamatan wajib dipilih.');
        }

        if ($kelurahan === '') {
            throw new Exception('Kelurahan wajib dipilih.');
        }

        if (!preg_match('/^\d{3}$/', $noTps)) {
            throw new Exception('Nomor TPS harus terdiri dari 3 digit.');
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
                AND id != ?
        ");

        $cek->execute([
            $provinsi,
            $kabupaten,
            $kecamatan,
            $kelurahan,
            $noTps,
            $id
        ]);

        if ($cek->fetchColumn() > 0) {
            throw new Exception('TPS tersebut sudah ada.');
        }

        $stmt = $pdo->prepare("
            UPDATE tps_kalsel
            SET
                provinsi = ?,
                kabupaten = ?,
                kecamatan = ?,
                kelurahan = ?,
                no_tps = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $provinsi,
            $kabupaten,
            $kecamatan,
            $kelurahan,
            $noTps,
            $id
        ]);

        flash('success', 'Data TPS berhasil diperbarui.');
        redirect('admin/list-tps.php');
        exit;
    } catch (Exception $e) {

        flash('error', $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Edit Data TPS</h1>

<form method="POST">

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Edit Data TPS
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

                        <option value="">Memuat data...</option>

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

                        <option value="">Memuat data...</option>

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

                        <option value="">Memuat data...</option>

                    </select>
                </div>

                <!-- Nomor TPS -->
                <div class="form-group col-md-6">

                    <label>Nomor TPS</label>

                    <input
                        type="text"
                        name="no_tps"
                        class="form-control"
                        maxlength="3"
                        pattern="[0-9]{3}"
                        inputmode="numeric"
                        value="<?= e($tps['no_tps']) ?>"
                        required>

                    <small class="text-muted">
                        Contoh: 001, 015, 120
                    </small>

                </div>

            </div>

        </div>

    </div>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i>
        Simpan Perubahan
    </button>

    <a href="<?= url('admin/list-tps.php') ?>" class="btn btn-secondary">
        Kembali
    </a>

</form>

<script>
    document.addEventListener('DOMContentLoaded', async function() {

        const API_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        const provinsi = document.getElementById('provinsi');
        const kabupaten = document.getElementById('kabupaten');
        const kecamatan = document.getElementById('kecamatan');
        const kelurahan = document.getElementById('kelurahan');

        // Data lama dari database
        const oldProvinsi = <?= json_encode($tps['provinsi']) ?>;
        const oldKabupaten = <?= json_encode($tps['kabupaten']) ?>;
        const oldKecamatan = <?= json_encode($tps['kecamatan']) ?>;
        const oldKelurahan = <?= json_encode($tps['kelurahan']) ?>;

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

        async function loadProvinsi() {

            resetSelect(provinsi, 'Memuat Provinsi...');

            const data = await fetchWilayah(`${API_URL}/provinces.json`);

            resetSelect(provinsi, 'Pilih Provinsi');

            let provinsiId = '';

            data.forEach(item => {

                const option = document.createElement('option');

                option.value = item.name;
                option.textContent = item.name;
                option.dataset.id = item.id;

                if (item.name === oldProvinsi) {
                    option.selected = true;
                    provinsiId = item.id;
                }

                provinsi.appendChild(option);

            });

            if (provinsiId) {
                await loadKabupaten(provinsiId);
            }

        }

        async function loadKabupaten(provinsiId) {

            resetSelect(kabupaten, 'Memuat Kabupaten...');

            const data = await fetchWilayah(`${API_URL}/regencies/${provinsiId}.json`);

            resetSelect(kabupaten, 'Pilih Kabupaten');

            let kabupatenId = '';

            data.forEach(item => {

                const option = document.createElement('option');

                option.value = item.name;
                option.textContent = item.name;
                option.dataset.id = item.id;

                if (item.name === oldKabupaten) {
                    option.selected = true;
                    kabupatenId = item.id;
                }

                kabupaten.appendChild(option);

            });

            if (kabupatenId) {
                await loadKecamatan(kabupatenId);
            }

        }

        async function loadKecamatan(kabupatenId) {

            resetSelect(kecamatan, 'Memuat Kecamatan...');

            const data = await fetchWilayah(`${API_URL}/districts/${kabupatenId}.json`);

            resetSelect(kecamatan, 'Pilih Kecamatan');

            let kecamatanId = '';

            data.forEach(item => {

                const option = document.createElement('option');

                option.value = item.name;
                option.textContent = item.name;
                option.dataset.id = item.id;

                if (item.name === oldKecamatan) {
                    option.selected = true;
                    kecamatanId = item.id;
                }

                kecamatan.appendChild(option);

            });

            if (kecamatanId) {
                await loadKelurahan(kecamatanId);
            }

        }

        async function loadKelurahan(kecamatanId) {

            resetSelect(kelurahan, 'Memuat Kelurahan...');

            const data = await fetchWilayah(`${API_URL}/villages/${kecamatanId}.json`);

            resetSelect(kelurahan, 'Pilih Kelurahan');

            data.forEach(item => {

                const option = document.createElement('option');

                option.value = item.name;
                option.textContent = item.name;

                if (item.name === oldKelurahan) {
                    option.selected = true;
                }

                kelurahan.appendChild(option);

            });

        }

        // ==========================
        // Event Provinsi
        // ==========================

        provinsi.addEventListener('change', async function() {

            const id = this.options[this.selectedIndex].dataset.id;

            resetSelect(kabupaten, 'Pilih Kabupaten');
            resetSelect(kecamatan, 'Pilih Kecamatan');
            resetSelect(kelurahan, 'Pilih Kelurahan');

            if (id) {
                await loadKabupaten(id);
            }

        });

        // ==========================
        // Event Kabupaten
        // ==========================

        kabupaten.addEventListener('change', async function() {

            const id = this.options[this.selectedIndex].dataset.id;

            resetSelect(kecamatan, 'Pilih Kecamatan');
            resetSelect(kelurahan, 'Pilih Kelurahan');

            if (id) {
                await loadKecamatan(id);
            }

        });

        // ==========================
        // Event Kecamatan
        // ==========================

        kecamatan.addEventListener('change', async function() {

            const id = this.options[this.selectedIndex].dataset.id;

            resetSelect(kelurahan, 'Pilih Kelurahan');

            if (id) {
                await loadKelurahan(id);
            }

        });

        // Jalankan pertama kali
        await loadProvinsi();

    });
</script>