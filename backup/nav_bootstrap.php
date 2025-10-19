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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    body {
        display: flex;
        min-height: 100vh;
        flex-direction: column;
    }
    .main-content {
        display: flex;
        flex: 1;
    }
    .sidebar {
        width: 280px;
        min-height: 100vh;
        background-color: #343a40;
        color: white;
    }
    .content {
        flex: 1;
        padding: 20px;
    }
    .sidebar .nav-link {
        color: #adb5bd;
    }
    .sidebar .nav-link:hover {
        color: #fff;
        background-color: #495057;
    }
    .sidebar .nav-link.active {
        color: #fff;
        background-color: #0d6efd;
    }
    .sidebar .dropdown-menu {
        background-color: #343a40;
        border: none;
    }
    .sidebar .dropdown-item {
        color: #adb5bd;
    }
    .sidebar .dropdown-item:hover {
        color: #fff;
        background-color: #495057;
    }
    #konten-frame {
        width: 100%;
        height: 100%;
        border: none;
    }
  </style>
</head>
<body>

<div class="main-content">
    <div class="sidebar p-3 d-flex flex-column">
        <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <img src="./logo.png" alt="Logo PMA" class="me-2" style="height: 40px;">
            <span class="fs-4">KSPPS PMA</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="#" onclick="navigateTo('./home/home.php')" class="nav-link">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            <li>
                <a href="#simpanan-submenu" data-bs-toggle="collapse" class="nav-link">
                    <i class="fas fa-wallet me-2"></i> Simpanan <i class="fas fa-chevron-down float-end"></i>
                </a>
                <div class="collapse" id="simpanan-submenu">
                    <ul class="nav flex-column ps-4">
                        <li><a href="#" onclick="navigateTo('./simpanan/transaksi_simpanan.php')" class="nav-link"><i class="fas fa-exchange-alt me-2"></i>Transaksi</a></li>
                        <li><a href="#" onclick="navigateTo('./simpanan/list.php')" class="nav-link"><i class="fas fa-list me-2"></i>Daftar</a></li>
                    </ul>
                </div>
            </li>
            <li>
                <a href="#pinjaman-submenu" data-bs-toggle="collapse" class="nav-link">
                    <i class="fas fa-hand-holding-usd me-2"></i> Pinjaman <i class="fas fa-chevron-down float-end"></i>
                </a>
                <div class="collapse" id="pinjaman-submenu">
                    <ul class="nav flex-column ps-4">
                        <li><a href="#" onclick="navigateTo('./pinjaman/transaksi_pinjaman.php')" class="nav-link"><i class="fas fa-exchange-alt me-2"></i>Transaksi</a></li>
                        <li><a href="#" onclick="navigateTo('./pinjaman/list.php')" class="nav-link"><i class="fas fa-list me-2"></i>Daftar</a></li>
                    </ul>
                </div>
            </li>
            <li>
                <a href="#penarikan-submenu" data-bs-toggle="collapse" class="nav-link">
                    <i class="fas fa-money-bill-wave me-2"></i> Penarikan <i class="fas fa-chevron-down float-end"></i>
                </a>
                <div class="collapse" id="penarikan-submenu">
                    <ul class="nav flex-column ps-4">
                        <li><a href="#" onclick="navigateTo('./penarikan/penarikan.php')" class="nav-link"><i class="fas fa-exchange-alt me-2"></i>Transaksi</a></li>
                        <li><a href="#" onclick="navigateTo('./penarikan/list.php')" class="nav-link"><i class="fas fa-list me-2"></i>Daftar</a></li>
                    </ul>
                </div>
            </li>
            <li>
                <a href="#angsuran-submenu" data-bs-toggle="collapse" class="nav-link">
                    <i class="fas fa-receipt me-2"></i> Angsuran <i class="fas fa-chevron-down float-end"></i>
                </a>
                <div class="collapse" id="angsuran-submenu">
                    <ul class="nav flex-column ps-4">
                        <li><a href="#" onclick="navigateTo('./angsuran/angsuran.php')" class="nav-link"><i class="fas fa-exchange-alt me-2"></i>Transaksi</a></li>
                        <li><a href="#" onclick="navigateTo('./angsuran/list.php')" class="nav-link"><i class="fas fa-list me-2"></i>Daftar</a></li>
                    </ul>
                </div>
            </li>
            <?php if ($role === 'admin'): ?>
            <li>
                <a href="#user-submenu" data-bs-toggle="collapse" class="nav-link">
                    <i class="fas fa-user-cog me-2"></i> User Management <i class="fas fa-chevron-down float-end"></i>
                </a>
                <div class="collapse" id="user-submenu">
                    <ul class="nav flex-column ps-4">
                        <li><a href="#" onclick="navigateTo('./users/creat.php')" class="nav-link"><i class="fas fa-user-edit me-2"></i>Pendaftaran</a></li>
                        <li><a href="#" onclick="navigateTo('./users/list.php')" class="nav-link"><i class="fas fa-list me-2"></i>Daftar User</a></li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($foto_ada): ?>
                    <img src="<?= htmlspecialchars($foto_path) ?>" alt="" width="32" height="32" class="rounded-circle me-2">
                <?php else: ?>
                    <div class="rounded-circle me-2 bg-secondary" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-user text-xl text-white"></i>
                    </div>
                <?php endif; ?>
                <strong><?= htmlspecialchars($nama_pengguna) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#" onclick="navigateTo('./users/profile.php')"><i class="fa-solid fa-gear me-2"></i>Pengaturan</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="./login/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span id="badge-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    0
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown" id="popup-notif">
                                <li><h6 class="dropdown-header">Notifikasi</h6></li>
                                <div id="list-notifikasi"></div>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#" id="baca-semua">Baca Semua</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <main>
            <iframe id="konten-frame" src="./home/home.php"></iframe>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function navigateTo(url) {
      document.getElementById("konten-frame").src = url;
    }

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
              badge.style.display = unreadCount === 0 ? 'none' : 'inline-block';
            }
            
            const sorted = result.data.sort((a, b) => {
              if (a.status === 'belum' && b.status !== 'belum') return -1;
              if (a.status !== 'belum' && b.status === 'belum') return 1;
              return new Date(b.tanggal) - new Date(a.tanggal);
            });
            
            const recent = sorted.slice(0, 5);
            
            recent.forEach(item => {
              const notificationItem = createNotificationItem(item);
              list.appendChild(notificationItem);
            });
          } else {
            list.innerHTML = `<li><a class="dropdown-item text-muted" href="#">Tidak ada notifikasi baru</a></li>`;
            if (badge) {
                badge.style.display = 'none';
            }
          }
        })
        .catch(err => {
          console.error("Error fetching notifications:", err);
          document.getElementById("list-notifikasi").innerHTML = 
            `<li><a class="dropdown-item text-danger" href="#">Gagal memuat notifikasi.</a></li>`;
        });
    }

    function createNotificationItem(item) {
      const li = document.createElement("li");
      
      const link = document.createElement("a");
      link.href = "#";
      link.className = `dropdown-item ${item.status === 'belum' ? 'bg-light' : ''}`;
      link.onclick = (e) => {
        e.preventDefault();
        updateNotificationStatus(item.id, () => redirectBasedOnNotification(item));
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
        <div class="d-flex align-items-start">
          <div class="flex-shrink-0 pt-1">
            <i class="${iconClass} text-primary"></i>
          </div>
          <div class="ms-3 flex-grow-1">
            <p class="mb-0 ${item.status === 'belum' ? 'fw-bold' : ''}">
              ${message}
            </p>
            <p class="text-muted small mb-0">
              ${formatDate(item.tanggal)}
            </p>
          </div>
          ${item.status === 'belum' ? '<span class="badge bg-primary rounded-pill ms-2">Baru</span>' : ''}
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
          fetchNotifikasi();
          if (callback) callback();
        }
      })
      .catch(err => console.error("Error updating notification:", err));
    }

    function markAllNotificationsAsRead() {
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
      .catch(err => console.error("Error marking all as read:", err));
    }

    function redirectBasedOnNotification(notif) {
      let url = './home/home.php';
      switch(notif.versi) {
        case '1': url = './simpanan/list.php'; break;
        case '2': url = './pinjaman/list.php'; break;
        case '3': url = './penarikan/list.php'; break;
        case '4': url = './angsuran/list.php'; break;
      }
      navigateTo(url);
    }

    document.addEventListener('DOMContentLoaded', () => {
      fetchNotifikasi();
      setInterval(fetchNotifikasi, 30000);
      document.getElementById('baca-semua').addEventListener('click', markAllNotificationsAsRead);
    });
</script>
</body>
</html>