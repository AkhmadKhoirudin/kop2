<?php
// Redirect ke halaman cetak laporan bulanan dengan parameter yang diberikan
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

// Redirect ke halaman cetak
header("Location: bulanan_laporan_cetak.php?bulan=$bulan&tahun=$tahun");
exit();
?>