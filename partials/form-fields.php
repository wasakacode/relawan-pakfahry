<?php
function input_value($name)
{
    return e($_POST[$name] ?? '');
}

$tps = $_POST['tps'] ?? '';
$rt = $_POST['rt'] ?? '';
$rw = $_POST['rw'] ?? '';

if ($tps != '' && !preg_match('/^TPS [0-9]{3}$/', $tps)) {
    die("Format TPS harus seperti: TPS 001");
}

if ($rt != '' && !preg_match('/^RT [0-9]{3}$/', $rt)) {
    die("Format RT harus seperti: RT 001");
}

if ($rw != '' && !preg_match('/^RW [0-9]{3}$/', $rw)) {
    die("Format Rw harus seperti: RW 001");
}
?>
<div class="card shadow mb-4" style="border-radius: 18px; overflow:hidden;">
    <div class="card-header py-3" style="background: linear-gradient(135deg, #eaf9ff, #c8efff);">
        <h6 class="m-0 font-weight-bold text-primary">
            Upload KTP untuk Isi Otomatis
        </h6>
    </div>

    <div class="card-body row align-items-center">

        <div class="form-group col-md-6">
            <label>Upload Foto KTP</label>
            <input
                type="file"
                id="upload_ktp_ocr"
                class="form-control-file"
                accept="image/*">

            <small class="text-muted d-block mt-2">
                Upload gambar KTP format JPG, JPEG, atau PNG. Sistem akan mencoba membaca data KTP dan mengisi form otomatis.
            </small>
        </div>

        <div class="form-group col-md-6">
            <label>Status Pembacaan</label>
            <div id="ocr_status" class="alert alert-info mb-0" style="border-radius: 12px;">
                Belum ada KTP yang di-upload.
            </div>
        </div>

        <div class="col-md-12 mt-3">
            <small class="text-danger">
                Catatan: hasil baca otomatis bisa saja belum sempurna, jadi tetap cek kembali data sebelum disimpan.
            </small>
        </div>

    </div>
</div>

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
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
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
                <option value="Belum Menikah">Belum Menikah</option>
                <option value="Sudah Menikah">Sudah Menikah</option>
                <option value="Pernah Menikah">Pernah Menikah</option>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label>Agama</label>
            <select name="agama" class="form-control">
                <option value="">Pilih</option>
                <option value="Islam">Islam</option>
                <option value="Kristen Protestan">Kristen Protestan</option>
                <option value="Katolik">Katolik</option>
                <option value="Hindu">Hindu</option>
                <option value="Budha">Budha</option>
                <option value="Konghuchu">Konghuchu</option>
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


<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Langkah 2 - Pemetaan Wilayah</h6>
    </div>

    <div class="card-body row">

        <div class="form-group col-md-6">
            <label>Provinsi</label>
            <select name="provinsi" id="provinsi" class="form-control" required>
                <option value="">Memuat data provinsi...</option>
            </select>
        </div>

        <div class="form-group col-md-6">
            <label>Kabupaten/Kota</label>
            <select name="kab_kota" id="kab_kota" class="form-control" required>
                <option value="">Pilih provinsi terlebih dahulu</option>
            </select>
        </div>

        <div class="form-group col-md-6">
            <label>Kecamatan</label>
            <select name="kecamatan" id="kecamatan" class="form-control" required>
                <option value="">Pilih kabupaten/kota terlebih dahulu</option>
            </select>
        </div>

        <div class="form-group col-md-6">
            <label>Desa/Kelurahan</label>
            <select name="desa_kelurahan" id="desa_kelurahan" class="form-control" required>
                <option value="">Pilih kecamatan terlebih dahulu</option>
            </select>
        </div>

        <div class="form-group col-md-4">
            <label>RT</label>
            <input
                type="text"
                id="rt"
                name="rt"
                class="form-control"
                value="<?= input_value('rt') ?>"
                placeholder="Contoh: 001"
                maxlength="3"
                oninput="validasiRT()">

            <small id="pesanRT" style="color:red;"></small>
        </div>

        <div class="form-group col-md-4">
            <label>RW</label>
            <input
                type="text"
                id="rw"
                name="rw"
                class="form-control"
                value="<?= input_value('rw') ?>"
                placeholder="Contoh: 001"
                maxlength="3"
                oninput="validasiRW()">

            <small id="pesanRW" style="color:red;"></small>
        </div>

        <div class="form-group col-md-4">
            <label>TPS</label>
            <input
                type="text"
                id="tps"
                name="tps"
                class="form-control"
                value="<?= input_value('tps') ?>"
                placeholder="Contoh: 001"
                maxlength="3"
                oninput="validasiTPS()">

            <small id="pesanTPS" style="color:red;"></small>
        </div>

        <script>
            function validasiTPS() {
            let input = document.getElementById("tps");
            let pesan = document.getElementById("pesanTPS");

            // Hanya angka
            input.value = input.value.replace(/[^0-9]/g, '');

            let regex = /^[0-9]{3}$/;

            if (input.value === "") {
                pesan.innerHTML = "";
            } else if (regex.test(input.value)) {
                pesan.innerHTML = "";
            } else {
                pesan.innerHTML = "TPS harus terdiri dari 3 digit angka, contoh: 001";
            }
        }

            function validasiRT() {
            let input = document.getElementById("rt");
            let pesan = document.getElementById("pesanRT");

            // Hanya angka
            input.value = input.value.replace(/[^0-9]/g, '');

            let regex = /^[0-9]{3}$/;

            if (input.value === "") {
                pesan.innerHTML = "";
            } else if (regex.test(input.value)) {
                pesan.innerHTML = "";
            } else {
                pesan.innerHTML = "RT harus terdiri dari 3 digit angka, contoh: 001";
            }
        }

            function validasiRW() {
            let input = document.getElementById("rw");
            let pesan = document.getElementById("pesanRW");

            // Hanya angka
            input.value = input.value.replace(/[^0-9]/g, '');

            let regex = /^[0-9]{3}$/;

            if (input.value === "") {
                pesan.innerHTML = "";
            } else if (regex.test(input.value)) {
                pesan.innerHTML = "";
            } else {
                pesan.innerHTML = "RW harus terdiri dari 3 digit angka, contoh: 001";
            }
        }
        </script>

    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        provinsiSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const provinsiId = selected.dataset.id;

            resetSelect(kabKotaSelect, 'Pilih provinsi terlebih dahulu');
            resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            if (provinsiId) {
                loadKabKota(provinsiId);
            }
        });

        kabKotaSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const kabKotaId = selected.dataset.id;

            resetSelect(kecamatanSelect, 'Pilih kabupaten/kota terlebih dahulu');
            resetSelect(desaSelect, 'Pilih kecamatan terlebih dahulu');

            if (kabKotaId) {
                loadKecamatan(kabKotaId);
            }
        });

        kecamatanSelect.addEventListener('change', function() {
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
        <h6 class="m-0 font-weight-bold text-primary">
            Langkah 3 - Kartu Keluarga
        </h6>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="form-group col-md-6">
                <label>Nomor KK</label>
                <input
                    name="nomor_kk"
                    class="form-control"
                    value="<?= input_value('nomor_kk') ?>">
            </div>

            <div class="col-md-12">
                <button
                    type="button"
                    id="btnTambahAnggota"
                    class="btn btn-success">

                    <i class="fas fa-plus"></i>
                    Tambah Anggota Keluarga

                </button>
            </div>

        </div>

        <hr>

        <div id="anggotaContainer"></div>

    </div>
</div>

<script>
    let anggotaIndex = 0;

    document
        .getElementById('btnTambahAnggota')
        .addEventListener('click', function() {

            anggotaIndex++;

            const html = `
        <div class="border rounded p-3 mb-3">

            <div class="d-flex justify-content-between mb-3">
                <h6>Anggota Keluarga ${anggotaIndex}</h6>

                <button
                    type="button"
                    class="btn btn-sm btn-danger btnHapus">

                    Hapus

                </button>
            </div>

            <div class="row">

                <div class="form-group col-md-4">
                    <label>Hubungan Keluarga</label>
                    <select
                        name="keluarga_hubungan_keluarga[]"
                        class="form-control">
                        <option value="">Pilih Hubungan</option>
                        <option value="Suami">Suami</option>
                        <option value="Istri">Istri</option>
                        <option value="Anak">Anak</option>
                        <option value="Orang Tua">Orang Tua</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>Jenis Kelamin</label>
                    <select
                        name="keluarga_jenis_kelamin[]"
                        class="form-control">
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>NIK</label>
                    <input
                        name="keluarga_nik[]"
                        class="form-control">
                </div>

                <div class="form-group col-md-4">
                    <label>Nama</label>
                    <input
                        name="keluarga_nama[]"
                        class="form-control">
                </div>

                <div class="form-group col-md-4">
                    <label>Tempat Lahir</label>
                    <input
                        name="keluarga_tempat_lahir[]"
                        class="form-control">
                </div>

                <div class="form-group col-md-4">
                    <label>Tanggal Lahir</label>
                    <input
                        type="date"
                        name="keluarga_tanggal_lahir[]"
                        class="form-control">
                </div>

                <div class="form-group col-md-4">
                    <label>Agama</label>
                    <select
                        name="keluarga_agama[]"
                        class="form-control">
                        <option value="">Pilih Agama</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>Pekerjaan</label>
                    <input
                        name="keluarga_pekerjaan[]"
                        class="form-control">
                </div>

            </div>

        </div>
        `;

            document
                .getElementById('anggotaContainer')
                .insertAdjacentHTML('beforeend', html);
        });

    document.addEventListener('click', function(e) {

        if (e.target.classList.contains('btnHapus')) {
            e.target.closest('.border').remove();
        }

    });
</script>

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

        <div class="form-group col-md-4">
            <label>Foto Bukti Rekrut <span class="text-danger">*</span></label>
            <input type="file"
                name="foto_bukti_rekrut"
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

    $files = ['foto_ktp', 'foto_diri', 'foto_bukti_rekrut'];
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

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const uploadKtp = document.getElementById('upload_ktp_ocr');
        const statusBox = document.getElementById('ocr_status');

        if (!uploadKtp) return;

        function setValueByName(name, value) {
            const field = document.querySelector(`[name="${name}"]`);
            if (!field || !value) return;

            if (field.tagName.toLowerCase() === 'select') {
                const options = Array.from(field.options);
                const found = options.find(option => {
                    return option.textContent.trim().toLowerCase() === value.trim().toLowerCase() ||
                        option.value.trim().toLowerCase() === value.trim().toLowerCase();
                });

                if (found) {
                    field.value = found.value;
                }
            } else {
                field.value = value.trim();
            }
        }

        function cleanText(text) {
            return text
                .replace(/\r/g, '\n')
                .replace(/[|]/g, 'I')
                .replace(/\s+/g, ' ')
                .replace(/ :/g, ':')
                .trim();
        }

        function findValue(text, labels) {
            for (const label of labels) {
                const regex = new RegExp(label + '\\s*[:\\-]?\\s*([^\\n]+)', 'i');
                const match = text.match(regex);

                if (match && match[1]) {
                    return match[1]
                        .replace(/NIK|Nama|Tempat|Tanggal|Lahir|Alamat|Agama|Pekerjaan/gi, '')
                        .trim();
                }
            }

            return '';
        }

        function formatTanggalKtp(value) {
            if (!value) return '';

            value = value.trim();

            const bulanMap = {
                'JANUARI': '01',
                'FEBRUARI': '02',
                'MARET': '03',
                'APRIL': '04',
                'MEI': '05',
                'JUNI': '06',
                'JULI': '07',
                'AGUSTUS': '08',
                'SEPTEMBER': '09',
                'OKTOBER': '10',
                'NOVEMBER': '11',
                'DESEMBER': '12'
            };

            let matchAngka = value.match(/(\d{1,2})[-\/\s](\d{1,2})[-\/\s](\d{4})/);
            if (matchAngka) {
                const day = matchAngka[1].padStart(2, '0');
                const month = matchAngka[2].padStart(2, '0');
                const year = matchAngka[3];
                return `${year}-${month}-${day}`;
            }

            let matchHuruf = value.toUpperCase().match(/(\d{1,2})\s+([A-Z]+)\s+(\d{4})/);
            if (matchHuruf && bulanMap[matchHuruf[2]]) {
                const day = matchHuruf[1].padStart(2, '0');
                const month = bulanMap[matchHuruf[2]];
                const year = matchHuruf[3];
                return `${year}-${month}-${day}`;
            }

            return '';
        }

        function normalizeJenisKelamin(value) {
            value = value.toUpperCase();

            if (value.includes('LAKI')) {
                return 'laki-laki';
            }

            if (value.includes('PEREMPUAN')) {
                return 'perempuan';
            }

            return '';
        }

        function normalizeAgama(value) {
            value = value.toUpperCase();

            if (value.includes('ISLAM')) return 'islam';
            if (value.includes('KRISTEN')) return 'kristen_protestan';
            if (value.includes('KATOLIK')) return 'katolik';
            if (value.includes('HINDU')) return 'hindu';
            if (value.includes('BUDHA') || value.includes('BUDDHA')) return 'budha';
            if (value.includes('KONGHUCU') || value.includes('KONGHUCHU')) return 'konghuchu';

            return '';
        }

        function normalizeStatusKawin(value) {
            value = value.toUpperCase();

            if (value.includes('BELUM')) return 'belum_menikah';
            if (value.includes('KAWIN') || value.includes('MENIKAH')) return 'sudah_menikah';
            if (value.includes('CERAI')) return 'pernah_menikah';

            return '';
        }

        function parseKtpText(rawText) {
            const lines = rawText
                .split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);

            const text = lines.join('\n');
            const textClean = cleanText(text);

            console.log('HASIL OCR KTP:', textClean);

            let nik = '';
            const nikMatch = textClean.match(/\b\d{16}\b/);
            if (nikMatch) {
                nik = nikMatch[0];
            }

            let nama = findValue(text, ['Nama', 'Narna']);
            let tempatTanggal = findValue(text, ['Tempat\\/Tgl Lahir', 'Tempat Tgl Lahir', 'Tempat\\/Tanggal Lahir', 'Tempat Tanggal Lahir']);

            let tempatLahir = '';
            let tanggalLahir = '';

            if (tempatTanggal) {
                const parts = tempatTanggal.split(',');
                tempatLahir = parts[0] ? parts[0].trim() : '';

                if (parts[1]) {
                    tanggalLahir = formatTanggalKtp(parts[1].trim());
                }
            }

            let jenisKelamin = findValue(text, ['Jenis Kelamin']);
            let alamat = findValue(text, ['Alamat']);
            let rtRw = findValue(text, ['RT\\/RW', 'RT RW']);
            let kelDesa = findValue(text, ['Kel\\/Desa', 'Kel Desa', 'Desa', 'Kelurahan']);
            let kecamatan = findValue(text, ['Kecamatan']);
            let agama = findValue(text, ['Agama']);
            let statusPernikahan = findValue(text, ['Status Perkawinan', 'Status Pernikahan']);
            let pekerjaan = findValue(text, ['Pekerjaan']);

            let rt = '';
            let rw = '';

            if (rtRw) {
                const rtRwMatch = rtRw.match(/(\d{1,3})\s*\/\s*(\d{1,3})/);
                if (rtRwMatch) {
                    rt = rtRwMatch[1].padStart(3, '0');
                    rw = rtRwMatch[2].padStart(3, '0');
                }
            }

            setValueByName('nik', nik);
            setValueByName('nama_lengkap', nama);
            setValueByName('tempat_lahir', tempatLahir);
            setValueByName('tanggal_lahir', tanggalLahir);
            setValueByName('jenis_kelamin', normalizeJenisKelamin(jenisKelamin));
            setValueByName('alamat', alamat);
            setValueByName('rt', rt);
            setValueByName('rw', rw);
            setValueByName('desa_kelurahan', kelDesa);
            setValueByName('kecamatan', kecamatan);
            setValueByName('agama', normalizeAgama(agama));
            setValueByName('status_pernikahan', normalizeStatusKawin(statusPernikahan));
            setValueByName('pekerjaan', pekerjaan);

            statusBox.className = 'alert alert-success mb-0';
            statusBox.style.borderRadius = '12px';
            statusBox.innerHTML = `
            <b>Data KTP berhasil dibaca.</b><br>
            Silakan cek ulang hasil yang masuk ke form, karena OCR bisa saja belum sempurna.
        `;
        }

        uploadKtp.addEventListener('change', async function() {
            const file = this.files[0];

            if (!file) return;

            if (!file.type.startsWith('image/')) {
                statusBox.className = 'alert alert-danger mb-0';
                statusBox.innerHTML = 'File harus berupa gambar KTP, seperti JPG, JPEG, atau PNG.';
                return;
            }

            statusBox.className = 'alert alert-warning mb-0';
            statusBox.style.borderRadius = '12px';
            statusBox.innerHTML = 'Sedang membaca data KTP, mohon tunggu...';

            try {
                const result = await Tesseract.recognize(
                    file,
                    'ind+eng', {
                        logger: function(m) {
                            if (m.status === 'recognizing text') {
                                const progress = Math.round(m.progress * 100);
                                statusBox.innerHTML = `Sedang membaca KTP... ${progress}%`;
                            }
                        }
                    }
                );

                parseKtpText(result.data.text);

            } catch (error) {
                console.error(error);

                statusBox.className = 'alert alert-danger mb-0';
                statusBox.style.borderRadius = '12px';
                statusBox.innerHTML = 'Gagal membaca KTP. Coba gunakan foto yang lebih jelas dan tidak blur.';
            }
        });

    });
</script>