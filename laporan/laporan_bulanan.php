<?php 
    session_start();
    include '../config.php';

    // Ambil parameter bulan dari URL, default ke bulan saat ini jika tidak ada
    $bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
    $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

    // Validasi bulan (1-12)
    if ($bulan < 1 || $bulan > 12) {
        $bulan = date('n');
    }

    // Buat rentang tanggal untuk filter
    $tanggal_awal = date('Y-m-01', strtotime("$tahun-$bulan-01"));
    $tanggal_akhir = date('Y-m-t', strtotime("$tahun-$bulan-01"));

    // 1. Ambil data simpanan dengan filter bulan
    $query = "SELECT s.*, a.nama 
              FROM simpanan s 
              JOIN anggota a ON s.id_anggota = a.id_anggota 
              WHERE s.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
              ORDER BY s.tanggal DESC";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error dalam query: " . mysqli_error($conn));
    }
    $simpanan_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $simpanan_data[] = $row;
    }
    $total_simpanan = 0;
    foreach ($simpanan_data as $simpanan) {
        $total_simpanan += $simpanan['jumlah'];
    }

    // 2. Ambil data pinjaman dengan filter bulan
    $query_pinjaman = "SELECT p.*, a.nama 
                      FROM pinjaman p 
                      JOIN anggota a ON p.id_anggota = a.id_anggota 
                      WHERE p.tanggal_pengajuan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                      ORDER BY p.tanggal_pengajuan DESC";
    $result_pinjaman = mysqli_query($conn, $query_pinjaman);
    if (!$result_pinjaman) {
        die("Error dalam query pinjaman: " . mysqli_error($conn));
    }
    $pinjaman_data = [];
    while ($row = mysqli_fetch_assoc($result_pinjaman)) {
        $pinjaman_data[] = $row;
    }
    $total_pinjaman = 0;
    foreach ($pinjaman_data as $pinjaman) {
        $total_pinjaman += $pinjaman['jumlah'];
    }

    // 3. Data angsuran
    $query_angsuran = "SELECT a.id_angsuran, ag.nama, a.jumlah, a.tanggal, a.status, a.id_pinjaman,
                      (SELECT COUNT(*) FROM angsuran a2 WHERE a2.id_pinjaman = a.id_pinjaman AND a2.id_angsuran <= a.id_angsuran) as pembayaran_ke,
                      p.jumlah as jumlah_pinjaman
                      FROM angsuran a 
                      JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman 
                      JOIN anggota ag ON p.id_anggota = ag.id_anggota 
                      WHERE a.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                      ORDER BY a.tanggal DESC";
    $result_angsuran = mysqli_query($conn, $query_angsuran);
    $angsuran_data = [];
    $total_angsuran = 0;

    if (!$result_angsuran) {
        die("Error dalam query angsuran: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result_angsuran)) {
        $angsuran_data[] = $row;
        $total_angsuran += $row['jumlah'];
    }

    // 4. Ambil Saldo Awal Bulan Sebelumnya
    $bulan_lalu_awal = date('Y-m-01', strtotime("$tahun-$bulan-01 -1 month"));
    $bulan_lalu_akhir = date('Y-m-t', strtotime("$tahun-$bulan-01 -1 month"));
    
    // Hitung total simpanan dan angsuran dari bulan lalu
    $query_simpanan_lalu = "SELECT COALESCE(SUM(CAST(jumlah AS UNSIGNED)), 0) as total FROM simpanan WHERE tanggal BETWEEN '$bulan_lalu_awal' AND '$bulan_lalu_akhir'";
    $result_simpanan_lalu = mysqli_query($conn, $query_simpanan_lalu);
    $simpanan_lalu = mysqli_fetch_assoc($result_simpanan_lalu)['total'];

    $query_angsuran_lalu = "SELECT COALESCE(SUM(CAST(jumlah AS UNSIGNED)), 0) as total FROM angsuran WHERE tanggal BETWEEN '$bulan_lalu_awal' AND '$bulan_lalu_akhir'";
    $result_angsuran_lalu = mysqli_query($conn, $query_angsuran_lalu);
    $angsuran_lalu = mysqli_fetch_assoc($result_angsuran_lalu)['total'];

    $pendapatan_bulan_lalu = $simpanan_lalu + $angsuran_lalu;
    
    // 5. Saldo Akhir (re-kalkulasi)
    // Saldo akhir adalah total pemasukan (simpanan + angsuran) dikurangi total pengeluaran (pinjaman + penarikan)
    $pendapatan_bulan_ini = $total_simpanan + $total_angsuran;
    
    // Ambil data penarikan
    $query_penarikan = "SELECT COALESCE(SUM(CAST(jumlah AS UNSIGNED)), 0) as total FROM tarik WHERE tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $result_penarikan = mysqli_query($conn, $query_penarikan);
    $total_penarikan = mysqli_fetch_assoc($result_penarikan)['total'];
    
    $saldo_akhir = $pendapatan_bulan_ini - ($total_pinjaman + $total_penarikan);

    // Perbandingan Pendapatan
    $selisih_pendapatan = $pendapatan_bulan_ini - $pendapatan_bulan_lalu;
    $persentase_perubahan = ($pendapatan_bulan_lalu > 0) ? ($selisih_pendapatan / $pendapatan_bulan_lalu) * 100 : ($pendapatan_bulan_ini > 0 ? 100 : 0);

    $evaluasi = '';
    if ($selisih_pendapatan > 0) {
        $evaluasi = "Peningkatan pendapatan yang baik.";
    } elseif ($selisih_pendapatan < 0) {
        $evaluasi = "Penurunan pendapatan, perlu evaluasi lebih lanjut.";
    } else {
        $evaluasi = "Pendapatan stabil.";
    }
    
    // Nama bulan untuk judul laporan
    $nama_bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    // Mengelompokkan data simpanan berdasarkan ID pengguna
    $query_simpanan_grouped = "SELECT a.id_anggota, a.nama, SUM(CAST(s.jumlah AS UNSIGNED)) AS total_simpanan
                          FROM simpanan s
                          JOIN anggota a ON s.id_anggota = a.id_anggota
                          WHERE s.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                          GROUP BY a.id_anggota, a.nama
                          ORDER BY total_simpanan DESC";
    $result_simpanan_grouped = mysqli_query($conn, $query_simpanan_grouped);
    $simpanan_grouped = [];
    if ($result_simpanan_grouped) {
        while ($row = mysqli_fetch_assoc($result_simpanan_grouped)) {
            $simpanan_grouped[] = $row;
        }
    }

    // Mengelompokkan data pinjaman berdasarkan ID pengguna
    $query_pinjaman_grouped = "SELECT a.id_anggota, a.nama, SUM(CAST(p.jumlah AS UNSIGNED)) AS total_pinjaman
                          FROM pinjaman p
                          JOIN anggota a ON p.id_anggota = a.id_anggota
                          WHERE p.tanggal_pengajuan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                          GROUP BY a.id_anggota, a.nama
                          ORDER BY total_pinjaman DESC";
    $result_pinjaman_grouped = mysqli_query($conn, $query_pinjaman_grouped);
    $pinjaman_grouped = [];
    if ($result_pinjaman_grouped) {
        while ($row = mysqli_fetch_assoc($result_pinjaman_grouped)) {
            $pinjaman_grouped[] = $row;
        }
    }

    // Mengelompokkan data penarikan berdasarkan ID pengguna
    $query_penarikan_grouped = "SELECT a.id_anggota, a.nama, SUM(CAST(t.jumlah AS UNSIGNED)) AS total_penarikan
                          FROM tarik t
                          JOIN anggota a ON t.id_anggota = a.id_anggota
                          WHERE t.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                          GROUP BY a.id_anggota, a.nama
                          ORDER BY total_penarikan DESC";
    $result_penarikan_grouped = mysqli_query($conn, $query_penarikan_grouped);
    $penarikan_grouped = [];
    if ($result_penarikan_grouped) {
        while ($row = mysqli_fetch_assoc($result_penarikan_grouped)) {
            $penarikan_grouped[] = $row;
        }
    }
    
    // Mengelompokkan data angsuran berdasarkan ID pengguna
    $query_angsuran_grouped = "SELECT ag.id_anggota, ag.nama, SUM(CAST(a.jumlah AS UNSIGNED)) AS total_angsuran
                               FROM angsuran a
                               JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman
                               JOIN anggota ag ON p.id_anggota = ag.id_anggota
                               WHERE a.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                               GROUP BY ag.id_anggota, ag.nama
                               ORDER BY total_angsuran DESC";
    $result_angsuran_grouped = mysqli_query($conn, $query_angsuran_grouped);
    $angsuran_grouped = [];
    if ($result_angsuran_grouped) {
        while ($row = mysqli_fetch_assoc($result_angsuran_grouped)) {
            $angsuran_grouped[] = $row;
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Simpan Pinjam Bulan <?= $nama_bulan[$bulan] ?> <?= $tahun ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 20px;
            margin: 0 auto;
            width: 760px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        /* .kop { text-align: center; margin-bottom: 20px; }
        .kop h1 { font-size: 16px; font-weight: bold; margin: 0; }
        .kop h2 { font-size: 14px; font-weight: normal; margin: 0; } */
        .line { border-bottom: 3px solid black; margin-top: 5px; margin-bottom: 2px; }
        .thin-line { border-bottom: 1px solid black; margin-bottom: 10px; }
        .section-title { font-size: 14px; font-weight: bold; text-decoration: underline; margin-top: 20px; }
        .ck-table-resized { border-collapse: collapse; border: 1px solid black; width: 100%; }
        .ck-table-resized th, .ck-table-resized td { border: 1px solid black; padding: 8px; text-align: left; font-size: 12px; }
        .text-right { text-align: right; }
        .summary-section { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; }
        .summary-section h3 { margin-top: 0; font-size: 14px; font-weight: bold; }
        .summary-details { display: flex; justify-content: space-between; }
        .summary-item { text-align: center; }
        .summary-item .value { font-size: 16px; font-weight: bold; }
        .summary-item .label { font-size: 12px; color: #555; }
        .increase { color: green; }
        .decrease { color: red; }
        .evaluation { margin-top: 10px; font-style: italic; font-size: 12px; }
         .kop {
            text-align: center;
            margin-bottom: 20px;
        }
        .kop h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        .kop h2 {
            font-size: 14px;
            font-weight: normal;
            margin: 0;
        }
        .line {
            border-bottom: 3px solid black;
            margin-top: 5px;
            margin-bottom: 2px;
        }
        .thin-line {
            border-bottom: 1px solid black;
            margin-bottom: 10px;
        }
        .logo-kiri {
            width: 100px;
            height: auto;
            padding-left: 5%;
        }
        .logo-kanan {
            width: 100px;
            height: auto;
            padding-right: 5%;
        }
        .kop-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kop-text {
            flex-grow: 1;
            text-align: center;
        }
        .se
        @media print {
            @page { size: A4; margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="container">
         <div class="kop">
            <div class="kop-header">
                <img src="../koperasi_indonesia.jpg" alt="Logo Koperasi Kiri" class="logo-kiri">
                <div class="kop-text">
                    <h1>KOPERASI RAMBA BULAN SIMPAN BULAN KPPS</h1>
                    <h2>Jl. Ki Gede Mayung, Sambeng, Kec. Gunungjati, Kabupaten Cirebon, Jawa Barat 45151</h2>
                </div>
                <img src="../11logo.png" alt="Logo Koperasi Kanan" class="logo-kanan">
            </div>
            <br>
            <div class="line"></div>
            <div class="thin-line"></div>
        </div>
        
        <p class="text-tgl">Laporan Tanggal: <?= date('d F Y', strtotime($tanggal)) ?></p>
        <br><h2 class="section-title" style="margin-top: 1px !important">1. RINGKASAN KEUANGAN bulanan</h2>
        <ul>     
            <li>Saldo Awal Bulan: Rp <?= number_format($saldo_awal, 0, ',', '.') ?></li>
            <li>Total Simpanan Masuk Bulan Ini: Rp  <?= number_format($simpanan['total_simpanan'], 0, ',', '.') ?></li>
            <li>Angsuran Diterima Bulan Ini: Rp <?= number_format($angsuran['total_angsuran'], 0, ',', '.') ?></li>
            <li>Total Pinjaman Bulan Ini: Rp <?= number_format($pinjaman['total_pinjaman'], 0, ',', '.') ?></li>
            <li>Total Penarikan Bulan Ini: Rp <?= number_format($penarikan['total_penarikan'], 0, ',', '.') ?></li>
            <li><strong>Saldo Akhir Bulan: Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong></li>
        </ul>
        <!-- Data Simpanan -->
            <!-- Data Simpanan -->
        <div class="section-title">Data Simpanan</div>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>ID Anggota</th>
                    <th>Nama</th>
                    <th class="text-right">Total Simpanan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($simpanan_grouped as $simpanan): ?>
                <tr>
                    <td><?= $simpanan['id_anggota'] ?></td>
                    <td><?= $simpanan['nama'] ?></td>
                    <td class="text-right">Rp <?= number_format($simpanan['total_simpanan'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Data Pinjaman -->
        <div class="section-title">Data Pinjaman</div>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>ID Anggota</th>
                    <th>Nama</th>
                    <th class="text-right">Total Pinjaman</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pinjaman_grouped as $pinjaman): ?>
                <tr>
                    <td><?= $pinjaman['id_anggota'] ?></td>
                    <td><?= $pinjaman['nama'] ?></td>
                    <td class="text-right">Rp <?= number_format($pinjaman['total_pinjaman'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Data Penarikan -->
        <div class="section-title">Data Penarikan</div>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>ID Anggota</th>
                    <th>Nama</th>
                    <th class="text-right">Total Penarikan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penarikan_grouped as $penarikan): ?>
                <tr>
                    <td><?= $penarikan['id_anggota'] ?></td>
                    <td><?= $penarikan['nama'] ?></td>
                    <td class="text-right">Rp <?= number_format($penarikan['total_penarikan'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Data Angsuran -->
        <div class="section-title">Data Angsuran</div>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>ID Anggota</th>
                    <th>Nama</th>
                    <th class="text-right">Total Angsuran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($angsuran_grouped as $angsuran): ?>
                <tr>
                    <td><?= $angsuran['id_anggota'] ?></td>
                    <td><?= $angsuran['nama'] ?></td>
                    <td class="text-right">Rp <?= number_format($angsuran['total_angsuran'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Ringkasan Keuangan -->
        <div class="section-title">Ringkasan Keuangan</div>
        <table class="ck-table-resized">
            <tbody>
                <tr>
                    <td>Total Simpanan</td>
                    <td class="text-right">Rp <?= number_format($total_simpanan, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Total Pinjaman</td>
                    <td class="text-right">Rp <?= number_format($total_pinjaman, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Total Penarikan</td>
                    <td class="text-right">Rp <?= number_format($total_penarikan, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Total Angsuran</td>
                    <td class="text-right">Rp <?= number_format($total_angsuran, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Saldo Akhir</th>
                    <th class="text-right">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></th>
                </tr>
            </tbody>
        </table>

        <!-- Ringkasan Pendapatan -->
        <div class="summary-section">
            <h3>Ringkasan Pendapatan</h3>
            <div class="summary-details">
                <div class="summary-item">
                    <div class="value">Rp <?= number_format($pendapatan_bulan_lalu, 0, ',', '.') ?></div>
                    <div class="label">Pendapatan Bulan Lalu</div>
                </div>
                <div class="summary-item">
                    <div class="value">Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?></div>
                    <div class="label">Pendapatan Bulan Ini</div>
                </div>
                <div class="summary-item">
                    <div class="value <?= ($selisih_pendapatan >= 0) ? 'increase' : 'decrease' ?>">
                        Rp <?= number_format(abs($selisih_pendapatan), 0, ',', '.') ?>
                    </div>
                    <div class="label">Selisih</div>
                </div>
                <div class="summary-item">
                    <div class="value <?= ($persentase_perubahan >= 0) ? 'increase' : 'decrease' ?>">
                        <?= number_format($persentase_perubahan, 2, ',', '.') ?>%
                    </div>
                    <div class="label">Perubahan</div>
                </div>
            </div>
            <div class="evaluation">
                <strong>Evaluasi:</strong> <?= $evaluasi ?>
            </div>
        </div>
    </div>
</body>
</html>
