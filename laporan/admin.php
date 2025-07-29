<?php
include '../config.php';

// Tanggal default: hari ini
$dari = $_GET['dari'] ?? date('Y-m-d');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Fungsi untuk format Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi untuk menampilkan tabel dengan desain yang konsisten
function tampilkanTabel($judul, $data, $warna) {
    $total = 0;
    
    echo '<div class="bg-white rounded-lg shadow overflow-hidden mb-6">';
    echo '<div class="bg-'.$warna.'-600 px-4 py-3">';
    echo '<h3 class="text-lg font-semibold text-white">'.$judul.'</h3>';
    echo '</div>';
    
    echo '<div class="overflow-x-auto">';
    echo '<table class="min-w-full divide-y divide-gray-200">';
    echo '<thead class="bg-gray-50">';
    echo '<tr>';
    echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>';
    echo '<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">'.$row['nama'].'</td>';
        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">'.formatRupiah($row['jumlah']).'</td>';
        echo '</tr>';
        $total += $row['jumlah'];
    }
    
    echo '<tr class="bg-gray-50 font-semibold">';
    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Total</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">'.formatRupiah($total).'</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    
    return $total;
}


// Handle export to Excel
if (isset($_GET['export'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Keuangan_".date('Ymd').".xls");
    
    // Style untuk Excel
    $header_style = 'background-color: #1E40AF; color: #FFFFFF; font-weight: bold;';
    $total_style = 'background-color: #BFDBFE; font-weight: bold;';
    
    echo "<table border='1'>";
    
    // Header Laporan
    echo "<tr>";
    echo "<td colspan='2' style='{$header_style}'>Laporan Keuangan Koperasi</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td colspan='2' style='{$header_style}'>Periode: ".date('d F Y', strtotime($dari))." - ".date('d F Y', strtotime($sampai))."</td>";
    echo "</tr>";
    echo "<tr><td colspan='2'>&nbsp;</td></tr>";
    
    // AKTIVA
    echo "<tr><td colspan='2' style='{$header_style}'>AKTIVA (PEMBIAYAAN)</td></tr>";
    echo "<tr><th style='{$header_style}'>Nama Produk</th><th style='{$header_style}'>Jumlah</th></tr>";
    
    $total_aktiva = 0;
    $q_aktiva = "SELECT p.nama_produk AS nama, IFNULL(SUM(pi.jumlah), 0) AS jumlah
                FROM produk p
                LEFT JOIN pinjaman pi ON p.id = pi.id_produk
                WHERE p.kategori = 'PEMBIAYAAN'
                AND pi.tanggal_pengajuan BETWEEN '$dari' AND '$sampai'
                GROUP BY p.nama_produk";
    $res_aktiva = $conn->query($q_aktiva);
    while ($row = $res_aktiva->fetch_assoc()) {
        echo "<tr><td>{$row['nama']}</td><td>Rp ".number_format($row['jumlah'],0,',','.')."</td></tr>";
        $total_aktiva += $row['jumlah'];
    }
    echo "<tr><td style='{$total_style}'>Total Aktiva</td><td style='{$total_style}'>Rp ".number_format($total_aktiva,0,',','.')."</td></tr>";
    
    // KEWAJIBAN
    echo "<tr><td colspan='2'>&nbsp;</td></tr>";
    echo "<tr><td colspan='2' style='{$header_style}'>KEWAJIBAN (SIMPANAN NON-WAJIB)</td></tr>";
    echo "<tr><th style='{$header_style}'>Nama Produk</th><th style='{$header_style}'>Jumlah</th></tr>";
    
    $total_kewajiban = 0;
    $q_kewajiban = "SELECT p.nama_produk AS nama, IFNULL(SUM(s.jumlah), 0) AS jumlah
                   FROM produk p
                   LEFT JOIN simpanan s ON p.id = s.id_prodak
                   WHERE p.kategori = 'SIMPANAN' AND p.akad != 'Wajib'
                   AND s.tanggal BETWEEN '$dari' AND '$sampai'
                   GROUP BY p.nama_produk";
    $res_kewajiban = $conn->query($q_kewajiban);
    while ($row = $res_kewajiban->fetch_assoc()) {
        echo "<tr><td>{$row['nama']}</td><td>Rp ".number_format($row['jumlah'],0,',','.')."</td></tr>";
        $total_kewajiban += $row['jumlah'];
    }
    echo "<tr><td style='{$total_style}'>Total Kewajiban</td><td style='{$total_style}'>Rp ".number_format($total_kewajiban,0,',','.')."</td></tr>";
    
    // EKUITAS
    echo "<tr><td colspan='2'>&nbsp;</td></tr>";
    echo "<tr><td colspan='2' style='{$header_style}'>EKUITAS (SIMPANAN WAJIB)</td></tr>";
    echo "<tr><th style='{$header_style}'>Nama Produk</th><th style='{$header_style}'>Jumlah</th></tr>";
    
    $total_ekuitas = 0;
    $q_ekuitas = "SELECT p.nama_produk AS nama, IFNULL(SUM(s.jumlah), 0) AS jumlah
                 FROM produk p
                 LEFT JOIN simpanan s ON p.id = s.id_prodak
                 WHERE p.kategori = 'SIMPANAN' AND p.akad = 'Wajib'
                 AND s.tanggal BETWEEN '$dari' AND '$sampai'
                 GROUP BY p.nama_produk";
    $res_ekuitas = $conn->query($q_ekuitas);
    while ($row = $res_ekuitas->fetch_assoc()) {
        echo "<tr><td>{$row['nama']}</td><td>Rp ".number_format($row['jumlah'],0,',','.')."</td></tr>";
        $total_ekuitas += $row['jumlah'];
    }
    echo "<tr><td style='{$total_style}'>Total Ekuitas</td><td style='{$total_style}'>Rp ".number_format($total_ekuitas,0,',','.')."</td></tr>";
    
    // RINGKASAN NERACA
    echo "<tr><td colspan='2'>&nbsp;</td></tr>";
    echo "<tr><td colspan='2' style='{$header_style}'>RINGKASAN NERACA</td></tr>";
    
    $selisih = $total_aktiva - ($total_kewajiban + $total_ekuitas);
    $status = $selisih == 0 ? 'Seimbang' : ($selisih > 0 ? 'Lebih' : 'Kurang');
    
    echo "<tr><td>Total Aktiva</td><td>Rp ".number_format($total_aktiva,0,',','.')."</td></tr>";
    echo "<tr><td>Total Pasiva (Kewajiban + Ekuitas)</td><td>Rp ".number_format(($total_kewajiban + $total_ekuitas),0,',','.')."</td></tr>";
    echo "<tr><td>Selisih Neraca</td><td>Rp ".number_format(abs($selisih),0,',','.')."</td></tr>";
    echo "<tr><td>Status Neraca</td><td>{$status}</td></tr>";
    
    echo "</table>";
    exit;
}
// Ambil data untuk tampilan web
$aktiva = $kewajiban = $ekuitas = [];

// Query Aktiva
$res_aktiva = $conn->query("SELECT p.nama_produk AS nama, IFNULL(SUM(pi.jumlah), 0) AS jumlah
                          FROM produk p
                          LEFT JOIN pinjaman pi ON p.id = pi.id_produk
                          WHERE p.kategori = 'PEMBIAYAAN'
                          AND pi.tanggal_pengajuan BETWEEN '$dari' AND '$sampai'
                          GROUP BY p.nama_produk");
while ($row = $res_aktiva->fetch_assoc()) $aktiva[] = $row;

// Query Kewajiban
$res_kewajiban = $conn->query("SELECT p.nama_produk AS nama, IFNULL(SUM(s.jumlah), 0) AS jumlah
                             FROM produk p
                             LEFT JOIN simpanan s ON p.id = s.id_prodak
                             WHERE p.kategori = 'SIMPANAN' AND p.akad != 'Wajib'
                             AND s.tanggal BETWEEN '$dari' AND '$sampai'
                             GROUP BY p.nama_produk");
while ($row = $res_kewajiban->fetch_assoc()) $kewajiban[] = $row;

// Query Ekuitas
$res_ekuitas = $conn->query("SELECT p.nama_produk AS nama, IFNULL(SUM(s.jumlah), 0) AS jumlah
                           FROM produk p
                           LEFT JOIN simpanan s ON p.id = s.id_prodak
                           WHERE p.kategori = 'SIMPANAN' AND p.akad = 'Wajib'
                           AND s.tanggal BETWEEN '$dari' AND '$sampai'
                           GROUP BY p.nama_produk");
while ($row = $res_ekuitas->fetch_assoc()) $ekuitas[] = $row;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Koperasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            #print-section { width: 100%; margin: 0; padding: 0; }
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6 no-print">
            <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan Koperasi</h1>
            <div class="flex space-x-2">
                <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak
                </button>
                <a href="?export=1&dari=<?= $dari ?>&sampai=<?= $sampai ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Excel
                </a>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white p-4 rounded-lg shadow mb-6 no-print">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="dari" value="<?= $dari ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="sampai" value="<?= $sampai ?>" class="w-full p-2 border rounded">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Content for Print -->
        <div id="print-section">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-blue-800">Total Aktiva</h3>
                        <div class="bg-blue-100 p-2 rounded-full">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-semibold text-blue-900">
                        <?= formatRupiah(array_sum(array_column($aktiva, 'jumlah'))) ?>
                    </p>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-green-800">Total Kewajiban</h3>
                        <div class="bg-green-100 p-2 rounded-full">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-semibold text-green-900">
                        <?= formatRupiah(array_sum(array_column($kewajiban, 'jumlah'))) ?>
                    </p>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-purple-800">Total Ekuitas</h3>
                        <div class="bg-purple-100 p-2 rounded-full">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-semibold text-purple-900">
                        <?= formatRupiah(array_sum(array_column($ekuitas, 'jumlah'))) ?>
                    </p>
                </div>
            </div>

            <!-- Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <?php $total_aktiva = tampilkanTabel("Aktiva (Pembiayaan)", $aktiva, 'blue'); ?>
                </div>
                <div>
                    <?php $total_kewajiban = tampilkanTabel("Kewajiban (Simpanan Non-Wajib)", $kewajiban, 'green'); ?>
                </div>
                <div>
                    <?php $total_ekuitas = tampilkanTabel("Ekuitas (Simpanan Wajib)", $ekuitas, 'purple'); ?>
                </div>
            </div>

            <!-- Balance Summary -->
            <div class="mt-6 bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Neraca</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-500">Total Aktiva</h3>
                        <p class="text-xl font-semibold"><?= formatRupiah($total_aktiva) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-500">Total Pasiva</h3>
                        <p class="text-xl font-semibold"><?= formatRupiah($total_kewajiban + $total_ekuitas) ?></p>
                    </div>
                </div>
                
                <?php
                $selisih = $total_aktiva - ($total_kewajiban + $total_ekuitas);
                $status = $selisih == 0 ? 'Seimbang' : ($selisih > 0 ? 'Lebih' : 'Kurang');
                $color = $selisih == 0 ? 'green' : ($selisih > 0 ? 'blue' : 'red');
                ?>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-base font-medium text-gray-700">Status Neraca</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-<?= $color ?>-100 text-<?= $color ?>-800">
                            <?= $status ?>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold text-<?= $color ?>-600">
                        <?= formatRupiah(abs($selisih)) ?>
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        <?php
                        if ($selisih == 0) {
                            echo "Neraca dalam kondisi seimbang antara aktiva dan pasiva.";
                        } elseif ($selisih > 0) {
                            echo "Aktiva lebih besar dari pasiva.";
                        } else {
                            echo "Aktiva lebih kecil dari pasiva.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-center text-sm text-gray-500 no-print">
            &copy; <?= date('Y') ?> Koperasi. Laporan dihasilkan pada <?= date('d F Y H:i') ?>
        </div>
    </div>
</body>
</html>