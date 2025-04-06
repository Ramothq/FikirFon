<?php
$host = "localhost";
$dbname = "fikirfon";  // PostgreSQL veritabanı adı
$user = "postgres";     // PostgreSQL kullanıcı adı
$password = "MVY75";   // PostgreSQL şifren (PostgreSQL’in şifresini buraya yaz!)

$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("❌ Veritabanına bağlanılamadı!");
} else {
    echo "✅ Bağlantı başarılı!";
}
?>
