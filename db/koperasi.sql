-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 31 Jul 2025 pada 08.13
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `koperasi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggota`
--

CREATE TABLE `anggota` (
  `id_anggota` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('laki-laki','perempuan','','') NOT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `tempat_lahir` varchar(50) NOT NULL,
  `alamat` text NOT NULL,
  `telepon` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('aktif','tidak aktif') DEFAULT 'aktif',
  `username` varchar(50) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `foto` varchar(255) DEFAULT NULL,
  `kk` text DEFAULT NULL,
  `ktp` text DEFAULT NULL,
  `NPWP` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `anggota`
--

INSERT INTO `anggota` (`id_anggota`, `nama`, `jenis_kelamin`, `tgl_lahir`, `tempat_lahir`, `alamat`, `telepon`, `email`, `status`, `username`, `password`, `role`, `foto`, `kk`, `ktp`, `NPWP`) VALUES
(9, 'neko91', 'laki-laki', '2003-06-05', 'cirebon', 'desa:kreyo block:2 kebon:saron', '081322878697', 'akhmadwibu05@outlook.com', 'aktif', 'neko', '$2y$10$HB14lVCDnx.knKvKh/KPBujiNklxUlS.hVGFmcuqPePjQkFSk0yG6', 'user', '687cbfaf00eb7.gif', NULL, '687cbfaf011c2.gif', 0),
(10, 'Akhmad Khoirudin', 'laki-laki', '2003-06-25', 'cirebon', 'desa:kreyo block:2 kebon:saron', '08132287869', 'akhmadwibu05@gmail.com', 'aktif', 'admin12345', '$2y$10$totai1NOEsodwJcyKv57x.pVc.3x3J1wH1GqIlAeUMz7vgMSe6cji', 'user', '687cc06888197.jpeg', '687cbde563a81.jpeg', '687cbde563e74.jpeg', 0),
(13, 'akhmadkhoirudin', 'perempuan', '2012-06-21', 'cirebon', 'sdsdsds', '081322878692', 'akhmadwibu05@aaaa.com', 'aktif', 'neko1', '$2y$10$km/Pi7WzbQXDpPRtcKYFbeqLo6SiHae65jzUlFNb8pPN1NWuDSbNS', 'user', NULL, NULL, NULL, 0),
(15, 'budiman', 'laki-laki', '0000-00-00', 'cirebon', 'desa:kreyo block:2 kebon:saron', '081322878692', 'akhmadwiasbu05@outlook.com', 'aktif', NULL, NULL, '', 'images.jpeg', 'images (3).jpeg', 'images (4).jpeg', 0),
(17, 'aaaaaaaaaaaaaa', 'laki-laki', '2000-12-04', 'cirebon', 'desa:kreyo block:2 kebon:saron', '081322878692', 'aaaaaaaaaaaaaaaakhmadwibu05@outlook.com', 'aktif', NULL, NULL, 'user', 'images (4).jpeg', 'images.jpeg', 'images (3).jpeg', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `angsuran`
--

CREATE TABLE `angsuran` (
  `id_angsuran` int(11) NOT NULL,
  `id_pinjaman` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jumlah` decimal(10,0) NOT NULL,
  `status` enum('sudah melakuakan pembayaran','belum melakuakan pembayaran','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `angsuran`
--
DELIMITER $$
CREATE TRIGGER `update_status_lunas` AFTER INSERT ON `angsuran` FOR EACH ROW BEGIN
    DECLARE total_angsuran DECIMAL(10,0);
    DECLARE total_pinjaman DECIMAL(10,0);

    -- Hitung total angsuran yang telah dibayarkan untuk pinjaman ini
    SELECT SUM(jumlah) INTO total_angsuran 
    FROM angsuran 
    WHERE id_pinjaman = NEW.id_pinjaman;

    -- Ambil jumlah pinjaman yang harus dibayar
    SELECT jumlah INTO total_pinjaman 
    FROM pinjaman 
    WHERE id_pinjaman = NEW.id_pinjaman;

    -- Jika total angsuran >= jumlah pinjaman, ubah status menjadi "lunas"
    IF total_angsuran >= total_pinjaman THEN
        UPDATE pinjaman 
        SET status = 'lunas' 
        WHERE id_pinjaman = NEW.id_pinjaman;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pinjaman`
--

CREATE TABLE `pinjaman` (
  `id_pinjaman` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `id_produk` varchar(20) NOT NULL,
  `tanggal_pengajuan` date NOT NULL,
  `jumlah` decimal(10,0) NOT NULL,
  `status` enum('pending','berjalan','cair','gagal','lunas','pengajuan') DEFAULT 'pending',
  `tenor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `jenis` varchar(50) NOT NULL,
  `kategori` varchar(20) NOT NULL,
  `akad` varchar(50) NOT NULL,
  `biaya` tinyint(1) DEFAULT 1,
  `ditarik` tinyint(1) DEFAULT 1,
  `berjangka` tinyint(1) DEFAULT 0,
  `bagi` tinyint(1) DEFAULT 1,
  `reward` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `jenis`, `kategori`, `akad`, `biaya`, `ditarik`, `berjangka`, `bagi`, `reward`, `status`, `tanggal_dibuat`) VALUES
(16, 'Tabungan Anggota Masyarakat Koperasi', 'tamaskop', 'SIMPANAN', 'Mudharabah Muthlaqah', 1, 1, 0, 1, 0, 1, '2025-07-02 10:13:45'),
(17, 'Tabungan Koperasi Anak Sekolah', 'takopnas', 'SIMPANAN', 'Mudharabah Muthlaqah', 1, 0, 0, 1, 0, 1, '2025-07-02 10:13:45'),
(18, 'Tabungan Qurban', 'taqorub', 'SIMPANAN', 'Mudharabah Muthlaqah', 1, 0, 0, 1, 0, 1, '2025-07-02 10:13:45'),
(19, 'Simpanan Berjangka Koperasi', 'simjak(deposito)', 'SIMPANAN', 'Mudharabah Muthlaqah', 0, 0, 1, 1, 0, 1, '2025-07-02 10:13:45'),
(20, 'Tabungan Harian Amanah (Wadiah)', 'tahara', 'SIMPANAN', 'Wadiah', 0, 1, 0, 0, 0, 1, '2025-07-02 10:13:45'),
(21, 'Simpanan Rencana Tabungan Amanah', 'sirata', 'SIMPANAN', 'Mudharabah Muthlaqah', 1, 1, 0, 1, 0, 1, '2025-07-02 10:13:45'),
(22, 'Tabungan Jiwa Rohani', 'tajiroh', 'SIMPANAN', 'Mudharabah Muthlaqah', 1, 1, 0, 1, 0, 1, '2025-07-02 10:13:45'),
(23, 'Simpanan Masa Tua', 'simata', 'SIMPANAN', 'Mudharabah Muthlaqah', 1, 1, 0, 1, 0, 1, '2025-07-02 10:13:45'),
(24, 'Simpanan Pokok', 'pokok', 'SIMPANAN', 'Wajib', 1, 0, 0, 0, 0, 1, '2025-07-02 10:13:45'),
(25, 'Simpanan Wajib', 'wajib', 'SIMPANAN', 'Wajib', 1, 0, 0, 0, 0, 1, '2025-07-02 10:13:45'),
(26, 'Pembiayaan Murabahah Mikro', 'murabahah_mikro', 'PEMBIAYAAN', 'Murabahah', 0, 0, 0, 0, 0, 1, '2025-07-02 10:21:29'),
(27, 'Pembiayaan Qardhul Hasan', 'qardhul_hasan', 'PEMBIAYAAN', 'Qardh', 0, 0, 0, 0, 0, 1, '2025-07-02 10:21:29'),
(28, 'Pembiayaan Mudharabah Usaha', 'mudharabah_usaha', 'PEMBIAYAAN', 'Mudharabah', 0, 0, 0, 1, 0, 1, '2025-07-02 10:21:29'),
(29, 'Pembiayaan Musyarakah Investasi', 'musyarakah_investasi', 'PEMBIAYAAN', 'Musyarakah', 0, 0, 0, 1, 0, 1, '2025-07-02 10:21:29'),
(30, 'Pembiayaan Konsumtif', 'konsumtif', 'PEMBIAYAAN', 'Ijarah', 0, 0, 0, 0, 0, 1, '2025-07-02 10:21:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `saldo_anggota`
--

CREATE TABLE `saldo_anggota` (
  `id_saldo` int(11) NOT NULL,
  `id_anggota` int(11) DEFAULT NULL,
  `saldo` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `simpanan`
--

CREATE TABLE `simpanan` (
  `id_simpanan` int(11) NOT NULL,
  `id_anggota` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jumlah` decimal(10,0) NOT NULL,
  `id_prodak` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `simpanan`
--
DELIMITER $$
CREATE TRIGGER `update_saldo_after_delete` AFTER DELETE ON `simpanan` FOR EACH ROW BEGIN
    UPDATE saldo_anggota
    SET saldo = saldo - OLD.jumlah
    WHERE id_anggota = OLD.id_anggota;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_saldo_after_insert` AFTER INSERT ON `simpanan` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM saldo_anggota WHERE id_anggota = NEW.id_anggota) THEN
        UPDATE saldo_anggota 
        SET saldo = saldo + NEW.jumlah
        WHERE id_anggota = NEW.id_anggota;
    ELSE
        INSERT INTO saldo_anggota (id_anggota, saldo) 
        VALUES (NEW.id_anggota, NEW.jumlah);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_saldo_after_update` AFTER UPDATE ON `simpanan` FOR EACH ROW BEGIN
    UPDATE saldo_anggota
    SET saldo = saldo - OLD.jumlah + NEW.jumlah
    WHERE id_anggota = NEW.id_anggota;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tarik`
--

CREATE TABLE `tarik` (
  `id_tarik` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp(),
  `jumlah` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `tarik`
--
DELIMITER $$
CREATE TRIGGER `update_saldo_after_tarik` AFTER INSERT ON `tarik` FOR EACH ROW BEGIN
    -- Kurangi saldo anggota setelah penarikan
    UPDATE saldo_anggota
    SET saldo = saldo - NEW.jumlah
    WHERE id_anggota = NEW.id_anggota;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_aktiva_rinci`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_aktiva_rinci` (
`id_produk` int(11)
,`nama_produk` varchar(100)
,`total_simpanan` decimal(32,0)
,`total_tarik` decimal(32,0)
,`saldo` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_saldo_anggota`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_saldo_anggota` (
`id_anggota` int(11)
,`nama` varchar(100)
,`total_simpanan` decimal(32,0)
,`total_penarikan` decimal(32,0)
,`saldo_aktual` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_shu_ringkas`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_shu_ringkas` (
`shu_tahun_berjalan` decimal(33,0)
,`shu_tahun_lalu` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_summary_produk_simpan_pinjam`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_summary_produk_simpan_pinjam` (
`id` int(11)
,`nama_produk` varchar(100)
,`kode_produk` varchar(50)
,`jenis_produk` varchar(8)
,`kategori` varchar(20)
,`jumlah_transaksi` bigint(21)
,`total_nominal` decimal(32,0)
,`total_angsuran` decimal(32,0)
,`sisa_pinjaman` decimal(33,0)
,`rata_rata_nominal` decimal(14,4)
,`tanggal_transaksi_pertama` date
,`tanggal_transaksi_terakhir` date
);

-- --------------------------------------------------------

--
-- Struktur untuk view `view_aktiva_rinci`
--
DROP TABLE IF EXISTS `view_aktiva_rinci`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_aktiva_rinci`  AS SELECT `p`.`id` AS `id_produk`, `p`.`nama_produk` AS `nama_produk`, coalesce(sum(`s`.`jumlah`),0) AS `total_simpanan`, coalesce((select sum(`t`.`jumlah`) from `tarik` `t` where `t`.`id_produk` = `p`.`id`),0) AS `total_tarik`, coalesce(sum(`s`.`jumlah`),0) - coalesce((select sum(`t`.`jumlah`) from `tarik` `t` where `t`.`id_produk` = `p`.`id`),0) AS `saldo` FROM (`produk` `p` left join `simpanan` `s` on(`s`.`id_prodak` = `p`.`id`)) WHERE `p`.`kategori` = 'SIMPANAN' AND `p`.`status` = 1 GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_saldo_anggota`
--
DROP TABLE IF EXISTS `view_saldo_anggota`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_saldo_anggota`  AS SELECT `a`.`id_anggota` AS `id_anggota`, `a`.`nama` AS `nama`, coalesce(sum(`s`.`jumlah`),0) AS `total_simpanan`, coalesce(sum(`t`.`jumlah`),0) AS `total_penarikan`, coalesce(sum(`s`.`jumlah`),0) - coalesce(sum(`t`.`jumlah`),0) AS `saldo_aktual` FROM ((`anggota` `a` left join `simpanan` `s` on(`a`.`id_anggota` = `s`.`id_anggota`)) left join `tarik` `t` on(`a`.`id_anggota` = `t`.`id_anggota`)) WHERE `a`.`status` = 'aktif' GROUP BY `a`.`id_anggota`, `a`.`nama` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_shu_ringkas`
--
DROP TABLE IF EXISTS `view_shu_ringkas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_shu_ringkas`  AS SELECT sum(case when year(`s`.`tanggal`) = year(curdate()) then `s`.`jumlah` else 0 end) - coalesce((select sum(`t`.`jumlah`) from `tarik` `t` where year(`t`.`tanggal`) = year(curdate())),0) AS `shu_tahun_berjalan`, sum(case when year(`s`.`tanggal`) = year(curdate()) - 1 then `s`.`jumlah` else 0 end) - coalesce((select sum(`t`.`jumlah`) from `tarik` `t` where year(`t`.`tanggal`) = year(curdate()) - 1),0) AS `shu_tahun_lalu` FROM `simpanan` AS `s` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_summary_produk_simpan_pinjam`
--
DROP TABLE IF EXISTS `view_summary_produk_simpan_pinjam`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_summary_produk_simpan_pinjam`  AS SELECT `p`.`id` AS `id`, `p`.`nama_produk` AS `nama_produk`, `p`.`jenis` AS `kode_produk`, 'Simpanan' AS `jenis_produk`, `p`.`kategori` AS `kategori`, count(`s`.`id_simpanan`) AS `jumlah_transaksi`, coalesce(sum(`s`.`jumlah`),0) AS `total_nominal`, NULL AS `total_angsuran`, NULL AS `sisa_pinjaman`, avg(`s`.`jumlah`) AS `rata_rata_nominal`, min(`s`.`tanggal`) AS `tanggal_transaksi_pertama`, max(`s`.`tanggal`) AS `tanggal_transaksi_terakhir` FROM (`produk` `p` left join `simpanan` `s` on(`p`.`id` = `s`.`id_prodak`)) WHERE `p`.`kategori` = 'SIMPANAN' GROUP BY `p`.`id`, `p`.`nama_produk`, `p`.`jenis`, `p`.`kategori`union all select `p`.`id` AS `id`,`p`.`nama_produk` AS `nama_produk`,`p`.`jenis` AS `kode_produk`,'Pinjaman' AS `jenis_produk`,`p`.`kategori` AS `kategori`,count(`pj`.`id_pinjaman`) AS `jumlah_transaksi`,coalesce(sum(`pj`.`jumlah`),0) AS `total_nominal`,coalesce(sum(`a`.`jumlah`),0) AS `total_angsuran`,coalesce(sum(`pj`.`jumlah`),0) - coalesce(sum(`a`.`jumlah`),0) AS `sisa_pinjaman`,avg(`pj`.`jumlah`) AS `rata_rata_nominal`,min(`pj`.`tanggal_pengajuan`) AS `tanggal_transaksi_pertama`,max(`pj`.`tanggal_pengajuan`) AS `tanggal_transaksi_terakhir` from ((`produk` `p` left join `pinjaman` `pj` on(`p`.`id` = `pj`.`id_produk`)) left join `angsuran` `a` on(`pj`.`id_pinjaman` = `a`.`id_pinjaman`)) where `p`.`kategori` = 'PEMBIAYAAN' group by `p`.`id`,`p`.`nama_produk`,`p`.`jenis`,`p`.`kategori` order by `jenis_produk` desc,`total_nominal` desc  ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id_anggota`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `angsuran`
--
ALTER TABLE `angsuran`
  ADD PRIMARY KEY (`id_angsuran`),
  ADD KEY `id_pinjaman` (`id_pinjaman`);

--
-- Indeks untuk tabel `pinjaman`
--
ALTER TABLE `pinjaman`
  ADD PRIMARY KEY (`id_pinjaman`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `saldo_anggota`
--
ALTER TABLE `saldo_anggota`
  ADD PRIMARY KEY (`id_saldo`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- Indeks untuk tabel `simpanan`
--
ALTER TABLE `simpanan`
  ADD PRIMARY KEY (`id_simpanan`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- Indeks untuk tabel `tarik`
--
ALTER TABLE `tarik`
  ADD PRIMARY KEY (`id_tarik`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id_anggota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `angsuran`
--
ALTER TABLE `angsuran`
  MODIFY `id_angsuran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pinjaman`
--
ALTER TABLE `pinjaman`
  MODIFY `id_pinjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13441;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `saldo_anggota`
--
ALTER TABLE `saldo_anggota`
  MODIFY `id_saldo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14208;

--
-- AUTO_INCREMENT untuk tabel `simpanan`
--
ALTER TABLE `simpanan`
  MODIFY `id_simpanan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tarik`
--
ALTER TABLE `tarik`
  MODIFY `id_tarik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14219;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `angsuran`
--
ALTER TABLE `angsuran`
  ADD CONSTRAINT `angsuran_ibfk_1` FOREIGN KEY (`id_pinjaman`) REFERENCES `pinjaman` (`id_pinjaman`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pinjaman`
--
ALTER TABLE `pinjaman`
  ADD CONSTRAINT `pinjaman_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `saldo_anggota`
--
ALTER TABLE `saldo_anggota`
  ADD CONSTRAINT `saldo_anggota_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `simpanan`
--
ALTER TABLE `simpanan`
  ADD CONSTRAINT `simpanan_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tarik`
--
ALTER TABLE `tarik`
  ADD CONSTRAINT `tarik_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `saldo_anggota` (`id_anggota`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
