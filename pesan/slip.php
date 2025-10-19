<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ./login/login.php");
    exit();
}

// Ambil parameter dari URL
$versi = isset($_GET['versi']) ? intval($_GET['versi']) : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Debug log
error_log("Slip.php dipanggil dengan versi: $versi, id: $id");

if ($versi === null || $id === null) {
    error_log("Parameter versi atau id tidak ditemukan");
    header("Location: ./all.php");
    exit();
}

// Include koneksi database
require_once __DIR__ . '/../config.php';

// Ambil informasi anggota dari session
$idAnggota = $_SESSION['id_anggota'];
$namaAnggota = $_SESSION['nama'];

// Ambil detail transaksi dari database berdasarkan versi
$transaksi = null;
$produk = null;

error_log("Memproses versi: $versi dengan ID: $id");

switch ($versi) {
    case 1: // Simpanan
        // Coba dengan id_simpanan terlebih dahulu
        $query = "SELECT s.*, p.nama_produk, a.nama as nama_anggota
                 FROM simpanan s
                 JOIN produk p ON s.id_prodak = p.id
                 JOIN anggota a ON s.id_anggota = a.id_anggota
                 WHERE s.id_simpanan = ?";
        error_log("Query simpanan: $query");
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaksi = mysqli_fetch_assoc($result);
        error_log("Hasil query simpanan: " . ($transaksi ? "Ditemukan" : "Tidak ditemukan"));
        break;
        
    case 2: // Pinjaman
        $query = "SELECT p.*, pr.nama_produk, a.nama as nama_anggota
                 FROM pinjaman p
                 JOIN produk pr ON p.id_produk = pr.id
                 JOIN anggota a ON p.id_anggota = a.id_anggota
                 WHERE p.id_pinjaman = ?";
        error_log("Query pinjaman: $query");
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaksi = mysqli_fetch_assoc($result);
        error_log("Hasil query pinjaman: " . ($transaksi ? "Ditemukan" : "Tidak ditemukan"));
        break;
        
    case 3: // Penarikan
        $query = "SELECT t.*, p.nama_produk, a.nama as nama_anggota
                 FROM tarik t
                 JOIN produk p ON t.id_produk = p.id
                 JOIN anggota a ON t.id_anggota = a.id_anggota
                 WHERE t.id_tarik = ?";
        error_log("Query penarikan: $query");
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaksi = mysqli_fetch_assoc($result);
        error_log("Hasil query penarikan: " . ($transaksi ? "Ditemukan" : "Tidak ditemukan"));
        break;
        
    case 4: // Angsuran
        $query = "SELECT a.*, p.id_pinjaman, p.id_anggota, pr.nama_produk, ang.nama as nama_anggota
                 FROM angsuran a
                 JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman
                 JOIN produk pr ON p.id_produk = pr.id
                 JOIN anggota ang ON p.id_anggota = ang.id_anggota
                 WHERE a.id_angsuran = ?";
        error_log("Query angsuran: $query");
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $transaksi = mysqli_fetch_assoc($result);
        error_log("Hasil query angsuran: " . ($transaksi ? "Ditemukan" : "Tidak ditemukan"));
        break;
        
    default:
        error_log("Versi tidak dikenali: $versi");
        header("Location: ./all.php");
        exit();
}

if (!$transaksi) {
    error_log("Transaksi tidak ditemukan untuk versi: $versi, id: $id");
    // Tampilkan pesan error daripada redirect
    echo "<div style='padding: 20px; text-align: center;'>";
    echo "<h2>Transaksi Tidak Ditemukan</h2>";
    echo "<p>Mohon maaf, transaksi yang Anda cari tidak dapat ditemukan.</p>";
    echo "<p>Versi: $versi, ID: $id</p>";
    echo "<a href='./all.php' style='display: inline-block; margin-top: 10px; padding: 8px 16px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Kembali ke Notifikasi</a>";
    echo "</div>";
    exit();
}

error_log("Transaksi ditemukan: " . json_encode($transaksi));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi</title>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #slip-content, #slip-content * {
                visibility: visible;
            }
            #slip-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .slip-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 2px solid #333;
            background: white;
            font-family: 'Courier New', monospace;
        }
        
        .slip-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .slip-body {
            line-height: 1.6;
        }
        
        .slip-footer {
            text-align: center;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 20px;
            font-size: 12px;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .label {
            font-weight: bold;
        }
        
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="slip-container">
        <div id="slip-content">
            <div class="slip-header">
                <h2>KOPERASI INDONESIA</h2>
                <p>Jl. Contoh No. 123, Jakarta</p>
                <p>Telp: (021) 1234567</p>
                <hr>
            </div>
            
            <div class="slip-body">
                <div class="row">
                    <span class="label">Tanggal:</span>
                    <span><?= htmlspecialchars($transaksi['tanggal'] ?? date('Y-m-d')) ?></span>
                </div>
                
                <div class="row">
                    <span class="label">Nama Anggota:</span>
                    <span><?= htmlspecialchars($namaAnggota) ?></span>
                </div>
                
                <div class="row">
                    <span class="label">ID Anggota:</span>
                    <span><?= htmlspecialchars($idAnggota) ?></span>
                </div>
                
                <hr style="margin: 15px 0;">
                
                <?php if ($versi == 1): // Simpanan ?>
                    <div class="row">
                        <span class="label">Jenis Transaksi:</span>
                        <span>Simpanan</span>
                    </div>
                    <div class="row">
                        <span class="label">ID Simpanan:</span>
                        <span><?= htmlspecialchars($transaksi['id_simpanan'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Nama Produk:</span>
                        <span><?= htmlspecialchars($transaksi['nama_produk'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Tanggal Simpanan:</span>
                        <span><?= htmlspecialchars($transaksi['tanggal'] ?? '-') ?></span>
                    </div>
                <?php elseif ($versi == 2): // Pinjaman ?>
                    <div class="row">
                        <span class="label">Jenis Transaksi:</span>
                        <span>Pinjaman</span>
                    </div>
                    <div class="row">
                        <span class="label">ID Pinjaman:</span>
                        <span><?= htmlspecialchars($transaksi['id_pinjaman'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Nama Produk:</span>
                        <span><?= htmlspecialchars($transaksi['nama_produk'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Tenor:</span>
                        <span><?= htmlspecialchars($transaksi['tenor'] ?? '-') ?> bulan</span>
                    </div>
                    <div class="row">
                        <span class="label">Tanggal Pengajuan:</span>
                        <span><?= htmlspecialchars($transaksi['tanggal_pengajuan'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Status:</span>
                        <span><?= htmlspecialchars($transaksi['status'] ?? '-') ?></span>
                    </div>
                <?php elseif ($versi == 3): // Penarikan ?>
                    <div class="row">
                        <span class="label">Jenis Transaksi:</span>
                        <span>Penarikan</span>
                    </div>
                    <div class="row">
                        <span class="label">ID Penarikan:</span>
                        <span><?= htmlspecialchars($transaksi['id_tarik'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Nama Produk:</span>
                        <span><?= htmlspecialchars($transaksi['nama_produk'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Tanggal Penarikan:</span>
                        <span><?= htmlspecialchars($transaksi['tanggal'] ?? '-') ?></span>
                    </div>
                <?php elseif ($versi == 4): // Angsuran ?>
                    <div class="row">
                        <span class="label">Jenis Transaksi:</span>
                        <span>Angsuran Pinjaman</span>
                    </div>
                    <div class="row">
                        <span class="label">ID Angsuran:</span>
                        <span><?= htmlspecialchars($transaksi['id_angsuran'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">ID Pinjaman:</span>
                        <span><?= htmlspecialchars($transaksi['id_pinjaman'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Nama Produk:</span>
                        <span><?= htmlspecialchars($transaksi['nama_produk'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Tanggal Angsuran:</span>
                        <span><?= htmlspecialchars($transaksi['tanggal'] ?? '-') ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Status Pembayaran:</span>
                        <span><?= htmlspecialchars($transaksi['status'] ?? '-') ?></span>
                    </div>
                <?php endif; ?>
                
                <hr style="margin: 15px 0;">
                
                <div class="row">
                    <span class="label">Jumlah:</span>
                    <span class="amount">Rp <?= number_format($transaksi['jumlah'] ?? 0, 0, ',', '.') ?></span>
                </div>
            </div>
            
            <div class="slip-footer">
                <p>Terima Kasih atas Kepercayaan Anda</p>
                <p><?= date('d/m/Y H:i:s') ?></p>
            </div>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                🖨️ Cetak Struk
            </button>
            <button onclick="window.history.back()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                ← Kembali
            </button>
        </div>
    </div>
</body>
</html>