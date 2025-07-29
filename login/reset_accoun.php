<?php
include '../config.php'; // Pastikan koneksi ke database berhasil

// Fungsi untuk mendekripsi ID anggota
function decryptAES($encryptedText, $key) {
    $data = base64_decode($encryptedText);
    $iv = substr($data, 0, 16); // Ambil IV dari awal string
    $encryptedData = substr($data, 16);
    return openssl_decrypt($encryptedData, 'AES-256-CBC', $key, 0, $iv);
}

// Kunci enkripsi harus sama dengan yang digunakan pada pengirim
$secretKey = "mySecretKey12345";

if (isset($_GET['id_anggota'])) {
    $encrypted_id = $_GET['id_anggota'];
    $id_anggota = decryptAES($encrypted_id, $secretKey);

    if ($id_anggota && is_numeric($id_anggota)) {
        // Ambil data anggota berdasarkan ID
        $sql = $conn->prepare("SELECT email, username FROM anggota WHERE id_anggota = ?");
        $sql->bind_param("i", $id_anggota);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows > 0) {
            $anggota = $result->fetch_assoc();
            $email = htmlspecialchars($anggota['email']);
            $username = htmlspecialchars($anggota['username']);

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                $new_password = trim($_POST['password']);

                if (strlen($new_password) < 6) {
                    echo "<script>alert('Password harus minimal 6 karakter!');</script>";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    $update = $conn->prepare("UPDATE anggota SET password = ? WHERE id_anggota = ?");
                    $update->bind_param("si", $hashed_password, $id_anggota);

                    if ($update->execute()) {
                        echo "<script>alert('Password berhasil diubah.');</script>";
                        header("Location: login.php");
                    } else {
                        echo "<script>alert('Gagal mengubah password.');</script>";
                    }
                }
            } else {
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Reset Password</title>
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

                        .container {
                            background: #fff;
                            padding: 20px;
                            border-radius: 8px;
                            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                            text-align: center;
                        }

                        input {
                            width: 90%;
                            padding: 10px;
                            margin-top: 10px;
                            border: 1px solid #ccc;
                            border-radius: 4px;
                        }

                        button {
                            margin-top: 10px;
                            padding: 10px;
                            background: #007bff;
                            color: #fff;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                        }

                        button:hover {
                            background: #0056b3;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Reset Password untuk Akun: <br> <?php echo $username; ?></h2>
                        <form method="post">
                            <label for="password">Password Baru:</label><br>
                            <input type="password" name="password" id="password" required minlength="6"><br>
                            <button type="submit">Ubah Password</button>
                        </form>
                    </div>
                </body>
                </html>
                <?php
            }
        } else {
            echo "<script>alert('ID anggota tidak ditemukan.');</script>";
        }
    } else {
        echo "<script>alert('ID anggota tidak valid.');</script>";
    }
} else {
    echo "<script>alert('Parameter ID anggota tidak ditemukan.');</script>";
}
?>
