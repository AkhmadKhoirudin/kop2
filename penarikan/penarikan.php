<?php
include '../config.php';

$pesan = '';
$saldo_list = [];

// Proses penarikan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anggota = $_POST['id_anggota'];
    $id_produk  = $_POST['id_produk'];
    $jumlah_raw = $_POST['jumlah'];
    $jumlah     = preg_replace('/[^\d]/', '', $jumlah_raw);
    $tanggal    = date('Y-m-d');

    // Ambil saldo terbaru dari saldo_anggota (hanya jika id_produk tersedia)
    $saldo_q = mysqli_query($conn, "SELECT saldo FROM saldo_anggota WHERE id_anggota = '$id_anggota'");
    $saldo_row = mysqli_fetch_assoc($saldo_q);
    $saldo_total = (int) $saldo_row['saldo'] ?? 0;

    if ($jumlah <= 0) {
        $pesan = "Jumlah penarikan harus lebih dari 0.";
    } elseif ($jumlah > $saldo_total) {
        $pesan = "Saldo tidak mencukupi. Sisa saldo: Rp " . number_format($saldo_total, 0, ',', '.');
    } else {
        // Simpan penarikan
        $query = "INSERT INTO tarik (id_anggota, id_produk, jumlah, tanggal) 
                  VALUES ('$id_anggota', '$id_produk', '$jumlah', '$tanggal')";
        if (mysqli_query($conn, $query)) {
            $pesan = "Penarikan berhasil sebesar Rp " . number_format($jumlah, 0, ',', '.');
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Simpan juga ke pesan.json/////////////////////////////////////////////////////////////////////////////////////////
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $pesan_json_file = '../pesan/pesan.json';

            $pesan_data = file_exists($pesan_json_file)
                ? json_decode(file_get_contents($pesan_json_file), true)
                : [];

            if (!is_array($pesan_data)) $pesan_data = [];

            $pesan_data[] = [
                "versi" => 3,
                "id_penarikan" => mysqli_insert_id($conn),
                "id_anggota"=>$id_anggota,
                "id_produk" => $id_produk,
                "tanggal" => $tanggal,
                "jumlah" => (int)$jumlah,
                "status" => "belum"
            ];

            // Tulis kembali ke file pesan.json
            file_put_contents($pesan_json_file, json_encode($pesan_data, JSON_PRETTY_PRINT));


        } else {
            $pesan = "Gagal menyimpan data penarikan: " . mysqli_error($conn);
        }
    }
}

// Ambil jenis simpanan (produk kategori SIMPANAN yang `ditarik = 1`) dan saldo > 0
if (isset($_GET['get_jenis']) && isset($_GET['id_anggota'])) {
    $id = (int)$_GET['id_anggota'];

    $sql = "
        SELECT pr.id, pr.nama_produk, pr.jenis, 
               COALESCE(SUM(s.jumlah), 0) - COALESCE((
                    SELECT SUM(t.jumlah) 
                    FROM tarik t 
                    WHERE t.id_anggota = $id AND t.id_produk = pr.id
               ), 0) AS sisa_saldo
        FROM produk pr
        LEFT JOIN simpanan s ON pr.id = s.id_prodak AND s.id_anggota = $id
        WHERE pr.kategori = 'SIMPANAN' AND pr.ditarik = 1
        GROUP BY pr.id
        HAVING sisa_saldo > 0
    ";

    $res = mysqli_query($conn, $sql);
    $result = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $result[] = [
            'id' => $row['id'],
            'nama_produk' => $row['nama_produk'],
            'jenis' => $row['jenis'],
            'saldo' => number_format(max(0, $row['sisa_saldo']), 0, ',', '.')
        ];
    }

    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Penarikan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const jumlahInput = document.getElementById("jumlah");
            const idAnggota = document.getElementById("id_anggota");
            const jenisSelect = document.getElementById("id_produk");

            // Format input jumlah
            jumlahInput.addEventListener("input", function (e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value !== "") {
                    e.target.value = new Intl.NumberFormat("id-ID").format(value);
                } else {
                    e.target.value = "";
                }
            });

            // Ambil jenis simpanan beserta saldo
            idAnggota.addEventListener('change', function () {
                const id = this.value;
                if (!id) return;
                fetch(`penarikan.php?get_jenis=1&id_anggota=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        jenisSelect.innerHTML = '<option value="">Pilih Jenis Simpanan</option>';
                        data.forEach(item => {
                            jenisSelect.innerHTML += `<option value="${item.id}">${item.nama_produk} (Saldo: Rp ${item.saldo})</option>`;
                        });
                    });
            });
        });
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h3 class="text-2xl font-bold mb-6 text-center">Form Penarikan Simpanan</h3>

    <?php if ($pesan) echo "<p class='text-center text-red-500 mb-4'><strong>$pesan</strong></p>"; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label for="id_anggota" class="block text-sm font-medium text-gray-700">ID Anggota:</label>
            <input type="number" name="id_anggota" id="id_anggota" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="id_produk" class="block text-sm font-medium text-gray-700">Jenis Simpanan:</label>
            <select name="id_produk" id="id_produk" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Pilih Jenis Simpanan</option>
            </select>
        </div>

        <div>
            <label for="jumlah" class="block text-sm font-medium text-gray-700">Jumlah Penarikan:</label>
            <input type="text" name="jumlah" id="jumlah" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <button type="submit"
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Tarik Dana
            </button>
        </div>
    </form>
</div>

</body>
</html>
