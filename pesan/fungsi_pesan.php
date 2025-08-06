<?php
/**
 * Menambahkan notifikasi baru ke file pesan.json dengan aman.
 *
 * @param array $notifikasi Data notifikasi yang akan ditambahkan.
 * @return bool True jika berhasil, false jika gagal.
 */
function tambahNotifikasi(array $notifikasi): bool
{
    $file_path = __DIR__ . '/pesan.json';
    
    // Buka file dengan mode 'c+' (baca/tulis, buat jika tidak ada)
    $handle = fopen($file_path, 'c+');
    
    if (!$handle) {
        // Gagal membuka file
        error_log('Gagal membuka file notifikasi: ' . $file_path);
        return false;
    }
    
    // Kunci file untuk penulisan eksklusif
    if (flock($handle, LOCK_EX)) {
        $isi_file = stream_get_contents($handle);
        $data = json_decode($isi_file, true);
        
        // Jika file kosong atau data tidak valid, inisialisasi sebagai array kosong
        if (!is_array($data)) {
            $data = [];
        }
        
        // Tambahkan notifikasi baru
        $data[] = $notifikasi;
        
        // Hapus isi file saat ini dan tulis ulang dengan data yang diperbarui
        ftruncate($handle, 0);
        rewind($handle);
        $berhasil_tulis = fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Lepaskan kunci
        flock($handle, LOCK_UN);
        
        // Tutup file
        fclose($handle);
        
        return $berhasil_tulis !== false;
    }
    
    // Jika gagal mendapatkan kunci
    fclose($handle);
    error_log('Gagal mendapatkan lock pada file notifikasi: ' . $file_path);
    return false;
}
?>