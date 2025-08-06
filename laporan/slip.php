<?php
session_start();
require_once '../config.php'; // Sesuaikan path sesuai struktur folder Anda

// Get transaction type and ID from query parameters
$jenis_transaksi = $_GET['jenis'] ?? '';
$id_transaksi = $_GET['id'] ?? '';

// Validate transaction type
if (!in_array($jenis_transaksi, ['simpanan', 'pinjaman', 'tarik', 'angsuran'])) {
    die("Jenis transaksi tidak valid");
}

// Load messages from JSON file
$pesanData = file_get_contents('../pesan/pesan.json');
$pesanList = json_decode($pesanData, true);
$pesan = $pesanList[$jenis_transaksi] ?? 'Bukti Transaksi';

// Fetch transaction data based on type
switch ($jenis_transaksi) {
    case 'simpanan':
        $query = "
            SELECT SQL_NO_CACHE s.id_simpanan, s.id_anggota, s.tanggal, s.jumlah, p.nama_produk as jenis,
            a.nama, a.alamat, a.telepon
        FROM simpanan s
        JOIN produk p ON s.id_prodak = p.id
        JOIN anggota a ON s.id_anggota = a.id_anggota
        WHERE s.id_simpanan = ?";
        break;
        
    case 'pinjaman':
        $query = "
            SELECT SQL_NO_CACHE p.id_pinjaman, p.id_anggota, p.tanggal_pengajuan, p.jumlah, p.tenor,
            pr.nama_produk as akad, a.nama, a.alamat, a.telepon
        FROM pinjaman p
        JOIN produk pr ON p.id_produk = pr.id
        JOIN anggota a ON p.id_anggota = a.id_anggota
        WHERE p.id_pinjaman = ?";
        break;
        
    case 'tarik':
        $query = "
            SELECT SQL_NO_CACHE t.id_tarik, t.id_anggota, t.tanggal, t.jumlah, p.nama_produk as jenis,
            a.nama, a.alamat, a.telepon
        FROM tarik t
        JOIN produk p ON t.id_produk = p.id
        JOIN anggota a ON t.id_anggota = a.id_anggota
        WHERE t.id_tarik = ?";
        break;
        
    case 'angsuran':
        $query = "
            SELECT SQL_NO_CACHE
                a.id_angsuran, a.tanggal, a.jumlah,
                p.id_pinjaman, p.jumlah as total_pinjaman,
                ag.id_anggota, ag.nama, ag.alamat, ag.telepon
            FROM angsuran a
            JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman
            JOIN anggota ag ON p.id_anggota = ag.id_anggota
            WHERE a.id_angsuran = ?";
        break;
}

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data transaksi tidak ditemukan");
}

$data = $result->fetch_assoc();

// Add ID field with proper prefix
$data['id_'.$jenis_transaksi] = $jenis_transaksi . '-' . str_pad($data['id_'.$jenis_transaksi], 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bukti Transaksi <?= ucfirst($jenis_transaksi) ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .struk { width: 700px; border: 1px solid #000; padding: 10px; margin: auto; }
        .header {
            text-align: center;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: -2px;
        }
        .header-text {
            flex-grow: 1;
            padding: 0;
        }
        .logo {
            height: 80px;
            width: auto;
            max-width: 150px;
        }
        .logo-kiri {
            order: 1;
        }
        .logo-kanan {
            order: 3;
        }
        .header-text {
            order: 2;
        }
        .content table {
            width: 100%;
            border-collapse: collapse;
        }
        .content td {
            padding: 5px 10px;
        }
        .content td:first-child {
            width: 30%;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
        }
        @media print {
            @page {
                size: auto;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 10px;
            }
            .print-button {
                display: none !important;
            }
        }
    </style>
    <!-- Cache prevention -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <button class="print-button" onclick="window.print()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
        Cetak Dokumen
    </button> <br>
    
    <div class="struk">
        <div class="header">
            <img src="../11logo.png" alt="Koperasi" class="logo logo-kiri">
            <div class="header-text">
                <h3>KOPERASI SIMPAN PINJAM DAN PEMBIAYAN PERAMBABULAN MAKMUR ABADIH</h3>
                <p>Jl. Ki Gede Mayung, Sambeng, Kec. Gunungjati, Kabupaten Cirebon, Jawa Barat 45151</p>
                <p style="font-weight: bold;"><?= htmlspecialchars($pesan) ?></p>
            </div>
            <img src="../koperasi_indonesia.jpg" alt="Koperasi" class="logo logo-kanan">
        </div>
        
        <div class="content">
            <table>
                <tr>
                    <td>KODE <?= strtoupper($jenis_transaksi) ?></td>
                    <td>: <?= htmlspecialchars($data['id_'.$jenis_transaksi]) ?></td>
                </tr>
                <tr>
                    <td>NAMA</td>
                    <td>: <?= htmlspecialchars($data['nama']) ?></td>
                </tr>
                <tr>
                    <td>ALAMAT</td>
                    <td>: <?= htmlspecialchars($data['alamat']) ?></td>
                </tr>
                <tr>
                    <td>TELEPON</td>
                    <td>: <?= htmlspecialchars($data['telepon']) ?></td>
                </tr>
                
                <?php if ($jenis_transaksi == 'pinjaman'): ?>
                    <tr>
                        <td>AKAD</td>
                        <td>: <?= htmlspecialchars($data['akad']) ?></td>
                    </tr>
                    <tr>
                        <td>JUMLAH PINJAMAN</td>
                        <td>: Rp <?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>TANGGAL PENGAJUAN</td>
                        <td>: <?= date("Y-m-d", strtotime($data['tanggal_pengajuan'])) ?></td>
                    </tr>
                    <tr>
                        <td>TENOR</td>
                        <td>: <?= htmlspecialchars($data['tenor']) ?> bulan</td>
                    </tr>
                <?php elseif ($jenis_transaksi == 'simpanan'): ?>
                    <tr>
                        <td>JENIS SIMPANAN</td>
                        <td>: <?= htmlspecialchars($data['jenis']) ?></td>
                    </tr>
                    <tr>
                        <td>JUMLAH SIMPANAN</td>
                        <td>: Rp <?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>TANGGAL TRANSAKSI</td>
                        <td>: <?= date("Y-m-d", strtotime($data['tanggal'])) ?></td>
                    </tr>
                <?php elseif ($jenis_transaksi == 'tarik'): ?>
                    <tr>
                        <td>JENIS PENARIKAN</td>
                        <td>: <?= htmlspecialchars($data['jenis']) ?></td>
                    </tr>
                    <tr>
                        <td>JUMLAH PENARIKAN</td>
                        <td>: Rp <?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>TANGGAL TRANSAKSI</td>
                        <td>: <?= date("Y-m-d", strtotime($data['tanggal'])) ?></td>
                    </tr>
                <?php elseif ($jenis_transaksi == 'angsuran'): ?>
                    <tr>
                        <td>ID ANGSURAN</td>
                        <td>: <?= htmlspecialchars($data['id_angsuran']) ?></td>
                    </tr>
                    <tr>
                        <td>TANGGAL BAYAR</td>
                        <td>: <?= htmlspecialchars($data['tanggal']) ?></td>
                    </tr>
                    <tr>
                        <td>JUMLAH BAYAR</td>
                        <td>: Rp <?= number_format($data['jumlah'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>TOTAL PINJAMAN</td>
                        <td>: Rp <?= number_format($data['total_pinjaman'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="footer">
            <table width="100%">
                <tr>
                    <td><strong>Petugas</strong></td>
                    <td><strong>Anggota</strong></td>
                </tr>
                <tr><td height="50px"></td><td height="50px"></td></tr>
                <tr>
                    <td>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <?= htmlspecialchars($_SESSION['nama']) ?> 
                        <?php else: ?>
                            ________________
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($_SESSION['role'] == 'user'): ?>
                            <?= htmlspecialchars($_SESSION['nama']) ?>
                        <?php else: ?>
                            ________________
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>                
    </div>
</body>
</html>