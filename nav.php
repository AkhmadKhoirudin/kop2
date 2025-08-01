<?php
session_start();
include __DIR__ . '/config.php';


if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ./login/login.php"); // arahkan ke login jika belum login
    exit();
}

$id_anggota = $_SESSION['id_anggota'];
$nama_pengguna = $_SESSION['nama']; // bisa langsung ambil juga

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
  <title>Website dengan Tailwind</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .nav-scroll {
        overflow: auto;         /* atau scroll */
        scrollbar-width: none;  /* Firefox */
        -ms-overflow-style: none; /* IE 10+ */
      }

      .nav-scroll::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
      }

  </style>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <div class="hidden md:flex md:flex-shrink-0">
      <div class="flex flex-col w-64 bg-indigo-800 text-white">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 px-4 bg-indigo-900">
          <img src="" alt="">
          <span class="text-xl font-bold">koperasi </span>
        </div>

<!-- Menu Navigasi -->
<nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto overflow-hidden nav-scroll">
  <!-- Dashboard -->
  <a href="#Dashboard" onclick="tampilkanLaporan('./home/home.php')" class="flex items-center px-4 py-2 text-white hover:bg-indigo-700 rounded-lg transition-colors">
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
    <div x-show="open" class="pl-8 mt-1 space-y-1">
      <a href="#" onclick="tampilkanLaporan('./simpanan/transaksi_simpanan.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-exchange-alt mr-2"></i>
        Transaksi Simpanan
      </a>
      <a href="#" onclick="tampilkanLaporan('./simpanan/list.php')" 
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
    <div x-show="open" class="pl-8 mt-1 space-y-1">
      <a href="#" onclick="tampilkanLaporan('./pinjaman/transaksi_pinjaman.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-exchange-alt mr-2"></i>
        Transaksi Pinjaman
      </a>
      <a href="#" onclick="tampilkanLaporan('./pinjaman/list.php')" 
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
    <div x-show="open" class="pl-8 mt-1 space-y-1">
      <a href="#" onclick="tampilkanLaporan('./penarikan/penarikan.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-exchange-alt mr-2"></i>
        Transaksi Penarikan
      </a>
      <a href="#" onclick="tampilkanLaporan('./penarikan/list.php')" 
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
    <div x-show="open" class="pl-8 mt-1 space-y-1">
      <a href="#" onclick="tampilkanLaporan('./angsuran/angsuran.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-exchange-alt mr-2"></i>
        Transaksi Angsuran
      </a>
      <a href="#" onclick="tampilkanLaporan('./angsuran/list.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-list mr-2"></i>
        Daftar Angsuran
      </a>
    </div>
  </div>

  <!-- User -->
  <div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2 text-white hover:bg-indigo-700 rounded-lg">
      <div class="flex items-center">
        <i class="fas fa-user-cog mr-3"></i>
        <span>User Management</span>
      </div>
      <i :class="{'rotate-90': open}" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open" class="pl-8 mt-1 space-y-1">
      <a href="#" onclick="tampilkanLaporan('./users/creat.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-user-edit mr-2"></i>
        pendaftaran
      </a>
      <a href="#" onclick="tampilkanLaporan('./users/list.php')" 
         class="flex items-center px-3 py-2 text-sm text-indigo-100 hover:bg-indigo-600 rounded">
        <i class="fas fa-list mr-2"></i>
        Daftar User
      </a>
    </div>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        <!-- Footer Sidebar -->
        <div class="p-4 border-t border-indigo-700 relative">
          <div class="flex items-center justify-between">
            <div class="flex items-center" >
              <?php if ($foto_ada): ?>
                <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto Profil" class="w-10 h-10 rounded-full object-cover">
              <?php else: ?>
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200">
                  <i class="fa-solid fa-user text-xl text-gray-500"></i>
                </div>
              <?php endif; ?>
              <div class="ml-3">
                <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama_pengguna) ?></p>
              </div>
            </div>

            <!-- Dropdown Trigger -->
            <!-- Dropdown Trigger -->
<div class="relative" id="dropdown-container">
  <button onclick="toggleUserMenu()" class="text-white focus:outline-none">
    <i class="fa-solid fa-ellipsis-vertical text-lg"></i>
  </button>

  <!-- Dropdown Menu -->
  <div id="user-menu" class="hidden absolute right-0 bottom-full mb-2 w-44 bg-white rounded shadow-lg z-50">
    <a onclick="tampilkanLaporan('./users/profile.php')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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

    <!-- Konten Utama -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Header -->
<header class="flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200 shadow-sm">
  <!-- Tombol Sidebar (Mobile) -->
  <button class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none" onclick="toggleSidebar()">
    <i class="fas fa-bars fa-lg"></i>
  </button>

  <!-- Menu Kanan -->
  <div class="flex items-center ml-auto space-x-4">
    <!-- Notifikasi -->
    <div class="relative">
      <button onclick="toggleDropdown()" class="relative text-gray-500 hover:text-gray-700 focus:outline-none">
        <i class="fas fa-bell fa-lg"></i>
        <span id="badge-count"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-semibold rounded-full w-5 h-5 flex items-center justify-center shadow hidden">
          3
        </span>
      </button>

      <!-- Dropdown Notifikasi -->
      <div id="popup-notif"
           class="absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-lg shadow-lg hidden z-50 transition-all duration-200">
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
                    onclick="tampilkanLaporan('./pesan/all.php')"
                    class="text-blue-600 hover:underline text-xs font-medium">
              Lihat Semua
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>


      <!-- Konten -->
      <main class="flex-1 overflow-hidden bg-gray-50">
          <iframe id="konten-frame" src="./home/home.php" frameborder="0" class="w-full h-full bg-white rounded"></iframe>
        </main>

    </div>
  </div>

  <!-- Script -->
<script>

  document.addEventListener('DOMContentLoaded', () => {
    fetchNotifikasi(); // Memuat notifikasi awal saat DOM dimuat

    // Event: Tombol "Baca Semua"
    document.getElementById('baca-semua').addEventListener('click', () => {
      fetch('/../pesan/update_status_all.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ aksi: 'baca-semua' })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          fetchNotifikasi(); // Refresh daftar notifikasi setelah "Baca Semua"
        } else {
          alert('Gagal memperbarui notifikasi');
        }
      })
      .catch(err => console.error("Error saat menandai semua notifikasi:", err));
    });
  });

  let isNotifLoading = false; // Flag untuk mencegah multiple fetch

  function tampilkanLaporan(url) {
    document.getElementById("konten-frame").src = url;
  }

  function formatRupiah(angka) {
    return new Intl.NumberFormat("id-ID", {
      style: "decimal",
      minimumFractionDigits: 0
    }).format(angka);
  }

  const badge = document.getElementById('badge-count'); // Dapatkan elemen badge notifikasi

  // Fungsi untuk mengambil dan menampilkan notifikasi
  function fetchNotifikasi() {
    if (isNotifLoading) return; // Jangan fetch jika sedang loading
    isNotifLoading = true;

    fetch("./pesan/api_pesan.php")
      .then(res => res.json())
      .then(result => {
        const list = document.getElementById("list-notifikasi");
        list.innerHTML = ""; // Bersihkan daftar notifikasi

        let unreadCount = 0; // Inisialisasi hitungan notifikasi belum dibaca

        if (result.status === "success" && result.data.length > 0) {
          // Hitung notifikasi yang belum dibaca
          unreadCount = result.data.filter(item => item.status === 'belum').length;
          if (badge) {
            badge.textContent = unreadCount; // Perbarui badge
            if (unreadCount > 0) {
              badge.classList.remove("hidden"); // Tampilkan badge jika ada notifikasi
            } else {
              badge.classList.add("hidden"); // Sembunyikan badge jika tidak ada notifikasi
            }
          }

          // Urutkan: 'belum' di atas, lalu ambil 5 teratas
          const sorted = result.data.sort((a, b) => {
            if (a.status === 'belum' && b.status !== 'belum') return -1;
            if (a.status !== 'belum' && b.status === 'belum') return 1;
            return 0;
          });

          const recent = sorted.slice(0, 5); // Ambil 5 notifikasi terbaru/belum dibaca

          recent.forEach(item => {
            let teks = "";
            let id_unik = null;

            // Coba ambil ID valid (angka) dari berbagai properti
            if (!isNaN(parseInt(item.id))) {
              id_unik = parseInt(item.id);
            } else if (!isNaN(parseInt(item.id_angsuran))) {
              id_unik = parseInt(item.id_angsuran);
            } else if (!isNaN(parseInt(item.id_pinjaman))) {
              id_unik = parseInt(item.id_pinjaman);
            } else if (!isNaN(parseInt(item.id_penarikan))) {
              id_unik = parseInt(item.id_penarikan);
            }

            const isValidId = Number.isInteger(id_unik);

            // Buat teks notifikasi berdasarkan versi
            switch (item.versi) {
              case 1:
                teks = `Simpanan: Rp ${formatRupiah(item.jumlah)} (Anggota ID ${item.id_anggota})`;
                break;
              case 2:
                teks = `Pinjaman: Rp ${formatRupiah(item.jumlah)} (Tenor ${item.tenor} bulan)`;
                break;
              case 3:
                teks = `Penarikan: Rp ${formatRupiah(item.jumlah)} (Anggota ID ${item.id_anggota})`;
                break;
              case 4:
                teks = `Angsuran: Rp ${formatRupiah(item.jumlah)} (Pinjaman ID ${item.id_pinjaman ?? '-'})`;
                break;
              default:
                teks = "Notifikasi tidak diketahui.";
            }

            // Tentukan gaya CSS berdasarkan status notifikasi
            const css = item.status === 'belum' ? "font-bold text-indigo-700" : "text-gray-600";

            // Tampilkan item notifikasi
            if (isValidId) {
              list.innerHTML += `
                <li class="border-b pb-1 text-sm ${css}" style="cursor:pointer"
                    onclick="updateStatusPesan(${id_unik})" title="Klik untuk tandai sudah dibaca">
                  ${item.tanggal} - ${teks}
                </li>`;
                
            } else {
              list.innerHTML += `
                <li class="border-b pb-1 text-sm text-gray-400 cursor-not-allowed" title="ID tidak valid, tidak bisa diproses">
                  ${item.tanggal} - ${teks}
                </li>`;
            }
          });

        } else {
          list.innerHTML = "<li class='text-sm text-gray-500'>Tidak ada notifikasi baru.</li>";
          if (badge) {
            badge.textContent = 0; // Set badge ke 0 jika tidak ada notifikasi
            badge.classList.add("hidden"); // Sembunyikan badge
          }
        }
      })
      .catch(err => {
        document.getElementById("list-notifikasi").innerHTML =
          `<li class='text-sm text-red-500'>Gagal memuat notifikasi.</li>`;
        console.error("Fetch error:", err);
        if (badge) {
          badge.textContent = 0; // Set badge ke 0 jika terjadi error
          badge.classList.add("hidden"); // Sembunyikan badge
        }
      })
      .finally(() => {
        isNotifLoading = false;
      });
  }

  // Fungsi untuk menutup semua dropdown yang terbuka
  function closeAllDropdowns() {
    document.getElementById("popup-notif").classList.add("hidden");
    document.getElementById("user-menu").classList.add("hidden");
  }

  // Fungsi untuk mengaktifkan/menonaktifkan dropdown notifikasi
  function toggleDropdown() {
    const dropdown = document.getElementById("popup-notif");
    if (dropdown.classList.contains("hidden")) {
      closeAllDropdowns(); // Tutup dropdown lain sebelum membuka ini
      dropdown.classList.remove("hidden");
      fetchNotifikasi(); // Muat ulang notifikasi saat dropdown dibuka
    } else {
      dropdown.classList.add("hidden"); // Tutup jika sudah terbuka
    }
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
          fetchNotifikasi(); // Muat ulang notifikasi setelah pembaruan status
        }
      })
      .catch(err => console.error("Update status pesan gagal:", err));
      tutupDropdown() 
  }

  function tutupDropdown() {
    document.getElementById("popup-notif").classList.add("hidden");
  }

  // Event listener untuk menutup dropdown saat mengklik di luar
  document.addEventListener("click", function (event) {
    const notificationDropdown = document.getElementById("popup-notif");
    const userMenuDropdown = document.getElementById("user-menu");
    const notificationButton = event.target.closest("button[onclick='toggleDropdown()']");
    const userMenuButton = event.target.closest("button[onclick='toggleUserMenu()']");

    if (!notificationDropdown.contains(event.target) && !notificationButton &&
        !userMenuDropdown.contains(event.target) && !userMenuButton) {
      closeAllDropdowns();
    }
  });
</script>

<script>
  // Fungsi untuk mengaktifkan/menonaktifkan menu pengguna
  function toggleUserMenu() {
    const menu = document.getElementById("user-menu");
    if (menu.classList.contains("hidden")) {
      closeAllDropdowns(); // Tutup dropdown lain sebelum membuka ini
      menu.classList.remove("hidden");
    } else {
      menu.classList.add("hidden"); // Tutup jika sudah terbuka
    }
  }

  // Event listener untuk menutup semua dropdown saat mengklik di luar elemen dropdown atau tombolnya
  document.addEventListener("click", function (event) {
    const notificationDropdown = document.getElementById("popup-notif");
    const userMenuDropdown = document.getElementById("user-menu");
    const notificationButton = document.querySelector("button[onclick='toggleDropdown()']");
    const userMenuButton = document.querySelector("button[onclick='toggleUserMenu()']");

    // Pastikan tombol-tombolnya ditemukan sebelum memeriksa contains
    const clickedOnNotificationButton = notificationButton && notificationButton.contains(event.target);
    const clickedOnUserMenuButton = userMenuButton && userMenuButton.contains(event.target);

    // Periksa apakah klik terjadi di luar dropdown notifikasi DAN bukan pada tombol notifikasi
    const clickedOutsideNotifArea = !notificationDropdown.contains(event.target) && !clickedOnNotificationButton;
    // Periksa apakah klik terjadi di luar menu pengguna DAN bukan pada tombol menu pengguna
    const clickedOutsideUserMenuArea = !userMenuDropdown.contains(event.target) && !clickedOnUserMenuButton;

    // Jika klik terjadi di luar kedua dropdown dan tombolnya, tutup semua dropdown
    if (clickedOutsideNotifArea && clickedOutsideUserMenuArea) {
      closeAllDropdowns();
    }
  });
</script>

</body>
</html>
