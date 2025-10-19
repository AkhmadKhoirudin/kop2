<?php
include '../config.php';

// Cek session dan ambil role serta id_anggota
session_start();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$id_anggota = isset($_SESSION['id_anggota']) ? $_SESSION['id_anggota'] : '';
// --- PESAN ---
$pesan = '';
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] === 'sukses_update') $pesan = 'sukses';
    if ($_GET['pesan'] === 'sukses_hapus') $pesan = 'sukses';
    if ($_GET['pesan'] === 'gagal') $pesan = 'gagal';
}

// --- HANDLE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pinjaman'])) {
    $id_pinjaman = intval($_POST['id_pinjaman']);
    $jumlah = preg_replace('/\D/', '', $_POST['jumlah']);
    $tenor = intval($_POST['tenor']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $id_produk = intval($_POST['id_produk']);

    // Fetch status enum values to validate
    $status_enum = [];
    $res_enum = mysqli_query($conn, "SHOW COLUMNS FROM pinjaman LIKE 'status'");
    if ($row_enum = mysqli_fetch_assoc($res_enum)) {
        if (preg_match("/^enum\((.*)\)$/", $row_enum['Type'], $matches)) {
            $status_enum = array_map(fn($v) => trim($v, "'"), explode(',', $matches[1]));
        }
    }

    if ($id_pinjaman > 0 && $jumlah > 0 && $tenor > 0 && in_array($status, $status_enum) && $id_produk > 0) {
        $query = "UPDATE pinjaman SET jumlah='$jumlah', tenor='$tenor', status='$status', id_produk='$id_produk' WHERE id_pinjaman=$id_pinjaman";
        if (mysqli_query($conn, $query)) {
            header("Location: list.php?pesan=sukses_update");
            exit();
        } else {
            header("Location: list.php?pesan=gagal");
            exit();
        }
    } else {
        header("Location: list.php?pesan=gagal");
        exit();
    }
}

// --- HANDLE DELETE ---
if (isset($_GET['hapus_id']) && is_numeric($_GET['hapus_id'])) {
    $id = (int) $_GET['hapus_id'];
    $deleteQuery = "DELETE FROM pinjaman WHERE id_pinjaman = $id";
    if (mysqli_query($conn, $deleteQuery)) {
        header("Location: list.php?pesan=sukses_hapus");
        exit();
    } else {
        header("Location: list.php?pesan=gagal");
        exit();
    }
}


// --- PAGINATION ---
$items_per_page = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// --- SORTING ---
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'tanggal_pengajuan';
$allowed_columns = ['id_pinjaman', 'nama', 'nama_produk', 'tanggal_pengajuan', 'jumlah', 'tenor', 'status'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'tanggal_pengajuan';
}
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

// --- SEARCH ---
$search_id_pinjaman = isset($_GET['id_pinjaman']) ? $_GET['id_pinjaman'] : '';
$search_nama = isset($_GET['nama']) ? $_GET['nama'] : '';
$search_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
$search_status = isset($_GET['status']) ? $_GET['status'] : '';

$searchConditions = [];
if ($search_id_pinjaman !== '') {
    $searchConditions[] = "p.id_pinjaman = " . (int)$search_id_pinjaman;
}
if ($search_nama !== '') {
    $searchConditions[] = "ag.nama LIKE '%" . mysqli_real_escape_string($conn, $search_nama) . "%'";
}
if ($search_tanggal !== '') {
    $searchConditions[] = "p.tanggal_pengajuan = '" . mysqli_real_escape_string($conn, $search_tanggal) . "'";
}
if ($search_status !== '') {
    $searchConditions[] = "p.status = '" . mysqli_real_escape_string($conn, $search_status) . "'";
}

$whereClause = count($searchConditions) > 0 ? 'WHERE ' . implode(' AND ', $searchConditions) : '';

// Filter berdasarkan role
if ($role === 'user' && $id_anggota !== '') {
    $whereClause .= ($whereClause === '' ? 'WHERE' : ' AND') . " p.id_anggota = '$id_anggota'";
}

// --- DATA FETCHING ---
// Total count for pagination
$total_query_str = "
    SELECT COUNT(*) as total 
    FROM pinjaman p
    LEFT JOIN anggota ag ON p.id_anggota = ag.id_anggota
    LEFT JOIN produk pr ON p.id_produk = pr.id
    $whereClause
";
$total_query = mysqli_query($conn, $total_query_str);
$total_row = mysqli_fetch_assoc($total_query);
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Paginated data
$query_str = "
    SELECT p.*, ag.nama, pr.nama_produk 
    FROM pinjaman p
    LEFT JOIN anggota ag ON p.id_anggota = ag.id_anggota
    LEFT JOIN produk pr ON p.id_produk = pr.id
    $whereClause
    ORDER BY $sort_column $sort_order
    LIMIT $items_per_page OFFSET $offset
";
$query = mysqli_query($conn, $query_str);

$data = [];
while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

// Fetch produk options for the modal
$produk_options_query = mysqli_query($conn, "SELECT id, nama_produk FROM produk WHERE UPPER(kategori) = 'PEMBIAYAAN'");
$produk_options = [];
while($row = mysqli_fetch_assoc($produk_options_query)){
    $produk_options[] = $row;
}

// Fetch status enum for search and modal
$status_enum = [];
$res_enum = mysqli_query($conn, "SHOW COLUMNS FROM pinjaman LIKE 'status'");
if ($row_enum = mysqli_fetch_assoc($res_enum)) {
    if (preg_match("/^enum\((.*)\)$/", $row_enum['Type'], $matches)) {
        $status_enum = array_map(fn($v) => trim($v, "'"), explode(',', $matches[1]));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pinjaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <style>
        @media (max-width: 640px) {
            .mobile-stack { flex-direction: column; }
            .mobile-full { width: 100%; }
            .mobile-text-sm { font-size: 0.875rem; }
            .mobile-p-2 { padding: 0.5rem; }
        }
        #swiperPopup { z-index: 1000; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    
<div class="container mx-auto p-4">
    <h3 class="text-xl md:text-2xl font-bold text-gray-700 mb-4">Daftar Pinjaman</h3>
    
    <!-- Search Form -->
    <form method="GET" class="mb-4">
        <div class="flex flex-wrap gap-2 mobile-stack">
            <input type="text" name="id_pinjaman" placeholder="ID Pinjaman" 
                   value="<?= htmlspecialchars($search_id_pinjaman); ?>" 
                   class="border rounded px-2 py-1 flex-grow mobile-full">
            <input type="text" name="nama" placeholder="Nama Anggota" 
                   value="<?= htmlspecialchars($search_nama); ?>" 
                   class="border rounded px-2 py-1 flex-grow mobile-full">
            <input type="date" name="tanggal" placeholder="Tanggal Pengajuan" 
                   value="<?= htmlspecialchars($search_tanggal); ?>" 
                   class="border rounded px-2 py-1 flex-grow mobile-full">
            <select name="status" class="border rounded px-2 py-1 flex-grow mobile-full">
                <option value="">Semua Status</option>
                <?php foreach ($status_enum as $s): ?>
                    <option value="<?= $s ?>" <?= $search_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex gap-2 mobile-full">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg flex-grow">
                    Cari
                </button>
                <a href="list.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg flex-grow text-center">
                    Reset
                </a>
                <?php if ($role === 'admin'): ?>
                <a href="transaksi_pinjaman.php" class="bg-green-500 text-white px-4 py-2 rounded-lg flex-grow text-center">
                    + Tambah
                </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow-lg mobile-text-sm">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <?php
                    $columns = [
                        'id_pinjaman' => 'ID Pinjaman',
                        'nama' => 'Nama Anggota',
                        'nama_produk' => 'Produk',
                        'tanggal_pengajuan' => 'Tanggal',
                        'jumlah' => 'Jumlah',
                        'tenor' => 'Tenor',
                        'status' => 'Status'
                    ];
                    foreach ($columns as $col => $title): ?>
                    <th class="px-3 py-2 text-left mobile-p-2">
                        <a href="?sort=<?= $col ?>&order=<?= $sort_column === $col && $sort_order === 'asc' ? 'desc' : 'asc'; ?>" 
                           class="hover:underline whitespace-nowrap"><?= $title ?></a>
                    </th>
                    <?php endforeach; ?>
                    <th class="px-3 py-2 text-left mobile-p-2 whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $row): ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-3 py-2 mobile-p-2"><?= $row['id_pinjaman']; ?></td>
                            <td class="px-3 py-2 mobile-p-2"><?= htmlspecialchars($row['nama']); ?></td>
                            <td class="px-3 py-2 mobile-p-2"><?= htmlspecialchars($row['nama_produk']); ?></td>
                            <td class="px-3 py-2 mobile-p-2 whitespace-nowrap"><?= $row['tanggal_pengajuan']; ?></td>
                            <td class="px-3 py-2 mobile-p-2 whitespace-nowrap">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td class="px-3 py-2 mobile-p-2"><?= $row['tenor']; ?> bln</td>
                            <td class="px-3 py-2 mobile-p-2">
                                <?php
                                $status_color = 'bg-gray-100 text-gray-600';
                                if ($row['status'] === 'disetujui') $status_color = 'bg-green-100 text-green-600';
                                if ($row['status'] === 'ditolak') $status_color = 'bg-red-100 text-red-600';
                                if ($row['status'] === 'lunas') $status_color = 'bg-blue-100 text-blue-600';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs capitalize <?= $status_color ?>">
                                    <?= htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 mobile-p-2 whitespace-nowrap">
                                <?php if ($role === 'admin'): ?>
                                <button onclick='openUpdatePopup(<?= json_encode($row) ?>)'
                                        class="text-blue-500 hover:underline mobile-text-sm">Edit</button> |
                                <button onclick="confirmDelete(<?= $row['id_pinjaman']; ?>)"
                                        class="text-red-500 hover:underline mobile-text-sm">Hapus</button> |
                                <?php endif; ?>
                                <a href="../laporan/slip.php?jenis=pinjaman&id=<?= $row['id_pinjaman'] ?>" target="_blank" class="text-blue-500 hover:underline mobile-text-sm">Print</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center px-3 py-4 text-gray-500">Tidak ada data pinjaman.</td>
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
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryString = http_build_query($queryParams);
                
                $max_display = 3; 
                $start = max(1, $page - floor($max_display / 2));
                $end = min($total_pages, $start + $max_display - 1);

                if ($end - $start < $max_display - 1) {
                    $start = max(1, $end - $max_display + 1);
                }
                ?>

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1; ?>&<?= $queryString ?>"
                       class="px-2 py-1 md:px-3 md:py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">
                        &lt;
                    </a>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?= $i; ?>&<?= $queryString ?>"
                       class="px-2 py-1 md:px-3 md:py-2 rounded-lg transition-colors duration-200 text-sm <?= $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1; ?>&<?= $queryString ?>"
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
        <h3 class="text-lg font-bold text-gray-700 mb-4">Update Pinjaman</h3>
        <form id="updateForm" method="POST" action="list.php">
            <input type="hidden" name="id_pinjaman" id="update_id_pinjaman">
            
            <div class="mb-4">
                <label for="update_id_produk" class="block text-sm font-medium text-gray-700">Produk</label>
                <select name="id_produk" id="update_id_produk" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                     <?php if (empty($produk_options)): ?>
                        <option value="" disabled>-- Tidak ada produk pinjaman --</option>
                    <?php else: ?>
                        <?php foreach ($produk_options as $produk): ?>
                            <option value="<?= $produk['id'] ?>"><?= htmlspecialchars($produk['nama_produk']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
               </select>
            </div>

            <div class="mb-4">
                <label for="update_jumlah" class="block text-sm font-medium text-gray-700">Jumlah</label>
                <input type="number" name="jumlah" id="update_jumlah" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-4">
                <label for="update_tenor" class="block text-sm font-medium text-gray-700">Tenor (bulan)</label>
                <input type="number" name="tenor" id="update_tenor" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mb-4">
                <label for="update_status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="update_status" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($status_enum as $s): ?>
                        <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
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
function openUpdatePopup(data) {
    document.getElementById('update_id_pinjaman').value = data.id_pinjaman;
    document.getElementById('update_id_produk').value = data.id_produk;
    document.getElementById('update_jumlah').value = data.jumlah;
    document.getElementById('update_tenor').value = data.tenor;
    document.getElementById('update_status').value = data.status;
    document.getElementById('updatePopup').classList.remove('hidden');
}

function closeUpdatePopup() {
    document.getElementById('updatePopup').classList.add('hidden');
}

function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data pinjaman ini?')) {
        window.location.href = 'list.php?hapus_id=' + id;
    }
}

// Swiper Popup: show if URL has pesan=sukses
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('pesan') && params.get('pesan').startsWith('sukses')) {
        document.getElementById('swiperPopup').classList.remove('hidden');
        new Swiper('.swiper-container', { loop: false });
        
        // Clean the URL
        const newUrl = window.location.pathname + window.location.search.replace(/&?pesan=[^&]*/, '').replace(/^\?$/, '');
        window.history.replaceState({}, document.title, newUrl);
    }
    document.getElementById('closeSwiperPopup').addEventListener('click', function () {
        document.getElementById('swiperPopup').classList.add('hidden');
    });
});
</script>
</body>
</html>
