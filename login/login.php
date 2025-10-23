<?php
session_start();
include '../config.php'; 

// Redirect jika sudah login
if (isset($_SESSION['id_anggota']) && isset($_SESSION['role']) && isset($_SESSION['nama'])) {
    header("Location: ../index.php");
    exit();
}

// Tentukan form mana yang ditampilkan pertama kali (default login)
$show_login = true; // true berarti tampilkan login

// Proses Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // **Registrasi**
    if (isset($_POST['signup'])) {
        $nama = trim($_POST['nama']);
        $alamat = trim($_POST['alamat']);
        $telepon = trim($_POST['telepon']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (!empty($password)) {
            // Periksa duplikat email
            $cek_email = $conn->prepare("SELECT id_anggota FROM anggota WHERE email = ?");
            $cek_email->bind_param("s", $email);
            $cek_email->execute();
            $cek_email->store_result();

            // Periksa duplikat username
            $cek_username = $conn->prepare("SELECT id_anggota FROM anggota WHERE username = ?");
            $cek_username->bind_param("s", $username);
            $cek_username->execute();
            $cek_username->store_result();

            if ($cek_email->num_rows > 0) {
                $register_error = 'Email sudah terdaftar!';
                $show_login = false; // Tetap di form registrasi jika error
            } elseif ($cek_username->num_rows > 0) {
                $register_error = 'Username sudah terdaftar!';
                $show_login = false; // Tetap di form registrasi jika error
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Simpan ke database
                $stmt = $conn->prepare("INSERT INTO anggota (nama, alamat, telepon, email, username, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $nama, $alamat, $telepon, $email, $username, $hashed_password);

                if ($stmt->execute()) {
                    $register_success = 'Registrasi berhasil! Silakan login.';
                    $show_login = true; // Setelah registrasi berhasil, tampilkan login
                } else {
                    $register_error = 'Terjadi kesalahan saat registrasi.';
                    $show_login = false;
                }
            }
        } else {
            $register_error = 'Password tidak boleh kosong.';
            $show_login = false;
        }
    }

    // **Login**
    if (isset($_POST['signin'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id_anggota, nama, role, password FROM anggota WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                if (password_verify($password, $row['password'])) {
                    $_SESSION['id_anggota'] = $row['id_anggota'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['nama'] = $row['nama'];
                    header("Location: ../index.php");
                    exit();
                } else {
                    $login_error = 'Password salah!';
                }
            } else {
                $login_error = 'Username tidak ditemukan!';
            }
        } else {
            $login_error = 'Harap isi username dan password!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Registrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../11logo.png">
</head>
<body class="bg-gray-100 h-full flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Login Form (Default) -->
        <div id="loginForm" class="<?php echo $show_login ? '' : 'hidden'; ?> p-8">
            <div class="text-center mb-8">
    <!-- Logo kiri dan kanan -->
    <div class="flex justify-center items-center space-x-6">
        <img src="../11logo.png" alt="Logo PMA" class="w-25 h-20">
        <img src="../koperasi_indonesia.jpg" alt="Logo Indonesia" class="w-20 h-20">
    </div>

    <!-- Judul -->
    <h2 class="text-2xl font-bold text-indigo-800 mt-4">Masuk ke Akun Anda</h2>
</div>

            
            <?php if (isset($login_error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg"><?= $login_error ?></div>
            <?php endif; ?>
            
            <?php if (isset($register_success)): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg"><?= $register_success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2 font-medium">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Username" required>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2 font-medium">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Password" required>
                    </div>
                </div>
                
                <button type="submit" name="signin" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                </button>
                
                <div class="text-center mt-4">
                    <a href="recovery_account.php" class="text-blue-600 hover:text-blue-800 text-sm">Lupa Password?</a>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Belum punya akun? 
                    <button onclick="showRegister()" class="text-blue-600 font-medium hover:underline">Daftar sekarang</button>
                </p>
            </div>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="<?php echo $show_login ? 'hidden' : ''; ?> p-8">
           <div class="text-center mb-8">
                <!-- Logo kiri dan kanan -->
                <div class="flex justify-center items-center space-x-6">
                    <img src="../11logo.png" alt="Logo PMA" class="w-20 h-20">
                    <img src="../koperasi_indonesia.jpg" alt="Logo Indonesia" class="w-20 h-20">
                </div>

                <!-- Judul -->
                <h2 class="text-2xl font-bold text-indigo-800 mt-4">Masuk ke Akun Anda</h2>
            </div>

            
            <?php if (isset($register_error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg"><?= $register_error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="grid gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Nama Lengkap</label>
                        <input type="text" name="nama" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Nama lengkap" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Alamat</label>
                        <input type="text" name="alamat" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Alamat" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Telepon</label>
                        <input type="tel" name="telepon" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Nomor telepon" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Email</label>
                        <input type="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Email" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Username</label>
                        <input type="text" name="username" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Username" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Password</label>
                        <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Password" required>
                    </div>
                </div>
                
                <button type="submit" name="signup" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition mt-6 flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i> Daftar
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Sudah punya akun? 
                    <button onclick="showLogin()" class="text-blue-600 font-medium hover:underline">Masuk disini</button>
                </p>
            </div>
        </div>
    </div>

    <script>
        function showRegister() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
        }
        
        function showLogin() {
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginForm').classList.remove('hidden');
        }
    </script>
</body>
</html>