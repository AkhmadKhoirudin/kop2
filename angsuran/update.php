<?php
session_start();
include '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_anggota']) || !isset($_SESSION['role'])) {
    header("Location: ../login/login.php");
    exit();
}

// Proses update angsuran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_angsuran = (int) $_POST['id_angsuran'];
    $jumlah = preg_replace('/[^\d]/', '', $_POST['jumlah']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validasi input
    if ($id_angsuran <= 0 || $jumlah <= 0 || empty($status)) {
        header("Location: list.php?pesan=error&msg=Data tidak valid");
        exit();
    }
    
    // Update data angsuran
    $query = "UPDATE angsuran SET jumlah = '$jumlah', status = '$status' WHERE id_angsuran = $id_angsuran";
    
    if (mysqli_query($conn, $query)) {
        // Redirect ke list.php dengan pesan sukses
        header("Location: list.php?pesan=sukses");
        exit();
    } else {
        // Redirect ke list.php dengan pesan error
        header("Location: list.php?pesan=error");
        exit();
    }
} else {
    // Jika bukan POST request, redirect ke list.php
    header("Location: list.php");
    exit();
}
?>