<?php
include '../config.php';

// Ambil semua anggota
$queryAnggota = mysqli_query($conn, "SELECT id_anggota FROM anggota");
$anggota = [];
while ($row = mysqli_fetch_assoc($queryAnggota)) {
    $anggota[] = $row['id_anggota'];
}

// Ambil semua produk kategori SIMPANAN
$queryProduk = mysqli_query($conn, "SELECT id FROM produk WHERE kategori = 'SIMPANAN'");
$produk = [];
while ($row = mysqli_fetch_assoc($queryProduk)) {
    $produk[] = $row['id'];
}

if (empty($anggota) || empty($produk)) {
    die("Tidak ada anggota atau produk simpanan.");
}

$today = date('Y-m-d');

// Transaksi 13 bulan ke belakang (termasuk bulan ini)
for ($i = 0; $i < 13; $i++) {
    $tanggal = date('Y-m-d', strtotime("-$i months", strtotime($today)));

    foreach ($anggota as $id_anggota) {
        foreach ($produk as $id_produk) {
            $jumlah = rand(50000, 200000); // Jumlah acak, bisa diganti tetap

            $insert = mysqli_query($conn, "INSERT INTO simpanan (id_anggota, id_produk, jumlah, tanggal, keterangan)
                VALUES ('$id_anggota', '$id_produk', '$jumlah', '$tanggal', 'Simpanan bulan ke-$i')");

            if (!$insert) {
                echo "Gagal menyimpan data untuk anggota $id_anggota produk $id_produk tanggal $tanggal<br>";
            }
        }
    }
}

echo "Berhasil input data simpanan selama 13 bulan ke belakang.";
?>
