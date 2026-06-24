<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/functions.php';

require_role('superadmin');

// Ambil data dapil
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM dapil
    WHERE id = ?
");

$stmt->execute([$id]);

$data = $stmt->fetch();

if (!$data) {
    flash('error', 'Data dapil tidak ditemukan.');
    redirect('admin/list-dapil.php');
    exit;
}

$selectedKabupaten = json_decode($data['kab_kota'], true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Ubah array kabupaten menjadi JSON
        $kabKota = json_encode(
            array_values(array_unique($_POST['kab_kota'] ?? [])),
            JSON_UNESCAPED_UNICODE
        );

        $daerahPemilihan = trim($_POST['daerah_pemilihan'] ?? '');
        $provinsi = trim($_POST['provinsi'] ?? '');

        if ($daerahPemilihan === '') {
            throw new Exception('Daerah pemilihan wajib diisi.');
        }

        if ($provinsi === '') {
            throw new Exception('Provinsi wajib dipilih.');
        }

        if (empty($_POST['kab_kota'])) {
            throw new Exception('Minimal pilih 1 Kabupaten/Kota.');
        }

        $cek = $pdo->prepare("
    SELECT COUNT(*)
    FROM dapil
    WHERE daerah_pemilihan = ?
    AND id <> ?
");

        $cek->execute([
            $daerahPemilihan,
            $id
        ]);

        if ($cek->fetchColumn() > 0) {
            throw new Exception('Nama dapil sudah ada.');
        }

        $stmt = $pdo->prepare("
    UPDATE dapil
    SET
        daerah_pemilihan = ?,
        provinsi = ?,
        kab_kota = ?
    WHERE id = ?
");

        $stmt->execute([
            $daerahPemilihan,
            $provinsi,
            $kabKota,
            $id
        ]);

        $pdo->commit();

        flash('success', 'Dapil berhasil diperbarui.');
        redirect('admin/list-dapil.php');
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('error', 'Gagal memperbarui dapil: ' . $e->getMessage());
        redirect('admin/edit-dapil.php?id=' . $id);
        exit;
    }
}
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/topbar.php';
?>

<h1 class="h3 mb-4 text-gray-800">Edit Daerah Pemilihan (Dapil)</h1>

<form method="POST">

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"> Daerah Pemilihan (Dapil)</h6>
        </div>
        <div class="card-body row">
            <div class="form-group col-md-12">
                <label>Daerah Pemilihan (Dapil)</label>
                <input
                    name="daerah_pemilihan"
                    type="text"
                    class="form-control"
                    value="<?= e($data['daerah_pemilihan']) ?>"
                    required>
            </div>
            <div class="form-group col-md-12">
                <label>Provinsi</label>
                <select name="provinsi" id="provinsi" class="form-control" required>
                    <option value="">Memuat data provinsi...</option>
                </select>
            </div>

            <div class="form-group col-md-12">
                <label>Kabupaten/Kota</label>
                <select
                    name="kab_kota[]"
                    id="kab_kota"
                    class="form-control"
                    multiple
                    required
                    size="10">
                </select>

                <small class="text-muted">
                    Tekan <strong>Ctrl</strong> (Windows) atau <strong>Cmd</strong> (Mac) untuk memilih lebih dari satu Kabupaten/Kota.
                </small>
            </div>
        </div>
    </div>

    <button class="btn btn-primary mb-4">
        <i class="fas fa-save"></i> Perbarui Dapil
    </button>

</form>

<script>
    const selectedProvinsi = <?= json_encode($data['provinsi']) ?>;
    const selectedKabupaten = <?= json_encode($selectedKabupaten) ?>;
</script>

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

                provinsiSelect.innerHTML =
                    '<option value="">Pilih Provinsi</option>';

                data.forEach(item => {

                    const option = document.createElement('option');
                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    if (item.name === selectedProvinsi) {
                        option.selected = true;
                    }

                    provinsiSelect.appendChild(option);
                });

                const selected =
                    provinsiSelect.options[
                        provinsiSelect.selectedIndex
                    ];

                if (selected && selected.dataset.id) {
                    await loadKabKota(selected.dataset.id);
                }

            } catch (error) {

                resetSelect(provinsiSelect, 'Gagal memuat provinsi');

            }

        }

        async function loadKabKota(provinsiId) {
            try {
                setLoading(kabKotaSelect, 'Memuat kabupaten/kota...');

                const data = await fetchWilayah(`${API_URL}/regencies/${provinsiId}.json`);

                kabKotaSelect.innerHTML = "";

                data.forEach(item => {

                    const option = document.createElement('option');
                    option.value = item.name;
                    option.textContent = item.name;
                    option.dataset.id = item.id;

                    if (selectedKabupaten.includes(item.name)) {
                        option.selected = true;
                    }

                    kabKotaSelect.appendChild(option);
                });

            } catch (error) {
                resetSelect(kabKotaSelect, 'Gagal memuat kabupaten/kota');
                console.error(error);
            }
        }


        provinsiSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const provinsiId = selected.dataset.id;

            resetSelect(kabKotaSelect, 'Pilih provinsi terlebih dahulu');
            if (provinsiId) {
                loadKabKota(provinsiId);
            }
        });

        loadProvinsi();
    });
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>