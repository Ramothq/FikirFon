<?php
require 'php/db.php';
require 'php/auth.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fikirleri getir
$query = "SELECT i.*, u.first_name, u.last_name, c.name as category_name 
          FROM ideas i 
          JOIN users u ON i.user_id = u.id 
          JOIN categories c ON i.category_id = c.id 
          ORDER BY i.created_at DESC";
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FikirFon - Ana Sayfa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-inner-container">
            <div class="nav-left">
                <a href="index.php" class="nav-logo">FikirFon</a>
            </div>
            
            <div class="nav-center">
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Kullanıcı ara..." class="search-input">
                    <button type="submit" class="search-btn">Ara</button>
                </form>
            </div>
            
            <div class="nav-right">
                <a href="trending.php" class="nav-link">Trend Fikirler</a>
                <a href="share_idea.php" class="nav-link">Fikir Paylaş</a>
                <a href="profile.php" class="nav-link">Profilim</a>
                <a href="php/logout.php" class="nav-link">Çıkış Yap</a>
            </div>
        </div>
    </nav>

    <main class="main">
        <div class="container">
            <div class="ideas-grid">
                <?php while ($idea = pg_fetch_assoc($result)): ?>
                    <div class="idea-card">
                        <div class="idea-header">
                            <h3 class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></h3>
                            <span class="idea-category"><?php echo htmlspecialchars($idea['category_name']); ?></span>
                        </div>
                        
                        <p class="idea-description"><?php echo htmlspecialchars($idea['description']); ?></p>
                        
                        <div class="idea-footer">
                            <div class="idea-author">
                                <span class="author-name"><?php echo htmlspecialchars($idea['first_name'] . ' ' . $idea['last_name']); ?></span>
                            </div>
                            <div class="idea-date">
                                <?php echo date('d.m.Y', strtotime($idea['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FikirFon</h3>
                    <p>Fikirlerinizi paylaşın, topluluğa katılın</p>
                </div>
                <div class="footer-section">
                    <h3>Hızlı Bağlantılar</h3>
                    <ul>
                        <li><a href="index.php">Ana Sayfa</a></li>
                        <li><a href="trending.php">Trend Fikirler</a></li>
                        <li><a href="share_idea.php">Fikir Paylaş</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>İletişim</h3>
                    <p>Email: info@fikirfon.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 FikirFon. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
</body>
</html> 