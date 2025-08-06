<?php
header('Content-Type: application/json');
$filename = __DIR__ . '/pesan.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$id = $_POST['id'] ?? null;

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID notifikasi diperlukan.']);
    exit;
}

if (!file_exists($filename)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'File notifikasi tidak ditemukan.']);
    exit;
}

$file_handle = fopen($filename, 'r+');
if (!$file_handle) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file notifikasi.']);
    exit;
}

// Lock file untuk mencegah race condition
if (flock($file_handle, LOCK_EX)) {
    $json_content = fread($file_handle, filesize($filename));
    $data = json_decode($json_content, true);
    $updated = false;
    $found = false;

    if (is_array($data)) {
        foreach ($data as &$item) {
            $is_match = false;
            $versi = $item['versi'] ?? 0;
            switch ($versi) {
                case 1: // Simpanan
                    if (isset($item['id']) && $item['id'] == $id) $is_match = true;
                    break;
                case 2: // Pinjaman
                    if (isset($item['id_pinjaman']) && $item['id_pinjaman'] == $id) $is_match = true;
                    break;
                case 3: // Penarikan
                    if (isset($item['id_penarikan']) && $item['id_penarikan'] == $id) $is_match = true;
                    break;
                case 4: // Angsuran
                    if (isset($item['id_angsuran']) && $item['id_angsuran'] == $id) $is_match = true;
                    break;
            }

            if ($is_match) {
                $found = true;
                if ($item['status'] !== 'baca') {
                    $item['status'] = 'baca';
                    $updated = true;
                }
                break;
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
        echo json_encode(['status' => 'success', 'message' => 'Status notifikasi diperbarui.']);
    } elseif ($found) {
        echo json_encode(['status' => 'success', 'message' => 'Status sudah dibaca.']);
    } else {
        echo json_encode(['status' => 'not_found', 'message' => 'Notifikasi tidak ditemukan.']);
    }

} else {
    fclose($file_handle);
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Server sibuk, coba lagi nanti.']);
}
?>
