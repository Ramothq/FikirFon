<?php
session_start();
require 'db_connect.php';

// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON yanıtı için header
header('Content-Type: application/json');

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Lütfen önce giriş yapın.']);
    exit;
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['idea_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$idea_id = $_POST['idea_id'];
$user_id = $_SESSION['user_id'];

// Fikrin var olup olmadığını kontrol et
$query = "SELECT id FROM ideas WHERE id = $1";
$result = pg_query_params($conn, $query, array($idea_id));

if (pg_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Fikir bulunamadı.']);
    exit;
}

// Kullanıcının daha önce destekleyip desteklemediğini kontrol et
$query = "SELECT id FROM supports WHERE idea_id = $1 AND user_id = $2";
$result = pg_query_params($conn, $query, array($idea_id, $user_id));
$existing_support = pg_fetch_assoc($result);

if ($existing_support) {
    // Destek varsa kaldır
    $query = "DELETE FROM supports WHERE idea_id = $1 AND user_id = $2";
    $result = pg_query_params($conn, $query, array($idea_id, $user_id));
    
    if ($result) {
        // Güncel destek sayısını al
        $query = "SELECT COUNT(*) as support_count FROM supports WHERE idea_id = $1";
        $result = pg_query_params($conn, $query, array($idea_id));
        $support_count = pg_fetch_assoc($result)['support_count'];
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'support_count' => $support_count,
            'message' => 'Destek başarıyla kaldırıldı.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Destek kaldırılırken bir hata oluştu.']);
    }
} else {
    // Destek yoksa ekle
    $query = "INSERT INTO supports (idea_id, user_id) VALUES ($1, $2)";
    $result = pg_query_params($conn, $query, array($idea_id, $user_id));
    
    if ($result) {
        // Güncel destek sayısını al
        $query = "SELECT COUNT(*) as support_count FROM supports WHERE idea_id = $1";
        $result = pg_query_params($conn, $query, array($idea_id));
        $support_count = pg_fetch_assoc($result)['support_count'];
        
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'support_count' => $support_count,
            'message' => 'Fikir başarıyla desteklendi.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Destek eklenirken bir hata oluştu.']);
    }
}
?> 