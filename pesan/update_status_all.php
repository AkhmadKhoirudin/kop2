<?php
session_start();

header('Content-Type: application/json');

// Pastikan pengguna sudah login
if (!isset($_SESSION['id_anggota'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

$id_anggota_login = $_SESSION['id_anggota'];
$json_file = __DIR__ . '/pesan.json';

if (!file_exists($json_file)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'File notifikasi tidak ditemukan.']);
    exit;
}

$file_handle = fopen($json_file, 'r+');
if (!$file_handle) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file notifikasi.']);
    exit;
}

// Lock file untuk mencegah race condition
if (flock($file_handle, LOCK_EX)) {
    $json_content = fread($file_handle, filesize($json_file) ?: 1);
    $data = json_decode($json_content, true);
    $updated = false;

    if (is_array($data)) {
        foreach ($data as &$item) {
            // Perbarui status jika notifikasi milik pengguna dan belum dibaca
            if (isset($item['id_anggota']) && $item['id_anggota'] == $id_anggota_login && $item['status'] === 'belum') {
                $item['status'] = 'baca';
                $updated = true;
            }
        }

        if ($updated) {
            // Kembali ke awal file untuk menulis ulang
            ftruncate($file_handle, 0);
            rewind($file_handle);
            fwrite($file_handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    flock($file_handle, LOCK_UN); // Lepaskan lock
    fclose($file_handle);

    if ($updated) {
        echo json_encode(['status' => 'success', 'message' => 'Semua notifikasi telah ditandai sebagai dibaca.']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Tidak ada notifikasi baru untuk ditandai.']);
    }

} else {
    fclose($file_handle);
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Server sibuk, coba lagi nanti.']);
}
?>
