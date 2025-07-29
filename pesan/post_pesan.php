<?php
// Path file pesan.json
$jsonFile = '../pesan/pesan.json';

// Ambil data dari request POST
$data = $_POST;

// Ambil isi file JSON
$existingData = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

// Pastikan $existingData array
if (!is_array($existingData)) {
    $existingData = [];
} 

// Tentukan versi dan struktur data yang dimasukkan
$versi = isset($data['versi']) ? intval($data['versi']) : 0;

$entry = ["versi" => $versi]; // Mulai dengan versi

switch ($versi) {
    case 1: // Simpanan
        $entry += [
            "id"         => $data['id'] ?? "",
            "id_anggota" => $data['id_anggota'] ?? "",
            "tanggal"    => $data['tanggal'] ?? date('Y-m-d'),
            "jumlah"     => (int)($data['jumlah'] ?? 0),
            "id_produk"  => $data['id_produk'] ?? ""
        ];
        break;

    case 2: // Pinjaman
        $entry += [
            "id"                => $data['id'] ?? "",
            "id_anggota"        => $data['id_anggota'] ?? "",
            "id_produk"         => $data['id_produk'] ?? "",
            "tanggal_pengajuan" => $data['tanggal_pengajuan'] ?? date('Y-m-d'),
            "jumlah"            => (int)($data['jumlah'] ?? 0),
            "tenor"             => (int)($data['tenor'] ?? 0)
        ];
        break;

    case 3: // Penarikan
        $entry += [
            "id"         => $data['id'] ?? "",
            "id_anggota" => $data['id_anggota'] ?? "",
            "id_produk"  => $data['id_produk'] ?? "",
            "jumlah"     => (int)($data['jumlah'] ?? 0)
        ];
        break;

    case 4: // Angsuran (berdasarkan id pinjaman)
        $entry += [
            "id"         => $data['id'] ?? "",
            "id_pinjaman"=> $data['id_pinjaman'] ?? "",
            "tanggal"    => $data['tanggal'] ?? date('Y-m-d'),
            "jumlah"     => (int)($data['jumlah'] ?? 0)
        ];
        break;

    default:
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Versi tidak dikenali"]);
        exit;
}

// Tambahkan ke data yang sudah ada
$existingData[] = $entry;

// Simpan kembali ke file JSON
file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));

// Respon sukses
echo json_encode(["status" => "success", "data" => $entry]);
?>
