<?php
// Koneksi database
$host = '127.0.0.1';
$dbname = 'koperasi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil ID anggota aktif
    $anggotaIds = $pdo->query("SELECT id_anggota FROM anggota WHERE status = 'aktif'")
                     ->fetchAll(PDO::FETCH_COLUMN);
    if (empty($anggotaIds)) die("Tidak ada anggota aktif!");

    // Ambil ID produk simpanan & pinjaman
    $produkSimpananIds = $pdo->query("SELECT id FROM produk WHERE kategori = 'SIMPANAN' AND status = 1")
                             ->fetchAll(PDO::FETCH_COLUMN);
    $produkPinjamanIds = $pdo->query("SELECT id FROM produk WHERE kategori = 'PEMBIAYAAN' AND status = 1")
                             ->fetchAll(PDO::FETCH_COLUMN);

    // Simulasi 13 bulan dari sekarang ke belakang
    for ($offset = 0; $offset < 13; $offset++) {
        $targetDate = new DateTime("first day of -$offset months");
        $year = $targetDate->format("Y");
        $month = $targetDate->format("m");

        echo "Proses Bulan: $month-$year\n";

        // --- SIMPANAN (400 data per bulan) ---
        $jumlahSimpanan = [200000, 300000, 400000, 500000, 600000, 700000, 800000];
        for ($i = 0; $i < 100; $i++) {
            $idAnggota = $anggotaIds[array_rand($anggotaIds)];
            $idProduk = $produkSimpananIds[array_rand($produkSimpananIds)];
            $jumlah = $jumlahSimpanan[array_rand($jumlahSimpanan)];
            $tanggal = date('Y-m-d', strtotime("$year-$month-" . rand(1, 28)));

            $pdo->prepare("INSERT INTO simpanan (id_anggota, tanggal, jumlah, id_prodak) VALUES (?, ?, ?, ?)")
                ->execute([$idAnggota, $tanggal, $jumlah, $idProduk]);

            $pdo->prepare("INSERT INTO saldo_anggota (id_anggota, saldo) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE saldo = saldo + ?")
                ->execute([$idAnggota, $jumlah, $jumlah]);
        }

        // --- PENARIKAN (300 data per bulan) ---
        $jumlahPenarikan = [50000, 100000, 150000, 200000, 250000];
        for ($i = 0; $i < 100; $i++) {
            $idAnggota = $anggotaIds[array_rand($anggotaIds)];
            $idProduk = $produkSimpananIds[array_rand($produkSimpananIds)];
            $jumlah = $jumlahPenarikan[array_rand($jumlahPenarikan)];
            $tanggal = date('Y-m-d H:i:s', strtotime("$year-$month-" . rand(1, 28)));

            $stmt = $pdo->prepare("SELECT saldo FROM saldo_anggota WHERE id_anggota = ?");
            $stmt->execute([$idAnggota]);
            $saldo = $stmt->fetchColumn();

            if ($saldo >= $jumlah) {
                $pdo->prepare("INSERT INTO tarik (id_anggota, id_produk, tanggal, jumlah) VALUES (?, ?, ?, ?)")
                    ->execute([$idAnggota, $idProduk, $tanggal, $jumlah]);

                $pdo->prepare("UPDATE saldo_anggota SET saldo = saldo - ? WHERE id_anggota = ?")
                    ->execute([$jumlah, $idAnggota]);
            }
        }

        // --- PINJAMAN (300 data per bulan) ---
        $jumlahPinjaman = [1000000, 2000000, 3000000, 4000000, 5000000];
        $tenorOptions = [3, 6, 9, 12];
        for ($i = 0; $i < 100; $i++) {
            $idAnggota = $anggotaIds[array_rand($anggotaIds)];
            $idProduk = $produkPinjamanIds[array_rand($produkPinjamanIds)];
            $jumlah = $jumlahPinjaman[array_rand($jumlahPinjaman)];
            $tenor = $tenorOptions[array_rand($tenorOptions)];
            $tanggal = date('Y-m-d', strtotime("$year-$month-" . rand(1, 28)));

            // Status random
            $rand = rand(1, 10);
            $status = $rand <= 5 ? 'cair' : ($rand <= 8 ? 'berjalan' : 'gagal');

            $pdo->prepare("INSERT INTO pinjaman (id_anggota, id_produk, tanggal_pengajuan, jumlah, status, tenor) 
                            VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$idAnggota, $idProduk, $tanggal, $jumlah, $status, $tenor]);

            $idPinjaman = $pdo->lastInsertId();
            $angsuran = ceil($jumlah / $tenor);

            // Generate angsuran sesuai status
            $bayar = $status === 'lunas' ? $tenor : ($status === 'gagal' ? rand(1, 2) : rand(ceil($tenor * 0.5), $tenor - 1));

            for ($j = 1; $j <= $tenor; $j++) {
                $tglAngsur = date('Y-m-d', strtotime("$tanggal +$j months"));
                $statusAngsur = $j <= $bayar ? 'sudah melakukan pembayaran' : 'belum melakukan pembayaran';

                $pdo->prepare("INSERT INTO angsuran (id_pinjaman, tanggal, jumlah, status) 
                                VALUES (?, ?, ?, ?)")
                    ->execute([$idPinjaman, $tglAngsur, $angsuran, $statusAngsur]);
            }
        }
    }

    echo "\nSUKSES! Data dummy telah digenerate untuk 13 bulan ke belakang.\n";

} catch (PDOException $e) {
    echo "ERROR DATABASE: " . $e->getMessage();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
