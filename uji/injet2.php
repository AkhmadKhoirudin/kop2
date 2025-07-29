<?php
$host = '127.0.0.1';
$dbname = 'koperasi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mulai transaksi untuk memastikan konsistensi data
    $pdo->beginTransaction();

    // Ambil ID anggota aktif
    $anggotaIds = $pdo->query("SELECT id_anggota FROM anggota WHERE status = 'aktif'")
                      ->fetchAll(PDO::FETCH_COLUMN);
    if (empty($anggotaIds)) die("Tidak ada anggota aktif!");

    // Ambil ID produk simpanan dan pinjaman yang aktif
    $produkSimpananIds = $pdo->query("SELECT id FROM produk WHERE kategori = 'SIMPANAN' AND status = 1")
                              ->fetchAll(PDO::FETCH_COLUMN);
    $produkPinjamanIds = $pdo->query("SELECT id FROM produk WHERE kategori = 'PEMBIAYAAN' AND status = 1")
                              ->fetchAll(PDO::FETCH_COLUMN);

    // Generate data untuk 13 bulan terakhir
    for ($offset = 0; $offset < 13; $offset++) {
        $targetDate = new DateTime("first day of -$offset months");
        $year = $targetDate->format("Y");
        $month = $targetDate->format("m");

        echo "Proses Bulan: $month-$year\n";

        // 1. Generate Data Simpanan
        $jumlahSimpanan = [200000, 300000, 400000, 500000, 600000, 700000, 800000];
        for ($i = 0; $i < 100; $i++) {
            $idAnggota = $anggotaIds[array_rand($anggotaIds)];
            $idProduk = $produkSimpananIds[array_rand($produkSimpananIds)];
            $jumlah = $jumlahSimpanan[array_rand($jumlahSimpanan)];
            $tanggal = date('Y-m-d', strtotime("$year-$month-" . rand(1, 28)));

            $pdo->prepare("INSERT INTO simpanan (id_anggota, tanggal, jumlah, id_prodak) VALUES (?, ?, ?, ?)")
                ->execute([$idAnggota, $tanggal, $jumlah, $idProduk]);
        }

        // 2. Generate Data Penarikan
        $jumlahPenarikan = [50000, 100000, 150000, 200000, 250000];
        for ($i = 0; $i < 100; $i++) {
            $idAnggota = $anggotaIds[array_rand($anggotaIds)];
            $idProduk = $produkSimpananIds[array_rand($produkSimpananIds)];
            $jumlah = $jumlahPenarikan[array_rand($jumlahPenarikan)];
            $tanggal = date('Y-m-d H:i:s', strtotime("$year-$month-" . rand(1, 28)));

            // Cek saldo sebelum penarikan
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM simpanan WHERE id_anggota = ?");
            $stmt->execute([$idAnggota]);
            $totalSimpanan = (float) $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM tarik WHERE id_anggota = ?");
            $stmt->execute([$idAnggota]);
            $totalPenarikan = (float) $stmt->fetchColumn();
            
            $saldo = $totalSimpanan - $totalPenarikan;

            if ($saldo >= $jumlah) {
                $pdo->prepare("INSERT INTO tarik (id_anggota, id_produk, tanggal, jumlah) VALUES (?, ?, ?, ?)")
                    ->execute([$idAnggota, $idProduk, $tanggal, $jumlah]);
            }
        }

        // 3. Generate Data Pinjaman dan Angsuran
        $jumlahPinjaman = [1000000, 2000000, 3000000, 4000000, 5000000];
        $tenorOptions = [3, 6, 9, 12];
        for ($i = 0; $i < 100; $i++) {
            $idAnggota = $anggotaIds[array_rand($anggotaIds)];
            $idProduk = $produkPinjamanIds[array_rand($produkPinjamanIds)];
            $jumlah = $jumlahPinjaman[array_rand($jumlahPinjaman)];
            $tenor = $tenorOptions[array_rand($tenorOptions)];
            $tanggal = date('Y-m-d', strtotime("$year-$month-" . rand(1, 28)));

            // Status pinjaman dengan probabilitas
            $rand = rand(1, 100);
            if ($rand <= 60) {
                $status = 'cair';
            } elseif ($rand <= 85) {
                $status = 'berjalan';
            } elseif ($rand <= 95) {
                $status = 'gagal';
            } else {
                $status = 'lunas';
            }

            // Insert data pinjaman
            $pdo->prepare("INSERT INTO pinjaman (id_anggota, id_produk, tanggal_pengajuan, jumlah, status, tenor) 
                          VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$idAnggota, $idProduk, $tanggal, $jumlah, $status, $tenor]);

            $idPinjaman = $pdo->lastInsertId();
            
            // Hitung angsuran dengan pembulatan yang benar
            $angsuranPokok = floor($jumlah / $tenor);
            $sisaPembulatan = $jumlah - ($angsuranPokok * $tenor);
            
            // Tentukan berapa kali angsuran sudah dibayar berdasarkan status
            if ($status === 'lunas') {
                $bayar = $tenor; // Semua angsuran dibayar
            } elseif ($status === 'gagal') {
                $bayar = rand(0, 2); // Maksimal 2x angsuran jika gagal
            } elseif ($status === 'berjalan') {
                $bayar = rand(ceil($tenor * 0.3), ceil($tenor * 0.8)); // 30-80% angsuran dibayar
            } else { // cair
                $bayar = 0; // Belum ada angsuran dibayar
            }

            // Generate data angsuran
            for ($j = 1; $j <= $tenor; $j++) {
                $tglAngsur = date('Y-m-d', strtotime("$tanggal +$j months"));
                $statusAngsur = $j <= $bayar ? 'sudah melakuakan pembayaran' : 'belum melakuakan pembayaran';
                
                // Angsuran terakhir mendapatkan sisa pembulatan
                $jumlahAngsuran = ($j == $tenor) ? ($angsuranPokok + $sisaPembulatan) : $angsuranPokok;

                $pdo->prepare("INSERT INTO angsuran (id_pinjaman, tanggal, jumlah, status) 
                              VALUES (?, ?, ?, ?)")
                    ->execute([$idPinjaman, $tglAngsur, $jumlahAngsuran, $statusAngsur]);
            }
        }
    }

    // Commit transaksi jika semua berhasil
    $pdo->commit();
    echo "\nSUKSES! Data dummy telah digenerate untuk 13 bulan ke belakang.\n";

} catch (PDOException $e) {
    // Rollback jika terjadi error
    $pdo->rollBack();
    echo "ERROR DATABASE: " . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage();
}
?>