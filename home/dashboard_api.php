<?php
header('Content-Type: application/json');
require_once '../config.php';

// Fungsi untuk mendapatkan koneksi database
function getDBConnection() {
    try {
        $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Fungsi untuk mendapatkan statistik dashboard
function getDashboardStats() {
    $conn = getDBConnection();
    
    try {
        // Total Anggota
        $stmt = $conn->query("SELECT COUNT(*) as total FROM anggota WHERE status = 'aktif'");
        $totalAnggota = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total Simpanan
        $stmt = $conn->query("SELECT SUM(jumlah) as total FROM simpanan");
        $totalSimpanan = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Pinjaman Aktif
        $stmt = $conn->query("SELECT COUNT(*) as total FROM pinjaman WHERE status IN ('cair', 'berjalan')");
        $pinjamanAktif = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Angsuran Hari Ini
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM angsuran WHERE tanggal = :today AND status = 'sudah melakuakan pembayaran'");
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $angsuranHariIni = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Persentase perubahan dari bulan lalu
        $lastMonth = date('Y-m', strtotime('-1 month'));
        
        // Hitung persentase perubahan anggota
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM anggota 
                               WHERE status = 'aktif' AND DATE_FORMAT(tgl_lahir, '%Y-%m') = :lastMonth");
        $stmt->bindParam(':lastMonth', $lastMonth);
        $stmt->execute();
        $anggotaLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $persenAnggota = $anggotaLastMonth > 0 ? round(($totalAnggota - $anggotaLastMonth) / $anggotaLastMonth * 100) : 0;
        
        // Hitung persentase perubahan simpanan
        $stmt = $conn->prepare("SELECT SUM(jumlah) as total FROM simpanan 
                               WHERE DATE_FORMAT(tanggal, '%Y-%m') = :lastMonth");
        $stmt->bindParam(':lastMonth', $lastMonth);
        $stmt->execute();
        $simpananLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $persenSimpanan = $simpananLastMonth > 0 ? round(($totalSimpanan - $simpananLastMonth) / $simpananLastMonth * 100) : 0;
        
         // Hitung persentase perubahan pinjaman dengan status cair dan berjalan
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pinjaman 
                                WHERE (status = 'berjalan' OR status = 'cair') 
                                AND DATE_FORMAT(tanggal_pengajuan, '%Y-%m') = :lastMonth");
        $stmt->bindParam(':lastMonth', $lastMonth);
        $stmt->execute();
        $pinjamanLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $persenPinjaman = $pinjamanLastMonth > 0 ? round(($pinjamanAktif - $pinjamanLastMonth) / $pinjamanLastMonth * 100) : 0;
        
        return [
            'total_anggota' => $totalAnggota,
            'total_simpanan' => $totalSimpanan,
            'pinjaman_aktif' => $pinjamanAktif,
            'angsuran_hari_ini' => $angsuranHariIni,
            'persen_anggota' => $persenAnggota,
            'persen_simpanan' => $persenSimpanan,
            'persen_pinjaman' => $persenPinjaman
        ];
        
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Fungsi untuk mendapatkan data chart simpanan bulanan
function getSimpananChartData() {
    $conn = getDBConnection();

    try {
        $labels = [];
        $data = [];

        // Ambil data 12 bulan terakhir dari bulan ini
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $labels[] = date('M', strtotime($month . '-01'));

            $stmt = $conn->prepare("SELECT SUM(jumlah) as total FROM simpanan 
                                    WHERE DATE_FORMAT(tanggal, '%Y-%m') = :month");
            $stmt->bindParam(':month', $month);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $result['total'] ?? 0;
            $data[] = round($total / 1000000, 2); // Misalnya ditampilkan dalam jutaan
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];

    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Fungsi untuk mendapatkan data chart distribusi pinjaman
function getPinjamanChartData() {
    $conn = getDBConnection();
    
    try {
        $labels = [];
        $data = [];
        
        // Ambil semua produk pinjaman
        $stmt = $conn->query("SELECT id, nama_produk FROM produk WHERE kategori = 'PEMBIAYAAN'");
        $produkPinjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($produkPinjaman as $produk) {
            $labels[] = $produk['nama_produk'];
            
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pinjaman 
                                  WHERE id_produk = :id_produk AND status IN ('cair', 'berjalan')");
            $stmt->bindParam(':id_produk', $produk['id']);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $data[] = $result['total'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
        
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}



// Main endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = [
        'stats' => getDashboardStats(),
        'simpanan_chart' => getSimpananChartData(),
        'pinjaman_chart' => getPinjamanChartData()
    ];
    
    echo json_encode($response);
}
?>