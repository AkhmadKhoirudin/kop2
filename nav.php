<?php
session_start();
include __DIR__ . '/config.php';

// Cek session
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ./login/login.php");
    exit();
}

// Ambil data user
$id_anggota = $_SESSION['id_anggota'];
$nama_pengguna = $_SESSION['nama'];
$role = $_SESSION['role'];

// Ambil foto profil
$stmt = $conn->prepare("SELECT foto FROM anggota WHERE id_anggota = ?");
$stmt->bind_param("i", $id_anggota);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$foto = $data['foto'] ?? null;
$foto_path = "./upload/foto/" . ($foto ?? 'default.png');
$foto_ada = $foto && file_exists($foto_path);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard PMA</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Scrollbar styling */
    .nav-scroll {
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #4f46e5 #312e81;
    }
    .nav-scroll::-webkit-scrollbar {
      width: 6px;
    }
    .nav-scroll::-webkit-scrollbar-track {
      background: #312e81;
    }
    .nav-scroll::-webkit-scrollbar-thumb {
      background-color: #4f46e5;
      border-radius: 3px;
    }
    
    /* Mobile sidebar */
    .mobile-sidebar {
      transform: translateX(-100%);
      transition: transform 0.3s ease-in-out;
      z-index: 50;
    }
    .mobile-sidebar.active {
      transform: translateX(0);
    }
    .sidebar-overlay {
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s;
      background: rgba(0,0,0,0.5);
      z-index: 40;
    }
    .sidebar-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .no-scroll {
      overflow: hidden;
    }
    
    /* Animation */
    [x-cloak] { display: none !important; }
  </style>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 font-sans">
  <!-- Mobile Sidebar Overlay -->
  <div id="sidebar-overlay" class="fixed inset-0 sidebar-overlay" onclick="closeMobileSidebar()"></div>

  <div class="flex h-screen">
    <!-- Desktop Sidebar -->
    <div class="hidden md:flex md:flex-shrink-0">
      <div class="flex flex-col w-64 bg-indigo-800 text-white">
        <!-- Logo -->
       <div class="flex items-center justify-center h-16 px-4 bg-indigo-800">
       
        <img src="./11logo.png" alt="Logo PMA" class=" h-12 w-auto mr-3">
        <span class="text-xl font-bold">KSPPS PMA</span>
      </div>

        <!-- Garis Pemisah -->
        <div class="border-t border-indigo-700 mx-4 my-2"></div>

        <!-- Menu Navigasi -->
        <nav class="flex-1 px-4 py-4 overflow-y-auto nav-scroll" x-data="{ activeMenu: null }">
          
          <!-- Dashboard -->
          <a href="#" onclick="navigateTo('./home/home.php')" class="flex items-center px-4 py-2 text-white hover:bg-indigo-700 rounded-lg transition-colors">
            <i class="fas fa-home mr-3"></i>
            <span>Dashboard</span>
          </a>

          <!-- Simpanan -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'simpanan' ? null : 'simpanan'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-wallet mr-3"></i>
                <span>Simpanan</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'simpanan'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'simpanan'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./simpanan/transaksi_simpanan.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Simpanan
              </a>
              <a href="#" onclick="navigateTo('./simpanan/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Simpanan
              </a>
            </div>
          </div>

          <!-- Pinjaman -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'pinjaman' ? null : 'pinjaman'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-hand-holding-usd mr-3"></i>
                <span>Pinjaman</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'pinjaman'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'pinjaman'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./pinjaman/transaksi_pinjaman.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Pinjaman
              </a>
              <a href="#" onclick="navigateTo('./pinjaman/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Pinjaman
              </a>
            </div>
          </div>

          <!-- Penarikan -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'penarikan' ? null : 'penarikan'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-money-bill-wave mr-3"></i>
                <span>Penarikan</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'penarikan'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'penarikan'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./penarikan/penarikan.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Penarikan
              </a>
              <a href="#" onclick="navigateTo('./penarikan/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Penarikan
              </a>
            </div>
          </div>

          <!-- Angsuran -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'angsuran' ? null : 'angsuran'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-receipt mr-3"></i>
                <span>Angsuran</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'angsuran'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'angsuran'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./angsuran/angsuran.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Angsuran
              </a>
              <a href="#" onclick="navigateTo('./angsuran/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Angsuran
              </a>
            </div>
          </div>

          <!-- User Management (Admin Only) -->
<!-- Laporan -->
            <div class="relative">
                <button @click="activeMenu = activeMenu === 'laporan' ? null : 'laporan'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-file-invoice-dollar mr-3"></i>
                        <span>Laporan</span>
                    </div>
                    <i :class="{'rotate-90': activeMenu === 'laporan'}" class="fas fa-chevron-right text-xs transition-transform"></i>
                </button>
                <div x-show="activeMenu === 'laporan'" x-transition class="pl-8 mt-1 space-y-1">
                    <a href="#" onclick="navigateTo('./laporan/harian_laporan.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                        <i class="fas fa-calendar-day mr-2"></i>
                        Laporan Harian
                    </a>
                    <a href="#" onclick="navigateTo('./laporan/admin.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                        <i class="fas fa-calendar-month mr-2"></i>
                        Laporan Bulanan
                    </a>
                </div>
            </div>
          <?php if ($role === 'admin'): ?>
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'user' ? null : 'user'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-user-cog mr-3"></i>
                <span>User Management</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'user'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'user'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./users/creat.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-user-edit mr-2"></i>
                Pendaftaran
              </a>
              <a href="#" onclick="navigateTo('./users/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar User
              </a>
               <a href="#" onclick="navigateTo('./users/informasi.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                data tabungan
              </a>
            </div>
          </div>
          <?php endif; ?>
        </nav>

        <!-- Footer Sidebar -->
        <div class="p-4 border-t border-indigo-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <?php if ($foto_ada): ?>
                <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto Profil" class="w-10 h-10 rounded-full object-cover">
              <?php else: ?>
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200">
                  <i class="fa-solid fa-user text-xl text-gray-500"></i>
                </div>
              <?php endif; ?>
              <div class="ml-3">
                <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama_pengguna) ?></p>
                <p class="text-xs text-indigo-200"><?= ucfirst($role) ?></p>
              </div>
            </div>
            <div class="relative">
              <button onclick="toggleUserMenu('desktop')" class="text-white focus:outline-none">
                <i class="fa-solid fa-ellipsis-vertical text-lg"></i>
              </button>
              <div id="user-menu" class="hidden absolute right-0 bottom-full mb-2 w-44 bg-white rounded shadow-lg z-50">
                <a href="#" onclick="navigateTo('./users/profile.php')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  <i class="fa-solid fa-gear mr-2"></i>Pengaturan Akun
                </a>
                <a href="./login/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                  <i class="fa-solid fa-right-from-bracket mr-2"></i>Logout
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Sidebar (Popup) -->
    <div id="mobile-sidebar" class="fixed inset-y-0 left-0 w-64 bg-indigo-800 text-white mobile-sidebar md:hidden" x-data="{ activeMenu: null }">
      <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between h-16 px-4 bg-white">
          <img src="./logo-removebg-preview.png" alt="Logo PMA" class="h-12 w-auto mr-3">
          <span class="text-xl font-bold  text-indigo-500">KSPPS PMA</span>
          <button onclick="closeMobileSidebar()" class="p-2 text-white hover:text-indigo-200">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Menu Content -->
        <nav class="flex-1 px-4 py-4 overflow-y-auto nav-scroll">
          <!-- Dashboard -->
          <a href="#" onclick="navigateTo('./home/home.php')" class="flex items-center px-4 py-2 text-white hover:bg-indigo-700 rounded-lg transition-colors">
            <i class="fas fa-home mr-3"></i>
            <span>Dashboard</span>
          </a>

          <!-- Simpanan -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'simpanan' ? null : 'simpanan'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-wallet mr-3"></i>
                <span>Simpanan</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'simpanan'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'simpanan'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./simpanan/transaksi_simpanan.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Simpanan
              </a>
              <a href="#" onclick="navigateTo('./simpanan/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Simpanan
              </a>
            </div>
          </div>

          <!-- Pinjaman -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'pinjaman' ? null : 'pinjaman'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-hand-holding-usd mr-3"></i>
                <span>Pinjaman</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'pinjaman'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'pinjaman'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./pinjaman/transaksi_pinjaman.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Pinjaman
              </a>
              <a href="#" onclick="navigateTo('./pinjaman/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Pinjaman
              </a>
            </div>
          </div>

          <!-- Penarikan -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'penarikan' ? null : 'penarikan'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-money-bill-wave mr-3"></i>
                <span>Penarikan</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'penarikan'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'penarikan'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./penarikan/penarikan.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Penarikan
              </a>
              <a href="#" onclick="navigateTo('./penarikan/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Penarikan
              </a>
            </div>
          </div>

          <!-- Angsuran -->
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'angsuran' ? null : 'angsuran'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-receipt mr-3"></i>
                <span>Angsuran</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'angsuran'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'angsuran'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./angsuran/angsuran.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Angsuran
              </a>
              <a href="#" onclick="navigateTo('./angsuran/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Angsuran
              </a>
            </div>
          </div>

          <!-- User Management (Admin Only) -->
          <?php if ($role === 'admin'): ?>
          <div class="relative">
            <button @click="activeMenu = activeMenu === 'user' ? null : 'user'" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-user-cog mr-3"></i>
                <span>User Management</span>
              </div>
              <i :class="{'rotate-90': activeMenu === 'user'}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="activeMenu === 'user'" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./users/creat.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-user-edit mr-2"></i>
                Pendaftaran
              </a>
              <a href="#" onclick="navigateTo('./users/list.php')" class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar User
              </a>
            </div>
          </div>
          <?php endif; ?>
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-indigo-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <?php if ($foto_ada): ?>
                <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto Profil" class="w-10 h-10 rounded-full object-cover">
              <?php else: ?>
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200">
                  <i class="fa-solid fa-user text-xl text-gray-500"></i>
                </div>
              <?php endif; ?>
              <div class="ml-3">
                <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama_pengguna) ?></p>
                <p class="text-xs text-indigo-200"><?= ucfirst($role) ?></p>
              </div>
            </div>
            <div class="relative">
              <button onclick="toggleUserMenu('mobile')" class="text-white focus:outline-none">
                <i class="fa-solid fa-ellipsis-vertical text-lg"></i>
              </button>
              <div id="mobile-user-menu" class="hidden absolute right-0 bottom-full mb-2 w-44 bg-white rounded shadow-lg z-50">
                <a href="#" onclick="navigateTo('./users/profile.php')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  <i class="fa-solid fa-gear mr-2"></i>Pengaturan Akun
                </a>
                <a href="./login/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                  <i class="fa-solid fa-right-from-bracket mr-2"></i>Logout
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Header -->
      <header class="flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200 shadow-sm">
        <!-- Mobile Menu Button -->
        <button class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none" onclick="openMobileSidebar()">
          <i class="fas fa-bars fa-lg"></i>
        </button>

        <!-- Right Menu -->
        <div class="flex items-center ml-auto space-x-4">
          <!-- Notification -->
          <div class="relative">
            <button onclick="toggleDropdown()" class="relative text-gray-500 hover:text-gray-700 focus:outline-none">
              <i class="fas fa-bell fa-lg"></i>
              <span id="badge-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-semibold rounded-full w-5 h-5 flex items-center justify-center shadow hidden">
                0
              </span>
            </button>
            <div id="popup-notif" class="hidden absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
              <div class="p-3 border-b">
                <h3 class="text-sm font-semibold text-gray-800">Notifikasi</h3>
              </div>
              <ul id="list-notifikasi" class="max-h-80 overflow-y-auto">
                <!-- Notifikasi akan dimuat di sini -->
              </ul>
              <div class="p-2 border-t text-center">
                <button id="baca-semua" class="text-sm text-indigo-600 hover:underline"> semua  </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-hidden bg-gray-50">
        <iframe id="konten-frame" src="./home/home.php" frameborder="0" class="w-full h-full bg-white rounded"></iframe>
      </main>
    </div>
  </div>

  <script>
    // --- UTILITY FUNCTIONS ---
    function formatRupiah(angka) {
      return new Intl.NumberFormat("id-ID").format(angka);
    }

    function formatDate(dateString) {
      const options = { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    }

    // --- SIDEBAR FUNCTIONS ---
    function openMobileSidebar() {
      document.getElementById('mobile-sidebar').classList.add('active');
      document.getElementById('sidebar-overlay').classList.add('active');
      document.body.classList.add('no-scroll');
    }

    function closeMobileSidebar() {
      document.getElementById('mobile-sidebar').classList.remove('active');
      document.getElementById('sidebar-overlay').classList.remove('active');
      document.body.classList.remove('no-scroll');
    }

    // --- NAVIGATION ---
    function navigateTo(url) {
      console.log('Navigasi ke:', url); // Debug log
      const frame = document.getElementById("konten-frame");
      if (frame) {
        // Hapus src terlebih dahulu untuk memastikan reload
        frame.src = "about:blank";
        // Beri sedikit delay sebelum mengatur src baru
        setTimeout(() => {
          frame.src = url;
        }, 100);
        
        if (window.innerWidth < 768) {
          closeMobileSidebar();
        }
      } else {
        console.error('Frame dengan ID "konten-frame" tidak ditemukan');
        // Jika frame tidak ditemukan, coba buka di tab baru
        window.open(url, '_blank');
      }
    }

    // --- DROPDOWN & MENU ---
    function toggleUserMenu(type = 'desktop') {
      const menuId = type === 'mobile' ? 'mobile-user-menu' : 'user-menu';
      const menu = document.getElementById(menuId);
      if (menu) {
        menu.classList.toggle("hidden");
      }
    }

    function toggleDropdown() {
      const dropdown = document.getElementById("popup-notif");
      if (dropdown) {
        dropdown.classList.toggle("hidden");
        if (!dropdown.classList.contains("hidden")) {
          fetchNotifikasi(); // Refresh on open
        }
      }
    }
    
    function closeAllDropdowns() {
      const elements = {
        'popup-notif': document.getElementById("popup-notif"),
        'user-menu': document.getElementById("user-menu"),
        'mobile-user-menu': document.getElementById("mobile-user-menu")
      };
      
      for (const key in elements) {
        if (elements[key]) {
          elements[key].classList.add('hidden');
        }
      }
    }

    function fetchNotifikasi() {
      fetch("./pesan/api_pesan.php")
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(result => {
            const list = document.getElementById("list-notifikasi");
            const badge = document.getElementById("badge-count");
            list.innerHTML = "";

            if (result.status === "success" && result.data.length > 0) {
                const unreadCount = result.data.filter(item => item.status === 'belum').length;

                if (badge) {
                    badge.textContent = unreadCount;
                    badge.classList.toggle("hidden", unreadCount === 0);
                }

                // Urutkan notifikasi: yang belum dibaca di atas, lalu berdasarkan tanggal
                const sorted = result.data.sort((a, b) => {
                    if (a.status === 'belum' && b.status !== 'belum') return -1;
                    if (a.status !== 'belum' && b.status === 'belum') return 1;
                    return new Date(b.tanggal) - new Date(a.tanggal);
                });

                // Tampilkan hingga 10 notifikasi terbaru
                const recent = sorted.slice(0, 10);
                recent.forEach(item => {
                    const notificationItem = createNotificationItem(item);
                    list.appendChild(notificationItem);
                });

            } else {
                list.innerHTML = `<li class="text-sm text-gray-500 py-4 text-center">Tidak ada notifikasi</li>`;
                if (badge) {
                    badge.textContent = '0';
                    badge.classList.add("hidden");
                }
            }
        })
        .catch(err => {
          console.error("Error fetching notifications:", err);
          document.getElementById("list-notifikasi").innerHTML = 
            `<li class="text-sm text-red-500 py-4 text-center">Gagal memuat notifikasi.</li>`;
        });
    }

    function createNotificationItem(item) {
      const li = document.createElement("li");
      li.className = `border-b border-gray-100 ${item.status === 'belum' ? 'bg-indigo-50' : ''}`;
      
      const link = document.createElement("a");
      link.href = "#";
      link.className = "block px-4 py-3 hover:bg-gray-50";
      
      // Tambahkan event handler klik
      // Ambil ID yang benar dari notifikasi
      let notifId;
      switch(parseInt(item.versi)) {
        case 1:
          notifId = item.id;
          break;
        case 2:
          notifId = item.id_pinjaman;
          break;
        case 3:
          notifId = item.id_penarikan;
          break;
        case 4:
          notifId = item.id_angsuran;
          break;
        default:
          notifId = item.id;
      }
      
      console.log('Notifikasi item:', item);
      console.log('Notifikasi ID:', notifId);

      link.onclick = (e) => {
        e.preventDefault();
        console.log('Notifikasi diklik:', item);
        // Hanya update status jika belum dibaca
        if (item.status === 'belum') {
            updateNotificationStatus(notifId, () => redirectBasedOnNotification(item));
        } else {
            redirectBasedOnNotification(item);
        }
      };
      
      let iconClass = "fas fa-bell";
      if (item.versi == 1) iconClass = "fas fa-wallet";
      else if (item.versi == 2) iconClass = "fas fa-hand-holding-usd";
      else if (item.versi == 3) iconClass = "fas fa-money-bill-wave";
      else if (item.versi == 4) iconClass = "fas fa-receipt";
      
      let message = "Notifikasi sistem";
      if (item.jumlah) {
          if (item.versi == 1) message = `Simpanan baru: Rp ${formatRupiah(item.jumlah)}`;
          else if (item.versi == 2) message = `Pinjaman baru: Rp ${formatRupiah(item.jumlah)}`;
          else if (item.versi == 3) message = `Penarikan: Rp ${formatRupiah(item.jumlah)}`;
          else if (item.versi == 4) message = `Angsuran: Rp ${formatRupiah(item.jumlah)}`;
      }
      
      link.innerHTML = `
        <div class="flex items-start">
          <div class="flex-shrink-0 pt-1">
            <i class="${iconClass} text-indigo-600"></i>
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm font-medium ${item.status === 'belum' ? 'text-indigo-800' : 'text-gray-700'}">
              ${message}
            </p>
            <p class="text-xs text-gray-500 mt-1">
              ${formatDate(item.tanggal)}
            </p>
          </div>
          ${item.status === 'belum' ? '<span class="flex-shrink-0 mt-1 ml-2 inline-block h-2 w-2 rounded-full bg-indigo-600"></span>' : ''}
        </div>
      `;
      
      li.appendChild(link);
      return li;
    }

    function updateNotificationStatus(id, callback) {
      fetch('./pesan/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
      })
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success') {
          // Kurangi jumlah badge secara langsung untuk responsivitas
          const badge = document.getElementById("badge-count");
          let currentCount = parseInt(badge.textContent);
          if (currentCount > 0) {
              badge.textContent = currentCount - 1;
          }
          if (currentCount - 1 <= 0) {
              badge.classList.add("hidden");
          }
          
          fetchNotifikasi(); // Sinkronkan dengan server
          if (callback) callback();
        }
      })
      .catch(err => console.error("Error updating notification:", err));
    }

    function markAllNotificationsAsRead() {
      fetch('./pesan/update_status_all.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'aksi=baca-semua'
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          fetchNotifikasi(); // Refresh list notifikasi
        } else {
          console.error("Gagal menandai semua notifikasi:", data.message);
        }
      })
      .catch(err => console.error("Error marking all as read:", err));
    }

    function redirectBasedOnNotification(notif) {
      let url = './home/home.php';
      let id_transaksi;
      
      console.log('Notifikasi diterima:', notif); // Debug log
      
      switch(parseInt(notif.versi)) {
        case 1:
          id_transaksi = notif.id;
          url = `./pesan/slip.php?versi=1&id=${id_transaksi}`;
          break;
        case 2:
          id_transaksi = notif.id_pinjaman;
          url = `./pesan/slip.php?versi=2&id=${id_transaksi}`;
          break;
        case 3:
          id_transaksi = notif.id_penarikan;
          url = `./pesan/slip.php?versi=3&id=${id_transaksi}`;
          break;
        case 4:
          id_transaksi = notif.id_angsuran;
          url = `./pesan/slip.php?versi=4&id=${id_transaksi}`;
          break;
        default:
          console.error('Versi notifikasi tidak dikenali:', notif.versi);
          return;
      }
      
      console.log('Mengarahkan ke:', url); // Debug log
      console.log('ID transaksi:', id_transaksi); // Debug log
      
      // Hanya buka di iframe, tidak di tab baru
      navigateTo(url);
      closeAllDropdowns();
    }

    // --- EVENT LISTENERS ---
    document.addEventListener('DOMContentLoaded', () => {
      // Initial fetch
      fetchNotifikasi();
      
      // Auto-refresh notifications every 30 seconds
      setInterval(fetchNotifikasi, 30000);
      
      // Mark all as read button
      document.getElementById('baca-semua').addEventListener('click', markAllNotificationsAsRead);

      // Close dropdowns when clicking outside
      document.addEventListener("click", function(event) {
        const notifButton = event.target.closest("button[onclick='toggleDropdown()']");
        const notifPopup = event.target.closest("#popup-notif");
        
        const userMenuButton = event.target.closest("button[onclick*='toggleUserMenu']");
        const userMenuPopup = event.target.closest("#user-menu");
        const mobileUserMenuPopup = event.target.closest("#mobile-user-menu");

        if (!notifButton && !notifPopup && !userMenuButton && !userMenuPopup && !mobileUserMenuPopup) {
            closeAllDropdowns();
        }
      });

      // Close mobile sidebar on resize
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
          closeMobileSidebar();
        }
      });
    });
  </script>
</body>
</html>
