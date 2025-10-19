<?php
include '../config.php';

// Cek session dan ambil role
session_start();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Jika bukan admin, redirect ke halaman list
if ($role !== 'admin') {
    header('Location: list.php');
    exit();
}

// Query untuk mendapatkan semua anggota
$anggota_query = "SELECT * FROM anggota ORDER BY id_anggota DESC";
$anggota_result = mysqli_query($conn, $anggota_query);

// Array untuk menyimpan data semua anggota
$semua_anggota = [];
while ($anggota = mysqli_fetch_assoc($anggota_result)) {
    $id_anggota = $anggota['id_anggota'];
    
    // Query untuk mendapatkan saldo anggota
    $saldo_query = "SELECT saldo FROM saldo_anggota WHERE id_anggota = $id_anggota";
    $saldo_result = mysqli_query($conn, $saldo_query);
    $saldo_data = mysqli_fetch_assoc($saldo_result);
    $saldo = $saldo_data ? $saldo_data['saldo'] : 0;
    
    // Query untuk mendapatkan total simpanan
    $total_simpanan_query = "SELECT COALESCE(SUM(jumlah), 0) as total_simpanan FROM simpanan WHERE id_anggota = $id_anggota";
    $total_simpanan_result = mysqli_query($conn, $total_simpanan_query);
    $total_simpanan_data = mysqli_fetch_assoc($total_simpanan_result);
    $total_simpanan = $total_simpanan_data['total_simpanan'];
    
    // Query untuk mendapatkan total penarikan
    $total_penarikan_query = "SELECT COALESCE(SUM(jumlah), 0) as total_penarikan FROM tarik WHERE id_anggota = $id_anggota";
    $total_penarikan_result = mysqli_query($conn, $total_penarikan_query);
    $total_penarikan_data = mysqli_fetch_assoc($total_penarikan_result);
    $total_penarikan = $total_penarikan_data['total_penarikan'];
    
    // Query untuk mendapatkan total pinjaman
    $total_pinjaman_query = "SELECT COALESCE(SUM(jumlah), 0) as total_pinjaman FROM pinjaman WHERE id_anggota = $id_anggota";
    $total_pinjaman_result = mysqli_query($conn, $total_pinjaman_query);
    $total_pinjaman_data = mysqli_fetch_assoc($total_pinjaman_result);
    $total_pinjaman = $total_pinjaman_data['total_pinjaman'];
    
    // Query untuk mendapatkan total angsuran
    $total_angsuran_query = "SELECT COALESCE(SUM(a.jumlah), 0) as total_angsuran FROM angsuran a JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman WHERE p.id_anggota = $id_anggota";
    $total_angsuran_result = mysqli_query($conn, $total_angsuran_query);
    $total_angsuran_data = mysqli_fetch_assoc($total_angsuran_result);
    $total_angsuran = $total_angsuran_data['total_angsuran'];
    
    // Simpan semua data anggota
    $semua_anggota[] = [
        'id_anggota' => $id_anggota,
        'nama' => $anggota['nama'],
        'email' => $anggota['email'],
        'alamat' => $anggota['alamat'],
        'telepon' => $anggota['telepon'],
        'foto' => $anggota['foto'],
        'saldo' => $saldo,
        'total_simpanan' => $total_simpanan,
        'total_penarikan' => $total_penarikan,
        'total_pinjaman' => $total_pinjaman,
        'total_angsuran' => $total_angsuran
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Semua Anggota</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto p-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Informasi Semua Anggota</h1>
                    <p class="text-gray-600">Data lengkap saldo, simpanan, pinjaman, angsuran, dan penarikan</p>
                </div>
                <div>
                    <a href="list.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Ringkasan Total -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Anggota</p>
                        <p class="text-2xl font-bold text-blue-600"><?= count($semua_anggota) ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Saldo</p>
                        <p class="text-2xl font-bold text-green-600">Rp <?= number_format(array_sum(array_column($semua_anggota, 'saldo')), 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-wallet text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Simpanan</p>
                        <p class="text-2xl font-bold text-blue-600">Rp <?= number_format(array_sum(array_column($semua_anggota, 'total_simpanan')), 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-piggy-bank text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Pinjaman</p>
                        <p class="text-2xl font-bold text-purple-600">Rp <?= number_format(array_sum(array_column($semua_anggota, 'total_pinjaman')), 0, ',', '.') ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-file-invoice-dollar text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Anggota -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Daftar Semua Anggota</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Simpanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penarikan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pinjaman</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Angsuran</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($semua_anggota as $anggota): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($anggota['foto']) && file_exists('../upload/foto/' . $anggota['foto'])): ?>
                                        <img src="../upload/foto/<?= $anggota['foto'] ?>" alt="foto" class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $anggota['id_anggota'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium"><?= htmlspecialchars($anggota['nama']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($anggota['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Rp <?= number_format($anggota['saldo'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium">
                                    Rp <?= number_format($anggota['total_simpanan'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 font-medium">
                                    Rp <?= number_format($anggota['total_penarikan'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600 font-medium">
                                    Rp <?= number_format($anggota['total_pinjaman'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-medium">
                                    Rp <?= number_format($anggota['total_angsuran'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="informasi.php?id=<?= $anggota['id_anggota'] ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail Per Anggota (Modal) -->
        <div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Detail Anggota</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div id="modalContent" class="space-y-4">
                        <!-- Konten detail akan dimuat di sini -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showDetail(id) {
            // Fetch detail data
            fetch(`get_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Populate modal content
                    document.getElementById('modalTitle').textContent = `Detail Anggota - ${data.nama}`;
                    
                    let content = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold mb-2">Informasi Dasar</h4>
                                    <p><strong>ID:</strong> ${data.id_anggota}</p>
                                    <p><strong>Nama:</strong> ${data.nama}</p>
                                    <p><strong>Email:</strong> ${data.email}</p>
                                    <p><strong>Telepon:</strong> ${data.telepon}</p>
                                    <p><strong>Alamat:</strong> ${data.alamat}</p>
                                </div>
                            </div>
                            
                            <div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold mb-2">Ringkasan Keuangan</h4>
                                    <p><strong>Saldo Aktual:</strong> Rp ${formatRupiah(data.saldo)}</p>
                                    <p><strong>Total Simpanan:</strong> Rp ${formatRupiah(data.total_simpanan)}</p>
                                    <p><strong>Total Penarikan:</strong> Rp ${formatRupiah(data.total_penarikan)}</p>
                                    <p><strong>Total Pinjaman:</strong> Rp ${formatRupiah(data.total_pinjaman)}</p>
                                    <p><strong>Total Angsuran:</strong> Rp ${formatRupiah(data.total_angsuran)}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <h4 class="font-semibold mb-4">Detail Transaksi</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h5 class="font-medium mb-2">Pinjaman Aktif</h5>
                                    <p>Jumlah: Rp ${formatRupiah(data.total_pinjaman - data.total_angsuran)}</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h5 class="font-medium mb-2">Simpanan Neto</h5>
                                    <p>Jumlah: Rp ${formatRupiah(data.total_simpanan - data.total_penarikan)}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('modalContent').innerHTML = content;
                    document.getElementById('detailModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error fetching detail:', error);
                });
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }
        
        function formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>