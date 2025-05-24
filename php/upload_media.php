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

if (!isset($_FILES['media'])) {
    echo json_encode(['success' => false, 'error' => 'Dosya yüklenmedi']);
    exit;
}

$file = $_FILES['media'];
$ideaId = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : null;
$type = isset($_POST['type']) ? $_POST['type'] : 'idea'; // 'profile' veya 'idea'

// Dosya türü kontrolü
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Desteklenmeyen dosya türü']);
    exit;
}

// Dosya boyutu kontrolü (10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Dosya boyutu çok büyük']);
    exit;
}

try {
    $s3Helper = new S3Helper();
    
    // Dosya yükleme dizini belirleme
    $directory = $type === 'profile' ? 'profile_images' : 'idea_media';
    
    // S3'e yükleme
    $result = $s3Helper->uploadFile($file, $directory);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // Veritabanına kaydetme
    $stmt = $db->prepare('
        INSERT INTO media (user_id, idea_id, file_name, file_type, file_size, s3_key)
        VALUES ($1, $2, $3, $4, $5, $6)
        RETURNING id
    ');
    
    $stmt->execute([
        $_SESSION['user_id'],
        $ideaId,
        $file['name'],
        $file['type'],
        $file['size'],
        $result['key']
    ]);
    
    $mediaId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'media_id' => $mediaId,
        'url' => $result['url']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 