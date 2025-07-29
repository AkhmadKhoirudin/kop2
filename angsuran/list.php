<?php
include '../config.php';

// Handle pagination
$items_per_page = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Handle sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'tanggal';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

// Search filter processing
$search_id_angsuran = isset($_GET['id_angsuran']) ? $_GET['id_angsuran'] : '';
$search_id_pinjaman = isset($_GET['id_pinjaman']) ? $_GET['id_pinjaman'] : '';
$search_nama = isset($_GET['nama']) ? $_GET['nama'] : '';
$search_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

$searchConditions = [];
if ($search_id_angsuran !== '') {
    $searchConditions[] = "a.id_angsuran = " . (int)$search_id_angsuran;
}
if ($search_id_pinjaman !== '') {
    $searchConditions[] = "a.id_pinjaman = " . (int)$search_id_pinjaman;
}
if ($search_nama !== '') {
    $searchConditions[] = "ag.nama LIKE '%" . mysqli_real_escape_string($conn, $search_nama) . "%'";
}
if ($search_tanggal !== '') {
    $searchConditions[] = "a.tanggal = '" . mysqli_real_escape_string($conn, $search_tanggal) . "'";
}

$whereClause = count($searchConditions) > 0 ? 'WHERE ' . implode(' AND ', $searchConditions) : '';

// Fetch total count for pagination
$total_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM angsuran a
    LEFT JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman
    LEFT JOIN anggota ag ON p.id_anggota = ag.id_anggota
    $whereClause
");
$total_row = mysqli_fetch_assoc($total_query);
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch paginated data
$query = mysqli_query($conn, "
    SELECT a.id_angsuran, a.id_pinjaman, a.tanggal, a.jumlah, a.status, p.id_anggota, ag.nama 
    FROM angsuran a
    LEFT JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman
    LEFT JOIN anggota ag ON p.id_anggota = ag.id_anggota
    $whereClause
    ORDER BY $sort_column $sort_order
    LIMIT $items_per_page OFFSET $offset
");

$data = [];
while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

// Handle delete action
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
    $deleteQuery = "DELETE FROM angsuran WHERE id_angsuran = $id";
    mysqli_query($conn, $deleteQuery);
    header("Location: list.php"); // Redirect to avoid duplicate deletions
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Angsuran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
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
        /* Optional: styling for the Swiper popup */
        #swiperPopup {
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    
<div class="container mx-auto p-4">
    <h3 class="text-xl md:text-2xl font-bold text-gray-700 mb-4">Daftar Angsuran</h3>
    
    <!-- Search Form -->
    <form method="GET" class="mb-4">
        <div class="flex flex-wrap gap-2 mobile-stack">
            <input type="text" name="id_angsuran" placeholder="ID Angsuran" 
                   value="<?php echo htmlspecialchars($search_id_angsuran); ?>" 
                   class="border rounded px-2 py-1 flex-grow mobile-full">
            <input type="text" name="id_pinjaman" placeholder="ID Pinjaman" 
                   value="<?php echo htmlspecialchars($search_id_pinjaman); ?>" 
                   class="border rounded px-2 py-1 flex-grow mobile-full">
            <input type="text" name="nama" placeholder="Nama Anggota" 
                   value="<?php echo htmlspecialchars($search_nama); ?>" 
                   class="border rounded px-2 py-1 flex-grow mobile-full">
            <input type="date" name="tanggal" placeholder="Tanggal" 
                   value="<?php echo htmlspecialchars($search_tanggal); ?>" 
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
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow-lg mobile-text-sm">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=id_angsuran&order=<?php echo $sort_column === 'id_angsuran' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap">ID Angsuran</a>
                    </th>
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=id_pinjaman&order=<?php echo $sort_column === 'id_pinjaman' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap">ID Pinjaman</a>
                    </th>
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=nama&order=<?php echo $sort_column === 'nama' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap">Nama</a>
                    </th>
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=tanggal&order=<?php echo $sort_column === 'tanggal' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap">Tanggal</a>
                    </th>
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=jumlah&order=<?php echo $sort_column === 'jumlah' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap">Jumlah</a>
                    </th>
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=status&order=<?php echo $sort_column === 'status' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap">Status</a>
                    </th>
                    <th class="px-3 py-2 text-left mobile-p-2 whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $row): ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-3 py-2 mobile-p-2"><?php echo $row['id_angsuran']; ?></td>
                            <td class="px-3 py-2 mobile-p-2"><?php echo $row['id_pinjaman']; ?></td>
                            <td class="px-3 py-2 mobile-p-2"><?php echo $row['nama']; ?></td>
                            <td class="px-3 py-2 mobile-p-2 whitespace-nowrap"><?php echo $row['tanggal']; ?></td>
                            <td class="px-3 py-2 mobile-p-2 whitespace-nowrap">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td class="px-3 py-2 mobile-p-2">
                                <span class="px-2 py-1 rounded-full text-xs <?php echo $row['status'] === 'Lunas' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 mobile-p-2 whitespace-nowrap">
                                <button onclick="openUpdatePopup(<?php echo $row['id_angsuran']; ?>, '<?php echo $row['jumlah']; ?>', '<?php echo $row['status']; ?>')" 
                                        class="text-blue-500 hover:underline mobile-text-sm">Edit</button> |
                                <button onclick="confirmDelete(<?php echo $row['id_angsuran']; ?>)" 
                                        class="text-red-500 hover:underline mobile-text-sm">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center px-3 py-4 text-gray-500">Tidak ada data angsuran.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-center">
        <?php if ($total_pages > 1): ?>
            <nav class="inline-flex space-x-1">
                <?php
                $max_display = 3; // Reduced for mobile
                $start = max(1, $page - floor($max_display / 2));
                $end = min($total_pages, $start + $max_display - 1);

                if ($end - $start < $max_display - 1) {
                    $start = max(1, $end - $max_display + 1);
                }
                ?>

                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>"
                       class="px-2 py-1 md:px-3 md:py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">
                        &lt;
                    </a>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>"
                       class="px-2 py-1 md:px-3 md:py-2 rounded-lg transition-colors duration-200 text-sm <?php echo $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>"
                       class="px-2 py-1 md:px-3 md:py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">
                        &gt;
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Update Popup -->
<div id="updatePopup" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center p-4">
    <div class="bg-white p-4 rounded-lg shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-700 mb-4">Update Angsuran</h3>
        <form id="updateForm" method="POST" action="update.php">
            <input type="hidden" name="id_angsuran" id="updateId">
            <div class="mb-4">
                <label for="updateJumlah" class="block text-sm font-medium text-gray-700">Jumlah</label>
                <input type="number" name="jumlah" id="updateJumlah" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="updateStatus" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="updateStatus" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="Lunas">Lunas</option>
                    <option value="Belum Lunas">Belum Lunas</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeUpdatePopup()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-lg">Batal</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Swiper Success Popup -->
<div id="swiperPopup" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-4 relative max-w-md w-full">
        <!-- Swiper container with one slide -->
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <div class="swiper-slide text-center">
                    <p class="text-green-600 font-bold text-lg">Operasi berhasil!</p>
                    <button id="closeSwiperPopup" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openUpdatePopup(id, jumlah, status) {
    document.getElementById('updateId').value = id;
    document.getElementById('updateJumlah').value = jumlah;
    document.getElementById('updateStatus').value = status;
    document.getElementById('updatePopup').classList.remove('hidden');
}

function closeUpdatePopup() {
    document.getElementById('updatePopup').classList.add('hidden');
}

function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        window.location.href = 'list.php?id=' + id;
    }
}

// Swiper Popup: show if URL has pesan=sukses
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('pesan') === 'sukses') {
        document.getElementById('swiperPopup').classList.remove('hidden');
        // Initialize Swiper (basic config)
        new Swiper('.swiper-container', { loop: false });
    }
    document.getElementById('closeSwiperPopup').addEventListener('click', function () {
        document.getElementById('swiperPopup').classList.add('hidden');
    });
});
</script>
</body>
</html>