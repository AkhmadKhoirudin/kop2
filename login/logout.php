<?php
session_start();
// Hapus semua variabel sesi
$_SESSION = [];
// Hapus session dari server
session_destroy();
// Redirect ke halaman login
header("Location: ./login.php");
exit();
?>
