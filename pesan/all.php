<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ./login/login.php");
    exit();
}

// Ambil ID anggota dan nama dari session
$idAnggota = $_SESSION['id_anggota'];
$namaAnggota = $_SESSION['nama'];

// Path ke file JSON
$jsonPath = __DIR__ . '/../pesan/pesan.json';

// Cek apakah file ada
if (!file_exists($jsonPath)) {
    echo "File tidak ditemukan.";
    exit();
}

// Ambil isi file JSON
$jsonContent = file_get_contents($jsonPath);
$data = json_decode($jsonContent, true);

if (!$data || !is_array($data)) {
    echo "Format JSON tidak valid.";
    exit();
}

// Filter notifikasi yang belum dibaca dan sesuai id_anggota
$notifikasiBelum = array_filter($data, function ($item) use ($idAnggota) {
    return isset($item['status']) && $item['status'] === 'belum' && isset($item['id_anggota']) && $item['id_anggota'] == $idAnggota;
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Notifikasi Belum Dibaca</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
      <h1 class="text-2xl font-semibold text-gray-800 mb-2">Halo, <?= htmlspecialchars($namaAnggota) ?> 👋</h1>
      <p class="text-gray-600">Berikut adalah notifikasi yang belum Anda baca:</p>
    </div>

    <div class="space-y-4">
      <?php if (empty($notifikasiBelum)): ?>
        <div class="bg-white p-4 rounded-lg shadow text-center text-gray-500">
          Tidak ada notifikasi baru 📭
        </div>
      <?php else: ?>
        <?php foreach ($notifikasiBelum as $item): ?>
          <div class="bg-white p-5 rounded-lg shadow border-l-4 border-yellow-500 transition hover:shadow-md">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">📅 <?= htmlspecialchars($item['tanggal']) ?></span>
              <span class="text-sm text-red-500 font-semibold uppercase"><?= htmlspecialchars($item['status']) ?></span>
            </div>
            <div class="text-gray-700 space-y-1">
              <p><strong>Jumlah:</strong> Rp<?= number_format($item['jumlah'], 0, ',', '.') ?></p>
              <p><strong>Produk:</strong> 
                <?= isset($item['id_prodak']) ? "Produk ID " . htmlspecialchars($item['id_prodak']) : 
                     (isset($item['id_produk']) ? "Produk ID " . htmlspecialchars($item['id_produk']) : '-') ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
