<?php
session_start();
require_once 'auth_token.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$ch = curl_init('http://gateway:80/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['jwt'] ?? '')
]);
$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>
