<?php
// Oturum başlatılıyor
session_start();

// Kullanıcı bilgilerini temizleyin
session_unset();

// Oturumdan çıkış yapın
session_destroy();

// Giriş sayfasına yönlendirin
header("Location: login.php");
exit;
?>
