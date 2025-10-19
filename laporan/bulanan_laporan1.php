<?php 
    session_start();
    include '../config.php';

    // Ambil parameter bulan dari URL, default ke bulan saat ini jika tidak ada
    $bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
    $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
    $print_mode = isset($_GET['print']) && $_GET['print'] == 'true';

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

    $query_saldo = mysqli_query($conn, "
        SELECT SUM(jumlah) AS total_saldo
        FROM pinjaman
        WHERE tanggal_pengajuan BETWEEN '$bulan_lalu_awal' AND '$bulan_lalu_akhir'
    ");

    $row_saldo = mysqli_fetch_assoc($query_saldo);
    $saldo_sebelum = $row_saldo['total_saldo'] ?? 0;

    // 5. Total Simpanan untuk bulan ini
    $query_simpanan = "SELECT SUM(jumlah) as total FROM simpanan WHERE tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $result_simpanan = mysqli_query($conn, $query_simpanan);
    $total_simpanan = mysqli_fetch_assoc($result_simpanan)['total'] ?? 0;

    // 6. Total Angsuran untuk bulan ini
    $query_angsuran = "SELECT SUM(jumlah) as total FROM angsuran WHERE tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $result_angsuran = mysqli_query($conn, $query_angsuran);
    $total_angsuran = mysqli_fetch_assoc($result_angsuran)['total'] ?? 0;

    // 7. Hitung Saldo Akhir
    $saldo_akhir = $saldo_sebelum + $total_simpanan + $total_angsuran;

    // Nama bulan untuk ditampilkan
    $nama_bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Simpan Pinjam Bulan <?= $nama_bulan[$bulan] ?> <?= $tahun ?></title>
    <style>
        /* Gaya umum untuk tampilan layar */
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
        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 20px;
        }
        .text-tgl{
            font-size: 14px;
            margin-top: 2px;
            margin-bottom: 1px;
        }
        .ck-table-resized {
            border-collapse: collapse;
            border: 1px solid black;
            width: 100%;
        }
        .ck-table-resized th, .ck-table-resized td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        ul {
            list-style-type: disc;
            padding-left: 40px;
            font-size: 12px;
        }
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .filter-form {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            z-index: 1000;
        }

        /* CSS untuk mode cetak */
        @media print {
            @page {
                size: A4;
                margin: 1cm;
                margin-top: 1cm;
                margin-bottom: 1cm;
                marks: none; 
            }
            body {
                -webkit-print-color-adjust: exact;
                margin: 0;
                padding: 0;
                background: none;
            }
            body::before,
            body::after {
                display: none !important;
            }
            header,
            footer {
                display: none !important;
            }

            .container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 20px;
                box-sizing: border-box;
                box-shadow: none;
                page-break-after: always;
            }

            .print-button, .filter-form {
                display: none;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <?php if (!$print_mode): ?>
    <div class="filter-form">
        <form method="get">
            <input type="hidden" name="print" value="false">
            <label for="bulan">Bulan:</label>
            <select name="bulan" id="bulan">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $bulan ? 'selected' : '' ?>>
                        <?= $nama_bulan[$i] ?>
                    </option>
                <?php endfor; ?>
            </select>
            <label for="tahun">Tahun:</label>
            <input type="number" name="tahun" id="tahun" value="<?= $tahun ?>" min="2000" max="2100">
            <button type="submit">Filter</button>
        </form>
    </div>

    <button class="print-button" onclick="window.print()">Cetak Halaman</button>
    <?php endif; ?>

    <?php if ($print_mode): ?>
    <div class="container" style="margin: 0; padding: 0;">
    <?php else: ?>
    <div class="container">
    <?php endif; ?>
        <div class="kop">
            <div class="kop-header">
                <img src="../koperasi_indonesia.jpg" alt="Logo Koperasi Kiri" class="logo-kiri">
                <div class="kop-text">
                    <h1>KOPERASI RAMBA BULAN SIMPAN BULAN KPPS</h1>
                    <h2>Jl. Ki Gede Mayung, Sambeng, Kec. Gunungjati, Kabupaten Cirebon, Jawa Barat 45151</h2>
                </div>
                <img src="../logo-removebg-preview.png" alt="Logo Koperasi Kanan" class="logo-kanan">
            </div>
            <br>
            <div class="line"></div>
            <div class="thin-line"></div>
        </div>

        <p class="text-tgl">Laporan bulan: <?= strtoupper($nama_bulan[$bulan]) ?> <?= $tahun ?></p>

        <br><h2 class="section-title" style="margin-top: 1px !important">1. RINGKASAN KEUANGAN</h2>
        <ul>     
            <li>Saldo Awal: Rp <?= number_format($saldo_sebelum, 0, ',', '.') ?></li>
            <li>Total Simpanan Masuk: Rp <?= number_format($total_simpanan, 0, ',', '.') ?></li>
            <li>Angsuran Diterima: Rp <?= number_format($total_angsuran, 0, ',', '.') ?></li>
            <li><strong>Saldo Akhir: Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong></li>
        </ul>

        <br><h2 class="section-title">2. DATA SIMPANAN</h2> 
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Anggota</th>
                    <th>Jenis Simpanan</th>
                    <th>Jumlah (Rp)</th>
                    <th>Tanggal Simpan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($simpanan_data as $simpanan): 
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($simpanan['nama']) ?></td>
                    <td><?= htmlspecialchars($simpanan['id_prodak']) ?></td>
                    <td><?= number_format($simpanan['jumlah'], 0, ',', '.') ?></td>
                    <td><?= date('d-m-Y', strtotime($simpanan['tanggal'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total Simpanan:</strong></td>
                    <td><strong>Rp <?= number_format($total_simpanan, 0, ',', '.') ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <br><h2 class="section-title">3. DATA PINJAMAN</h2>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Anggota</th>
                    <th>Jumlah Pinjaman (Rp)</th>
                    <th>Tenor</th>
                    <th>Angsuran per Bulan (Rp)</th>
                    <th>Tanggal Pinjam</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($pinjaman_data as $pinjaman): 
                    $angsuran_per_bulan = $pinjaman['jumlah'] / $pinjaman['tenor'];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($pinjaman['nama']) ?></td>
                    <td><?= number_format($pinjaman['jumlah'], 0, ',', '.') ?></td>
                    <td><?= $pinjaman['tenor'] ?> bulan</td>
                    <td><?= number_format($angsuran_per_bulan, 0, ',', '.') ?></td>
                    <td><?= date('d-m-Y', strtotime($pinjaman['tanggal_pengajuan'])) ?></td>
                    <td><?= htmlspecialchars($pinjaman['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right;"><strong>Total Pinjaman:</strong></td>
                    <td><strong>Rp <?= number_format($total_pinjaman, 0, ',', '.') ?></strong></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <h2 class="section-title">4. DATA ANGSURAN</h2>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Anggota</th>
                    <th>Jumlah Pinjaman</th>
                    <th>Pembayaran Ke</th>
                    <th>Jumlah Angsuran (Rp)</th>
                    <th>Tanggal Bayar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($angsuran_data as $angsuran): 
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($angsuran['nama']) ?></td>
                    <td><?= number_format($angsuran['jumlah_pinjaman'], 0, ',', '.') ?></td>
                    <td><?= $angsuran['pembayaran_ke'] ?></td>
                    <td><?= number_format($angsuran['jumlah'], 0, ',', '.') ?></td>
                    <td><?= date('d-m-Y', strtotime($angsuran['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($angsuran['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total Angsuran:</strong></td>
                    <td><strong>Rp <?= number_format($total_angsuran, 0, ',', '.') ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <br><h2 class="section-title">5. EVALUASI LAPORAN PADA BULAN <?= strtoupper($nama_bulan[$bulan]) ?></h2>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Bidang</th>
                    <th>Bulan Lalu</th>
                    <th>Bulan Kini</th>
                    <th>Evaluasi</th>
                    <th>Ket.</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fungsi untuk mendapatkan total bulan lalu
                function getTotalSimpananBulanLalu($conn, $bulan, $tahun) {
                    $bulan_lalu = $bulan - 1;
                    $tahun_lalu = $tahun;
                    if ($bulan_lalu < 1) {
                        $bulan_lalu = 12;
                        $tahun_lalu--;
                    }
                    $tanggal_awal = date('Y-m-01', strtotime("$tahun_lalu-$bulan_lalu-01"));
                    $tanggal_akhir = date('Y-m-t', strtotime("$tahun_lalu-$bulan_lalu-01"));
                    
                    $query = "SELECT SUM(jumlah) as total FROM simpanan WHERE tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    return $row['total'] ?? 0;
                }
                
                function getTotalPinjamanBulanLalu($conn, $bulan, $tahun) {
                    $bulan_lalu = $bulan - 1;
                    $tahun_lalu = $tahun;
                    if ($bulan_lalu < 1) {
                        $bulan_lalu = 12;
                        $tahun_lalu--;
                    }
                    $tanggal_awal = date('Y-m-01', strtotime("$tahun_lalu-$bulan_lalu-01"));
                    $tanggal_akhir = date('Y-m-t', strtotime("$tahun_lalu-$bulan_lalu-01"));
                    
                    $query = "SELECT SUM(jumlah) as total FROM pinjaman WHERE tanggal_pengajuan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    return $row['total'] ?? 0;
                }
                
                function getTotalAngsuranBulanLalu($conn, $bulan, $tahun) {
                    $bulan_lalu = $bulan - 1;
                    $tahun_lalu = $tahun;
                    if ($bulan_lalu < 1) {
                        $bulan_lalu = 12;
                        $tahun_lalu--;
                    }
                    $tanggal_awal = date('Y-m-01', strtotime("$tahun_lalu-$bulan_lalu-01"));
                    $tanggal_akhir = date('Y-m-t', strtotime("$tahun_lalu-$bulan_lalu-01"));
                    
                    $query = "SELECT SUM(jumlah) as total FROM angsuran WHERE tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    return $row['total'] ?? 0;
                }
                
                // Data untuk evaluasi
                $evaluasi_data = [
                    [
                        'bidang' => 'Simpanan',
                        'bulan_lalu' => getTotalSimpananBulanLalu($conn, $bulan, $tahun),
                        'bulan_kini' => $total_simpanan,
                        'evaluasi' => '',
                        'keterangan' => ''
                    ],
                    [
                        'bidang' => 'Pinjaman',
                        'bulan_lalu' => getTotalPinjamanBulanLalu($conn, $bulan, $tahun),
                        'bulan_kini' => $total_pinjaman,
                        'evaluasi' => '',
                        'keterangan' => ''
                    ],
                    [
                        'bidang' => 'Angsuran',
                        'bulan_lalu' => getTotalAngsuranBulanLalu($conn, $bulan, $tahun),
                        'bulan_kini' => $total_angsuran,
                        'evaluasi' => '',
                        'keterangan' => ''
                    ],
                    [
                        'bidang' => 'Saldo Akhir',
                        'bulan_lalu' => $saldo_sebelum,
                        'bulan_kini' => $saldo_akhir,
                        'evaluasi' => '',
                        'keterangan' => ''
                    ]
                ];
                
                // Hitung evaluasi dan keterangan
                foreach ($evaluasi_data as &$data) {
                    $selisih = $data['bulan_kini'] - $data['bulan_lalu'];
                    $persentase = ($data['bulan_lalu'] != 0) ? ($selisih / $data['bulan_lalu'] * 100) : 0;
                    
                    if ($selisih > 0) {
                        $data['evaluasi'] = 'Meningkat ' . number_format(abs($persentase), 2) . '%';
                        $data['keterangan'] = 'Positif';
                    } elseif ($selisih < 0) {
                        $data['evaluasi'] = 'Menurun ' . number_format(abs($persentase), 2) . '%';
                        $data['keterangan'] = 'Perlu perhatian';
                    } else {
                        $data['evaluasi'] = 'Stabil';
                        $data['keterangan'] = 'Tidak ada perubahan';
                    }
                }
                unset($data);
                
                // Tampilkan data evaluasi
                $no = 1;
                foreach ($evaluasi_data as $eval):
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $eval['bidang'] ?></td>
                    <td>Rp <?= number_format($eval['bulan_lalu'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($eval['bulan_kini'], 0, ',', '.') ?></td>
                    <td><?= $eval['evaluasi'] ?></td>
                    <td><?= $eval['keterangan'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <br><p>Demikian laporan keuangan Koperasi Ramba Bulan SIMPAN BULAN KPPS ini, terima kasih atas perhatiannya.</p>
    </div>
    
    <?php if ($print_mode): ?>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    <?php endif; ?>
</body>
</html>