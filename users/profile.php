<?php
session_start();
include '../config.php';

// Redirect jika belum login
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ../login.php");
    exit();
}

// Inisialisasi variabel
$errors = [];
$success = '';

// Fungsi untuk sinkronisasi database dengan file
function synchronizeFilesWithDatabase($conn, $userId) {
    $basePath = __DIR__ . '/../upload/';
    $documentTypes = ['kk', 'ktp', 'foto'];
    
    foreach ($documentTypes as $type) {
        // Dapatkan nama file dari database
        $query = "SELECT $type FROM anggota WHERE id_anggota = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $filename = $row[$type];
        
        // Jika ada nama file di database tapi file tidak ada
        if (!empty($filename) && !file_exists($basePath . $type . '/' . $filename)) {
            // Update database untuk set kolom ke NULL
            $updateQuery = "UPDATE anggota SET $type = NULL WHERE id_anggota = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("i", $userId);
            $updateStmt->execute();
            
            error_log("Sinkronisasi: File $filename tidak ditemukan di folder $type, database diupdate");
        }
    }
}

// Ambil data anggota
$id_anggota = $_SESSION['id_anggota'];

// Jalankan sinkronisasi sebelum mengambil data
synchronizeFilesWithDatabase($conn, $id_anggota);

$query = "SELECT * FROM anggota WHERE id_anggota = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_anggota);
$stmt->execute();
$result = $stmt->get_result();
$anggota = $result->fetch_assoc();

// Format tanggal lahir
$tgl_lahir = date('d F Y', strtotime($anggota['tgl_lahir']));

// Fungsi untuk handle upload file dengan sinkronisasi
function handleUpload($file, $type, $conn, $userId, $currentFilename = null) {
    $uploadDir = __DIR__ . '/../upload/' . $type . '/';
    
    // Buat folder jika belum ada
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Hapus file lama jika ada
    if (!empty($currentFilename) && file_exists($uploadDir . $currentFilename)) {
        unlink($uploadDir . $currentFilename);
    }
    
    if (!empty($file['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validasi tipe file
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            return ['error' => 'Jenis file tidak diizinkan. Hanya JPEG, PNG, GIF, atau PDF yang diperbolehkan.'];
        }
        
        // Validasi ukuran file
        if ($file['size'] > $maxSize) {
            return ['error' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
        }
        
        // Generate nama file unik dengan ekstensi asli
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Update database
            $sql = "UPDATE anggota SET $type = ? WHERE id_anggota = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $fileName, $userId);
            $stmt->execute();
            
            return ['success' => $fileName];
        } else {
            return ['error' => 'Gagal mengupload file.'];
        }
    }
    return null;
}

// Proses Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Profil
    if (isset($_POST['update_profile'])) {
        $nama = trim($_POST['nama']);
        $jenis_kelamin = trim($_POST['jenis_kelamin']);
        $tgl_lahir = trim($_POST['tgl_lahir']);
        $tempat_lahir = trim($_POST['tempat_lahir']);
        $alamat = trim($_POST['alamat']);
        $telepon = trim($_POST['telepon']);
        $email = trim($_POST['email']);
        $status = trim($_POST['status']);
        $npwp = trim($_POST['npwp']);

        // Validasi
        if (empty($nama)) $errors[] = "Nama lengkap harus diisi";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid";
        if (empty($telepon) || !is_numeric($telepon)) $errors[] = "Nomor telepon harus angka";
        if (!empty($npwp) && !is_numeric($npwp)) $errors[] = "NPWP harus berupa angka";

        // Handle file upload dengan sinkronisasi
        if (!empty($_FILES['kk']['name'])) {
            $result = handleUpload($_FILES['kk'], 'kk', $conn, $id_anggota, $anggota['kk']);
            if (isset($result['error'])) {
                $errors[] = 'KK: ' . $result['error'];
            } else {
                $anggota['kk'] = $result['success'];
            }
        }
        
        if (!empty($_FILES['ktp']['name'])) {
            $result = handleUpload($_FILES['ktp'], 'ktp', $conn, $id_anggota, $anggota['ktp']);
            if (isset($result['error'])) {
                $errors[] = 'KTP: ' . $result['error'];
            } else {
                $anggota['ktp'] = $result['success'];
            }
        }
        
        if (!empty($_FILES['foto']['name'])) {
            $result = handleUpload($_FILES['foto'], 'foto', $conn, $id_anggota, $anggota['foto']);
            if (isset($result['error'])) {
                $errors[] = 'Foto Profil: ' . $result['error'];
            } else {
                $anggota['foto'] = $result['success'];
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE anggota SET 
                    nama = ?, 
                    jenis_kelamin = ?, 
                    tgl_lahir = ?, 
                    tempat_lahir = ?, 
                    alamat = ?, 
                    telepon = ?, 
                    email = ?, 
                    status = ?, 
                    kk = ?, 
                    ktp = ?, 
                    NPWP = ?,
                    foto = ? 
                    WHERE id_anggota = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssssi", 
                $nama, $jenis_kelamin, $tgl_lahir, $tempat_lahir, 
                $alamat, $telepon, $email, $status,
                $anggota['kk'], $anggota['ktp'], $npwp, $anggota['foto'],
                $id_anggota);
            
            if ($stmt->execute()) {
                $success = "Profil berhasil diperbarui!";
                // Update session nama jika berubah
                $_SESSION['nama'] = $nama;
                // Refresh data
                header("Refresh:1");
            } else {
                $errors[] = "Gagal memperbarui profil: " . $conn->error;
            }
        }
    }

    // Update Password
    if (isset($_POST['update_password'])) {
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validasi
        if (!password_verify($current_password, $anggota['password'])) {
            $errors[] = "Password saat ini salah";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Password baru tidak cocok";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password minimal 6 karakter";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql = "UPDATE anggota SET password = ? WHERE id_anggota = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $id_anggota);
            
            if ($stmt->execute()) {
                $success = "Password berhasil diubah!";
            } else {
                $errors[] = "Gagal mengubah password: " . $conn->error;
            }
        }
    }
    
    // Hapus Dokumen
    if (isset($_POST['delete_document'])) {
        $doc_type = $_POST['doc_type'];
        $uploadDir = __DIR__ . '/../upload/';
        
        switch ($doc_type) {
            case 'kk':
                if (!empty($anggota['kk'])) {
                    if (file_exists($uploadDir . 'kk/' . $anggota['kk'])) {
                        unlink($uploadDir . 'kk/' . $anggota['kk']);
                    }
                    $sql = "UPDATE anggota SET kk = NULL WHERE id_anggota = ?";
                }
                break;
            case 'ktp':
                if (!empty($anggota['ktp'])) {
                    if (file_exists($uploadDir . 'ktp/' . $anggota['ktp'])) {
                        unlink($uploadDir . 'ktp/' . $anggota['ktp']);
                    }
                    $sql = "UPDATE anggota SET ktp = NULL WHERE id_anggota = ?";
                }
                break;
            case 'foto':
                if (!empty($anggota['foto'])) {
                    if (file_exists($uploadDir . 'foto/' . $anggota['foto'])) {
                        unlink($uploadDir . 'foto/' . $anggota['foto']);
                    }
                    $sql = "UPDATE anggota SET foto = NULL WHERE id_anggota = ?";
                }
                break;
        }
        
        if (isset($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_anggota);
            if ($stmt->execute()) {
                $success = "Dokumen berhasil dihapus!";
                // Update data anggota
                $anggota[$doc_type] = null;
                header("Refresh:1");
            } else {
                $errors[] = "Gagal menghapus dokumen: " . $conn->error;
            }
        }
    }
}

// Fungsi untuk memeriksa dan membersihkan file yang tidak terdaftar
function cleanOrphanedFiles($conn) {
    $basePath = __DIR__ . '/../upload/';
    $folders = ['kk', 'ktp', 'foto'];
    
    foreach ($folders as $folder) {
        $folderPath = $basePath . $folder . '/';
        if (!file_exists($folderPath)) continue;
        
        $filesInFolder = scandir($folderPath);
        $filesInFolder = array_diff($filesInFolder, ['.', '..']);
        
        // Dapatkan semua file yang terdaftar di database
        $registeredFiles = [];
        $result = $conn->query("SELECT $folder FROM anggota WHERE $folder IS NOT NULL");
        while ($row = $result->fetch_assoc()) {
            $registeredFiles[] = $row[$folder];
        }
        
        // Hapus file yang tidak terdaftar
        foreach ($filesInFolder as $file) {
            if (!in_array($file, $registeredFiles)) {
                unlink($folderPath . $file);
                error_log("Pembersihan: File $file di folder $folder dihapus karena tidak terdaftar");
            }
        }
    }
}

// Jalankan pembersihan file (bisa dijadwalkan via cron job)
cleanOrphanedFiles($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8 px-4">
        <!-- Notifikasi -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">
                    <?php foreach ($errors as $error): ?>
                        <?= $error ?><br>
                    <?php endforeach; ?>
                </span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" onclick="this.parentElement.parentElement.style.display='none'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Sukses!</strong>
                <span class="block sm:inline"><?= $success ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" onclick="this.parentElement.parentElement.style.display='none'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Header Profil -->
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-white">Profil Anggota</h1>
                <div class="space-x-2">
                    <button onclick="openModal('editModal')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-edit mr-1"></i> Edit Profil
                    </button>
                    <button onclick="openModal('passwordModal')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-key mr-1"></i> Ubah Password
                    </button>
                </div>
            </div>

            <!-- Informasi Profil -->
            <div class="p-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Foto Profil -->
                    <div class="w-full md:w-1/4">
                        <div class="bg-gray-200 rounded-lg overflow-hidden">
                            <?php if (!empty($anggota['foto'])): ?>
                                <img src="../upload/foto/<?= $anggota['foto'] ?>" alt="Foto Profil" class="w-full h-auto">
                            <?php else: ?>
                                <div class="h-64 flex items-center justify-center text-gray-500">
                                    <i class="fas fa-user-circle text-6xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Dokumen -->
                        <div class="mt-4 space-y-2">
                            <h3 class="font-semibold text-lg">Dokumen:</h3>
                            <div class="bg-gray-100 p-3 rounded">
                               <p class="text-sm">
                                <i class="fas fa-file-pdf text-red-500 mr-2"></i> KK: 
                                <?= !empty($anggota['kk']) 
                                    ? '<a href="../upload/kk/' . htmlspecialchars($anggota['kk']) . '" class="text-blue-500 hover:underline" download>Download</a>' 
                                    : 'Tidak ada' ?>
                                </p>

                                <p class="text-sm">
                                <i class="fas fa-file-image text-green-500 mr-2"></i> KTP: 
                                <?= !empty($anggota['ktp']) 
                                    ? '<a href="../upload/ktp/' . htmlspecialchars($anggota['ktp']) . '" class="text-blue-500 hover:underline" download>Download</a>' 
                                    : 'Tidak ada' ?>
                                </p>

                                <p class="text-sm"><i class="fas fa-hashtag text-purple-500 mr-2"></i> NPWP: 
                                    <?= !empty($anggota['NPWP']) ? $anggota['NPWP'] : 'Tidak ada' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Profil -->
                    <div class="w-full md:w-3/4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200">
                                    <tr>
                                        <th colspan="2" class="px-4 py-2 text-left">Informasi Pribadi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium w-1/3">ID Anggota</td>
                                        <td class="px-4 py-2"><?= $anggota['id_anggota'] ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Nama Lengkap</td>
                                        <td class="px-4 py-2"><?= $anggota['nama'] ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Jenis Kelamin</td>
                                        <td class="px-4 py-2"><?= ucfirst($anggota['jenis_kelamin']) ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Tanggal Lahir</td>
                                        <td class="px-4 py-2"><?= $tgl_lahir ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Tempat Lahir</td>
                                        <td class="px-4 py-2"><?= $anggota['tempat_lahir'] ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Alamat</td>
                                        <td class="px-4 py-2"><?= nl2br($anggota['alamat']) ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Telepon</td>
                                        <td class="px-4 py-2"><?= $anggota['telepon'] ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Email</td>
                                        <td class="px-4 py-2"><?= $anggota['email'] ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">NPWP</td>
                                        <td class="px-4 py-2"><?= !empty($anggota['NPWP']) ? $anggota['NPWP'] : 'Tidak ada' ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Status</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $anggota['status'] == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= ucfirst($anggota['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Username</td>
                                        <td class="px-4 py-2"><?= $anggota['username'] ?></td>
                                    </tr>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-medium">Role</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                <?= ucfirst($anggota['role']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Profil -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Edit Profil</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= $anggota['nama'] ?>" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="w-full px-3 py-2 border rounded">
                            <option value="laki-laki" <?= $anggota['jenis_kelamin'] == 'laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="perempuan" <?= $anggota['jenis_kelamin'] == 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" value="<?= $anggota['tgl_lahir'] ?>" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="<?= $anggota['tempat_lahir'] ?>" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div class="mb-4 md:col-span-2">
                        <label class="block text-gray-700 mb-2">Alamat</label>
                        <textarea name="alamat" class="w-full px-3 py-2 border rounded"><?= $anggota['alamat'] ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Telepon</label>
                        <input type="text" name="telepon" value="<?= $anggota['telepon'] ?>" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" value="<?= $anggota['email'] ?>" class="w-full px-3 py-2 border rounded">
                    </div>


                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">NPWP</label>
                        <input type="text" name="npwp" value="<?= $anggota['NPWP'] ?>" 
                               class="w-full px-3 py-2 border rounded" 
                               pattern="[0-9]+" title="NPWP harus berupa angka" 
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <p class="text-xs text-gray-500 mt-1">*Hanya angka yang diperbolehkan</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded">
                            <option value="aktif" <?= $anggota['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $anggota['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    
                    <!-- Upload Dokumen -->
                    <div class="mb-4 md:col-span-2">
                        <h3 class="font-semibold text-lg mb-2">Upload Dokumen</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-700 mb-1">Kartu Keluarga (KK)</label>
                                <div class="flex items-center">
                                    <input type="file" name="kk" class="w-full px-3 py-2 border rounded">
                                    <?php if (!empty($anggota['kk'])): ?>
                                        <span class="ml-2 text-sm text-gray-600">File terupload: <?= $anggota['kk'] ?></span>
                                        <button type="submit" name="delete_document" value="kk" class="ml-2 text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-1">KTP</label>
                                <div class="flex items-center">
                                    <input type="file" name="ktp" class="w-full px-3 py-2 border rounded">
                                    <?php if (!empty($anggota['ktp'])): ?>
                                        <span class="ml-2 text-sm text-gray-600">File terupload: <?= $anggota['ktp'] ?></span>
                                        <button type="submit" name="delete_document" value="ktp" class="ml-2 text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-1">Foto Profil</label>
                                <div class="flex items-center">
                                    <input type="file" name="foto" class="w-full px-3 py-2 border rounded">
                                    <?php if (!empty($anggota['foto'])): ?>
                                        <span class="ml-2 text-sm text-gray-600">File terupload: <?= $anggota['foto'] ?></span>
                                        <button type="submit" name="delete_document" value="foto" class="ml-2 text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

     

                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editModal')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Batal</button>
                    <button type="submit" name="update_profile" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ubah Password -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('passwordModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Ubah Password</h2>
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Password Saat Ini</label>
                    <input type="password" name="current_password" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('passwordModal')" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Batal</button>
                    <button type="submit" name="update_password" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        // Fungsi untuk menutup modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Tutup modal jika klik di luar area modal
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>