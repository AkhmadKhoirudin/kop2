<?php
$koneksi = new mysqli("localhost", "root", "", "koperasi");

// === Ambil data produk dan saldo per produk (simpanan - penarikan per produk) ===
$data_produk = [];
$q_produk = $koneksi->query("
    SELECT 
        p.id, 
        p.nama_produk,
        IFNULL(SUM(s.jumlah), 0) AS total_simpanan,
        IFNULL((
            SELECT SUM(t.jumlah) 
            FROM tarik t 
            WHERE t.id_produk = p.id
        ), 0) AS total_tarik
    FROM produk p
    LEFT JOIN simpanan s ON s.id_prodak = p.id
    WHERE p.kategori = 'SIMPANAN'
    GROUP BY p.id
");

$aktiva_total = 0;
while ($row = $q_produk->fetch_assoc()) {
    $saldo = $row['total_simpanan'] - $row['total_tarik'];
    $aktiva_total += $saldo;
    $data_produk[] = [
        'nama_produk' => $row['nama_produk'],
        'saldo' => $saldo
    ];
}

// === Kewajiban: simpanan selain pokok & wajib ===
$q_kewajiban = $koneksi->query("
    SELECT IFNULL(SUM(s.jumlah), 0) AS total 
    FROM simpanan s 
    JOIN produk p ON s.id_prodak = p.id 
    WHERE p.jenis NOT IN ('pokok', 'wajib')
");
$kewajiban = $q_kewajiban->fetch_assoc()['total'];

// === Ekuitas: simpanan pokok dan wajib ===
$q_ekuitas = $koneksi->query("
    SELECT IFNULL(SUM(s.jumlah), 0) AS total 
    FROM simpanan s 
    JOIN produk p ON s.id_prodak = p.id 
    WHERE p.jenis IN ('pokok', 'wajib')
");
$ekuitas = $q_ekuitas->fetch_assoc()['total'];

// === SHU Manual (atau bisa dari VIEW jika tersedia) ===
$shu_tahun_lalu = 10000000;
$shu_tahun_berjalan = 8000000;
$ekuitas_total = $ekuitas + $shu_tahun_lalu + $shu_tahun_berjalan;

function rupiah($n) {
    return "Rp " . number_format($n, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Neraca Koperasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h2 class="mb-4">NERACA</h2>

  <div class="row">
    <div class="col-md-6">
      <h4>AKTIVA</h4>
      <table class="table table-bordered">
        <thead class="table-light">
          <tr><th>Jenis Simpanan</th><th class="text-end">Jumlah</th></tr>
        </thead>
        <tbody>
          <?php foreach ($data_produk as $produk): ?>
          <tr>
            <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
            <td class="text-end"><?= rupiah($produk['saldo']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary">
          <tr>
            <th>Total Aktiva</th>
            <th class="text-end"><?= rupiah($aktiva_total) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="col-md-6">
      <h4>PASIVA</h4>
      <table class="table table-bordered">
        <tbody>
          <tr class="table-light"><th colspan="2">05 - Kewajiban / Liabilities</th></tr>
          <tr>
            <td>Kewajiban kepada anggota (simpanan umum)</td>
            <td class="text-end"><?= rupiah($kewajiban) ?></td>
          </tr>
          <tr class="table-secondary">
            <th>Total Kewajiban</th>
            <th class="text-end"><?= rupiah($kewajiban) ?></th>
          </tr>

          <tr class="table-light"><th colspan="2">06 - Ekuitas / Capital</th></tr>
          <tr><td>Simpanan Pokok & Wajib</td><td class="text-end"><?= rupiah($ekuitas) ?></td></tr>
          <tr><td>SHU Tahun Lalu</td><td class="text-end"><?= rupiah($shu_tahun_lalu) ?></td></tr>
          <tr><td>SHU Tahun Berjalan</td><td class="text-end"><?= rupiah($shu_tahun_berjalan) ?></td></tr>
          <tr class="table-secondary">
            <th>Total Ekuitas</th>
            <th class="text-end"><?= rupiah($ekuitas_total) ?></th>
          </tr>
        </tbody>
        <tfoot class="table-success">
          <tr>
            <th>Total Pasiva</th>
            <th class="text-end"><?= rupiah($kewajiban + $ekuitas_total) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</body>
</html>
