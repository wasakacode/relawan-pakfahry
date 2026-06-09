        <?php
        require_once __DIR__ . '/../auth/auth.php';

        require_role(['superadmin', 'admin', 'relawan']);

        require_once __DIR__ . '/../partials/header.php';
        require_once __DIR__ . '/../partials/sidebar.php';
        require_once __DIR__ . '/../partials/topbar.php';

        $stmt = $pdo->query("
            SELECT
                kab_kota,
                COUNT(*) AS total
            FROM profiles
            WHERE type='relawan'
            GROUP BY kab_kota
            ORDER BY total DESC
        ");

        $wilayah = $stmt->fetchAll();

        if(isset($_GET['kab'])){

            $stmt = $pdo->prepare("
                SELECT
                    kecamatan,
                    COUNT(*) AS total
                FROM profiles
                WHERE type='relawan'
                AND kab_kota = ?
                GROUP BY kecamatan
                ORDER BY total DESC
            ");

            $stmt->execute([$_GET['kab']]);

            $data = $stmt->fetchAll();
        }
        if(isset($_GET['kab']) && isset($_GET['kec'])){

            $stmt = $pdo->prepare("
                SELECT
                    desa_kelurahan,
                    COUNT(*) AS total
                FROM profiles
                WHERE type='relawan'
                AND kab_kota = ?
                AND kecamatan = ?
                GROUP BY desa_kelurahan
            ");

            $stmt->execute([
                $_GET['kab'],
                $_GET['kec']
            ]);

            $data = $stmt->fetchAll();
        }
        if(
            isset($_GET['kab']) &&
            isset($_GET['kec']) &&
            isset($_GET['desa'])
        ){

            $stmt = $pdo->prepare("
                SELECT
                    rt,
                    rw,
                    COUNT(*) AS total
                FROM profiles
                WHERE type='relawan'
                AND kab_kota = ?
                AND kecamatan = ?
                AND desa_kelurahan = ?
                GROUP BY rt,rw
                ORDER BY total DESC
            ");

            $stmt->execute([
                $_GET['kab'],
                $_GET['kec'],
                $_GET['desa']
            ]);

            $data = $stmt->fetchAll();
        }

         $totalRelawan = $pdo->query("
            SELECT COUNT(*)
            FROM profiles
            WHERE type='relawan'
        ")->fetchColumn();

        $dataWilayah = [];

        foreach($wilayah as $row){

            $dataWilayah[] = [
                'nama' => $row['kab_kota'],
                'total' => $row['total'],
                'persen' => round(($row['total']/$totalRelawan)*100,2),
                'url' => '?kab='.urlencode($row['kab_kota'])
            ];
        }
        ?>


        <h1 class="h3 mb-4 text-gray-800">
            <i class="fas fa-chart-pie"></i>
            Statistik Wilayah Relawan
        </h1>


        <div class="row mb-4">

            <div class="col-lg-4">

                <div class="card border-left-primary shadow h-100">

                    <div class="card-body">

                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-2">
                            Total Relawan
                        </div>

                        <div class="h3 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($totalRelawan) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="mb-3">

        <?php if(isset($_GET['kab'])): ?>
            <a href="statistik_wilayah.php" class="badge badge-primary">
                Semua Kota
            </a>
        <?php endif; ?>

        <?php if(isset($_GET['kab'])): ?>
            <span class="mx-2">></span>
            <span class="badge badge-info">
                <?= e($_GET['kab']) ?>
            </span>
        <?php endif; ?>

        <?php if(isset($_GET['kec'])): ?>
            <span class="mx-2">></span>
            <span class="badge badge-success">
                <?= e($_GET['kec']) ?>
            </span>
        <?php endif; ?>

        <?php if(isset($_GET['desa'])): ?>
            <span class="mx-2">></span>
            <span class="badge badge-warning">
                <?= e($_GET['desa']) ?>
            </span>
        <?php endif; ?>

        </div>

        

<?php if(!isset($_GET['kab'])): ?>
    <div class="row">

    <?php foreach($dataWilayah as $row): ?>

    <div class="col-lg-4 mb-4">

        <div class="card shadow h-100">

            <div class="card-body">

                <h5 class="font-weight-bold">
                    <?= e($row['nama']) ?>
                </h5>

                <h3 class="text-primary">
                    <?= number_format($row['total']) ?>
                </h3>

                <div class="progress mb-3">
                    <div class="progress-bar"
                        style="width:<?= $row['persen'] ?>%">
                    </div>
                </div>

                <a href="<?= $row['url'] ?>"
                   class="btn btn-primary btn-sm">
                    Lihat Kecamatan
                </a>

            </div>

        </div>

    </div>

    <?php endforeach; ?>
</div>
<?php endif; ?>

    <?php if(isset($_GET['kab']) && !isset($_GET['kec'])): ?>
    <div class="row">

<?php foreach($data as $row): ?>

<div class="col-md-4 mb-3">

    <div class="card shadow">

        <div class="card-body">

            <h5><?= e($row['kecamatan']) ?></h5>

            <h3 class="text-info">
                <?= $row['total'] ?>
            </h3>

            <a href="?kab=<?= urlencode($_GET['kab']) ?>
            &kec=<?= urlencode($row['kecamatan']) ?>"
            class="btn btn-info btn-sm">

                Lihat Kelurahan

            </a>

        </div>

    </div>

</div>

<?php endforeach; ?>
</div>
<?php endif; ?>

    <?php if(isset($_GET['kab']) && isset($_GET['kec']) && !isset($_GET['desa'])): ?>


        <div class="row">
<?php foreach($data as $row): ?>

<div class="col-md-4 mb-3">

    <div class="card shadow">

        <div class="card-body">

            <h5><?= e($row['desa_kelurahan']) ?></h5>

            <h3 class="text-success">
                <?= $row['total'] ?>
            </h3>

            <a href="?kab=<?= urlencode($_GET['kab']) ?>
            &kec=<?= urlencode($_GET['kec']) ?>
            &desa=<?= urlencode($row['desa_kelurahan']) ?>"
            class="btn btn-success btn-sm">

                Lihat RT/RW

            </a>

        </div>

    </div>

</div>

<?php endforeach; ?>
</div>
<?php endif; ?>

       <?php if(isset($_GET['desa'])): ?>

<div class="row">

<?php foreach($data as $row): ?>

<div class="col-md-3 mb-3">

    <div class="card border-left-success shadow">

        <div class="card-body text-center">

            <h5>
                RT <?= e($row['rt']) ?>
                /
                RW <?= e($row['rw']) ?>
            </h5>

            <h2 class="text-success">
                <?= $row['total'] ?>
            </h2>

            <small>Relawan</small>

        </div>

    </div>

</div>

<?php endforeach; ?>
</div>
<?php endif; ?>



        <?php require_once __DIR__ . '/../partials/footer.php'; ?>