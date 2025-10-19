<?php
include '../config.php';

// Cek session dan ambil role
session_start();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Jika bukan admin, return error
if ($role !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Ambil ID anggota dari parameter
$id_anggota = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_anggota <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

// Query untuk mendapatkan data anggota
$anggota_query = "SELECT * FROM anggota WHERE id_anggota = $id_anggota";
$anggota_result = mysqli_query($conn, $anggota_query);
$anggota = mysqli_fetch_assoc($anggota_result);

if (!$anggota) {
    echo json_encode(['error' => 'Anggota tidak ditemukan']);
    exit();
}

// Query untuk mendapatkan saldo anggota
$saldo_query = "SELECT saldo FROM saldo_anggota WHERE id_anggota = $id_anggota";
$saldo_result = mysqli_query($conn, $saldo_query);
$saldo_data = mysqli_fetch_assoc($saldo_result);
$saldo = $saldo_data ? $saldo_data['saldo'] : 0;

// Query untuk mendapatkan total simpanan
$total_simpanan_query = "SELECT COALESCE(SUM(jumlah), 0) as total_simpanan FROM simpanan WHERE id_anggota = $id_anggota";
$total_simpanan_result = mysqli_query($conn, $total_simpanan_query);
$total_simpanan_data = mysqli_fetch_assoc($total_simpanan_result);
$total_simpanan = $total_simpanan_data['total_simpanan'];

// Query untuk mendapatkan total penarikan
$total_penarikan_query = "SELECT COALESCE(SUM(jumlah), 0) as total_penarikan FROM tarik WHERE id_anggota = $id_anggota";
$total_penarikan_result = mysqli_query($conn, $total_penarikan_query);
$total_penarikan_data = mysqli_fetch_assoc($total_penarikan_result);
$total_penarikan = $total_penarikan_data['total_penarikan'];

// Query untuk mendapatkan total pinjaman
$total_pinjaman_query = "SELECT COALESCE(SUM(jumlah), 0) as total_pinjaman FROM pinjaman WHERE id_anggota = $id_anggota";
$total_pinjaman_result = mysqli_query($conn, $total_pinjaman_query);
$total_pinjaman_data = mysqli_fetch_assoc($total_pinjaman_result);
$total_pinjaman = $total_pinjaman_data['total_pinjaman'];

// Query untuk mendapatkan total angsuran
$total_angsuran_query = "SELECT COALESCE(SUM(a.jumlah), 0) as total_angsuran FROM angsuran a JOIN pinjaman p ON a.id_pinjaman = p.id_pinjaman WHERE p.id_anggota = $id_anggota";
$total_angsuran_result = mysqli_query($conn, $total_angsuran_query);
$total_angsuran_data = mysqli_fetch_assoc($total_angsuran_result);
$total_angsuran = $total_angsuran_data['total_angsuran'];

// Return data dalam format JSON
echo json_encode([
    'id_anggota' => $anggota['id_anggota'],
    'nama' => $anggota['nama'],
    'email' => $anggota['email'],
    'telepon' => $anggota['telepon'],
    'alamat' => $anggota['alamat'],
    'saldo' => $saldo,
    'total_simpanan' => $total_simpanan,
    'total_penarikan' => $total_penarikan,
    'total_pinjaman' => $total_pinjaman,
    'total_angsuran' => $total_angsuran
]);