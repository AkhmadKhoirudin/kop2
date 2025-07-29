<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'koperasi');
define('DB_USER', 'root');     // Ganti jika perlu
define('DB_PASS', '');         // Ganti jika perlu

// Set header CORS (jika diperlukan untuk API/Frontend yang beda origin)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Buat koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
