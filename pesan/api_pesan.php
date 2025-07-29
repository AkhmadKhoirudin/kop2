<?php
session_start(); // Pastikan session diaktifkan

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$pesan_file = __DIR__ . '/../pesan/pesan.json';

if (!file_exists($pesan_file)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'File pesan.json tidak ditemukan.'
    ]);
    exit;
}

$isi_file = file_get_contents($pesan_file);
$data = json_decode($isi_file, true);

if (!is_array($data)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Format JSON tidak valid.'
    ]);
    exit;
}

// Pastikan session berisi ID anggota
if (!isset($_SESSION['id_anggota'])) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Session tidak ditemukan.'
    ]);
    exit;
}

$id_anggota_login = $_SESSION['id_anggota'];
$role = $_SESSION['role'] ?? 'anggota'; // Default role jika tidak ada

// Filter berdasarkan id_anggota
$data = array_filter($data, function ($item) use ($id_anggota_login, $role) {
    // Role admin lihat semua
    if ($role === 'admin') return true;

    // Anggota hanya bisa lihat yang sesuai dengan ID mereka
    return isset($item['id_anggota']) && $item['id_anggota'] == $id_anggota_login;
});

// Opsional: filter berdasarkan versi jika diberikan di query
if (isset($_GET['versi'])) {
    $versi = intval($_GET['versi']);
    $data = array_filter($data, function ($item) use ($versi) {
        return isset($item['versi']) && $item['versi'] == $versi;
    });
}

$data = array_values($data); // Reset index array

// Output
echo json_encode([
    'status' => 'success',
    'total' => count($data),
    'data' => $data
], JSON_PRETTY_PRINT);
