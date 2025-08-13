<?php
$koneksi = new mysqli("localhost", "root", "", "koperasi");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// ?dari=2025-05-05&sampai=2025-05-05
// contoh pengunaan 

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$aktiva = [];
$kewajiban = [];
$ekuitas = [];

// AKTIVA (PEMBIAYAAN)
$q_aktiva = "
    SELECT p.nama_produk AS nama, IFNULL(SUM(pi.jumlah), 0) AS jumlah
    FROM produk p
    LEFT JOIN pinjaman pi ON p.id = pi.id_produk
    WHERE p.kategori = 'PEMBIAYAAN'
    AND pi.tanggal_pengajuan BETWEEN '$dari' AND '$sampai'
    GROUP BY p.nama_produk
";
$res_aktiva = $koneksi->query($q_aktiva);
while ($row = $res_aktiva->fetch_assoc()) {
    $aktiva[] = $row;
}

// KEWAJIBAN (SIMPANAN bukan Wajib)
$q_kewajiban = "
    SELECT p.nama_produk AS nama, IFNULL(SUM(s.jumlah), 0) AS jumlah
    FROM produk p
    LEFT JOIN simpanan s ON p.id = s.id_prodak
    WHERE p.kategori = 'SIMPANAN' AND p.akad != 'Wajib'
    AND s.tanggal BETWEEN '$dari' AND '$sampai'
    GROUP BY p.nama_produk
";
$res_kewajiban = $koneksi->query($q_kewajiban);
while ($row = $res_kewajiban->fetch_assoc()) {
    $kewajiban[] = $row;
}

// EKUITAS (SIMPANAN Wajib)
$q_ekuitas = "
    SELECT p.nama_produk AS nama, IFNULL(SUM(s.jumlah), 0) AS jumlah
    FROM produk p
    LEFT JOIN simpanan s ON p.id = s.id_prodak
    WHERE p.kategori = 'SIMPANAN' AND p.akad = 'Wajib'
    AND s.tanggal BETWEEN '$dari' AND '$sampai'
    GROUP BY p.nama_produk
";
$res_ekuitas = $koneksi->query($q_ekuitas);
while ($row = $res_ekuitas->fetch_assoc()) {
    $ekuitas[] = $row;
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '');
}

function tampilkanTabel($judul, $data) {
    echo "<h3 class='text-xl font-semibold mb-2'>$judul</h3>";
    echo "<div class='overflow-x-auto'>";
    echo "<table class='table-auto w-full border border-gray-300 mb-6'>";
    echo "<thead class='bg-gray-100 text-left'>";
    echo "<tr><th class='border px-4 py-2'>Nama</th><th class='border px-4 py-2 text-right'>Jumlah</th></tr>";
    echo "</thead><tbody>";
    $total = 0;
    foreach ($data as $d) {
        echo "<tr>";
        echo "<td class='border px-4 py-2'>{$d['nama']}</td>";
        echo "<td class='border px-4 py-2 text-right'>" . formatRupiah($d['jumlah']) . "</td>";
        echo "</tr>";
        $total += $d['jumlah'];
    }
    echo "<tr class='font-bold bg-gray-50'>";
    echo "<td class='border px-4 py-2'>Total</td>";
    echo "<td class='border px-4 py-2 text-right'>" . formatRupiah($total) . "</td>";
    echo "</tr>";
    echo "</tbody></table></div>";
    return $total;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca Koperasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans py-10">
<div class="max-w-5xl mx-auto bg-white shadow-md rounded-lg p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Laporan Neraca Koperasi</h1>

    <!-- Form Filter Tanggal -->
    <form method="get" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label for="dari" class="block font-medium mb-1">Dari Tanggal:</label>
            <input type="date" id="dari" name="dari" value="<?= $dari ?>" class="w-full border px-3 py-2 rounded">
        </div>
        <div>
            <label for="sampai" class="block font-medium mb-1">Sampai Tanggal:</label>
            <input type="date" id="sampai" name="sampai" value="<?= $sampai ?>" class="w-full border px-3 py-2 rounded">
        </div>
        <div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Tampilkan</button>
        </div>
    </form>

    <?php
    $totalAktiva = tampilkanTabel("AKTIVA (ASET)", $aktiva);
    $totalKewajiban = tampilkanTabel("PASIVA - KEWAJIBAN", $kewajiban);
    $totalEkuitas = tampilkanTabel("PASIVA - EKUITAS", $ekuitas);
    $totalPasiva = $totalKewajiban + $totalEkuitas;
    ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
        <div class="bg-blue-100 p-4 rounded-lg shadow text-center">
            <h4 class="text-lg font-semibold">Total AKTIVA</h4>
            <p class="text-xl font-bold text-blue-800"><?= formatRupiah($totalAktiva) ?></p>
        </div>
        <div class="bg-yellow-100 p-4 rounded-lg shadow text-center">
            <h4 class="text-lg font-semibold">Total PASIVA (Kewajiban + Ekuitas)</h4>
            <p class="text-xl font-bold text-yellow-800"><?= formatRupiah($totalPasiva) ?></p>
        </div>
    </div>
</div>
</body>
</html>
