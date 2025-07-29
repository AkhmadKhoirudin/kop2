<?php
$filename = '../pesan/pesan.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id || !file_exists($filename)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid atau file tidak ditemukan.']);
        exit;
    }

    $json = json_decode(file_get_contents($filename), true);
    $updated = false;

    foreach ($json as &$item) {
        if (
            (isset($item['id']) && $item['id'] == $id) ||
            (isset($item['id_angsuran']) && $item['id_angsuran'] == $id) ||
            (isset($item['id_pinjaman']) && $item['id_pinjaman'] == $id) ||
            (isset($item['id_penarikan']) && $item['id_penarikan'] == $id)
        ) {
            $item['status'] = 'baca';
            $updated = true;
            break;
        }
    }

    if ($updated) {
        file_put_contents($filename, json_encode($json, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
}
?>
