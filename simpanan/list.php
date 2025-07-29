<?php
include '../config.php';

// Valid sort columns
$allowed = ['nama_anggota', 'nama_produk', 'tanggal', 'jumlah'];
$sort = in_array($_GET['sort'] ?? '', $allowed) ? $_GET['sort'] : 'tanggal';
$order = ($_GET['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

// Query
$query = "
    SELECT 
        simpanan.id_simpanan,
        anggota.nama AS nama_anggota,
        produk.nama_produk,
        simpanan.tanggal,
        simpanan.jumlah
    FROM simpanan
    JOIN anggota ON simpanan.id_anggota = anggota.id_anggota
    JOIN produk ON simpanan.id_prodak = produk.id
    ORDER BY $sort $order
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Simpanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-blue-700 mb-4">📋 Daftar Simpanan</h1>

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'sukses'): ?>
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 border border-green-300">
                ✅ Data simpanan berhasil ditambahkan.
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <input type="text" id="search" placeholder="🔍 Cari ID atau Nama..." class="p-2 border rounded w-full max-w-md" onkeyup="filterTable()">
        </div>

        <div class="overflow-x-auto shadow rounded bg-white">
            <table id="simpananTable" class="min-w-full text-sm">
                <thead class="bg-blue-100 text-blue-800">
                    <tr>
                        <th class="px-4 py-2 border">No</th>
                        <th class="px-4 py-2 border text-left">
                            <a href="?sort=nama_anggota&order=<?= $sort === 'nama_anggota' && $order === 'asc' ? 'desc' : 'asc' ?>" class="hover:underline">Nama Anggota</a>
                        </th>
                        <th class="px-4 py-2 border text-left">
                            <a href="?sort=nama_produk&order=<?= $sort === 'nama_produk' && $order === 'asc' ? 'desc' : 'asc' ?>" class="hover:underline">Produk</a>
                        </th>
                        <th class="px-4 py-2 border text-left">
                            <a href="?sort=tanggal&order=<?= $sort === 'tanggal' && $order === 'asc' ? 'desc' : 'asc' ?>" class="hover:underline">Tanggal</a>
                        </th>
                        <th class="px-4 py-2 border text-right">
                            <a href="?sort=jumlah&order=<?= $sort === 'jumlah' && $order === 'asc' ? 'desc' : 'asc' ?>" class="hover:underline">Jumlah</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2 border text-center"><?= $no++ ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($row['nama_anggota']) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td class="px-4 py-2 border text-right">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function filterTable() {
        const input = document.getElementById("search").value.toLowerCase();
        const rows = document.querySelectorAll("#simpananTable tbody tr");

        rows.forEach(row => {
            const id = row.cells[0].textContent.toLowerCase();
            const nama = row.cells[1].textContent.toLowerCase();
            row.style.display = id.includes(input) || nama.includes(input) ? "" : "none";
        });
    }
    </script>
</body>
</html>
