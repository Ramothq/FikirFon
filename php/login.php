<?php
session_start();
require 'db.php'; // Veritabanı bağlantısı

$error = ""; // Hata mesajını saklamak için değişken

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Kullanıcıyı veritabanından çek
    $query = "SELECT id, first_name, last_name, password FROM users WHERE email = $1";
    $result = pg_query_params($conn, $query, array($email));

    if ($row = pg_fetch_assoc($result)) {
        // Şifre doğrulama
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['first_name'] . " " . $row['last_name'];
            header("Location: profile.php");
            exit;
        } else {
            $error = "❌ Hatalı şifre!";
        }
    } else {
        $error = "❌ Bu e-posta ile kayıtlı kullanıcı bulunamadı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FikirFon - Giriş Yap</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<!-- En Üstte "FikirFon" Yazısı -->
<header>
    <h1 class="logo">FikirFon</h1>
</header>

<div class="container">
    <!-- Sol Taraf (Giriş Formu) -->
    <div class="left">
        <h2>Giriş Yap</h2>

        <!-- Hata Mesajı -->
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="E-Posta" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <button type="submit">Giriş Yap</button>
        </form>
        <p>Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p> <!-- Yönlendirme düzeltildi -->
    </div>

    <!-- Sağ Taraf (Slogan ve Animasyon) -->
    <div class="right">
        <p class="slogan">"Geleceği <span id="slogan-animation"></span>"</p>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sloganText = "Birlikte İnşa Ediyoruz!";
        let index = 0;
        function writeSlogan() {
            if (index < sloganText.length) {
                document.getElementById("slogan-animation").innerHTML += sloganText.charAt(index);
                index++;
                setTimeout(writeSlogan, 100);
            }
        }
        writeSlogan();
    });
</script>

</body>
</html>
