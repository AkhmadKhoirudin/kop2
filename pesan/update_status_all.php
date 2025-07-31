<?php
session_start();

header('Content-Type: application/json');

// Cek apakah sudah login
$anggota = $_SESSION['id'] ?? null;
if (!$anggota) {
    echo json_encode(['success' => false, 'msg' => 'Belum login']);
    exit;
}

// Path file JSON
$json_file = __DIR__ . '/../pesan/pesan.json';

// Cek apakah file ada
if (!file_exists($json_file)) {
    echo json_encode(['success' => false, 'msg' => 'File tidak ditemukan']);
    exit;
}

// Ambil dan decode data JSON
$json_content = file_get_contents($json_file);
$data = json_decode($json_content, true);

// Validasi format
if (!is_array($data)) {
    echo json_encode(['success' => false, 'msg' => 'Format JSON tidak valid']);
    exit;
}

// Update status
$updated = false;
foreach ($data as &$item) {
    if (($item['status'] ?? '') === 'belum' && ($item['id_anggota'] ?? null) == $anggota) {
        $item['status'] = 'baca';
        $updated = true;
    }
}

// Simpan jika ada yang diubah
if ($updated) {
    if (file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['success' => true, 'msg' => 'Notifikasi diperbarui']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan perubahan']);
    }
} else {
    echo json_encode(['success' => true, 'msg' => 'Tidak ada notifikasi yang perlu diperbarui']);
}
