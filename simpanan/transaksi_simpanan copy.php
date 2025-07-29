<?php
include '../config.php';

// Ambil data anggota
$anggota_result = mysqli_query($conn, "SELECT id_anggota, nama FROM anggota");

// Ambil produk kategori SIMPANAN
$produk_result = mysqli_query($conn, "SELECT id, nama_produk FROM produk WHERE kategori = 'SIMPANAN'");

// Simpan simpanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anggota = mysqli_real_escape_string($conn, $_POST['id_anggota']);
    $id_prodak  = mysqli_real_escape_string($conn, $_POST['id_prodak']);
    $jumlah     = str_replace('.', '', $_POST['jumlah']);
    $tanggal    = date('Y-m-d');

    if ($jumlah > 0 && $id_prodak) {
        $query = "INSERT INTO simpanan (id_anggota, tanggal, jumlah, id_prodak) 
                  VALUES ('$id_anggota', '$tanggal', '$jumlah', '$id_prodak')";
        $pesan = mysqli_query($conn, $query) ? "Berhasil disimpan." : "Gagal menyimpan.";
    } else {
        $pesan = "Data tidak valid.";
    }
}
?>

<script src="rupiah.js" defer></script>

<form method="POST">
    <label>ID Anggota:</label><br>
    <input type="number" name="id_anggota" id="id_anggota" required>
    <div id="nama_anggota" style="font-weight:bold;"></div><br>

    <label>Produk Simpanan:</label><br>
    <select name="id_prodak" required>
        <option value="">Pilih Produk</option>
        <?php while ($p = mysqli_fetch_assoc($produk_result)) { ?>
            <option value="<?= $p['id']; ?>"><?= $p['nama_produk']; ?></option>
        <?php } ?>
    </select><br><br>

    <label>Jumlah:</label><br>
    <input type="text" name="jumlah" id="jumlah" required><br><br>

    <input type="submit" value="Simpan">
</form>

<?php if (isset($pesan)) echo "<p>$pesan</p>"; ?>

<script>
// Data anggota dari PHP ke JS
    const anggotaData = <?php
        $anggota_data = [];
        mysqli_data_seek($anggota_result, 0);
        while ($a = mysqli_fetch_assoc($anggota_result)) {
            $anggota_data[$a['id_anggota']] = $a['nama'];
        }
        echo json_encode($anggota_data);
    ?>;

    document.getElementById('id_anggota').addEventListener('input', function () {
        const id = this.value;
        document.getElementById('nama_anggota').innerText = anggotaData[id] ? 'Nama: ' + anggotaData[id] : '';
    });
</script>
