<?php
// Oturum başlatılıyor
session_start();

// Tüm oturum verilerini temizle
$_SESSION = array();

// Oturum çerezini sil
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Oturumu sonlandır
session_destroy();

// Giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>
