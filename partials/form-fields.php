<?php
function input_value($name) { 
    return e($_POST[$name] ?? ''); 
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Langkah 1 - Data Kependudukan</h6>
    </div>

    <div class="card-body row">

        <div class="form-group col-md-4">
            <label>NIK</label>
            <input name="nik" class="form-control" value="<?= input_value('nik') ?>" required>
        </div>

        <div class="form-group col-md-8">
            <label>Nama Lengkap</label>
            <input name="nama_lengkap" class="form-control" value="<?= input_value('nama_lengkap') ?>" required>
        </div>

        <div class="form-group col-md-4">
            <label>Tempat Lahir</label>
            <input name="tempat_lahir" class="form-control" value="<?= input_value('tempat_lahir') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" class="form-control" value="<?= input_value('tanggal_lahir') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-control">
                <option value="">Pilih</option>
                <option value="laki-laki">Laki-laki</option>
                <option value="perempuan">Perempuan</option>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label>Golongan Darah</label>
            <select name="golongan_darah" class="form-control">
                <option value="">Pilih</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label>Status Pernikahan</label>
            <select name="status_pernikahan" class="form-control">
                <option value="">Pilih</option>
                <option value="belum_menikah">Belum Menikah</option>
                <option value="sudah_menikah">Sudah Menikah</option>
                <option value="pernah_menikah">Pernah Menikah</option>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label>Agama</label>
            <select name="agama" class="form-control">
                <option value="agama">Pilih</option>
                <option value="islam">Islam</option>
                <option value="kristen_protestan">Kristen Protestan</option>
                <option value="katolik">katolik</option>
                <option value="hindu">Hindu</option>
                <option value="budha">Budha</option>
                <option value="konghuchu">Konghuchu</option>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label>Pekerjaan</label>
            <input name="pekerjaan" class="form-control" value="<?= input_value('pekerjaan') ?>">
        </div>

        <div class="form-group col-md-12">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control"><?= input_value('alamat') ?></textarea>
        </div>

    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinsiSelect = document.getElementById('provinsi');
    const kabKotaSelect = document.getElementById('kab_kota');
    const kecamatanSelect = document.getElementById('kecamatan');
    const desaSelect = document.getElementById('desa_kelurahan');

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
            resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            const data = await fetchWilayah(`${API_URL}/regencies/${provinsiId}.json`);

            kabKotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';

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

    async function loadKecamatan(kabKotaId) {
        try {
            setLoading(kecamatanSelect, 'Memuat kecamatan...');
            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            const data = await fetchWilayah(`${API_URL}/districts/${kabKotaId}.json`);

            kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.name;
                option.textContent = item.name;
                option.dataset.id = item.id;
                kecamatanSelect.appendChild(option);
            });

        } catch (error) {
            resetSelect(kecamatanSelect, 'Gagal memuat kecamatan');
            console.error(error);
        }
    }

    async function loadDesa(kecamatanId) {
        try {
            setLoading(desaSelect, 'Memuat desa/kelurahan...');

            const data = await fetchWilayah(`${API_URL}/villages/${kecamatanId}.json`);

            desaSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.name;
                option.textContent = item.name;
                option.dataset.id = item.id;
                desaSelect.appendChild(option);
            });

        } catch (error) {
            resetSelect(desaSelect, 'Gagal memuat desa/kelurahan');
            console.error(error);
        }
    }

    provinsiSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const provinsiId = selected.dataset.id;

        resetSelect(kabKotaSelect, 'Pilih provinsi terlebih dahulu');
        resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
        resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

        if (provinsiId) {
            loadKabKota(provinsiId);
        }
    });

    kabKotaSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const kabKotaId = selected.dataset.id;

        resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
        resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

        if (kabKotaId) {
            loadKecamatan(kabKotaId);
        }
    });

    kecamatanSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const kecamatanId = selected.dataset.id;

        resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

        if (kecamatanId) {
            loadDesa(kecamatanId);
        }
    });

    loadProvinsi();
});
</script>


<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Langkah 3 - Kartu Keluarga</h6>
    </div>

    <div class="card-body row">

        <div class="form-group col-md-4">
            <label>Nomor KK</label>
            <input name="nomor_kk" class="form-control" value="<?= input_value('nomor_kk') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Hubungan Keluarga</label>
            <input name="hubungan_keluarga" class="form-control" value="<?= input_value('hubungan_keluarga') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>NIK Anggota Keluarga</label>
            <input name="keluarga_nik" class="form-control" value="<?= input_value('keluarga_nik') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Nama Anggota Keluarga</label>
            <input name="keluarga_nama" class="form-control" value="<?= input_value('keluarga_nama') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Tempat Lahir Anggota</label>
            <input name="keluarga_tempat_lahir" class="form-control" value="<?= input_value('keluarga_tempat_lahir') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Tanggal Lahir Anggota</label>
            <input type="date" name="keluarga_tanggal_lahir" class="form-control" value="<?= input_value('keluarga_tanggal_lahir') ?>">
        </div>

    </div>
</div>


<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Langkah 4 - Informasi Kontak</h6>
    </div>

    <div class="card-body row">

        <div class="form-group col-md-6">
            <label>Nomor Telepon</label>
            <input name="nomor_telepon" class="form-control" value="<?= input_value('nomor_telepon') ?>">
        </div>

        <div class="form-group col-md-6">
            <label>Nomor WhatsApp</label>
            <input name="nomor_whatsapp" class="form-control" value="<?= input_value('nomor_whatsapp') ?>">
        </div>

    </div>
</div>


<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Langkah 5 - Dokumentasi</h6>
    </div>

    <div class="card-body row">

        <div class="form-group col-md-4">
            <label>Foto KTP</label>
            <input type="file" name="foto_ktp" class="form-control-file">
        </div>

        <div class="form-group col-md-4">
            <label>Foto Diri</label>
            <input type="file" name="foto_diri" class="form-control-file">
        </div>

        <div class="form-group col-md-4">
            <label>Foto Bukti Rekrut</label>
            <input type="file" name="foto_bukti_rekrut" class="form-control-file">
        </div>

    </div>
</div>