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
        $simpan = mysqli_query($conn, $query);

        if ($simpan) {
            $pesan = "Berhasil disimpan.";

            // Ambil ID simpanan yang baru saja dibuat
            $id_simpanan = mysqli_insert_id($conn);

            // Simpan juga ke pesan.json (versi 1)
            $pesan_json_file = '../pesan/pesan.json';
            $pesan_data = file_exists($pesan_json_file)
                ? json_decode(file_get_contents($pesan_json_file), true)
                : [];

            if (!is_array($pesan_data)) $pesan_data = [];

            $pesan_data[] = [
                "versi" => 1,
                "id" => $id_simpanan,
                "id_anggota" => $id_anggota,
                "tanggal" => $tanggal,
                "jumlah" => (int)$jumlah,
                "id_prodak" => $id_prodak,
                "status" => "belum"
            ];

            file_put_contents($pesan_json_file, json_encode($pesan_data, JSON_PRETTY_PRINT));
            header("Location: list.php?pesan=sukses");
        } else {
            $pesan = "Gagal menyimpan.";
        }
    } else {
        $pesan = "Data tidak valid.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Simpanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="rupiah.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg mx-auto bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 text-center">Transaksi Simpanan</h1>
        <?php if (isset($pesan)) echo "<div class='mb-4 p-3 rounded bg-blue-50 text-blue-800 font-semibold'>$pesan</div>"; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ID Anggota</label>
                <input type="number" name="id_anggota" id="id_anggota" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div id="nama_anggota" class="mt-1 font-semibold text-blue-600"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Produk Simpanan</label>
                <select name="id_prodak" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Produk</option>
                    <?php while ($p = mysqli_fetch_assoc($produk_result)) { ?>
                        <option value="<?= $p['id']; ?>"><?= $p['nama_produk']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                <input type="text" name="jumlah" id="jumlah" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold transition">Simpan</button>
        </form>
    </div>

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
</body>
</html>
