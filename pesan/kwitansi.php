<?php
// Path file JSON
$jsonFile = '../pesan/pesan.json';

// Ambil method request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // === TAMBAH DATA BARU ===
    $data = $_POST;
    $existingData = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

    if (!is_array($existingData)) {
        $existingData = [];
    }

    $versi = isset($data['versi']) ? intval($data['versi']) : 0;
    $entry = ["versi" => $versi];

    switch ($versi) {
        case 1: // Simpanan
            $entry += [
                "id_simpanan" => $data['id'] ?? "",
                "id_anggota"  => $data['id_anggota'] ?? "",
                "tanggal"    => $data['tanggal'] ?? date('Y-m-d'),
                "jumlah"     => (int)($data['jumlah'] ?? 0),
                "id_produk"  => $data['id_produk'] ?? ""
            ];
            break;

        case 2: // Pinjaman
            $entry += [
                "id_pinjaman"       => $data['id'] ?? "",
                "id_anggota"        => $data['id_anggota'] ?? "",
                "id_produk"         => $data['id_produk'] ?? "",
                "tanggal_pengajuan" => $data['tanggal_pengajuan'] ?? date('Y-m-d'),
                "jumlah"            => (int)($data['jumlah'] ?? 0),
                "tenor"             => (int)($data['tenor'] ?? 0)
            ];
            break;

        case 3: // Penarikan
            $entry += [
                "id_penarikan" => $data['id'] ?? "",
                "id_anggota"   => $data['id_anggota'] ?? "",
                "id_produk"  => $data['id_produk'] ?? "",
                "jumlah"     => (int)($data['jumlah'] ?? 0)
            ];
            break;

        case 4: // Angsuran
            $entry += [
                "id_angsuran" => $data['id_angsuran'] ?? "",
                "id_pinjaman" => $data['id_pinjaman'] ?? "",
                "tanggal"     => $data['tanggal'] ?? date('Y-m-d'),
                "jumlah"      => (int)($data['jumlah'] ?? 0)
            ];
            break;

        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Versi tidak dikenali"]);
            exit;
    }

    $existingData[] = $entry;
    file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));

    echo json_encode(["status" => "success", "data" => $entry]);

} elseif ($method === 'GET') {
    // === CARI DATA BERDASARKAN ID & VERSI ===
    $versi = isset($_GET['versi']) ? intval($_GET['versi']) : null;
    $id    = isset($_GET['id']) ? $_GET['id'] : null;

    if ($versi === null || $id === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Parameter versi dan id wajib ada"]);
        exit;
    }

    $dataList = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

    // Cari berdasarkan versi & id
    foreach ($dataList as $row) {
        $id_field = "id";
        if ($versi == 1) $id_field = "id_simpanan";
        if ($versi == 2) $id_field = "id_pinjaman";
        if ($versi == 3) $id_field = "id_penarikan";
        if ($versi == 4) $id_field = "id_angsuran";
        
        if (isset($row['versi']) && $row['versi'] == $versi && isset($row[$id_field]) && $row[$id_field] == $id) {
            echo json_encode(["status" => "found", "data" => $row]);
            exit;
        }
    }

    echo json_encode(["status" => "not_found", "message" => "Data tidak ditemukan"]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan"]);
}
?>
