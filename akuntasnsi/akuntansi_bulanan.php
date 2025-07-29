<?php
// akuntansi_bulanan.php
include '../config.php'; 

// Ambil semua anggota yang punya simpanan (selain wadiah dan berjangka)
$sql = "SELECT a.id_anggota, p.jenis, SUM(s.jumlah) AS total_simpanan
        FROM simpanan s
        JOIN anggota a ON s.id_anggota = a.id_anggota
        JOIN produk p ON s.id_prodak = p.id
        WHERE p.kategori = 'SIMPANAN' 
              AND p.akad != 'Wadiah Yad Dhamanah' 
              AND p.berjangka = 0
        GROUP BY a.id_anggota, p.jenis";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $id_anggota = $row['id_anggota'];
    $jenis = $row['jenis'];

    // 1. Biaya Admin Rp.1.000
    $biaya_admin = 1000;
    $conn->query("INSERT INTO simpanan (id_anggota, tanggal, jumlah, jenis) VALUES ($id_anggota, NOW(), -$biaya_admin, '$jenis')");

    // 2. Simulasi jasa bagi hasil, misal dapat Rp 250.000
    $jasa_bagi_hasil = 250000;
    $conn->query("INSERT INTO simpanan (id_anggota, tanggal, jumlah, jenis) VALUES ($id_anggota, NOW(), $jasa_bagi_hasil, '$jenis')");

    // 3. Infaq 2% ke Baitul Mal
    $infaq = $jasa_bagi_hasil * 0.02;
    $conn->query("INSERT INTO simpanan (id_anggota, tanggal, jumlah, jenis) VALUES ($id_anggota, NOW(), -$infaq, 'infaq_baitulmal')");

    // 4. Pajak 10% jika jasa >= 240rb
    if ($jasa_bagi_hasil >= 240000) {
        $pajak = $jasa_bagi_hasil * 0.1;
        $conn->query("INSERT INTO simpanan (id_anggota, tanggal, jumlah, jenis) VALUES ($id_anggota, NOW(), -$pajak, 'pajak_bagi_hasil')");
    }
}

// 5. Tutup rekening saldo 20rb tidak aktif selama 6 bulan
$sql_tutup = "SELECT sa.id_anggota, sa.saldo FROM saldo_anggota sa
              JOIN simpanan s ON sa.id_anggota = s.id_anggota
              WHERE sa.saldo = 20000
              GROUP BY sa.id_anggota
              HAVING MAX(s.tanggal) < DATE_SUB(NOW(), INTERVAL 6 MONTH)";

$res_tutup = $conn->query($sql_tutup);
while ($r = $res_tutup->fetch_assoc()) {
    $id = $r['id_anggota'];
    $conn->query("INSERT INTO simpanan (id_anggota, tanggal, jumlah, jenis) VALUES ($id, NOW(), -20000, 'tutup_rekening')");
}

echo "Proses akuntansi bulanan selesai.";
?>
