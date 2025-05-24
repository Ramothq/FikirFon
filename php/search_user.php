<?php
session_start();
require 'db_connect.php';

// JSON yanıtı için header
header('Content-Type: application/json');

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Lütfen önce giriş yapın.']);
    exit;
}

// Arama sorgusunu al
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// Kullanıcıları ara
$search_query = "SELECT id, first_name, last_name, role 
                FROM users 
                WHERE LOWER(first_name) LIKE LOWER($1) 
                OR LOWER(last_name) LIKE LOWER($1) 
                OR LOWER(role) LIKE LOWER($1)
                ORDER BY first_name, last_name
                LIMIT 10";

$search_term = '%' . $query . '%';
$result = pg_query_params($conn, $search_query, array($search_term));

if (!$result) {
    echo json_encode(['error' => 'Arama sırasında bir hata oluştu.']);
    exit;
}

$users = array();
while ($row = pg_fetch_assoc($result)) {
    $users[] = array(
        'id' => $row['id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'role' => $row['role']
    );
}

echo json_encode($users);
?>
