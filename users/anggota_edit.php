<?php
include '../config.php';

// Ambil ID anggota
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID tidak valid!";
    exit;
}

// Ambil data anggota dari database
$sql = "SELECT * FROM anggota WHERE id_anggota = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$anggota = $result->fetch_assoc();

if (!$anggota) {
    echo "Data tidak ditemukan!";
    exit;
}

// Fungsi validasi file gambar
function isValidImage($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    return in_array($file['type'], $allowed);
}

// Proses update saat form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];

    // Update foto jika diunggah
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0 && isValidImage($_FILES['foto'])) {
        $foto_name = uniqid() . '.' . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['foto']['tmp_name'], '../upload/foto/' . $foto_name);
    } else {
        $foto_name = $anggota['foto']; // tidak diubah
    }

    // Update KK
    if (isset($_FILES['kk']) && $_FILES['kk']['error'] === 0 && isValidImage($_FILES['kk'])) {
        $kk_name = uniqid() . '.' . pathinfo($_FILES['kk']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['kk']['tmp_name'], '../upload/kk/' . $kk_name);
    } else {
        $kk_name = $anggota['kk'];
    }

    // Update KTP
    if (isset($_FILES['ktp']) && $_FILES['ktp']['error'] === 0 && isValidImage($_FILES['ktp'])) {
        $ktp_name = uniqid() . '.' . pathinfo($_FILES['ktp']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['ktp']['tmp_name'], '../upload/ktp/' . $ktp_name);
    } else {
        $ktp_name = $anggota['ktp'];
    }

    // Lakukan update ke database
    $update = "UPDATE anggota SET nama=?, email=?, alamat=?, foto=?, kk=?, ktp=?, status=? WHERE id_anggota=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("sssssssi", $nama, $email, $alamat, $foto_name, $kk_name, $ktp_name, $status, $id);
    if ($stmt->execute()) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data berhasil diperbarui',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'list.php';
                    }
                });
            });
        </script>
    ";
    } else {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Data gagal diperbarui',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'list.php';
                    }
                });
            });
        </script>
    ";
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Anggota</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-xl mx-auto bg-white shadow-lg rounded-xl p-6">
        <h2 class="text-2xl font-semibold mb-4">Edit Data Anggota</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block mb-1 font-medium">Nama</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($anggota['nama']) ?>" required class="w-full border rounded p-2">
            </div>

            <div>
                <label class="block mb-1 font-medium">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($anggota['email']) ?>" required class="w-full border rounded p-2">
            </div>

            <div>
                <label class="block mb-1 font-medium">Alamat</label>
                <textarea name="alamat" class="w-full border rounded p-2" rows="3"><?= htmlspecialchars($anggota['alamat']) ?></textarea>
            </div>

            <div class="flex items-center gap-4">
                <div>
                    <label class="block mb-1 font-medium">Foto</label>
                    <?php if ($anggota['foto']): ?>
                        <img src="../upload/foto/<?= $anggota['foto'] ?>" class="w-16 h-16 rounded-full object-cover">
                    <?php else: ?>
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M5.121 17.804A13.937 13.937 0 0112 15c2.386 0 4.604.626 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    <?php endif; ?>
                    <input type="file" name="foto" accept="image/*" class="mt-2">
                </div>
            </div>

            <div>
                <label class="block font-medium mb-1">Kartu Keluarga (KK)</label>
                <p class="mb-1"><?= $anggota['kk'] ? '✅' : '❌' ?></p>
                <input type="file" name="kk" accept="image/*">
            </div>

            <div>
                <label class="block font-medium mb-1">KTP</label>
                <p class="mb-1"><?= $anggota['ktp'] ? '✅' : '❌' ?></p>
                <input type="file" name="ktp" accept="image/*">
            </div>
            <div>
                <label class="block mt-4 mb-1 font-semibold">Status</label>
                <select name="status" class="w-full p-2 border rounded">
                    <option value="aktif" <?= ($anggota['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="tidak aktif" <?= ($anggota['status'] ?? '') === 'tidak aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>

            <div class="pt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                    Simpan Perubahan
                </button>
                <a href="list.php" class="ml-4 text-gray-600 hover:text-black">Kembali</a>
            </div>


        </form>
    </div>
</body>
</html>
