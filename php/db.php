<?php
$host = "localhost";
$port = "5432"; // PostgreSQL varsayılan portu
$dbname = "fikirfon"; // Veritabanı adı
$user = "postgres"; // PostgreSQL kullanıcı adı (default: postgres)
$password = "MVY75"; // PostgreSQL şifren

// PostgreSQL bağlantısını oluştur
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Bağlantı hatası: " . pg_last_error());
}
?>
