<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "database-1.ckpmm2coabsv.us-east-1.rds.amazonaws.com";
$port = "5432"; // PostgreSQL varsayılan portu
$dbname = "fikirfon";  // PostgreSQL veritabanı adı
$user = "postgres";     // PostgreSQL kullanıcı adı
$password = "MVY75MVY";   // PostgreSQL şifren

try {
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
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
