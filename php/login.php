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
    <title>Giriş Yap - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
        }

        .auth-card {
            background: var(--white);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.5s ease;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .auth-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .auth-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .auth-footer a:hover {
            color: var(--primary-dark);
        }

        .error-message {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .auth-decoration {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .decoration-1 {
            top: 10%;
            left: 10%;
        }

        .decoration-2 {
            bottom: 10%;
            right: 10%;
            animation-delay: -3s;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-decoration decoration-1"></div>
        <div class="auth-decoration decoration-2"></div>
        
        <div class="auth-card">
            <div class="auth-header">
                <h1>FikirFon</h1>
                <p>Fikirlerinizi paylaşın, topluluğa katılın</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" required placeholder="E-posta adresinizi girin">
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required placeholder="Şifrenizi girin">
                </div>

                <button type="submit" class="auth-btn">Giriş Yap</button>
            </form>

            <div class="auth-footer">
                Hesabınız yok mu? <a href="register.php">Hemen Kaydolun</a>
            </div>
        </div>
    </div>
</body>
</html>
