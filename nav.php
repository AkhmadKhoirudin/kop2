<?php
session_start();
include __DIR__ . '/config.php';

if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ./login/login.php");
    exit();
}

$id_anggota = $_SESSION['id_anggota'];
$nama_pengguna = $_SESSION['nama'];

// Ambil data foto dari database
$stmt = $conn->prepare("SELECT foto FROM anggota WHERE id_anggota = ?");
$stmt->bind_param("i", $id_anggota);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$foto = $data['foto'] ?? null;
$foto_path = "./upload/foto/" . $foto;
$foto_ada = $foto && file_exists($foto_path);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Koperasi - Dashboard</title>
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
    
    /* Mobile sidebar styles */
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
    
    /* Animation for dropdown */
    [x-cloak] { display: none !important; }
    .dropdown-enter-active,
    .dropdown-leave-active {
      transition: all 0.2s ease;
    }
    .dropdown-enter-from,
    .dropdown-leave-to {
      opacity: 0;
      transform: translateY(-10px);
    }
  </style>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 font-sans">
  <!-- Mobile Sidebar Overlay -->
  <div id="sidebar-overlay" class="fixed inset-0 sidebar-overlay" onclick="closeMobileSidebar()"></div>

  <div class="flex h-screen">
    <!-- Desktop Sidebar (Hidden on mobile) -->
    <div class="hidden md:flex md:flex-shrink-0">
      <div class="flex flex-col w-64 bg-indigo-800 text-white">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 px-4 bg-indigo-900">
          <span class="text-xl font-bold">Koperasi</span>
        </div>

        <!-- Menu Navigasi -->
        <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto nav-scroll">
          <!-- Dashboard -->
          <a href="#Dashboard" onclick="navigateTo('./home/home.php')" class="flex items-center px-4 py-2 text-white hover:bg-indigo-700 rounded-lg transition-colors">
            <i class="fas fa-home mr-3"></i>
            <span>Dashboard</span>
          </a>

          <!-- Simpanan -->
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-wallet mr-3"></i>
                <span>Simpanan</span>
              </div>
              <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./simpanan/transaksi_simpanan.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Simpanan
              </a>
              <a href="#" onclick="navigateTo('./simpanan/list.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Simpanan
              </a>
            </div>
          </div>

          <!-- Pinjaman -->
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-hand-holding-usd mr-3"></i>
                <span>Pinjaman</span>
              </div>
              <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./pinjaman/transaksi_pinjaman.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Pinjaman
              </a>
              <a href="#" onclick="navigateTo('./pinjaman/list.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Pinjaman
              </a>
            </div>
          </div>

          <!-- Penarikan -->
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-money-bill-wave mr-3"></i>
                <span>Penarikan</span>
              </div>
              <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./penarikan/penarikan.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Penarikan
              </a>
              <a href="#" onclick="navigateTo('./penarikan/list.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Penarikan
              </a>
            </div>
          </div>

          <!-- Angsuran -->
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-receipt mr-3"></i>
                <span>Angsuran</span>
              </div>
              <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./angsuran/angsuran.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-exchange-alt mr-2"></i>
                Transaksi Angsuran
              </a>
              <a href="#" onclick="navigateTo('./angsuran/list.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar Angsuran
              </a>
            </div>
          </div>

          <!-- User Management -->
          <?php if ($_SESSION['role'] === 'admin'): ?>
          <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-user-cog mr-3"></i>
                <span>User Management</span>
              </div>
              <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
            </button>
            <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
              <a href="#" onclick="navigateTo('./users/creat.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-user-edit mr-2"></i>
                Pendaftaran
              </a>
              <a href="#" onclick="navigateTo('./users/list.php')" 
                 class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                <i class="fas fa-list mr-2"></i>
                Daftar User
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
                <p class="text-xs text-indigo-200"><?= ucfirst($_SESSION['role']) ?></p>
              </div>
            </div>
            <div class="relative">
              <button onclick="toggleUserMenu('desktop')" class="text-white focus:outline-none">
                <i class="fa-solid fa-ellipsis-vertical text-lg"></i>
              </button>
              <div id="user-menu" class="hidden absolute right-0 bottom-full mb-2 w-44 bg-white rounded shadow-lg z-50">
                <a onclick="navigateTo('./users/profile.php')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
    <div id="mobile-sidebar" class="fixed inset-y-0 left-0 w-64 bg-indigo-800 text-white mobile-sidebar md:hidden">
      <div class="flex flex-col h-full">
        <!-- Mobile Sidebar Header -->
        <div class="flex items-center justify-between h-16 px-4 bg-indigo-900">
          <span class="text-xl font-bold">Koperasi</span>
          <button onclick="closeMobileSidebar()" class="p-2 text-white hover:text-indigo-200">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Mobile Sidebar Content -->
        <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto nav-scroll">
          <!-- Dashboard -->
          <a href="#Dashboard" onclick="navigateTo('./home/home.php')" class="flex items-center px-4 py-2 text-white hover:bg-indigo-700 rounded-lg transition-colors">
            <i class="fas fa-home mr-3"></i>
            <span>Dashboard</span>
          </a>

          <!-- Simpanan -->
          <!-- Menu Simpanan -->
            <div x-data="{ open: false }" class="relative">
              <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
                <div class="flex items-center">
                  <i class="fas fa-wallet mr-3"></i>
                  <span>Simpanan</span>
                </div>
                <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
              </button>
              <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                <a href="#" onclick="navigateTo('./simpanan/transaksi_simpanan.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-exchange-alt mr-2"></i>
                  Transaksi Simpanan
                </a>
                <a href="#" onclick="navigateTo('./simpanan/list.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-list mr-2"></i>
                  Daftar Simpanan
                </a>
              </div>
            </div>

            <!-- Menu Pinjaman -->
            <div x-data="{ open: false }" class="relative">
              <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
                <div class="flex items-center">
                  <i class="fas fa-hand-holding-usd mr-3"></i>
                  <span>Pinjaman</span>
                </div>
                <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
              </button>
              <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                <a href="#" onclick="navigateTo('./pinjaman/transaksi_pinjaman.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-exchange-alt mr-2"></i>
                  Transaksi Pinjaman
                </a>
                <a href="#" onclick="navigateTo('./pinjaman/list.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-list mr-2"></i>
                  Daftar Pinjaman
                </a>
              </div>
            </div>

            <!-- Menu Penarikan -->
            <div x-data="{ open: false }" class="relative">
              <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
                <div class="flex items-center">
                  <i class="fas fa-money-bill-wave mr-3"></i>
                  <span>Penarikan</span>
                </div>
                <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
              </button>
              <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                <a href="#" onclick="navigateTo('./penarikan/penarikan.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-exchange-alt mr-2"></i>
                  Transaksi Penarikan
                </a>
                <a href="#" onclick="navigateTo('./penarikan/list.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-list mr-2"></i>
                  Daftar Penarikan
                </a>
              </div>
            </div>

            <!-- Menu Angsuran -->
            <div x-data="{ open: false }" class="relative">
              <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
                <div class="flex items-center">
                  <i class="fas fa-receipt mr-3"></i>
                  <span>Angsuran</span>
                </div>
                <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
              </button>
              <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                <a href="#" onclick="navigateTo('./angsuran/angsuran.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-exchange-alt mr-2"></i>
                  Transaksi Angsuran
                </a>
                <a href="#" onclick="navigateTo('./angsuran/list.php')" 
                  class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
                  <i class="fas fa-list mr-2"></i>
                  Daftar Angsuran
                </a>
              </div>
            </div>
          <!-- ... (menu lainnya sama dengan desktop) ... -->
        </nav>

        <!-- Mobile Sidebar Footer -->
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
                <p class="text-xs text-indigo-200"><?= ucfirst($_SESSION['role']) ?></p>
              </div>
            </div>
            <div class="relative">
              <button onclick="toggleUserMenu('mobile')" class="text-white focus:outline-none">
                <i class="fa-solid fa-ellipsis-vertical text-lg"></i>
              </button>
              <div id="mobile-user-menu" class="hidden absolute right-0 bottom-full mb-2 w-44 bg-white rounded shadow-lg z-50">
                <a onclick="navigateTo('./users/profile.php')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
              <span id="badge-count"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-semibold rounded-full w-5 h-5 flex items-center justify-center shadow hidden">
                3
              </span>
            </button>
            <!-- Notification Dropdown -->
            <div id="popup-notif"
                 class="absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-lg shadow-lg hidden z-50">
              <div class="p-4">
                <div class="flex justify-between items-center mb-3">
                  <h3 class="text-sm font-semibold text-gray-800">Notifikasi</h3>
                  <button onclick="tutupDropdown()" class="text-gray-400 hover:text-gray-600 text-sm">
                    <i class="fas fa-times"></i>
                  </button>
                </div>

                <ul id="list-notifikasi" class="space-y-2 max-h-60 overflow-y-auto text-sm text-gray-700">
                  <!-- Notifikasi akan diisi lewat JS -->
                </ul>

                <div class="flex justify-between mt-3">
                  <button id="baca-semua"
                          class="text-blue-600 hover:underline text-xs font-medium">
                    Baca Semua
                  </button>
                  <button id="lihat-semua-pesan"
                          onclick="navigateTo('./pesan/all.php')"
                          class="text-blue-600 hover:underline text-xs font-medium">
                    Lihat Semua
                  </button>
                </div>
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
    // Mobile Sidebar Functions
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

    // Navigation function
    function navigateTo(url) {
      document.getElementById("konten-frame").src = url;
      if (window.innerWidth < 768) {
        closeMobileSidebar();
      }
    }

    // User menu toggle
    function toggleUserMenu(type = 'desktop') {
      const menuId = type === 'mobile' ? 'mobile-user-menu' : 'user-menu';
      const menu = document.getElementById(menuId);
      menu.classList.toggle("hidden");
    }

    // Notification dropdown
    function toggleDropdown() {
      const dropdown = document.getElementById("popup-notif");
      dropdown.classList.toggle("hidden");
    }

    function tutupDropdown() {
      document.getElementById("popup-notif").classList.add("hidden");
    }

    // Close all dropdowns when clicking outside
    document.addEventListener("click", function(event) {
      const isNotification = event.target.closest("#popup-notif") || 
                           event.target.closest("button[onclick='toggleDropdown()']");
      const isUserMenu = event.target.closest("#user-menu") || 
                        event.target.closest("#mobile-user-menu") ||
                        event.target.closest("button[onclick*='toggleUserMenu']");
      const isSidebar = event.target.closest("#mobile-sidebar");
      const isSidebarButton = event.target.closest("button[onclick='openMobileSidebar()']");
      
      if (!isNotification && !isUserMenu) {
        tutupDropdown();
        document.getElementById("user-menu").classList.add("hidden");
        document.getElementById("mobile-user-menu").classList.add("hidden");
      }
      
      if (!isSidebar && !isSidebarButton && window.innerWidth < 768) {
        closeMobileSidebar();
      }
    });

    // Close mobile sidebar when window is resized to desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 768) {
        closeMobileSidebar();
      }
    });

    // Notification functions
    document.addEventListener('DOMContentLoaded', () => {
      fetchNotifikasi();
      
      document.getElementById('baca-semua').addEventListener('click', () => {
        fetch('./pesan/update_status_all.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ aksi: 'baca-semua' })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            fetchNotifikasi();
          }
        })
        .catch(err => console.error("Error:", err));
      });
    });

    function fetchNotifikasi() {
      fetch("./pesan/api_pesan.php")
        .then(res => res.json())
        .then(result => {
          const list = document.getElementById("list-notifikasi");
          list.innerHTML = "";
          
          if (result.status === "success" && result.data.length > 0) {
            const unreadCount = result.data.filter(item => item.status === 'belum').length;
            const badge = document.getElementById('badge-count');
            
            if (badge) {
              badge.textContent = unreadCount;
              badge.classList.toggle("hidden", unreadCount === 0);
            }
            
            const sorted = result.data.sort((a, b) => {
              if (a.status === 'belum' && b.status !== 'belum') return -1;
              if (a.status !== 'belum' && b.status === 'belum') return 1;
              return 0;
            });
            
            const recent = sorted.slice(0, 5);
            
            recent.forEach(item => {
              let teks = "";
              switch (item.versi) {
                case 1: teks = `Simpanan: Rp ${formatRupiah(item.jumlah)}`; break;
                case 2: teks = `Pinjaman: Rp ${formatRupiah(item.jumlah)}`; break;
                case 3: teks = `Penarikan: Rp ${formatRupiah(item.jumlah)}`; break;
                case 4: teks = `Angsuran: Rp ${formatRupiah(item.jumlah)}`; break;
                default: teks = "Notifikasi tidak diketahui.";
              }
              
              const css = item.status === 'belum' ? "font-bold text-indigo-700" : "text-gray-600";
              list.innerHTML += `
                <li class="border-b pb-1 text-sm ${css}" onclick="updateStatusPesan(${item.id})">
                  ${item.tanggal} - ${teks}
                </li>`;
            });
          } else {
            list.innerHTML = "<li class='text-sm text-gray-500'>Tidak ada notifikasi baru.</li>";
            document.getElementById('badge-count').classList.add("hidden");
          }
        })
        .catch(err => {
          console.error("Fetch error:", err);
          document.getElementById("list-notifikasi").innerHTML = 
            "<li class='text-sm text-red-500'>Gagal memuat notifikasi.</li>";
        });
    }

    function updateStatusPesan(id) {
      fetch('./pesan/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
      })
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success') {
          fetchNotifikasi();
        }
      })
      .catch(err => console.error("Error:", err));
      tutupDropdown();
    }

    function formatRupiah(angka) {
      return new Intl.NumberFormat("id-ID").format(angka);
    }
  </script>
</body>
</html>