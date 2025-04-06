<?php
session_start();
require 'db.php'; // Veritabanı bağlantısı

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini veritabanından çek
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, role FROM users WHERE id = $1";
$result = pg_query_params($conn, $query, array($user_id));

if ($row = pg_fetch_assoc($result)) {
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $role = ucfirst($row['role']); // İlk harfi büyük yap
} else {
    echo "❌ Kullanıcı bulunamadı!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - FikirFon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/profile.css">
</head>
<body>

<!-- MODERN NAVBAR -->
<nav>
    <div class="nav-left">
        <h1>FikirFon</h1>
    </div>
    <div class="nav-center">
        <input type="text" class="search-box" placeholder="Kullanıcı, fikir veya etiket ara...">
    </div>
    <div class="nav-right">
        <a href="logout.php" class="logout-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Çıkış Yap
        </a>
    </div>
</nav>

<!-- MODERN PROFİL KARTI -->
<main>
    <div class="profile-container">
        <img src="../images/default-profile.jpg" alt="Profil Resmi" class="profile-img">
        <h2><?php echo htmlspecialchars($first_name . " " . $last_name); ?></h2>
        <p class="user-role"><?php echo htmlspecialchars($role); ?></p>
        <button class="edit-profile">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Profili Düzenle
        </button>
        
        <!-- Fikir Paylaş Butonu -->
        <button id="shareIdeaBtn" class="share-idea-btn">Fikir Paylaş</button>

        <!-- Fikir Paylaş Formu (Başta gizli) -->
        <div id="shareIdeaForm" style="display:none;">
            <h3>Fikir Paylaş</h3>
            <form action="process_idea.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Fikir Başlığı" required>
                <textarea name="description" placeholder="Fikir Açıklaması" required></textarea>
                <select name="category" required>
                    <option value="">Kategori Seç</option>
                    <option value="Teknoloji">Teknoloji</option>
                    <option value="İş">İş</option>
                    <option value="Sanat">Sanat</option>
                </select>
                <input type="file" name="media" accept="image/*,video/*">
                <button type="submit">Paylaş</button>
            </form>
        </div>
    </div>
</main>

<!-- Fikirleri Göster -->
<?php
$query = "SELECT * FROM ideas WHERE user_id = $1 ORDER BY created_at DESC";
$result = pg_query_params($conn, $query, array($user_id));
echo "<div class='idea-container'>";

// Eğer fikir varsa, ekrana yazdır
if (pg_num_rows($result) > 0) {
    echo "<div class='idea-list'>";
    while ($row = pg_fetch_assoc($result)) {
        echo "<div class='idea-card'>";
        echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($row['description'])) . "</p>";
        echo "<p><strong>Kategori:</strong> " . htmlspecialchars($row['category']) . "</p>";

        // Eğer medya dosyası varsa göster
        if (!empty($row['media_url'])) {
            echo "<p><strong>Eklenen Medya:</strong></p>";
            echo "<img src='" . htmlspecialchars($row['media_url']) . "' alt='Fikir görseli' class='idea-image'>";
        }

        echo "<p><small>Paylaşım Tarihi: " . $row['created_at'] . "</small></p>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p>Henüz hiç fikir paylaşmadın.</p>";
}
echo "</div>"; // Kapatma etiketi
?>

<!-- JavaScript -->
<script>
    // Fikir Paylaş Butonuna tıklandığında formu aç
    document.getElementById("shareIdeaBtn").onclick = function() {
        var form = document.getElementById("shareIdeaForm");
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
        } else {
            form.style.display = "none";
        }
    }
</script>

</body>
</html>
