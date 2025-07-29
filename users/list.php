<?php
include '../config.php';

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if (!empty($cari)) {
    $cari_escaped = mysqli_real_escape_string($conn, $cari);
    $sql = "SELECT * FROM anggota 
            WHERE id_anggota LIKE '%$cari_escaped%' 
            OR nama LIKE '%$cari_escaped%' 
            OR alamat LIKE '%$cari_escaped%' 
            ORDER BY id_anggota DESC";
} else {
    $sql = "SELECT * FROM anggota ORDER BY id_anggota DESC";
}

$result = mysqli_query($conn, $sql); // <-- DIPINDAH KE SINI
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Anggota</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function showDetail(id) {
      document.getElementById('modal-' + id).classList.remove('hidden');
    }

    function closeModal(id) {
      document.getElementById('modal-' + id).classList.add('hidden');
    }
  </script>
</head>
<body class="bg-gray-100 p-6">
  <h1 class="text-2xl font-bold mb-4">Daftar Anggota</h1>
    <form method="GET" class="mb-6 flex gap-2">
    <input type="text" name="cari" placeholder="Cari berdasarkan ID, Nama, atau Alamat"
        value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>"
        class="px-4 py-2 border rounded w-full max-w-md" />
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cari</button>
    <button type="reset">reset</button>
    </form>



  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

    <?php while($row = mysqli_fetch_assoc($result)): ?>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center space-x-4">
          <?php if (!empty($row['foto']) && file_exists('../upload/foto/' . $row['foto'])): ?>
            <img src="../upload/foto/<?= $row['foto'] ?>" alt="foto" class="w-16 h-16 rounded-full object-cover">
          <?php else: ?>
            <!-- Heroicon user -->
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 1115 0v.75H4.5v-.75z"/>
            </svg>
          <?php endif; ?>

          <div>
            <h3 class="text-lg font-semibold"><?= htmlspecialchars($row['nama']) ?></h3>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($row['email']) ?></p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($row['alamat']) ?></p>
            <button onclick="showDetail(<?= $row['id_anggota'] ?>)" class="mt-2 text-blue-600 hover:underline text-sm">Lihat Detail</button>
          </div>
        </div>
      </div>

      <!-- Modal Detail -->
      <div id="modal-<?= $row['id_anggota'] ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md relative">
          <!-- Close Button -->
          <button onclick="closeModal(<?= $row['id_anggota'] ?>)" class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-xl">&times;</button>

          <!-- Modal Content -->
          <h2 class="text-xl font-bold mb-4">Detail Anggota</h2>
          <ul class="space-y-2 text-sm text-gray-700 mb-4">
            <li><strong>ID:</strong> <?= $row['id_anggota'] ?></li>
            <li><strong>Nama:</strong> <?= htmlspecialchars($row['nama']) ?></li>
            <li><strong>Jenis Kelamin:</strong> <?= $row['jenis_kelamin'] ?></li>
            <li><strong>Tempat, Tanggal Lahir:</strong> <?= $row['tempat_lahir'] ?>, <?= $row['tgl_lahir'] ?></li>
            <li><strong>Alamat:</strong> <?= $row['alamat'] ?></li>
            <li><strong>Telepon:</strong> <?= $row['telepon'] ?></li>
            <li><strong>Email:</strong> <?= $row['email'] ?></li>
            <li><strong>Status:</strong> <?= $row['status'] ?></li>
            <li><strong>Username:</strong> <?= $row['username'] ?></li>
            <li><strong>Role:</strong> <?= $row['role'] ?></li>
            <li><strong>NPWP:</strong> <?= $row['NPWP'] ?></li>
            <li><strong>KK:</strong>
                <?php if (!empty($row['kk']) && file_exists('../upload/kk/' . $row['kk'])): ?>
                    ✅
                    <a href="<?= '../upload/kk/' . $row['kk'] ?>" download class="text-blue-600 underline ml-2">Download</a>
                    <a onclick="printDocument('../upload/kk/<?= $row['kk'] ?>')"class="text-green-600 underline ml-2">Print</a>
                <?php else: ?>
                    ❌
                <?php endif; ?>
            </li>
            <li><strong>KTP:</strong>
                <?php if (!empty($row['ktp']) && file_exists('../upload/ktp/' . $row['ktp'])): ?>
                    ✅
                    <a href="<?= '../upload/ktp/' . $row['ktp'] ?>" download class="text-blue-600 underline ml-2">Download</a>
                    <a onclick="printDocument('../upload/ktp/<?= $row['ktp'] ?>')" class="text-green-600 underline ml-2">Print</a>
                <?php else: ?>
                    ❌
                <?php endif; ?>
            </li>

          </ul>

          <!-- Tombol Aksi -->
          <div class="flex justify-end space-x-2">
            <a href="anggota_edit.php?id=<?= $row['id_anggota'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">✏️ Edit</a>
            <button onclick="closeModal(<?= $row['id_anggota'] ?>)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-3 py-1 rounded text-sm">Tutup</button>
          </div>
        </div>
      </div>
    <?php endwhile; ?>

  </div>
<script>
    function printDocument(filePath) {
    const printWindow = window.open(filePath, '_blank', 'width=800,height=600');
    printWindow.onload = function () {
        printWindow.focus();
        printWindow.print();
    };
    }
</script>

</body>
</html>
