<?php
session_start();
include '../config.php'; // Pastikan koneksi ke database berhasil

// Fungsi untuk mengenkripsi data dengan AES-256-CBC
function encryptAES($text, $key) {
    $iv = openssl_random_pseudo_bytes(16); // Generate IV acak
    $encrypted = openssl_encrypt($text, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted); // Gabungkan IV dan hasil enkripsi
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $email = trim($_POST['email']);

        if (!empty($email)) {
            // Periksa apakah email ada di tabel anggota
            $cek_email = $conn->prepare("SELECT id_anggota FROM anggota WHERE email = ?");
            $cek_email->bind_param("s", $email);
            $cek_email->execute();
            $cek_email->store_result();

            if ($cek_email->num_rows > 0) {
                $cek_email->bind_result($id_anggota);
                $cek_email->fetch();

                // Kunci rahasia untuk enkripsi (harus sama dengan yang digunakan untuk dekripsi)
                $secretKey = "mySecretKey12345"; 

                // Enkripsi id_anggota sebelum dikirim
                $encrypted_id = encryptAES($id_anggota, $secretKey);

                // URL API Google Script
                $script_url = "https://script.google.com/macros/s/AKfycbzS_-pdHPr66ZIqPrZ6wzZQYgKwn_DjayMnAI7jHVPSwIsOmwFFlq2FWoBgwZP7vtdS/exec";
                $query = http_build_query(['email' => $email, 'id_anggota' => $encrypted_id]);

                $response = file_get_contents($script_url . "?" . $query);

                if ($response) {
                    echo "<script>alert('Email berhasil dikirim ke $email.');</script>";
                } else {
                    echo "<script>alert('Gagal mengirim email. Silakan coba lagi.');</script>";
                }
            } else {
                echo "<script>alert('Email tidak ditemukan.');</script>";
            }
        } else {
            echo "<script>alert('Field email harus diisi.');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .recovery-container {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 600px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .recovery-container h2 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #333;
        }

        .recovery-container p {
            background: #e7f4e4;
            color: #3c763d;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .recovery-container input {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .recovery-container button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .recovery-container button:hover {
            background: #0056b3;
        }

        @media screen and (max-width: 480px) {
            .recovery-container {
                width: 90%;
                padding: 15px;
            }

            .recovery-container h2 {
                font-size: 18px;
            }

            .recovery-container p {
                font-size: 12px;
                padding: 8px;
            }

            .recovery-container input {
                width: 90%;
            }

            .recovery-container button {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <form method="POST" action="">
            <h2>Account Recovery</h2>
            <p>Please enter your email to recover your account.</p>
            <input type="email" name="email" id="email" placeholder="@example.com" required>
            <button type="submit" name="update">Check Email</button>
        </form>
    </div>
</body>
</html>
