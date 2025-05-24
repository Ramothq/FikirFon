<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/S3Helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum açmanız gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu']);
    exit;
}

if (!isset($_POST['media_id'])) {
    echo json_encode(['success' => false, 'error' => 'Medya ID belirtilmedi']);
    exit;
}

$mediaId = (int)$_POST['media_id'];

try {
    // Medya bilgilerini al
    $stmt = $db->prepare('
        SELECT * FROM media 
        WHERE id = $1 AND user_id = $2
    ');
    
    $stmt->execute([$mediaId, $_SESSION['user_id']]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$media) {
        throw new Exception('Medya bulunamadı veya silme yetkiniz yok');
    }
    
    // S3'ten sil
    $s3Helper = new S3Helper();
    $result = $s3Helper->deleteFile($media['s3_key']);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // Veritabanından sil
    $stmt = $db->prepare('DELETE FROM media WHERE id = $1');
    $stmt->execute([$mediaId]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 