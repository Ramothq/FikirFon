<?php
session_start();
require_once "db_connect.php"; // ✅ Veritabanı bağlantısını içe aktar

// ✅ Bağlantıyı kontrol et
if (!isset($conn) || !$conn) {
    die("❌ process_idea.php içinde bağlantı başarısız!");
}
?>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ✅ Bağlantıyı içe aktar ve $conn değişkenini al
require_once "db_connect.php"; 

// ✅ Bağlantıyı kontrol et
if (!isset($conn) || !$conn) {
    die("❌ Veritabanı bağlantısı başarısız!");
}

$user_id = $_SESSION['user_id'];
$title = $_POST['title'];
$description = $_POST['description'];
$category = $_POST['category'];
$media_path = NULL; // Varsayılan olarak boş

// Dosya yükleme işlemi
if (!empty($_FILES['media']['name'])) {
    $upload_dir = "uploads/";
    $filename = time() . "_" . basename($_FILES["media"]["name"]);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES["media"]["tmp_name"], "../" . $target_path)) {
        $media_path = $target_path;
    } else {
        die("❌ Dosya yüklenirken hata oluştu!");
    }
}

// ✅ PostgreSQL sorgusu hazırlama
$query = "INSERT INTO ideas (user_id, title, description, category, media_url) VALUES ($1, $2, $3, $4, $5)";
pg_prepare($conn, "insert_idea", $query);
$result = pg_execute($conn, "insert_idea", array($user_id, $title, $description, $category, $media_path));

if ($result) {
    header("Location: profile.php?success=1");
} else {
    echo "❌ Hata oluştu!";
}
?>
