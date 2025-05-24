<?php
require 'db.php'; // Veritabanı bağlantısı

$error = ""; // Hata mesajı için değişken

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; // Formdan gelen rolü doğrudan kullan

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
    <title>Kayıt Ol - FikirFon</title>
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
            max-width: 600px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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

        .form-group select {
            padding: 0.8rem 1rem;
            border: 2px solid var(--primary-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-primary);
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.2);
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

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
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

            <?php if (isset($success)): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="register.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Ad</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="Adınız">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Soyad</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Soyadınız">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" required placeholder="E-posta adresiniz">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Şifre</label>
                        <input type="password" id="password" name="password" required placeholder="Şifreniz">
                    </div>

                    <div class="form-group">
                        <label for="role">Rolünüzü Seçin</label>
                        <select id="role" name="role" required>
                            <option value="Girişimci">Girişimci</option>
                            <option value="Yatırımcı">Yatırımcı</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="auth-btn">Kayıt Ol</button>
            </form>

            <div class="auth-footer">
                Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a>
            </div>
        </div>
    </div>
</body>
</html>
