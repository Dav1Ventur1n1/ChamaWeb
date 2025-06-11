<?php 
session_start();
require_once 'inc/connect.php';
require_once 'auth_token.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Configurar cabeçalho e token
$headers = [
    "Authorization: Bearer " . ($_SESSION['jwt'] ?? '')
];

// Construir URL base da API via gateway
$apiUrl = 'http://gateway:80/tickets';

// Montar query string com filtros
$queryParams = [];

if (!empty($_GET['pesquisa'])) {
    $queryParams['pesquisa'] = $_GET['pesquisa'];
}
if (!empty($_GET['team_id'])) {
    $queryParams['team_id'] = $_GET['team_id'];
}
if (!empty($queryParams)) {
    $apiUrl .= '?' . http_build_query($queryParams);
}

// Iniciar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro na requisição: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

header('Content-Type: application/json');
echo $response;
