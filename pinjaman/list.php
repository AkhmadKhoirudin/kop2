<?php
include '../config.php';

$pesan = null;
$search = $_GET['cari'] ?? '';

// --- ENUM STATUS ---
$status_enum = [];
$res_enum = mysqli_query($conn, "SHOW COLUMNS FROM pinjaman LIKE 'status'");
if ($row_enum = mysqli_fetch_assoc($res_enum)) {
    if (preg_match("/^enum\((.*)\)$/", $row_enum['Type'], $matches)) {
        $status_enum = array_map(fn($v) => trim($v, "'"), explode(',', $matches[1]));
    }
}

// --- HANDLE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pinjaman'])) {
    $id_pinjaman = intval($_POST['id_pinjaman']);
    $jumlah = preg_replace('/\D/', '', $_POST['jumlah']);
    $tenor = intval($_POST['tenor']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if ($id_pinjaman > 0 && $jumlah > 0 && $tenor > 0 && in_array($status, $status_enum)) {
        $query = "UPDATE pinjaman SET jumlah='$jumlah', tenor='$tenor', status='$status' WHERE id_pinjaman=$id_pinjaman";
        $pesan = mysqli_query($conn, $query) ? 'sukses' : 'gagal';
    } else {
        $pesan = 'invalid';
    }
}

// --- SORTING ---
$allowed_columns = ['id_pinjaman', 'id_anggota', 'id_produk', 'tanggal_pengajuan', 'jumlah', 'tenor', 'status'];
$order = $_GET['order'] ?? 'id_pinjaman';
$sort = strtolower($_GET['sort'] ?? 'desc');
if (!in_array($order, $allowed_columns)) $order = 'id_pinjaman';
if (!in_array($sort, ['asc', 'desc'])) $sort = 'desc';

function sortUrl($column, $currentOrder, $currentSort) {
    $newSort = ($column === $currentOrder && $currentSort === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, ['order' => $column, 'sort' => $newSort]);
    return '?' . http_build_query($params);
}
function sortIcon($column, $currentOrder, $currentSort) {
    if ($column !== $currentOrder) return '';
    return $currentSort === 'asc' ? ' ▲' : ' ▼';
}

// --- DATA FILTERING ---
$where = "";
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where = "WHERE id_pinjaman LIKE '%$search%' OR id_anggota LIKE '%$search%'";
}
$query = "SELECT * FROM pinjaman $where ORDER BY $order $sort";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pinjaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold text-gray-700">Daftar Pinjaman</h1>
        <div class="flex items-center gap-2">
            <form method="get" action="" class="flex items-center">
                <input type="text" name="cari" placeholder="Cari ID Pinjaman atau ID Anggota"
                       value="<?= htmlspecialchars($search); ?>"
                       class="px-3 py-1 border rounded border-gray-300 text-sm focus:outline-none focus:ring focus:border-blue-400">
                <button type="submit" class="ml-2 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Cari</button>
            </form>
            <button onclick="window.print()" class="ml-2 px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">Print</button>
        </div>
    </div>

    <div class="overflow-auto rounded-lg border border-gray-300">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-700 font-semibold">
                <tr>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('id_pinjaman', $order, $sort) ?>">ID<?= sortIcon('id_pinjaman', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('id_anggota', $order, $sort) ?>">ID Anggota<?= sortIcon('id_anggota', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('id_produk', $order, $sort) ?>">ID Produk<?= sortIcon('id_produk', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('tanggal_pengajuan', $order, $sort) ?>">Tanggal<?= sortIcon('tanggal_pengajuan', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('jumlah', $order, $sort) ?>">Jumlah<?= sortIcon('jumlah', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('tenor', $order, $sort) ?>">Tenor<?= sortIcon('tenor', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2"><a href="<?= sortUrl('status', $order, $sort) ?>">Status<?= sortIcon('status', $order, $sort) ?></a></th>
                    <th class="border px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border px-4 py-2"><?= $row['id_pinjaman']; ?></td>
                            <td class="border px-4 py-2"><?= $row['id_anggota']; ?></td>
                            <td class="border px-4 py-2"><?= $row['id_produk']; ?></td>
                            <td class="border px-4 py-2"><?= $row['tanggal_pengajuan']; ?></td>
                            <td class="border px-4 py-2">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td class="border px-4 py-2"><?= $row['tenor']; ?> bln</td>
                            <td class="border px-4 py-2 capitalize"><?= $row['status']; ?></td>
                            <td class="border px-4 py-2">
                                <button onclick='openEditPopup(<?= json_encode($row) ?>)' class="text-yellow-600 hover:underline">Edit</button>
                                <a href="../laporan/slip.php?jenis=pinjaman&id=<?= $row['id_pinjaman'] ?>" target="_blank" class="text-blue-500 hover:underline ml-2">Print</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="border px-4 py-4 text-center text-gray-500">Data tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-center">Edit Pinjaman</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id_pinjaman" id="edit_id_pinjaman" />
            <div>
                <label class="block text-sm font-medium text-gray-700">Jumlah Pinjaman:</label>
                <input type="text" name="jumlah" id="edit_jumlah"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tenor (bulan):</label>
                <input type="number" name="tenor" id="edit_tenor"
                       class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status:</label>
                <select name="status" id="edit_status"
                        class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($status_enum as $s): ?>
                        <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeEditPopup()" class="px-4 py-2 rounded bg-gray-400 text-white hover:bg-gray-500">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- JS -->
<script>
function openEditPopup(data) {
    document.getElementById('edit_id_pinjaman').value = data.id_pinjaman;
    document.getElementById('edit_jumlah').value = data.jumlah;
    document.getElementById('edit_tenor').value = data.tenor;
    document.getElementById('edit_status').value = data.status;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditPopup() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<!-- SweetAlert -->
<?php if ($pesan === 'sukses'): ?>
<script>
Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Data diperbarui.', timer: 3000, showConfirmButton: false });
</script>
<?php elseif ($pesan === 'gagal'): ?>
<script>
Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan saat menyimpan data.' });
</script>
<?php elseif ($pesan === 'invalid'): ?>
<script>
Swal.fire({ icon: 'warning', title: 'Data tidak valid!', text: 'Cek kembali input yang diberikan.' });
</script>
<?php endif; ?>

</body>
</html>
