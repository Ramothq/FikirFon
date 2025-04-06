<?php
require 'db.php'; // Veritabanı bağlantısı

$error = ""; // Hata mesajı için değişken

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // E-posta kontrolü
    $checkQuery = "SELECT * FROM users WHERE email = $1";
    $checkResult = pg_query_params($conn, $checkQuery, array($email));

    if (pg_num_rows($checkResult) > 0) {
        $error = "❌ Bu e-posta zaten kayıtlı!";
    } else {
        // Şifreyi hashle
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Kullanıcıyı ekle
        $insertQuery = "INSERT INTO users (first_name, last_name, email, password, role) VALUES ($1, $2, $3, $4, $5)";
        $result = pg_query_params($conn, $insertQuery, array($first_name, $last_name, $email, $hashedPassword, $role));

        if ($result) {
            header("Location: login.php");
            exit;
        } else {
            $error = "❌ Kayıt sırasında bir hata oluştu!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FikirFon - Kayıt Ol</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<header>
    <h1 class="logo">FikirFon</h1>
</header>

<div class="container">
    <div class="left">
        <h2>Kayıt Ol</h2>
        
        <!-- Hata mesajını formun içinde göstermek için -->
        <?php if (!empty($error)) { ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php } ?>

        <form action="register.php" method="POST">
            <input type="text" name="first_name" placeholder="Adınız" required>
            <input type="text" name="last_name" placeholder="Soyadınız" required>
            <input type="email" name="email" placeholder="E-Posta" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <select name="role" required>
                <option value="girisimci">Girişimci</option>
                <option value="yatirimci">Yatırımcı</option>
            </select>
            <button type="submit">Kayıt Ol</button>
        </form>
        <p>Zaten hesabın var mı? <a href="login.php">Giriş Yap</a></p>
    </div>

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
