<?php
session_start();
include '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role'])) {
    header("Location: ../login/login.php");
    exit();
}

// Dapatkan data session
$id_anggota_session = $_SESSION['id_anggota'];
$role_user = $_SESSION['role'];
$nama_user = $_SESSION['nama'];

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
    // Jika role adalah user, gunakan id_anggota dari session
    // Jika role bukan user, gunakan id_anggota dari input form
    if ($role_user === 'user') {
        $id_anggota = $id_anggota_session;
        // Validasi bahwa produk pinjaman tersedia
        $id_produk = $_POST['id_produk'];
        $cek_produk = mysqli_query($conn, "SELECT id FROM produk WHERE id = $id_produk AND kategori = 'PEMBIAYAAN'");
        if (mysqli_num_rows($cek_produk) === 0) {
            $pesan = "Produk pinjaman tidak tersedia.";
            header("Location: list.php?pesan=" . urlencode($pesan));
            exit();
        }
    } else {
        $id_anggota = $_POST['id_anggota'];
        $id_produk = $_POST['id_produk'];
    }
    
    $jumlah     = preg_replace('/\D/', '', $_POST['jumlah']); // Hapus titik/karakter
    $tenor      = intval($_POST['tenor']);
    $tanggal    = date('Y-m-d');

    if ($jumlah > 0 && $tenor > 0) {
        $query = "INSERT INTO pinjaman (id_anggota, id_produk, tanggal_pengajuan, jumlah, tenor, status)
                  VALUES ('$id_anggota', '$id_produk', '$tanggal', '$jumlah', '$tenor', 'pengajuan')";
        $pesan = mysqli_query($conn, $query) ? "Berhasil disimpan." : "Gagal menyimpan.";

        // Tambahkan notifikasi menggunakan fungsi terpusat
        include_once __DIR__ . '/../pesan/fungsi_pesan.php';
        $notifikasi_baru = [
            "versi"       => 2,
            "id_pinjaman" => mysqli_insert_id($conn),
            "id_anggota"  => $id_anggota,
            "id_produk"   => $id_produk,
            "tanggal"     => $tanggal,
            "jumlah"      => (int)$jumlah,
            "tenor"       => $tenor,
            "status"      => "belum"
        ];
        
        tambahNotifikasi($notifikasi_baru);

        header("Location: list.php?pesan=sukses");
        exit(); // Pastikan script berhenti setelah redirect
        
    } else {
        $pesan = "Jumlah dan tenor harus lebih dari 0.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Transaksi Pinjaman</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const idInput = document.getElementById("id_anggota");
        const namaSpan = document.getElementById("nama_anggota");
        const jumlahInput = document.getElementById("jumlah");

        // Jika role adalah user, disable input id_anggota
        <?php if ($role_user === 'user'): ?>
            idInput.disabled = true;
        <?php else: ?>
            idInput.addEventListener("input", function () {
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
        <?php endif; ?>

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
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md">
        <h1 class="text-xl font-bold mb-6 text-center text-gray-700">Transaksi Pinjaman</h1>

        <?php if (isset($pesan)) echo "<p class='text-center mb-4 text-sm text-red-600'>$pesan</p>"; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">ID Anggota:</label>
                <?php if ($role_user === 'user'): ?>
                    <input type="number" name="id_anggota" id="id_anggota" value="<?php echo $id_anggota_session; ?>" readonly
                        class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100 shadow-sm"/>
                    <input type="hidden" name="id_anggota" value="<?php echo $id_anggota_session; ?>">
                <?php else: ?>
                    <input type="number" name="id_anggota" id="id_anggota" required
                        class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Anggota:</label>
                <span id="nama_anggota" class="mt-1 block text-gray-800 font-semibold"><?php echo $role_user === 'user' ? $nama_user : '-'; ?></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Produk Pinjaman:</label>
                <select name="id_produk" required
                    class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Produk</option>
                    <?php while ($p = mysqli_fetch_assoc($produk_result)) { ?>
                        <option value="<?= $p['id']; ?>"><?= $p['nama_produk']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Jumlah Pinjaman:</label>
                <input type="text" name="jumlah" id="jumlah" required
                    class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tenor (bulan):</label>
                <input type="number" name="tenor" min="1" required
                    class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>

            <div class="pt-4">
                <input type="submit" value="Simpan"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition"/>
            </div>
        </form>
    </div>

</body>
</html>


