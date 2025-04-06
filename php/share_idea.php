<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fikir Paylaş</title>
    <link rel="stylesheet" href="../css/share_idea.css">
</head>
<body>

    <!-- Navbar (profile.php ile aynı) -->
    <nav>
        <div class="nav-left">
            <h1>FikirFon</h1>
        </div>
        <div class="nav-center">
            <input type="text" class="search-box" placeholder="Kullanıcı Ara...">
        </div>
        <div class="nav-right">
            <a href="profile.php">Profil</a>
            <a href="explore.php">Öne Çıkanlar</a>
            <a href="logout.php" class="logout-btn">Çıkış Yap</a>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <div class="main-container">
        <div class="idea-form">
            <h2>Yeni Fikir Paylaş</h2>
            <form action="process_idea.php" method="POST" enctype="multipart/form-data" class="idea-form">
                <input type="text" name="title" placeholder="Fikir Başlığı" required>
                <textarea name="description" placeholder="Fikir Açıklaması" rows="4" required></textarea>
                <select name="category" required>
                    <option value="">Kategori Seç</option>
                    <option value="Teknoloji">Teknoloji</option>
                    <option value="Sağlık">Sağlık</option>
                    <option value="Eğitim">Eğitim</option>
                </select>
                <input type="file" name="media">
                <button type="submit" class="submit-btn">Paylaş</button>
            </form>
        </div>
    </div>

</body>
</html>
