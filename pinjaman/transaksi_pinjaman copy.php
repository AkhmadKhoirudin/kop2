<?php
include '../config.php';

// Handle AJAX request to get nama anggota
if (isset($_GET['get_nama']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "SELECT nama FROM anggota WHERE id_anggota = $id");
    $data = mysqli_fetch_assoc($query);
    echo $data ? $data['nama'] : '';
    exit;
}

// Ambil produk pinjaman
$produk_result = mysqli_query($conn, "SELECT id, nama_produk FROM produk WHERE kategori = 'PEMBIAYAAN'");

// Simpan data pinjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anggota = $_POST['id_anggota'];
    $id_produk = $_POST['id_produk'];
    $jumlah     = preg_replace('/\D/', '', $_POST['jumlah']); // Hapus titik/karakter
    $tenor      = intval($_POST['tenor']);
    $tanggal    = date('Y-m-d');

    if ($jumlah > 0 && $tenor > 0) {
        $query = "INSERT INTO pinjaman (id_anggota, id_produk, tanggal_pengajuan, jumlah, tenor, status) 
                  VALUES ('$id_anggota', '$id_produk', '$tanggal', '$jumlah', '$tenor', 'pengajuan')";
        $pesan = mysqli_query($conn, $query) ? "Berhasil disimpan." : "Gagal menyimpan.";
    } else {
        $pesan = "Jumlah dan tenor harus lebih dari 0.";
    }
}
?>


    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const idInput = document.getElementById("id_anggota");
        const namaSpan = document.getElementById("nama_anggota");
        const jumlahInput = document.getElementById("jumlah");

        idInput.addEventListener("change", function () {
            const id = idInput.value;
            if (id !== "") {
                fetch("?get_nama=1&id=" + id)
                    .then(response => response.text())
                    .then(data => {
                        namaSpan.textContent = data || "- Tidak ditemukan";
                    })
                    .catch(() => {
                        namaSpan.textContent = "- Gagal mengambil data";
                    });
            } else {
                namaSpan.textContent = "-";
            }
        });

        jumlahInput.addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, "");
            if (value !== "") {
                e.target.value = new Intl.NumberFormat("id-ID").format(value);
            } else {
                e.target.value = "";
            }
        });
    });
    </script>
</head>
<body>

<?php if (isset($pesan)) echo "<p>$pesan</p>"; ?>

<form method="POST">
    <label>ID Anggota:</label><br>
    <input type="number" name="id_anggota" id="id_anggota" required><br><br>

    <label>Nama Anggota:</label><br>
    <span id="nama_anggota">-</span><br><br>

    <label>Produk Pinjaman:</label><br>
    <select name="id_produk" required>
        <option value="">Pilih Produk</option>
        <?php while ($p = mysqli_fetch_assoc($produk_result)) { ?>
            <option value="<?= $p['id']; ?>"><?= $p['nama_produk']; ?></option>
        <?php } ?>
    </select><br><br>

    <label>Jumlah Pinjaman:</label><br>
    <input type="text" name="jumlah" id="jumlah" required><br><br>

    <label>Tenor (bulan):</label><br>
    <input type="number" name="tenor" min="1" required><br><br>

    <input type="submit" value="Simpan">
</form>

