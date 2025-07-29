<?php
// clear.php - Script untuk membersihkan data transaksi

// Koneksi database
$host = '127.0.0.1';
$dbname = 'koperasi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Memulai proses pembersihan data...\n\n";

    // 1. Matikan sementara constraint foreign key
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Daftar tabel yang akan dibersihkan (hanya data transaksi)
    $tablesToClear = [
        'simpanan',
        'tarik',
        'pinjaman',
        'angsuran',
        'saldo_anggota'
    ];

    // 3. Proses penghapusan data
    foreach ($tablesToClear as $table) {
        // Gunakan TRUNCATE untuk tabel yang besar agar lebih cepat
        if ($table === 'simpanan' || $table === 'angsuran') {
            $pdo->exec("TRUNCATE TABLE $table");
            echo "-> Data tabel $table telah dikosongkan (TRUNCATE)\n";
        } else {
            // Untuk tabel lain gunakan DELETE
            $pdo->exec("DELETE FROM $table");
            echo "-> Data tabel $table telah dihapus (DELETE)\n";
        }
    }

    // 4. Reset saldo anggota ke 0
    $pdo->exec("UPDATE anggota SET saldo = 0 WHERE 1");
    echo "-> Saldo semua anggota telah direset ke 0\n";

    // 5. Reset auto increment
    $pdo->exec("ALTER TABLE simpanan AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE tarik AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE pinjaman AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE angsuran AUTO_INCREMENT = 1");
    echo "-> Auto increment telah direset\n";

    // 6. Hidangkan kembali constraint foreign key
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\nPembersihan data selesai! Data transaksi telah dikosongkan.\n";
    echo "Data anggota dan produk tetap tersimpan.\n";

} catch(PDOException $e) {
    echo "ERROR: Gagal membersihkan data. " . $e->getMessage();
    
    // Pastikan foreign key checks dihidupkan kembali jika terjadi error
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}
?>