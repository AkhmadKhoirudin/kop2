<?php
session_start();

// Periksa apakah sesi telah diatur
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role']) || !isset($_SESSION['nama'])) {
    header("Location: ../login/login.php"); // Redirect ke halaman login
    exit;
}
?>
