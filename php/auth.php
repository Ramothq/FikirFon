<?php
session_start();

// Oturum kontrolü için yardımcı fonksiyon
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kullanıcı bilgilerini getir
function getUserData($conn, $userId) {
    $query = "SELECT * FROM users WHERE id = $1";
    $result = pg_query_params($conn, $query, array($userId));
    return pg_fetch_assoc($result);
}

// Kullanıcı rolünü kontrol et
function checkUserRole($conn, $userId, $requiredRole) {
    $query = "SELECT role FROM users WHERE id = $1";
    $result = pg_query_params($conn, $query, array($userId));
    $user = pg_fetch_assoc($result);
    return $user && $user['role'] === $requiredRole;
}
?> 