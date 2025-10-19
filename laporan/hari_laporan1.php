<?php 
    session_start();
    include '../config.php';
// Pengecekan sesi dan peran
    if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
        // Jika sesi tidak ada atau kosong, redirect ke halaman login
        header("Location: ../login/login.php?error=silahkan_login");
        exit();
    }

    $allowed_roles = ['admin'];
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Jika peran tidak diizinkan, hancurkan sesi dan redirect ke halaman login dengan pesan error
        session_unset(); // Hapus semua variabel sesi
        session_destroy(); // Hancurkan sesi
        header("Location: ../login/login.php?error=Hanya admin yang bisa mengakses");
        exit();
    }

    // Ambil parameter tanggal dari URL, default ke hari ini jika tidak ada
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

    // Validasi format tanggal Y-m-d
    $date_format = 'Y-m-d';
    $d = DateTime::createFromFormat($date_format, $tanggal);
    if (!$d || $d->format($date_format) !== $tanggal) {
        // Jika format tidak valid, kembalikan ke tanggal hari ini
        $tanggal = date('Y-m-d');
    }

    // 1. Ambil data simpanan dengan filter tanggal
    $query_simpanan = "SELECT s.*, a.nama 
                      FROM simpanan s 
                      JOIN anggota a ON s.id_anggota = a.id_anggota 
                      WHERE s.tanggal = '$tanggal'
                      ORDER BY s.tanggal DESC";
    $result_simpanan = mysqli_query($conn, $query_simpanan);
    if (!$result_simpanan) {
        die("Error dalam query simpanan: " . mysqli_error($conn));
    }
    $simpanan_data = [];
    while ($row = mysqli_fetch_assoc($result_simpanan)) {
        $simpanan_data[] = $row;
    }
    $total_simpanan_harian = 0;
    foreach ($simpanan_data as $simpanan) {
        $total_simpanan_harian += $simpanan['jumlah'];
    }

    // 2. Ambil data pinjaman dengan filter tanggal
    $query_pinjaman = "SELECT p.*, a.nama 
                       FROM pinjaman p 
                       JOIN anggota a ON p.id_anggota = a.id_anggota 
                       WHERE p.tanggal_pengajuan = '$tanggal'
                       ORDER BY p.tanggal_pengajuan DESC";
    $result_pinjaman = mysqli_query($conn, $query_pinjaman);
    if (!$result_pinjaman) {
        die("Error dalam query pinjaman: " . mysqli_error($conn));
    }
    $pinjaman_data = [];
    while ($row = mysqli_fetch_assoc($result_pinjaman)) {
        $pinjaman_data[] = $row;
    }
    $total_pinjaman_harian = 0;
    foreach ($pinjaman_data as $pinjaman) {
        $total_pinjaman_harian += $pinjaman['jumlah'];
    }

    // 3. Data angsuran dengan filter tanggal
    $query_angsuran = "SELECT a.id_angsuran, ag.nama, a.jumlah, a.tanggal, a.status, a.id_pinjaman,
                      (SELECT COUNT(*) FROM angsuran a2 WHERE a2.id_pinjaman = a.id_pinjaman AND a2.id_angsuran <= a.id_angsuran) as pembayaran_ke,
                      p.jumlah as jumlah_pinjaman
                      FROM angsuran a 
                      JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman 
                      JOIN anggota ag ON p.id_anggota = ag.id_anggota 
                      WHERE a.tanggal = '$tanggal'
                      ORDER BY a.tanggal DESC";
    $result_angsuran = mysqli_query($conn, $query_angsuran);
    if (!$result_angsuran) {
        die("Error dalam query angsuran: " . mysqli_error($conn));
    }
    $angsuran_data = [];
    $total_angsuran_harian = 0;
    while ($row = mysqli_fetch_assoc($result_angsuran)) {
        $angsuran_data[] = $row;
        $total_angsuran_harian += $row['jumlah'];
    }

// 4. Ambil data tarik dengan filter tanggal
    $query_tarik = "SELECT t.*, a.nama 
                       FROM tarik t 
                       JOIN anggota a ON t.id_anggota = a.id_anggota 
                       WHERE DATE(t.tanggal) = '$tanggal'
                       ORDER BY t.tanggal DESC";
    $result_tarik = mysqli_query($conn, $query_tarik);
    if (!$result_tarik) {
        die("Error dalam query tarik: " . mysqli_error($conn));
    }
    $tarik_data = [];
    $total_tarik_harian = 0;
    while ($row = mysqli_fetch_assoc($result_tarik)) {
        $tarik_data[] = $row;
        $total_tarik_harian += $row['jumlah'];
    }
    // 4. Ambil Saldo Awal (Saldo Akhir dari hari sebelumnya)
    $tanggal_kemarin = date('Y-m-d', strtotime($tanggal . ' -1 day'));

    // Total simpanan sampai kemarin
    $query_total_simpanan_kemarin = "SELECT SUM(jumlah) as total FROM simpanan WHERE tanggal <= '$tanggal_kemarin'";
    $result_total_simpanan_kemarin = mysqli_query($conn, $query_total_simpanan_kemarin);
    $total_simpanan_kemarin = mysqli_fetch_assoc($result_total_simpanan_kemarin)['total'] ?? 0;

    // Total angsuran sampai kemarin
    $query_total_angsuran_kemarin = "SELECT SUM(jumlah) as total FROM angsuran WHERE tanggal <= '$tanggal_kemarin'";
    $result_total_angsuran_kemarin = mysqli_query($conn, $query_total_angsuran_kemarin);
    $total_angsuran_kemarin = mysqli_fetch_assoc($result_total_angsuran_kemarin)['total'] ?? 0;
    
    // Total tarik sampai kemarin
    $query_total_tarik_kemarin = "SELECT SUM(jumlah) as total FROM tarik WHERE DATE(tanggal) <= '$tanggal_kemarin'";
    $result_total_tarik_kemarin = mysqli_query($conn, $query_total_tarik_kemarin);
    $total_tarik_kemarin = mysqli_fetch_assoc($result_total_tarik_kemarin)['total'] ?? 0;

    // Total pinjaman (sebagai pengurang saldo) sampai kemarin
    $query_total_pinjaman_kemarin = "SELECT SUM(jumlah) as total FROM pinjaman WHERE tanggal_pengajuan <= '$tanggal_kemarin'";
    $result_total_pinjaman_kemarin = mysqli_query($conn, $query_total_pinjaman_kemarin);
    $total_pinjaman_kemarin = mysqli_fetch_assoc($result_total_pinjaman_kemarin)['total'] ?? 0;

    $saldo_awal = $total_simpanan_kemarin + $total_angsuran_kemarin - $total_pinjaman_kemarin - $total_tarik_kemarin;

    // 5. Hitung Saldo Akhir Hari Ini
    $saldo_akhir = $saldo_awal + $total_simpanan_harian + $total_angsuran_harian - $total_pinjaman_harian - $total_tarik_harian; // Asumsi pinjaman dan penarikan mengurangi saldo
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - <?= date('d F Y', strtotime($tanggal)) ?></title>
    <style>
        /* Gaya umum untuk tampilan layar */
        body {
            font-family: Arial, sans-serif; background: #f4f4f4;padding: 20px;
        }
        .container { background: white; padding: 20px;margin: 0 auto; width: 760px;box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
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
            .signature-input {
                border: none !important; /* Hapus semua border saat cetak */
            }
             .signature-input::placeholder {
                color: #000 !important; /* Pastikan placeholder terlihat saat cetak */
            }
        }
        .signature-container {
            margin-top: 40px;
            width: 100%;
            display: flex;
            justify-content: space-around;
            page-break-inside: avoid;
        }
        .signature-block {
            text-align: center;
            width: 280px;
        }
        .signature-block p {
            margin-bottom: 70px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 5px;
        }
        .signature-input {
            border: 1px solid #ccc; /* Border default untuk layar */
            text-align: center;
            font-weight: bold;
            width: 220px;
            padding: 5px;
            background-color: transparent;
        }

        .signature-input::placeholder {
            color: #555;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="filter-form">
        <form method="get">
            <label for="tanggal">Tanggal:</label>
            <input type="date" name="tanggal" id="tanggal" value="<?= $tanggal ?>">
            <button type="submit">Filter</button>
        </form>
    </div>

    <button class="print-button" onclick="window.print()">Cetak Halaman</button>
    
    <button class="print-button" onclick="exportToPDF()" style="background-color: #dc3545; margin-left: 10px;">Export ke PDF</button>

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

        <br><h2 class="section-title" style="margin-top: 1px !important">1. RINGKASAN KEUANGAN HARIAN</h2>
        <ul>     
            
            <li>Total Simpanan Masuk Hari Ini: Rp <?= number_format($total_simpanan_harian, 0, ',', '.') ?></li>
            <li>Angsuran Diterima Hari Ini: Rp <?= number_format($total_angsuran_harian, 0, ',', '.') ?></li>
            <li>Total Pinjaman Hari Ini: Rp <?= number_format($total_pinjaman_harian, 0, ',', '.') ?></li>
            <li>Total Penarikan Hari Ini: Rp <?= number_format($total_tarik_harian, 0, ',', '.') ?></li>
            <!-- <li><strong>Saldo Akhir Hari: Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong></li> -->
        </ul>

        <br><h2 class="section-title">2. DATA SIMPANAN HARIAN</h2> 
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
                if (!empty($simpanan_data)):
                    foreach ($simpanan_data as $simpanan): 
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($simpanan['nama']) ?></td>
                    <td><?= htmlspecialchars($simpanan['id_prodak']) ?></td>
                    <td><?= number_format($simpanan['jumlah'], 0, ',', '.') ?></td>
                    <td><?= date('d-m-Y', strtotime($simpanan['tanggal'])) ?></td>
                </tr>
                <?php 
                    endforeach;
                else: ?>
                <tr><td colspan="5" style="text-align: center;">Tidak ada data simpanan pada tanggal ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total Simpanan Hari Ini:</strong></td>
                    <td><strong>Rp <?= number_format($total_simpanan_harian, 0, ',', '.') ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <br><h2 class="section-title">3. DATA PINJAMAN HARIAN</h2>
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
                if (!empty($pinjaman_data)):
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
                <?php 
                    endforeach;
                else: ?>
                <tr><td colspan="7" style="text-align: center;">Tidak ada data pinjaman pada tanggal ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right;"><strong>Total Pinjaman Hari Ini:</strong></td>
                    <td><strong>Rp <?= number_format($total_pinjaman_harian, 0, ',', '.') ?></strong></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <h2 class="section-title">4. DATA ANGSURAN HARIAN</h2>
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
                 if (!empty($angsuran_data)):
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
                <?php 
                    endforeach;
                else: ?>
                <tr><td colspan="7" style="text-align: center;">Tidak ada data angsuran pada tanggal ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total Angsuran Hari Ini:</strong></td>
                    <td><strong>Rp <?= number_format($total_angsuran_harian, 0, ',', '.') ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
<h2 class="section-title">5. DATA PENARIKAN HARIAN</h2>
        <table class="ck-table-resized">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Anggota</th>
                    <th>Jumlah Penarikan (Rp)</th>
                    <th>Tanggal Tarik</th>
                </tr>
            </thead>

            <tbody>
                <?php 
                $no = 1;
                 if (!empty($tarik_data)):
                    foreach ($tarik_data as $tarik): 
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($tarik['nama']) ?></td>
                    <td><?= number_format($tarik['jumlah'], 0, ',', '.') ?></td>
                    <td><?= date('d-m-Y H:i:s', strtotime($tarik['tanggal'])) ?></td>
                </tr>
                <?php 
                    endforeach;
                else: ?>
                <tr><td colspan="4" style="text-align: center;">Tidak ada data penarikan pada tanggal ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right;"><strong>Total Penarikan Hari Ini:</strong></td>
                    <td><strong>Rp <?= number_format($total_tarik_harian, 0, ',', '.') ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <br><p>Demikian laporan keuangan harian Koperasi Ramba Bulan SIMPAN BULAN KPPS ini, terima kasih atas perhatiannya.</p>
        <div class="signature-container">
            <div class="signature-block">
                <p>Kepala Koperasi</p>
                <input type="text" class="signature-input" placeholder="(.....................)">
            </div>
            <div class="signature-block">
                <p>Bendahara</p>
                <input type="text" class="signature-input" placeholder="(.....................)">
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        // Fungsi untuk export ke PDF
        function exportToPDF() {
            // Buat elemen PDF
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            // Tambahkan font untuk mendukung karakter Indonesia
            pdf.setFont('helvetica');
    
            // Ambil konten laporan
            const content = document.querySelector('.container');
            
            // Gunakan html2canvas untuk mengubah HTML ke canvas
            html2canvas(content, {
                scale: 2,
                useCORS: true,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                
                // Hitung tinggi gambar untuk menyesuaikan dengan halaman PDF
                const imgWidth = 190;
                const pageHeight = 295;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                
                let position = 35;
                
                // Tambahkan gambar ke PDF
                pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                // Jika konten lebih dari satu halaman, tambahkan halaman baru
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                // Simpan PDF
                pdf.save(`Laporan_Harian_<?= date('Y-m-d', strtotime($tanggal)) ?>.pdf`);
            });
        }
    </script>
    
</body>
</html>

