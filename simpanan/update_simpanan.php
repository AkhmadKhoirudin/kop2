<?php
// update_simpanan.php

include '../config.php';

// Pastikan ini adalah permintaan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi dan ambil data dari POST
    if (isset($_POST['id_simpanan']) && is_numeric($_POST['id_simpanan']) &&
        isset($_POST['jumlah']) && is_numeric($_POST['jumlah']) &&
        isset($_POST['id_prodak']) && is_numeric($_POST['id_prodak'])) {

        $id_simpanan = $_POST['id_simpanan'];
        $jumlah = $_POST['jumlah'];
        $id_prodak = $_POST['id_prodak'];

        // Siapkan pernyataan SQL untuk pembaruan
        $sql = "UPDATE simpanan SET jumlah = ?, id_prodak = ? WHERE id_simpanan = ?";

        if ($stmt = $config->prepare($sql)) {
            // Ikat parameter ke pernyataan yang telah disiapkan
            $stmt->bind_param("dii", $jumlah, $id_prodak, $id_simpanan);

            // Eksekusi pernyataan
            if ($stmt->execute()) {
                // Berhasil, arahkan kembali ke halaman daftar
                header("Location: list.php");
                exit();
            } else {
                echo "Error: Gagal memperbarui data.";
            }

            // Tutup pernyataan
            $stmt->close();
        } else {
            echo "Error: Gagal menyiapkan pernyataan SQL.";
        }
    } else {
        echo "Error: Data yang dikirim tidak valid.";
    }
} else {
    // Jika bukan permintaan POST, arahkan kembali
    header("Location: list.php");
    exit();
}

// Tutup koneksi database
$config->close();
?>