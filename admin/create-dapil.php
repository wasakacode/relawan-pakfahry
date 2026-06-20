<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role('superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Ubah array kabupaten menjadi JSON
        $kabKota = !empty($_POST['kab_kota'])
            ? json_encode($_POST['kab_kota'], JSON_UNESCAPED_UNICODE)
            : null;

        $stmt = $pdo->prepare("
            INSERT INTO dapil
            (daerah_pemilihan, provinsi, kab_kota)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            trim($_POST['daerah_pemilihan']),
            $_POST['provinsi'],
            $kabKota
        ]);

        $pdo->commit();

        flash('success', 'Dapil berhasil dibuat.');
        redirect('admin/create-dapil.php');

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('error', 'Gagal membuat dapil: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Buat Admin (Koordinator Kecamatan)</h1>

<form method="POST" enctype="multipart/form-data">


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"> Daerah Pemilihan (Dapil)</h6>
        </div>
        <div class="card-body row">
            <div class="form-group col-md-12">
                <label>Daerah Pemilihan (Dapil)</label>
                <input name="daerah_pemilihan" type="text" class="form-control" required>
            </div>

            <div class="form-group col-md-12">
                <label>Provinsi</label>
                <select name="provinsi" id="provinsi" class="form-control" required>
                <option value="">Memuat data provinsi...</option>
            </select>
            </div>
            <div class="form-group col-md-12">
                <label>Kabupaten/Kota</label>
                <select name="kab_kota[]" id="kab_kota" class="form-control" multiple required size="10">
                </select>

                <small class="text-muted">
                    Tekan <strong>Ctrl</strong> (Windows) atau <strong>Cmd</strong> (Mac) untuk memilih lebih dari satu Kabupaten/Kota.
                </small>
            </div>
        </div>

    </div>

    <button class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Simpan Admin
    </button>

</form>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const provinsiSelect = document.getElementById('provinsi');
        const kabKotaSelect = document.getElementById('kab_kota');

        const API_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        function resetSelect(select, text) {
            select.innerHTML = `<option value="">${text}</option>`;
        }

        function setLoading(select, text = 'Memuat data...') {
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
            try {
                setLoading(provinsiSelect, 'Memuat data provinsi...');

                const data = await fetchWilayah(`${API_URL}/provinces.json`);

                provinsiSelect.innerHTML = '<option value="">Pilih Provinsi</option>';

                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;
                    provinsiSelect.appendChild(option);
                });

            } catch (error) {
                resetSelect(provinsiSelect, 'Gagal memuat provinsi');
                console.error(error);
            }
        }

        async function loadKabKota(provinsiId) {
            try {
                setLoading(kabKotaSelect, 'Memuat kabupaten/kota...');

                console.log("Provinsi ID:", provinsiId);

                const data = await fetchWilayah(`${API_URL}/regencies/${provinsiId}.json`);

                 console.log(data);

                kabKotaSelect.innerHTML ="";

                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;
                    kabKotaSelect.appendChild(option);
                });

            } catch (error) {
                resetSelect(kabKotaSelect, 'Gagal memuat kabupaten/kota');
                console.error(error);
            }
        }


        provinsiSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            console.log(selected);
            const provinsiId = selected.dataset.id;

            resetSelect(kabKotaSelect, 'Pilih provinsi terlebih dahulu');
            console.log(provinsiId);
            if (provinsiId) {
                loadKabKota(provinsiId);
            }
        });

        loadProvinsi();
    });
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>