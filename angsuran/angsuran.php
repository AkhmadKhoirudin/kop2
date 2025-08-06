<?php
include '../config.php';

// Proses simpan angsuran
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pinjaman = (int) $_POST['id_pinjaman'];
    $jumlah      = preg_replace('/[^\d]/', '', $_POST['jumlah']);
    $tanggal     = date('Y-m-d');

    // Cek apakah pinjaman valid dan belum lunas
    $cek = mysqli_query($conn, "SELECT jumlah FROM pinjaman WHERE id_pinjaman = $id_pinjaman AND status != 'lunas'");
    $pinjaman = mysqli_fetch_assoc($cek);

    if (!$pinjaman) {
        $pesan = "Pinjaman tidak ditemukan atau sudah lunas.";
    } elseif ($jumlah <= 0) {
        $pesan = "Jumlah angsuran harus lebih dari 0.";
    } else {
        // Simpan angsuran
        $query = "INSERT INTO angsuran (id_pinjaman, tanggal, jumlah, status) 
                  VALUES ('$id_pinjaman', '$tanggal', '$jumlah', 'sudah melakukan pembayaran')";
        if (mysqli_query($conn, $query)) {
            // Hitung total angsuran sekarang
            $sum = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM angsuran WHERE id_pinjaman = $id_pinjaman");
            $angsuran = mysqli_fetch_assoc($sum);
            $total_bayar = $angsuran['total'] ?? 0;

            if ($total_bayar >= $pinjaman['jumlah']) {
                // Update status jadi lunas
                mysqli_query($conn, "UPDATE pinjaman SET status = 'lunas' WHERE id_pinjaman = $id_pinjaman");
            }

            // Tambahkan notifikasi menggunakan fungsi terpusat
            include_once __DIR__ . '/../pesan/fungsi_pesan.php';
            $notifikasi_baru = [
                "versi"       => 4,
                "id_angsuran" => mysqli_insert_id($conn),
                "id_pinjaman" => $id_pinjaman,
                "tanggal"     => $tanggal,
                "jumlah"      => (int)$jumlah,
                "status"      => "belum"
            ];
            
            tambahNotifikasi($notifikasi_baru);

            header("Location: list.php?pesan=sukses");
            exit(); // Pastikan script berhenti setelah redirect
              
            $pesan = "Angsuran berhasil disimpan.";
        } else {
            $pesan = "Gagal menyimpan angsuran.";
        }
    }
}

// Get nama anggota
if (isset($_GET['get_nama']) && isset($_GET['id_anggota'])) {
    $id = (int)$_GET['id_anggota'];
    $res = mysqli_query($conn, "SELECT nama FROM anggota WHERE id_anggota = $id");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['nama' => $row['nama'] ?? '']);
    exit;
}

// Get daftar pinjaman (yang belum lunas)
if (isset($_GET['get_pinjaman']) && isset($_GET['id_anggota'])) {
    $id = (int)$_GET['id_anggota'];
    $data = [];
    $result = mysqli_query($conn, "
        SELECT p.id_pinjaman, pr.nama_produk 
        FROM pinjaman p
        LEFT JOIN produk pr ON p.id_produk = pr.id
        WHERE p.id_anggota = $id AND p.status != 'lunas'
    ");
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// Get detail pinjaman + tagihan bulan ini
if (isset($_GET['get_detail']) && isset($_GET['id_pinjaman'])) {
    $id = (int)$_GET['id_pinjaman'];
    $pinjaman = mysqli_query($conn, "SELECT jumlah, tenor, tanggal_pengajuan FROM pinjaman WHERE id_pinjaman = $id");
    $p = mysqli_fetch_assoc($pinjaman);
    $jumlah = $p['jumlah'] ?? 0;
    $tenor = $p['tenor'] ?? 1;
    $tgl_pengajuan = $p['tanggal_pengajuan'] ?? date('Y-m-d');

    $angsuran = mysqli_query($conn, "SELECT SUM(jumlah) AS dibayar FROM angsuran WHERE id_pinjaman = $id");
    $a = mysqli_fetch_assoc($angsuran);
    $dibayar = $a['dibayar'] ?? 0;

    $sisa = max(0, $jumlah - $dibayar);
    $bulanan = $tenor > 0 ? ceil($jumlah / $tenor) : 0;

    $bulan_ke = (date('Y') - date('Y', strtotime($tgl_pengajuan))) * 12 + (date('n') - date('n', strtotime($tgl_pengajuan))) + 1;

    $bln_ini = date('Y-m');
    $ang_bulan_ini = mysqli_query($conn, "
        SELECT SUM(jumlah) as total 
        FROM angsuran 
        WHERE id_pinjaman = $id AND DATE_FORMAT(tanggal, '%Y-%m') = '$bln_ini'
    ");
    $ab = mysqli_fetch_assoc($ang_bulan_ini);
    $dibayar_bulan_ini = $ab['total'] ?? 0;
    $sisa_bulan_ini = max(0, $bulanan - $dibayar_bulan_ini);

    echo json_encode([
        'jumlah' => number_format($jumlah, 0, ',', '.'),
        'dibayar' => number_format($dibayar, 0, ',', '.'),
        'sisa' => number_format($sisa, 0, ',', '.'),
        'bulanan' => number_format($bulanan, 0, ',', '.'),
        'bulan_ke' => $bulan_ke,
        'harus_bayar_bulan_ini' => number_format($sisa_bulan_ini, 0, ',', '.')
    ]);
    exit;
}

// Get list angsuran sebelumnya
if (isset($_GET['get_list_angsuran']) && isset($_GET['id_pinjaman'])) {
    $id = (int)$_GET['id_pinjaman'];
    $query = mysqli_query($conn, "SELECT tanggal, jumlah, status FROM angsuran WHERE id_pinjaman = $id ORDER BY tanggal ASC");

    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = [
            'tanggal' => $row['tanggal'],
            'jumlah' => number_format($row['jumlah'], 0, ',', '.'),
            'status' => $row['status']
        ];
    }

    echo json_encode($data);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Angsuran Pinjaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const idAnggota = document.getElementById('id_anggota');
        const namaAnggota = document.getElementById('nama_anggota');
        const idPinjaman = document.getElementById('id_pinjaman');
        const detail = document.getElementById('detail');

        idAnggota.addEventListener('input', function () {
            const id = this.value;
            fetch(`angsuran.php?get_nama=1&id_anggota=${id}`)
                .then(res => res.json())
                .then(data => namaAnggota.value = data.nama || '');

            fetch(`angsuran.php?get_pinjaman=1&id_anggota=${id}`)
                .then(res => res.json())
                .then(data => {
                    idPinjaman.innerHTML = '<option value="">Pilih ID Pinjaman</option>';
                    data.forEach(item => {
                        idPinjaman.innerHTML += `<option value="${item.id_pinjaman}">${item.id_pinjaman} - ${item.nama_produk}</option>`;
                    });
                });
        });

        idPinjaman.addEventListener('change', function () {
            const id = this.value;
            if (id !== "") {
                fetch(`angsuran.php?get_detail=1&id_pinjaman=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        detail.innerHTML = `
                            <p>Total Pinjaman: Rp ${data.jumlah}</p>
                            <p>Sudah Dibayar: Rp ${data.dibayar}</p>
                            <p>Sisa Pinjaman: Rp ${data.sisa}</p>
                            <p>Angsuran Bulanan: Rp ${data.bulanan}</p>
                            <p><strong>Tagihan Bulan Ini (bulan ke-${data.bulan_ke}):</strong> Rp ${data.harus_bayar_bulan_ini}</p>
                        `;
                    });

                fetch(`angsuran.php?get_list_angsuran=1&id_pinjaman=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        const list = document.getElementById('daftar_angsuran');
                        list.innerHTML = '';
                        if (data.length === 0) {
                            list.innerHTML = '<li>Belum ada angsuran.</li>';
                        } else {
                            data.forEach(item => {
                                list.innerHTML += `<li>${item.tanggal} - Rp ${item.jumlah} (${item.status})</li>`;
                            });
                        }
                    });
            } else {
                detail.innerHTML = "<p>-</p>";
                document.getElementById('daftar_angsuran').innerHTML = "";
            }
        });

        document.getElementById("jumlah").addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, "");
            e.target.value = value !== "" ? new Intl.NumberFormat("id-ID").format(value) : "";
        });
    });


    </script>
</head>
<body class="bg-gray-100 font-sans">
<div class="container mx-auto p-4">
    <h3 class="text-xl font-bold mb-4">Form Angsuran Pinjaman</h3>

    <?php if ($pesan) echo "<p class='text-green-500 font-semibold mb-4'>$pesan</p>"; ?>

    <form method="POST" class="bg-white p-6 rounded shadow-md">
        <div class="mb-4">
            <label class="block text-gray-700 font-medium">ID Anggota:</label>
            <input type="number" name="id_anggota" id="id_anggota" required 
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-medium">Nama Anggota:</label>
            <input type="text" id="nama_anggota" readonly 
                   class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-medium">ID Pinjaman:</label>
            <select name="id_pinjaman" id="id_pinjaman" required 
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
                <option value="">Pilih ID Pinjaman</option>
            </select>
        </div>

        <div id="detail" class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded">
            <p>Total Pinjaman: -</p>
            <p>Sudah Dibayar: -</p>
            <p>Sisa Pinjaman: -</p>
            <p>Angsuran Bulanan: -</p>
            <p><strong>Tagihan Bulan Ini:</strong> -</p>
        </div>

        <div id="list_angsuran" class="mb-4">
            <h4 class="text-lg font-semibold mb-2">Daftar Pembayaran Sebelumnya:</h4>
            <ul id="daftar_angsuran" class="list-disc pl-5 text-gray-700">
                <li>Belum ada data.</li>
            </ul>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-medium">Jumlah Angsuran:</label>
            <input type="text" name="jumlah" id="jumlah" required 
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <button type="submit" 
                class="w-full bg-blue-500 text-white font-medium py-2 px-4 rounded hover:bg-blue-600">
            Bayar Angsuran
        </button>
    </form>
</div>
</body>
</html>
