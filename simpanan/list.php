<?php
include '../config.php';

// Handle delete action
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("DELETE FROM simpanan WHERE id_simpanan = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Redirect untuk mencegah penghapusan berulang saat refresh
    header("Location: list.php");
    exit();
}

// Konfigurasi Pagination
$perPage = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// Sorting
$allowed_sort = ['nama_anggota', 'nama_produk', 'tanggal', 'jumlah'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'tanggal';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'asc' : 'desc';

// Search filter processing
$search_nama_anggota = isset($_GET['nama_anggota']) ? $_GET['nama_anggota'] : '';
$search_nama_produk = isset($_GET['nama_produk']) ? $_GET['nama_produk'] : '';
$search_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

$searchConditions = [];
if ($search_nama_anggota !== '') {
    $searchConditions[] = "anggota.nama LIKE '%" . mysqli_real_escape_string($conn, $search_nama_anggota) . "%'";
}
if ($search_nama_produk !== '') {
    $searchConditions[] = "produk.nama_produk LIKE '%" . mysqli_real_escape_string($conn, $search_nama_produk) . "%'";
}
if ($search_tanggal !== '') {
    $searchConditions[] = "simpanan.tanggal = '" . mysqli_real_escape_string($conn, $search_tanggal) . "'";
}

$whereClause = count($searchConditions) > 0 ? 'WHERE ' . implode(' AND ', $searchConditions) : '';

// Buat URL parameter untuk mempertahankan state
$urlParams = http_build_query(array_filter([
    'sort' => $sort,
    'order' => $order,
    'nama_anggota' => $search_nama_anggota,
    'nama_produk' => $search_nama_produk,
    'tanggal' => $search_tanggal,
]));

// Total data
$totalQuery = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM simpanan
    JOIN anggota ON simpanan.id_anggota = anggota.id_anggota
    JOIN produk ON simpanan.id_prodak = produk.id
    $whereClause
");
$totalData = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($totalData / $perPage);

// Query data
$query = "
    SELECT
        simpanan.id_simpanan,
        anggota.nama AS nama_anggota,
        produk.nama_produk,
        simpanan.id_prodak,
        simpanan.tanggal,
        simpanan.jumlah
    FROM simpanan
    JOIN anggota ON simpanan.id_anggota = anggota.id_anggota
    JOIN produk ON simpanan.id_prodak = produk.id
    $whereClause
    ORDER BY $sort $order
    LIMIT $start, $perPage
";
$result = mysqli_query($conn, $query);

// Ambil data produk untuk dropdown
$produk_query = mysqli_query($conn, "SELECT id, nama_produk FROM produk WHERE kategori = 'SIMPANAN'");
$produk_options = [];
while ($row = mysqli_fetch_assoc($produk_query)) {
    $produk_options[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Simpanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 640px) {
            .mobile-stack {
                flex-direction: column;
            }
            .mobile-full {
                width: 100%;
            }
            .mobile-text-sm {
                font-size: 0.875rem;
            }
            .mobile-p-2 {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="container mx-auto p-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <h3 class="text-xl md:text-2xl font-bold text-gray-700">Daftar Simpanan</h3>
            <a href="transaksi_simpanan.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow">+ Tambah Simpanan</a>
        </div>

        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="flex flex-wrap gap-2 mobile-stack">
                <input type="text" name="nama_anggota" placeholder="Nama Anggota" 
                       value="<?= htmlspecialchars($search_nama_anggota); ?>" 
                       class="border rounded px-2 py-1 flex-grow mobile-full">
                <input type="text" name="nama_produk" placeholder="Nama Produk" 
                       value="<?= htmlspecialchars($search_nama_produk); ?>" 
                       class="border rounded px-2 py-1 flex-grow mobile-full">
                <input type="date" name="tanggal" placeholder="Tanggal" 
                       value="<?= htmlspecialchars($search_tanggal); ?>" 
                       class="border rounded px-2 py-1 flex-grow mobile-full">
                <div class="flex gap-2 mobile-full">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg flex-grow">
                        Cari
                    </button>
                    <a href="list.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg flex-grow text-center">
                        Reset
                    </a>
                </div>
            </div>
             <!-- Hidden fields untuk sorting -->
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow-lg mobile-text-sm">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="px-3 py-2 text-center mobile-p-2">No</th>
                        <th class="px-3 py-2 text-left mobile-p-2">
                            <a href="?page=<?= $page ?>&sort=nama_anggota&order=<?= $sort === 'nama_anggota' && $order === 'asc' ? 'desc' : 'asc' ?>&nama_anggota=<?= urlencode($search_nama_anggota) ?>&nama_produk=<?= urlencode($search_nama_produk) ?>&tanggal=<?= urlencode($search_tanggal) ?>" class="hover:underline whitespace-nowrap">Nama Anggota</a>
                        </th>
                        <th class="px-3 py-2 text-left mobile-p-2">
                            <a href="?page=<?= $page ?>&sort=nama_produk&order=<?= $sort === 'nama_produk' && $order === 'asc' ? 'desc' : 'asc' ?>&nama_anggota=<?= urlencode($search_nama_anggota) ?>&nama_produk=<?= urlencode($search_nama_produk) ?>&tanggal=<?= urlencode($search_tanggal) ?>" class="hover:underline whitespace-nowrap">Produk</a>
                        </th>
                        <th class="px-3 py-2 text-left mobile-p-2">
                            <a href="?page=<?= $page ?>&sort=tanggal&order=<?= $sort === 'tanggal' && $order === 'asc' ? 'desc' : 'asc' ?>&nama_anggota=<?= urlencode($search_nama_anggota) ?>&nama_produk=<?= urlencode($search_nama_produk) ?>&tanggal=<?= urlencode($search_tanggal) ?>" class="hover:underline whitespace-nowrap">Tanggal</a>
                        </th>
                        <th class="px-3 py-2 text-right mobile-p-2">
                            <a href="?page=<?= $page ?>&sort=jumlah&order=<?= $sort === 'jumlah' && $order === 'asc' ? 'desc' : 'asc' ?>&nama_anggota=<?= urlencode($search_nama_anggota) ?>&nama_produk=<?= urlencode($search_nama_produk) ?>&tanggal=<?= urlencode($search_tanggal) ?>" class="hover:underline whitespace-nowrap">Jumlah</a>
                        </th>
                        <th class="px-3 py-2 text-center mobile-p-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = $start + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="border-b hover:bg-gray-100">
                                <td class="px-3 py-2 text-center mobile-p-2"><?= $no++ ?></td>
                                <td class="px-3 py-2 mobile-p-2"><?= htmlspecialchars($row['nama_anggota']) ?></td>
                                <td class="px-3 py-2 mobile-p-2"><?= htmlspecialchars($row['nama_produk']) ?></td>
                                <td class="px-3 py-2 mobile-p-2 whitespace-nowrap"><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td class="px-3 py-2 text-right mobile-p-2 whitespace-nowrap">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                <td class="px-3 py-2 text-center mobile-p-2 whitespace-nowrap">
                                    <button onclick="openUpdatePopup(<?= $row['id_simpanan'] ?>, '<?= $row['jumlah'] ?>', <?= $row['id_prodak'] ?>)" class="text-blue-500 hover:underline mobile-text-sm">Edit</button> |
                                    <button onclick="confirmDelete(<?= $row['id_simpanan'] ?>)" class="text-red-500 hover:underline mobile-text-sm">Hapus</button> |
                                    <a href="../laporan/slip.php?jenis=simpanan&id=<?= $row['id_simpanan'] ?>" target="_blank" class="text-blue-500 hover:underline mobile-text-sm">Print</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center px-3 py-4 text-gray-500">Tidak ada data simpanan yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-center">
            <?php if ($totalPages > 1): ?>
                <nav class="inline-flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&<?= $urlParams ?>"
                           class="px-2 py-1 md:px-3 md:py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">
                            <
                        </a>
                    <?php endif; ?>

                    <?php
                    $max_display = 3; 
                    $startPage = max(1, $page - floor($max_display / 2));
                    $endPage = min($totalPages, $startPage + $max_display - 1);
                    if ($endPage - $startPage < $max_display - 1) {
                        $startPage = max(1, $endPage - $max_display + 1);
                    }
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>&<?= $urlParams ?>"
                           class="px-2 py-1 md:px-3 md:py-2 rounded-lg transition-colors duration-200 text-sm <?= $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            <?= $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&<?= $urlParams ?>"
                           class="px-2 py-1 md:px-3 md:py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">
                            >
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Popup -->
    <div id="updatePopup" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center p-4">
        <div class="bg-white p-4 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold text-gray-700 mb-4">Update Simpanan</h3>
            <form id="updateForm" method="POST" action="update_simpanan.php">
                <input type="hidden" name="id_simpanan" id="updateId">
                <div class="mb-4">
                    <label for="updateProduk" class="block text-sm font-medium text-gray-700">Produk</label>
                    <select name="produk" id="updateProduk" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($produk_options as $produk): ?>
                            <option value="<?= $produk['id'] ?>"><?= htmlspecialchars($produk['nama_produk']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="updateJumlah" class="block text-sm font-medium text-gray-700">Jumlah</label>
                    <input type="number" name="jumlah" id="updateJumlah"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeUpdatePopup()"
                            class="bg-gray-500 text-white px-4 py-2 rounded-lg">Batal</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUpdatePopup(id, jumlah, id_prodak) {
            document.getElementById('updateId').value = id;
            document.getElementById('updateJumlah').value = jumlah;
            document.getElementById('updateProduk').value = id_prodak;
            document.getElementById('updatePopup').classList.remove('hidden');
        }

        function closeUpdatePopup() {
            document.getElementById('updatePopup').classList.add('hidden');
        }

        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data simpanan ini?')) {
                window.location.href = 'list.php?delete_id=' + id;
            }
        }
    </script>
</body>
</html>