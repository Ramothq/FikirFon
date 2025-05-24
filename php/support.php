<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || !isset($_POST['idea_id'])) {
    http_response_code(400);
    echo "Geçersiz istek.";
    exit;
}

$user_id = $_SESSION['user_id'];
$idea_id = $_POST['idea_id'];

// Daha önce destek vermiş mi kontrol et
$check = pg_query_params($conn, "SELECT * FROM supports WHERE user_id = $1 AND idea_id = $2", [$user_id, $idea_id]);

if (pg_num_rows($check) > 0) {
    // Zaten destek vermiş, destekten çekiliyor
    pg_query_params($conn, "DELETE FROM supports WHERE user_id = $1 AND idea_id = $2", [$user_id, $idea_id]);
    echo "destek-cekildi";
} else {
    // Destek veriliyor
    pg_query_params($conn, "INSERT INTO supports (user_id, idea_id) VALUES ($1, $2)", [$user_id, $idea_id]);
    echo "desteklendi";
}
?>
