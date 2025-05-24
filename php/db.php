<?php
$host = "database-1.ckpmm2coabsv.us-east-1.rds.amazonaws.com";
$port = "5432"; // PostgreSQL varsayılan portu
$dbname = "fikirfon"; // Veritabanı adı
$user = "postgres"; // PostgreSQL kullanıcı adı (default: postgres)
$password = "MVY75MVY"; // PostgreSQL şifren

// PostgreSQL bağlantısını oluştur
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Veritabanı bağlantısı başarısız: " . pg_last_error());
}
?>
