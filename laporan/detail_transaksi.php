<?php
$conn = new mysqli("localhost", "root", "", "koperasi");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$id_anggota = $_GET['id'] ?? 0;
$filter_jenis = $_GET['jenis'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';
$sort_column = $_GET['sort'] ?? 'tanggal';
$sort_order = $_GET['order'] ?? 'DESC';
$next_order = ($sort_order === 'ASC') ? 'DESC' : 'ASC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$allowed_columns = ['jenis', 'tanggal', 'jumlah', 'keterangan', 'status'];
if (!in_array($sort_column, $allowed_columns)) $sort_column = 'tanggal';

// Ambil saldo
$saldo = 0;
$saldo_q = $conn->query("SELECT saldo FROM saldo_anggota WHERE id_anggota = $id_anggota");
if ($saldo_q && $saldo_q->num_rows > 0) {
    $saldo = $saldo_q->fetch_assoc()['saldo'];
}
// Ambil total pinjaman belum lunas dan nama produknya
$pinjaman_q = $conn->query("
  SELECT SUM(pj.jumlah) AS total_pinjaman, GROUP_CONCAT(DISTINCT p.nama_produk SEPARATOR ', ') AS produk
  FROM pinjaman pj
  LEFT JOIN produk p ON pj.id_produk = p.id
  WHERE pj.id_anggota = $id_anggota AND pj.status != 'Lunas'
");
$sisa_pinjaman = 0;
$nama_produk = '-';
if ($pinjaman_q && $pinjaman_q->num_rows > 0) {
    $pj = $pinjaman_q->fetch_assoc();
    $sisa_pinjaman = $pj['total_pinjaman'] ?? 0;
    $nama_produk = $pj['produk'] ?? '-';
}

// Gabungan query semua transaksi
$base_query = "
(
    SELECT 'Simpanan' AS jenis, s.tanggal, s.jumlah, p.nama_produk AS keterangan, NULL AS status
    FROM simpanan s
    LEFT JOIN produk p ON s.id_prodak = p.id
    WHERE s.id_anggota = $id_anggota
)
UNION ALL
(
    SELECT 'Penarikan' AS jenis, t.tanggal, t.jumlah, p.nama_produk AS keterangan, NULL AS status
    FROM tarik t
    LEFT JOIN produk p ON t.id_produk = p.id
    WHERE t.id_anggota = $id_anggota
)
UNION ALL
(
    SELECT 'Pinjaman' AS jenis, pj.tanggal_pengajuan AS tanggal, pj.jumlah, p.nama_produk AS keterangan, pj.status
    FROM pinjaman pj
    LEFT JOIN produk p ON pj.id_produk = p.id
    WHERE pj.id_anggota = $id_anggota
)
UNION ALL
(
    SELECT 'Angsuran' AS jenis, a.tanggal, a.jumlah, CONCAT('Angsuran untuk ID ', a.id_pinjaman) AS keterangan, a.status
    FROM angsuran a
    JOIN pinjaman pj ON a.id_pinjaman = pj.id_pinjaman
    WHERE pj.id_anggota = $id_anggota
)
";

$where_clause = [];
if ($filter_jenis !== '') {
    $where_clause[] = "jenis = '" . $conn->real_escape_string($filter_jenis) . "'";
}
if ($tanggal_filter !== '') {
    $where_clause[] = "DATE(tanggal) = '" . $conn->real_escape_string($tanggal_filter) . "'";
}
$where_sql = count($where_clause) ? 'WHERE ' . implode(' AND ', $where_clause) : '';

$query = "
    SELECT * FROM ($base_query) AS transaksi
    $where_sql
    ORDER BY $sort_column $sort_order
    LIMIT $limit OFFSET $offset
";


// Hitung total data untuk pagination
$count_result = $conn->query("SELECT COUNT(*) AS total FROM ($base_query) AS transaksi $where_sql");
$total_rows = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);


$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Transaksi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="max-w-6xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-4">Riwayat Transaksi</h1>
  <div class="bg-blue-100 text-blue-800 p-4 rounded mb-6">
  <p><strong>Sisa Saldo:</strong> Rp <?= number_format($saldo, 0, ',', '.') ?></p>
  <p><strong>Sisa Pinjaman Belum Lunas:</strong> Rp <?= number_format($sisa_pinjaman, 0, ',', '.') ?></p>
  <p><strong>Produk Pinjaman:</strong> <?= htmlspecialchars($nama_produk) ?></p>
</div>


  <form method="GET" class="mb-4 flex flex-wrap gap-4">
    <input type="hidden" name="id" value="<?= $id_anggota ?>">
    <div>
      <label class="text-sm font-medium block mb-1">Jenis Transaksi</label>
      <select name="jenis" class="border rounded px-3 py-2">
        <option value="">-- Semua --</option>
        <?php foreach (['Simpanan', 'Penarikan', 'Pinjaman', 'Angsuran'] as $jenis): ?>
          <option value="<?= $jenis ?>" <?= ($filter_jenis == $jenis) ? 'selected' : '' ?>><?= $jenis ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">Tanggal</label>
      <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>" class="border rounded px-3 py-2">
    </div>
    <div class="flex items-end">
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Terapkan</button>
    </div>
  </form>

  <div class="overflow-x-auto bg-white rounded shadow">
    <table class="min-w-full text-sm border text-left">
      <thead class="bg-gray-800 text-white">
        <tr>
          <?php foreach (['jenis' => 'Jenis Transaksi', 'tanggal' => 'Tanggal', 'jumlah' => 'Jumlah', 'keterangan' => 'Keterangan', 'status' => 'Status'] as $col => $label): ?>
            <th class="px-4 py-2 border">
              <a href="?id=<?= $id_anggota ?>&sort=<?= $col ?>&order=<?= $next_order ?>&jenis=<?= $filter_jenis ?>&tanggal=<?= $tanggal_filter ?>&page=<?= $page ?>">
                <?= $label ?>
              </a>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-2"><?= htmlspecialchars($row['jenis']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['tanggal']) ?></td>
              <td class="px-4 py-2">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['keterangan']) ?></td>
              <td class="px-4 py-2"><?= $row['status'] ?? '-' ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center py-4">Tidak ada transaksi ditemukan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

 <div class="mt-4 flex justify-center gap-2">
  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?id=<?= $id_anggota ?>&jenis=<?= $filter_jenis ?>&tanggal=<?= $tanggal_filter ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>&page=<?= $i ?>"
       class="px-3 py-1 rounded <?= ($page == $i) ? 'bg-blue-600 text-white font-bold' : 'bg-gray-200 hover:bg-gray-300' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>
</div>

</div>
</body>
</html>
