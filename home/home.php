
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Koperasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <div class="flex flex-col flex-1 overflow-hidden">
            <main class="flex-1 overflow-y-auto p-6">
                <section id="dashboard">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Overview</h2>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Anggota</p>
                                    <p class="text-2xl font-semibold text-gray-800" id="Total_Anggota"></p>
                                </div>
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-green-500">
                              <i class="fas fa-arrow-up mr-1" id="up_anggota"></i>   <!--  dari bulan lalu -->
                            </p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Simpanan</p>
                                    <p class="text-2xl font-semibold text-gray-800" id="Total_Simpanan"></p>
                                </div>
                                <div class="p-3 rounded-full bg-green-100 text-green-600">
                                    <i class="fas fa-wallet text-xl"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-green-500">
                              <i class="fas fa-arrow-up mr-1" id="up_simpanan"></i>   <!-- 8% dari bulan lalu -->
                            </p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Pinjaman Aktif</p>
                                    <p class="text-2xl font-semibold text-gray-800" id="Pinjaman_Aktif"></p>
                                </div>
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                    <i class="fas fa-hand-holding-usd text-xl"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-red-500">
                                <i class="fas fa-arrow-down mr-1" id="up_pinjaman"></i> 5% dari bulan lalu
                            </p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Angsuran Hari Ini</p>
                                    <p class="text-2xl font-semibold text-gray-800" id="angsuran_aktif"></p><!--total yang aktif atau belum lunas -->
                                </div>
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                    <i class="fas fa-calendar-day text-xl"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-equals mr-1" id="up_ansuran"></i> Sama dengan kemarin
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Trend Simpanan Bulanan</h3>
                            <canvas id="simpananChart" height="250"></canvas>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Distribusi Pinjaman per Produk</h3>
                            <canvas id="pinjamanChart" height="250"></canvas>
                        </div>
                    </div>                    
                </section>             

            </main>
        </div>
    </div>
<script>
// Inisialisasi chart dengan data dummy (seperti kode Anda)
const simpananCtx = document.getElementById('simpananChart').getContext('2d');
const simpananChart = new Chart(simpananCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Total Simpanan (juta)',
            data: [],
            backgroundColor: 'rgba(59, 130, 246, 0.05)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 2,
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: false }
        }
    }
});


const pinjamanCtx = document.getElementById('pinjamanChart').getContext('2d');
const pinjamanChart = new Chart(pinjamanCtx, {
    type: 'doughnut',
    data: {
        labels: [], // Akan diisi dari API
        datasets: [{
            data: [],
            backgroundColor: [
                'rgba(59, 130, 246, 0.7)',
                'rgba(16, 185, 129, 0.7)',
                'rgba(245, 158, 11, 0.7)',
                'rgba(139, 92, 246, 0.7)',
                'rgba(239, 68, 68, 0.7)',
                'rgba(34, 197, 94, 0.7)',  // tambahkan jika data lebih dari 5
                'rgba(168, 85, 247, 0.7)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});


// Fungsi untuk memuat data dashboard (kode baru)
async function loadDashboardData() {
    try {
        const response = await fetch('dashboard_api.php');
        const data = await response.json();
        
        // Update statistik
        document.getElementById('Total_Anggota').textContent = data.stats.total_anggota;
        document.getElementById('Total_Simpanan').textContent = formatCurrency(data.stats.total_simpanan);
        document.getElementById('Pinjaman_Aktif').textContent = data.stats.pinjaman_aktif;
        document.getElementById('angsuran_aktif').textContent = data.stats.angsuran_hari_ini;
        
        // Update persentase perubahan
        updatePercentage('up_anggota', data.stats.persen_anggota);
        updatePercentage('up_simpanan', data.stats.persen_simpanan);
        updatePercentage('up_pinjaman', data.stats.persen_pinjaman);
        
        // Update chart simpanan
        simpananChart.data.labels = data.simpanan_chart.labels;
        simpananChart.data.datasets[0].data = data.simpanan_chart.data;
        simpananChart.update();
        
        // Update chart pinjaman
        pinjamanChart.data.labels = data.pinjaman_chart.labels;
        pinjamanChart.data.datasets[0].data = data.pinjaman_chart.data;
        pinjamanChart.update();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// Fungsi untuk memformat mata uang
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Fungsi untuk update persentase
function updatePercentage(elementId, percentage) {
    const element = document.getElementById(elementId);
    const parentElement = element.parentElement;
    
    if (percentage > 0) {
        element.className = 'fas fa-arrow-up mr-1';
        parentElement.className = 'mt-2 text-xs text-green-500';
        parentElement.innerHTML = `<i class="fas fa-arrow-up mr-1"></i> ${Math.abs(percentage)}% dari bulan lalu`;
    } else if (percentage < 0) {
        element.className = 'fas fa-arrow-down mr-1';
        parentElement.className = 'mt-2 text-xs text-red-500';
        parentElement.innerHTML = `<i class="fas fa-arrow-down mr-1"></i> ${Math.abs(percentage)}% dari bulan lalu`;
    } else {
        element.className = 'fas fa-equals mr-1';
        parentElement.className = 'mt-2 text-xs text-gray-500';
        parentElement.innerHTML = '<i class="fas fa-equals mr-1"></i> Sama dengan bulan lalu';
    }
}

// Panggil fungsi load data saat halaman dimuat
document.addEventListener('DOMContentLoaded', loadDashboardData);

// Refresh data setiap 1 menit
setInterval(loadDashboardData, 60000);
</script>
</body>
</html>