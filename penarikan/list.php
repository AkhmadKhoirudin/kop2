<?php
// Koneksi ke DB
$conn = new mysqli("localhost", "root", "", "koperasi");

// Cek session dan ambil role serta id_anggota
session_start();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$id_anggota = isset($_SESSION['id_anggota']) ? $_SESSION['id_anggota'] : '';

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

// Ambil kata kunci pencarian
$keyword = isset($_GET['search']) ? trim($_GET['search']) : "";

// Pagination setup
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$countSql = "SELECT COUNT(*) AS total
             FROM tarik
             LEFT JOIN anggota ON tarik.id_anggota = anggota.id_anggota";
if ($keyword !== "") {
    $keywordEscaped = $conn->real_escape_string($keyword);
    $countSql .= " WHERE tarik.id_tarik LIKE '%$keywordEscaped%'
                   OR tarik.id_anggota LIKE '%$keywordEscaped%'
                   OR anggota.nama LIKE '%$keywordEscaped%'
                   OR tarik.id_produk LIKE '%$keywordEscaped%'
                   OR tarik.tanggal LIKE '%$keywordEscaped%'
                   OR tarik.jumlah LIKE '%$keywordEscaped%'";
}
// Filter berdasarkan role
if ($role === 'user' && $id_anggota !== '') {
    $countSql .= ($keyword !== "" ? " AND" : " WHERE") . " tarik.id_anggota = '$id_anggota'";
}
$totalResult = $conn->query($countSql);
$totalData = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);

// Query tarik
$sql = "SELECT tarik.*, anggota.nama AS nama_anggota
        FROM tarik
        LEFT JOIN anggota ON tarik.id_anggota = anggota.id_anggota";
if ($keyword !== "") {
    $sql .= " WHERE tarik.id_tarik LIKE '%$keywordEscaped%'
              OR tarik.id_anggota LIKE '%$keywordEscaped%'
              OR anggota.nama LIKE '%$keywordEscaped%'
              OR tarik.id_produk LIKE '%$keywordEscaped%'
              OR tarik.tanggal LIKE '%$keywordEscaped%'
              OR tarik.jumlah LIKE '%$keywordEscaped%'";
}
// Filter berdasarkan role
if ($role === 'user' && $id_anggota !== '') {
    $sql .= ($keyword !== "" ? " AND" : " WHERE") . " tarik.id_anggota = '$id_anggota'";
}
$sql .= " ORDER BY tarik.id_tarik DESC LIMIT $limit OFFSET $offset";
$data = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Daftar Penarikan</title>
</head>
<body class="bg-gray-50 font-sans">
    <div class="container mx-auto p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-2">
            <h2 class="text-xl md:text-2xl font-bold text-gray-700">Daftar Penarikan</h2>
            <div class="flex gap-2">
                <!-- Form pencarian -->
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" placeholder="Cari data..." value="<?= htmlspecialchars($keyword) ?>" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <input type="date" name="tanggal" id="tanggal" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Cari</button>
                    <button type="reset" onclick="window.location='?'" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">Reset</button>
                </form>
                <!-- Tombol tambah - hanya tampilkan untuk admin -->
                <?php if ($role === 'admin'): ?>
                <a href="penarikan.php" class="bg-green-500 text-white px-4 py-2 rounded-lg">+ Tambah</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow-lg">
                <tr class="whitespace-nowrap bg-blue-500 text-white">
                    <th class="px-3 py-2 text-left">ID</th>
                    <th class="px-3 py-2 text-left">Nama</th>
                    <th class="px-3 py-2 text-left">ID Produk</th>
                    <th class="px-3 py-2 text-left">Tanggal</th>
                    <th class="px-3 py-2 text-left">Jumlah</th>
                    <th class="px-3 py-2 text-left">Aksi</th>
                </tr>
                <?php while($row = $data->fetch_assoc()): ?>
                    <?php $status = !empty($row['status']) ? $row['status'] : 'Pending'; ?>
                    <tr class="border-b hover:bg-gray-100">
                        <td class="px-3 py-2"><?= $row['id_tarik'] ?></td>
                        <td class="px-3 py-2"><?= $row['nama_anggota'] ?></td>
                        <td class="px-3 py-2"><?= $row['id_produk'] ?></td>
                        <td class="px-3 py-2"><?= $row['tanggal'] ?></td>
                        <td class="px-3 py-2">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        <td class="whitespace-nowrap flex gap-2">
                            <?php if ($role === 'admin'): ?>
                            <a href="?hapus=<?= $row['id_tarik'] ?>" onclick="return confirm('Hapus penarikan ini?')" class="text-red-500 hover:underline">Hapus</a>
                            <?php endif; ?>
                            <a href="../laporan/slip.php?jenis=tarik&id=<?= $row['id_tarik'] ?>" target="_blank" class="text-blue-500 hover:underline">Print</a>
                            <?php if ($role === 'admin'): ?>
                            <a href="#" onclick="openUpdatePopup('<?= $row['id_tarik'] ?>', '<?= $row['jumlah'] ?>', '<?= $status ?>'); return false;" class="text-blue-500 hover:underline">Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($data->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                            Tidak ada transaksi
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <!-- Pagination -->
          <div class="flex gap-2 mt-4 justify-center flex-wrap">

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($keyword) ?>" class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">&lt;</a>
                <?php endif; ?>

                <?php
                // Selalu tampilkan halaman 1, 2, 3
                for ($i = 1; $i <= 3 && $i <= $totalPages; $i++) {
                    echo '<a href="?page='.$i.'&search='.urlencode($keyword).'" class="px-3 py-1 rounded '.($i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300').'">'.$i.'</a>';
                }

                // Titik-titik di tengah
                if ($page > 4 && $page < $totalPages - 2) {
                    echo '<span class="px-3 py-1">...</span>';
                    echo '<a href="?page='.$page.'&search='.urlencode($keyword).'" class="px-3 py-1 rounded bg-blue-500 text-white">'.$page.'</a>';
                    echo '<span class="px-3 py-1">...</span>';
                } elseif ($page >= $totalPages - 2 && $totalPages > 3) {
                    echo '<span class="px-3 py-1">...</span>';
                }

                // Halaman terakhir
                if ($totalPages > 3) {
                    echo '<a href="?page='.$totalPages.'&search='.urlencode($keyword).'" class="px-3 py-1 rounded '.($page == $totalPages ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300').'">'.$totalPages.'</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($keyword) ?>" class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">&gt;</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Popup -->
    <div id="editPopup" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center p-4">
        <div class="bg-white p-4 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold text-gray-700 mb-4">Edit Penarikan</h3>
            <form id="editForm" method="POST" action="update.php">
                <input type="hidden" name="id_tarik" id="editId">
                <div class="mb-4">
                    <label for="editJumlah" class="block text-sm font-medium text-gray-700">Jumlah</label>
                    <input type="number" name="jumlah" id="editJumlah" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="editStatus" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="editStatus" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Selesai">Selesai</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditPopup()" class="bg-gray-500 text-white px-4 py-2 rounded-lg">Batal</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

<script>
function openUpdatePopup(id, jumlah, status) {
    document.getElementById('editId').value = id;
    document.getElementById('editJumlah').value = jumlah;
    document.getElementById('editStatus').value = status;
    document.getElementById('editPopup').classList.remove('hidden');
}
function closeEditPopup() {
    document.getElementById('editPopup').classList.add('hidden');
}
window.addEventListener('click', function(event) {
    const popup = document.getElementById('editPopup');
    if (event.target === popup) {
        closeEditPopup();
    }
});
</script>
</body>
</html>
