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
                <option>Laki-laki</option>
                <option>Perempuan</option>
            </select>
        </div>

        <div class="form-group col-md-3">
            <label>Golongan Darah</label>
            <input name="golongan_darah" class="form-control" value="<?= input_value('golongan_darah') ?>">
        </div>

        <div class="form-group col-md-3">
            <label>Status Pernikahan</label>
            <input name="status_pernikahan" class="form-control" value="<?= input_value('status_pernikahan') ?>">
        </div>

        <div class="form-group col-md-3">
            <label>Agama</label>
            <input name="agama" class="form-control" value="<?= input_value('agama') ?>">
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

        <div class="form-group col-md-4">
            <label>Provinsi</label>
            <input name="provinsi" class="form-control" value="<?= input_value('provinsi') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Kab/Kota</label>
            <input name="kab_kota" class="form-control" value="<?= input_value('kab_kota') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Kecamatan</label>
            <input name="kecamatan" class="form-control" value="<?= input_value('kecamatan') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>Desa/Kelurahan</label>
            <input name="desa_kelurahan" class="form-control" value="<?= input_value('desa_kelurahan') ?>">
        </div>

        <div class="form-group col-md-2">
            <label>RT</label>
            <input name="rt" class="form-control" value="<?= input_value('rt') ?>">
        </div>

        <div class="form-group col-md-2">
            <label>RW</label>
            <input name="rw" class="form-control" value="<?= input_value('rw') ?>">
        </div>

        <div class="form-group col-md-4">
            <label>TPS</label>
            <input name="tps" class="form-control" value="<?= input_value('tps') ?>">
        </div>

    </div>
</div>


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