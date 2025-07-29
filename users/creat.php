<?php
// Koneksi database
$koneksi = new mysqli("localhost", "root", "", "koperasi");
if ($koneksi->connect_error) {
    // Using htmlspecialchars to prevent potential XSS if error is somehow reflected.
    die("Koneksi gagal: " . htmlspecialchars($koneksi->connect_error));
}

// Function to generate a unique filename to prevent directory traversal and overwriting files.
function generateUniqueFileName($originalName, $prefix = '') {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    // Sanitize extension to prevent malicious input
    $safe_extension = preg_replace("/[^a-zA-Z0-9]/", "", $extension);
    return $prefix . uniqid('', true) . '.' . $safe_extension;
}

if (isset($_POST['submit'])) {
    $nama          = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tgl_lahir     = $_POST['tgl_lahir'];
    $tempat_lahir  = $_POST['tempat_lahir'];
    $alamat        = $_POST['alamat'];
    $telepon       = $_POST['telepon'];
    $email         = $_POST['email'];
    $status        = $_POST['status'];
    $npwp          = $_POST['npwp'] ?? '';

    // Lokasi direktori upload
    $base_upload_dir = "../upload/";
    $foto_dir = $base_upload_dir . "foto/";
    $ktp_dir  = $base_upload_dir . "ktp/";
    $kk_dir   = $base_upload_dir . "kk/";

    // Cek dan buat direktori jika belum ada
    if (!is_dir($foto_dir)) mkdir($foto_dir, 0777, true);
    if (!is_dir($ktp_dir))  mkdir($ktp_dir,  0777, true);
    if (!is_dir($kk_dir))   mkdir($kk_dir,   0777, true);

    // --- Secure File Upload Handling ---
    $fotoName = '';
    $ktpName  = '';
    $kkName   = '';
    $uploadError = false;
    $errorMessages = [];

    // Allowed file types and max size (e.g., 2MB)
    $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_file_size = 2 * 1024 * 1024;

    // Helper function for file validation
    function validate_and_upload($file_input, $upload_dir, &$file_name_var, $allowed_types, $max_size, &$errors) {
        if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES[$file_input]['tmp_name'];
            $file_size = $_FILES[$file_input]['size'];
            $original_name = $_FILES[$file_input]['name'];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_types)) {
                $errors[] = "Tipe file tidak diizinkan untuk " . $file_input . ". Hanya: " . implode(', ', $allowed_types);
                return false;
            }
            if ($file_size > $max_size) {
                $errors[] = "Ukuran file terlalu besar untuk " . $file_input . ". Maksimal 2MB.";
                return false;
            }

            $file_name_var = generateUniqueFileName($original_name, $file_input . '_');
            if (!move_uploaded_file($file_tmp_name, $upload_dir . $file_name_var)) {
                $errors[] = "Gagal memindahkan file yang diupload untuk " . $file_input . ".";
                return false;
            }
            return true;
        } else if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] !== UPLOAD_ERR_NO_FILE) {
             $errors[] = "Error saat upload file " . $file_input . ". Kode: " . $_FILES[$file_input]['error'];
             return false;
        } else if (!isset($_FILES[$file_input]) || $_FILES[$file_input]['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "File " . $file_input . " wajib diupload.";
            return false;
        }
        return true; 
    }

    if (!validate_and_upload('foto', $foto_dir, $fotoName, $allowed_image_types, $max_file_size, $errorMessages)) $uploadError = true;
    if (!validate_and_upload('ktp', $ktp_dir, $ktpName, $allowed_image_types, $max_file_size, $errorMessages)) $uploadError = true;
    if (!validate_and_upload('kk', $kk_dir, $kkName, $allowed_image_types, $max_file_size, $errorMessages)) $uploadError = true;


    if ($uploadError) {
        $error_str = implode("\\n", $errorMessages);
        echo "<script>
                Swal.fire({
                    title: 'Gagal Upload!',
                    text: '" . $error_str . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.history.back();
                });
              </script>";
    } else {
        // Simpan data ke database menggunakan prepared statement for security
        $sql = "INSERT INTO anggota (nama, jenis_kelamin, tgl_lahir, tempat_lahir, alamat, telepon, email, status, npwp, foto, ktp, kk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $koneksi->prepare($sql);
        // 'ssssssssss' specifies the variable types: s=string
        $stmt->bind_param("ssssssssssss", $nama, $jenis_kelamin, $tgl_lahir, $tempat_lahir, $alamat, $telepon, $email, $status, $npwp, $fotoName, $ktpName, $kkName);

        if ($stmt->execute()) {
            echo "<script>
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'User berhasil ditambahkan.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href='creat.php';
                        }
                    });
                  </script>";
        } else {
            // Use htmlspecialchars to prevent XSS from error message
            $errorMessage = htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
            echo "<script>
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal menambahkan user: " . $errorMessage . "',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                  </script>";
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah User Baru</title>
  <script src="https://cdn.tailwindcss.com"></script>                                          
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-5xl mx-auto p-8 bg-white rounded shadow mt-10">
    <h2 class="text-2xl font-bold mb-6 text-center">Form Pendaftaran Anggota Koperasi</h2>
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <div>
        <label class="block mb-1 font-semibold">Nama Lengkap</label>
        <input type="text" name="nama" class="w-full p-2 border rounded" required>

        <label class="block mt-4 mb-1 font-semibold">Jenis Kelamin</label>
        <select name="jenis_kelamin" class="w-full p-2 border rounded">
          <option value="laki-laki">Laki-laki</option>
          <option value="perempuan">Perempuan</option>
        </select>

        <label class="block mt-4 mb-1 font-semibold">Tanggal Lahir</label>
        <input type="date" name="tgl_lahir" class="w-full p-2 border rounded" required>

        <label class="block mt-4 mb-1 font-semibold">Tempat Lahir</label>
        <input type="text" name="tempat_lahir" class="w-full p-2 border rounded" required>

        <label class="block mt-4 mb-1 font-semibold">Alamat</label>
        <textarea name="alamat" class="w-full p-2 border rounded" required></textarea>

        <label class="block mt-4 mb-1 font-semibold">Telepon</label>
        <input type="text" name="telepon" class="w-full p-2 border rounded" required>
        
        <label class="block mt-4 mb-1 font-semibold">Role</label>
        <select name="status" class="w-full p-2 border rounded">
          <option value="aktif">Aktif</option>
          <option value="tidak aktif">Tidak Aktif</option>
        </select>

      </div>

      <div>
        <label class="block mb-1 font-semibold">Email</label>
        <input type="email" name="email" class="w-full p-2 border rounded" required>

        <label class="block mt-4 mb-1 font-semibold">NPWP (Opsional)</label>
        <input type="text" name="npwp" class="w-full p-2 border rounded">

        <label class="block mt-4 mb-1 font-semibold">Upload Foto (jpg, jpeg, png, gif - max 2MB)</label>
        <input type="file" name="foto" class="w-full" required accept="image/png, image/gif, image/jpeg">

        <label class="block mt-4 mb-1 font-semibold">Upload KTP (jpg, jpeg, png, gif - max 2MB)</label>
        <input type="file" name="ktp" class="w-full" required accept="image/png, image/gif, image/jpeg">

        <label class="block mt-4 mb-1 font-semibold">Upload KK (jpg, jpeg, png, gif - max 2MB)</label>
        <input type="file" name="kk" class="w-full" required accept="image/png, image/gif, image/jpeg">
      </div>

      <div class="col-span-1 md:col-span-2 text-center mt-6">
        <button type="submit" name="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Simpan</button>
      </div>
    </form>
  </div>
</body>
</html>
<?php
if(isset($koneksi)) {
    $koneksi->close();
}
?>
