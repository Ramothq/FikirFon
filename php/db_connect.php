<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "fikirfon";  // PostgreSQL veritabanı adı
$user = "postgres";     // PostgreSQL kullanıcı adı
$password = "MVY75";   // PostgreSQL şifren

try {
    $conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");
    if (!$conn) {
        throw new Exception("Veritabanına bağlanılamadı: " . pg_last_error());
    }
    
    // Bağlantıyı test et
    $test_query = "SELECT 1";
    $test_result = pg_query($conn, $test_query);
    if (!$test_result) {
        throw new Exception("Veritabanı bağlantısı test edilemedi: " . pg_last_error($conn));
    }
    
} catch (Exception $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
