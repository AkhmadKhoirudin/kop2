<?php
// File: laporan_harian_expandable_complete.php

// ==============================================
// KONEKSI DATABASE DAN LOGIKA PHP
// ==============================================

function connectDB() {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $db = 'koperasi';
    
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    return $conn;
}

// Ambil parameter tanggal
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Koneksi database
$db = connectDB();

// Query data simpanan harian
$query_simpanan = "SELECT 
                    s.tanggal,
                    p.nama_produk AS jenis,
                    COUNT(*) AS jumlah_transaksi,
                    SUM(s.jumlah) AS total_nominal,
                    GROUP_CONCAT(CONCAT(a.nama, ' (Rp ', FORMAT(s.jumlah, 0), ')') SEPARATOR '|') AS detail_anggota
                  FROM simpanan s
                  JOIN anggota a ON s.id_anggota = a.id_anggota
                  JOIN produk p ON s.id_prodak = p.id
                  WHERE s.tanggal = ?
                  GROUP BY p.nama_produk
                  ORDER BY p.nama_produk";
$stmt = $db->prepare($query_simpanan);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$simpanan_per_jenis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Query data pinjaman harian
$query_pinjaman = "SELECT 
                    p.tanggal_pengajuan AS tanggal,
                    pr.nama_produk AS jenis,
                    COUNT(*) AS jumlah_transaksi,
                    SUM(p.jumlah) AS total_nominal,
                    GROUP_CONCAT(CONCAT(a.nama, ' (Rp ', FORMAT(p.jumlah, 0), ' - ', p.status, ')') SEPARATOR '|') AS detail_anggota
                  FROM pinjaman p
                  JOIN anggota a ON p.id_anggota = a.id_anggota
                  JOIN produk pr ON p.id_produk = pr.id
                  WHERE p.tanggal_pengajuan = ?
                  GROUP BY pr.nama_produk
                  ORDER BY pr.nama_produk";
$stmt = $db->prepare($query_pinjaman);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$pinjaman_per_jenis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Query data penarikan harian
$query_penarikan = "SELECT 
                    t.tanggal,
                    p.nama_produk AS jenis,
                    COUNT(*) AS jumlah_transaksi,
                    SUM(t.jumlah) AS total_nominal,
                    GROUP_CONCAT(CONCAT(a.nama, ' (Rp ', FORMAT(t.jumlah, 0), ')') SEPARATOR '|') AS detail_anggota
                  FROM tarik t
                  JOIN anggota a ON t.id_anggota = a.id_anggota
                  JOIN produk p ON t.id_produk = p.id
                  WHERE t.tanggal = ?
                  GROUP BY p.nama_produk
                  ORDER BY p.nama_produk";
$stmt = $db->prepare($query_penarikan);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$penarikan_per_jenis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung total
$total_simpanan = array_sum(array_column($simpanan_per_jenis, 'total_nominal'));
$total_pinjaman = array_sum(array_column($pinjaman_per_jenis, 'total_nominal'));
$total_penarikan = array_sum(array_column($penarikan_per_jenis, 'total_nominal'));

// Fungsi helper
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($date) {
    return date('d/m/Y', strtotime($date));
}

// Data manager
$manager_nama = "menejer";

// ==============================================
// TAMPILAN HTML
// ==============================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian KSPPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        @layer utilities {
            .rotate-90 {
                transform: rotate(90deg);
            }
            .transition-all {
                transition-property: all;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                transition-duration: 150ms;
            }
            .detail-row {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease-out;
            }
            .detail-row.expanded {
                max-height: 500px;
                transition: max-height 0.5s ease-in;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col items-center mb-8 border-b-2 border-blue-800 pb-4">
            <div class="flex items-center mb-4">
                <img src="logo-koperasi.png" alt="Logo Koperasi" class="h-16 w-16 mr-4">
                <div>
                    <h1 class="text-2xl font-bold text-blue-800">KSPPS BERKAH ABADI</h1>
                    <p class="text-gray-600">Jl. Koperasi No. 123, Kota Cirebon</p>
                </div>
            </div>
            <h2 class="text-xl font-semibold text-blue-700">LAPORAN HARIAN TRANSAKSI</h2>
            <h3 class="text-lg text-gray-600">Tanggal: <?= formatTanggal($tanggal) ?></h3>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex border-b mb-6">
            <button class="tab-btn px-4 py-2 font-medium border-b-2 border-blue-800 text-blue-800" data-tab="simpanan">
                Simpanan
            </button>
            <button class="tab-btn px-4 py-2 font-medium text-gray-600" data-tab="pinjaman">
                Pinjaman
            </button>
            <button class="tab-btn px-4 py-2 font-medium text-gray-600" data-tab="penarikan">
                Penarikan
            </button>
            <button class="tab-btn px-4 py-2 font-medium text-gray-600" data-tab="anggota">
                Anggota
            </button>
        </div>

        <!-- Simpanan Section -->
        <div id="simpanan-tab" class="tab-content">
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Jenis Simpanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($simpanan_per_jenis as $index => $jenis): ?>
                        <tr class="hover:bg-gray-50" id="row-simpanan-<?= $index ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?= htmlspecialchars($jenis['jenis']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $jenis['jumlah_transaksi'] ?> transaksi</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= formatRupiah($jenis['total_nominal']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="expand-btn p-1 rounded-full hover:bg-gray-200" 
                                        data-target="detail-simpanan-<?= $index ?>">
                                    <svg class="w-5 h-5 transform transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-0 py-0">
                                <div id="detail-simpanan-<?= $index ?>" class="detail-row">
                                    <div class="px-6 py-4 ml-8">
                                        <h4 class="font-medium text-gray-700 mb-2">Detail Anggota:</h4>
                                        <ul class="list-disc pl-5 space-y-1">
                                            <?php 
                                            $details = explode('|', $jenis['detail_anggota']);
                                            foreach ($details as $detail): 
                                            ?>
                                                <li><?= htmlspecialchars(trim($detail)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($simpanan_per_jenis)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada transaksi simpanan pada tanggal ini</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($simpanan_per_jenis)): ?>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td class="px-6 py-4" colspan="2">Total Simpanan</td>
                            <td class="px-6 py-4 text-right"><?= formatRupiah($total_simpanan) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Pinjaman Section -->
        <div id="pinjaman-tab" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Jenis Pinjaman</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pinjaman_per_jenis as $index => $jenis): ?>
                        <tr class="hover:bg-gray-50" id="row-pinjaman-<?= $index ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?= htmlspecialchars($jenis['jenis']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $jenis['jumlah_transaksi'] ?> pengajuan</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= formatRupiah($jenis['total_nominal']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="expand-btn p-1 rounded-full hover:bg-gray-200" 
                                        data-target="detail-pinjaman-<?= $index ?>">
                                    <svg class="w-5 h-5 transform transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-0 py-0">
                                <div id="detail-pinjaman-<?= $index ?>" class="detail-row">
                                    <div class="px-6 py-4 ml-8">
                                        <h4 class="font-medium text-gray-700 mb-2">Detail Anggota:</h4>
                                        <ul class="list-disc pl-5 space-y-1">
                                            <?php 
                                            $details = explode('|', $jenis['detail_anggota']);
                                            foreach ($details as $detail): 
                                            ?>
                                                <li><?= htmlspecialchars(trim($detail)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pinjaman_per_jenis)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada pengajuan pinjaman pada tanggal ini</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($pinjaman_per_jenis)): ?>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td class="px-6 py-4" colspan="2">Total Pinjaman</td>
                            <td class="px-6 py-4 text-right"><?= formatRupiah($total_pinjaman) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Penarikan Section -->
        <div id="penarikan-tab" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Jenis Penarikan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($penarikan_per_jenis as $index => $jenis): ?>
                        <tr class="hover:bg-gray-50" id="row-penarikan-<?= $index ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?= htmlspecialchars($jenis['jenis']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $jenis['jumlah_transaksi'] ?> penarikan</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= formatRupiah($jenis['total_nominal']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="expand-btn p-1 rounded-full hover:bg-gray-200" 
                                        data-target="detail-penarikan-<?= $index ?>">
                                    <svg class="w-5 h-5 transform transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-0 py-0">
                                <div id="detail-penarikan-<?= $index ?>" class="detail-row">
                                    <div class="px-6 py-4 ml-8">
                                        <h4 class="font-medium text-gray-700 mb-2">Detail Anggota:</h4>
                                        <ul class="list-disc pl-5 space-y-1">
                                            <?php 
                                            $details = explode('|', $jenis['detail_anggota']);
                                            foreach ($details as $detail): 
                                            ?>
                                                <li><?= htmlspecialchars(trim($detail)) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($penarikan_per_jenis)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada penarikan pada tanggal ini</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($penarikan_per_jenis)): ?>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td class="px-6 py-4" colspan="2">Total Penarikan</td>
                            <td class="px-6 py-4 text-right"><?= formatRupiah($total_penarikan) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Anggota Section -->
        <div id="anggota-tab" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8 p-6">
                <h3 class="text-lg font-semibold mb-4 text-blue-800">Data Anggota</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Anggota Aktif</h4>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <p class="text-2xl font-bold text-green-700">1,245</p>
                            <p class="text-sm text-green-600">Total anggota aktif</p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Anggota Non-Aktif</h4>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <p class="text-2xl font-bold text-red-700">42</p>
                            <p class="text-sm text-red-600">Total anggota non-aktif</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <h4 class="font-medium text-gray-700 mb-2">Registrasi Hari Ini</h4>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-2xl font-bold text-blue-700">8</p>
                        <p class="text-sm text-blue-600">Anggota baru terdaftar</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="flex justify-end mt-12">
            <div class="text-center">
                <p class="mb-12">Cirebon, <?= date('d F Y') ?></p>
                <p class="font-semibold"><?= htmlspecialchars($manager_nama) ?></p>
                <p class="border-t-2 border-blue-800 pt-1 w-48 mx-auto">Manager KSPPS</p>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk expand/collapse detail
        document.querySelectorAll('.expand-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetRow = document.getElementById(targetId);
                const icon = this.querySelector('svg');
                
                // Toggle class expanded
                targetRow.classList.toggle('expanded');
                
                // Rotate icon
                icon.classList.toggle('rotate-90');
                
                // Scroll ke row yang di-expand
                const parentRow = this.closest('tr');
                if (targetRow.classList.contains('expanded')) {
                    parentRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });

        // Fungsi untuk tab navigation
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Update active tab
                document.querySelectorAll('.tab-btn').forEach(t => {
                    t.classList.remove('border-b-2', 'border-blue-800', 'text-blue-800');
                    t.classList.add('text-gray-600');
                });
                this.classList.add('border-b-2', 'border-blue-800', 'text-blue-800');
                this.classList.remove('text-gray-600');
                
                // Show selected tab content
                document.querySelectorAll('.tab-content').forEach(c => {
                    c.classList.add('hidden');
                });
                document.getElementById(`${tabId}-tab`).classList.remove('hidden');
            });
        });

        // Fungsi untuk export ke Excel
        function exportToExcel() {
            // Buat HTML untuk dokumen Excel
            const activeTab = document.querySelector('.tab-btn.text-blue-800').getAttribute('data-tab');
            let html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="UTF-8">
                    <title>Laporan Harian KSPPS <?= formatTanggal($tanggal) ?></title>
                    <!--[if gte mso 9]>
                    <xml>
                        <x:ExcelWorkbook>
                            <x:ExcelWorksheets>
                                <x:ExcelWorksheet>
                                    <x:Name>Laporan Harian</x:Name>
                                    <x:WorksheetOptions>
                                        <x:DisplayGridlines/>
                                    </x:WorksheetOptions>
                                </x:ExcelWorksheet>
                            </x:ExcelWorksheets>
                        </x:ExcelWorkbook>
                    </xml>
                    <![endif]-->
                    <style>
                        td, th {
                            border: 1px solid #ddd;
                            padding: 5px;
                        }
                        th {
                            background-color: #1e40af;
                            color: white;
                            font-weight: bold;
                        }
                        .text-right {
                            text-align: right;
                        }
                    </style>
                </head>
                <body>
                    <h1>Laporan Harian KSPPS</h1>
                    <h2>Tanggal: <?= formatTanggal($tanggal) ?></h2>
                    
                    ${document.getElementById(`${activeTab}-tab`).innerHTML}
                </body>
                </html>
            `;
            
            // Buat blob dan download
            const blob = new Blob([html], {type: 'application/vnd.ms-excel'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `Laporan_${activeTab}_<?= date('Y-m-d', strtotime($tanggal)) ?>.xls`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>