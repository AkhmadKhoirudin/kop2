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
      <header class="flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200">
        <button class="md:hidden text-gray-500 focus:outline-none" onclick="toggleSidebar()">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Menu Kanan -->
        <div class="flex items-center space-x-4 ml-auto">
          <!-- Notifikasi Dropdown -->
          <div class="relative">
            <button onclick="toggleDropdown()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
              <i class="fas fa-bell"></i>
            </button>
            <span id="badge-count" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1">0</span>
            <div id="popup-notif" class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded shadow-lg hidden z-50">
              <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="text-sm font-semibold text-gray-800">Notifikasi</h3>
                  <button onclick="tutupDropdown()" class="text-gray-400 hover:text-gray-600 text-xs">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <ul id="list-notifikasi" class="space-y-2 text-sm text-gray-700"></ul>
                <button id="baca-semua" class="text-blue-500 hover:underline text-xs">Baca Semua</button>
 
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
    fetchNotifikasi();

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
          fetchNotifikasi(); // Refresh list notifikasi
        } else {
          alert('Gagal memperbarui notifikasi');
        }
      });
    });
  });

  let isNotifLoading = false;

  function tampilkanLaporan(url) {
    document.getElementById("konten-frame").src = url;
  }

  function formatRupiah(angka) {
    return new Intl.NumberFormat("id-ID", {
      style: "decimal",
      minimumFractionDigits: 0
    }).format(angka);
  }
const badge = document.getElementById('badge-count');
if (badge) badge.textContent = belum.length;

  function toggleDropdown() {
    const dropdown = document.getElementById("popup-notif");

    const isHidden = dropdown.classList.contains("hidden");
    document.querySelectorAll(".absolute").forEach(el => el.classList.add("hidden"));
    if (isHidden) dropdown.classList.remove("hidden");
    else return;

    if (isNotifLoading) return;
    isNotifLoading = true;

    fetch("./pesan/api_pesan.php")
      .then(res => res.json())
      .then(result => {
        const list = document.getElementById("list-notifikasi");
        list.innerHTML = "";

        if (result.status === "success" && result.data.length > 0) {
          // Urutkan: 'belum' di atas, baru ambil 5 teratas
          const sorted = result.data.sort((a, b) => {
            if (a.status === 'belum' && b.status !== 'belum') return -1;
            if (a.status !== 'belum' && b.status === 'belum') return 1;
            return 0;
          });

          const recent = sorted.slice(0, 5);

          recent.forEach(item => {
            let teks = "";
            let id_unik = null;

            // Coba ambil ID valid (angka)
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

            // Buat teks notifikasi
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

            const css = item.status === 'belum' ? "font-bold text-indigo-700" : "text-gray-600";

            // Tampilkan item
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
        }
      })
      .catch(err => {
        document.getElementById("list-notifikasi").innerHTML =
          `<li class='text-sm text-red-500'>Gagal memuat notifikasi.</li>`;
        console.error("Fetch error:", err);
      })
      .finally(() => {
        isNotifLoading = false;
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
          toggleDropdown(); // reload daftar
        }
      })
      .catch(err => console.error("Update gagal:", err));
  }

  function tutupDropdown() {
    const dropdown = document.getElementById("popup-notif");
    if (dropdown) dropdown.classList.add("hidden");
  }

  document.addEventListener("click", function (event) {
    const dropdown = document.getElementById("popup-notif");
    const button = event.target.closest("button[onclick='toggleDropdown()']");
    if (!dropdown.contains(event.target) && !button) {
      dropdown.classList.add("hidden");
    }
  });
</script>

<script>
  function toggleUserMenu() {
    const menu = document.getElementById("user-menu");
    menu.classList.toggle("hidden");
  }

  // Tutup dropdown jika klik di luar elemen
  document.addEventListener("click", function (event) {
    const container = document.getElementById("dropdown-container");
    const menu = document.getElementById("user-menu");

    // Jika elemen yang diklik bukan bagian dari dropdown
    if (!container.contains(event.target)) {
      menu.classList.add("hidden");
    }
  });
</script>

</body>
</html>
