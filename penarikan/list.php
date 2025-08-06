<?php
// Koneksi ke DB
$conn = new mysqli("localhost", "root", "", "koperasi");

// Hapus penarikan
if (isset($_GET['hapus'])) {
    $id_tarik = intval($_GET['hapus']);

    // Ambil data tarik yang akan dihapus
    $result = $conn->query("SELECT * FROM tarik WHERE id_tarik = $id_tarik");
    if ($row = $result->fetch_assoc()) {
        $id_anggota = $row['id_anggota'];
        $jumlah     = $row['jumlah'];

        // Tambahkan jumlah ke saldo_anggota
        $conn->query("UPDATE saldo_anggota SET saldo = saldo + $jumlah WHERE id_anggota = $id_anggota");

        // Hapus data tarik
        $conn->query("DELETE FROM tarik WHERE id_tarik = $id_tarik");
        echo "<script>alert('Penarikan berhasil dihapus dan saldo dikembalikan.');</script>";
    }
}

// Ambil data tarik
$data = $conn->query("SELECT * FROM tarik");
?>

<h2>Daftar Penarikan</h2>
<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>ID Anggota</th>
        <th>ID Produk</th>
        <th>Tanggal</th>
        <th>Jumlah</th>
        <th>Aksi</th>
    </tr>
    <?php while($row = $data->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id_tarik'] ?></td>
        <td><?= $row['id_anggota'] ?></td>
        <td><?= $row['id_produk'] ?></td>
        <td><?= $row['tanggal'] ?></td>
        <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
        <td>
            <a href="?hapus=<?= $row['id_tarik'] ?>" onclick="return confirm('Hapus penarikan ini?')">Hapus</a>
            | <a href="../laporan/slip.php?jenis=tarik&id=<?= $row['id_tarik'] ?>" target="_blank">Print</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
