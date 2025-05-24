<?php
session_start();
require_once 'db_connect.php';
require_once 'helpers/S3Helper.php'; // S3Helper dosyasını dahil et

// JSON yanıtı için header
header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'Giriş yapmalısınız.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// POST ile fikir ID'sini al
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $idea_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($idea_id === false) {
        $response['error'] = 'Geçersiz fikir ID.';
        echo json_encode($response);
        exit();
    }

    // Fikrin mevcut kullanıcıya ait olup olmadığını kontrol et
    $check_query = "SELECT id FROM ideas WHERE id = $1 AND user_id = $2";
    $check_result = pg_query_params($conn, $check_query, array($idea_id, $user_id));

    if (!$check_result || pg_num_rows($check_result) == 0) {
        $response['error'] = 'Bu fikri silme yetkiniz yok veya fikir bulunamadı.';
        echo json_encode($response);
        exit();
    }

    // Fikri sil
    $delete_query = "DELETE FROM ideas WHERE id = $1 AND user_id = $2";
    $delete_result = pg_query_params($conn, $delete_query, array($idea_id, $user_id));

    if ($delete_result) {
        $response['success'] = true;
        $response['message'] = 'Fikir başarıyla silindi.';
    } else {
        $response['error'] = 'Fikir silinirken bir hata oluştu.';
        // Daha detaylı hata için pg_last_error eklenebilir gerekirse
    }

} else {
    $response['error'] = 'Geçersiz istek.';
}

echo json_encode($response);

pg_close($conn);
?> 