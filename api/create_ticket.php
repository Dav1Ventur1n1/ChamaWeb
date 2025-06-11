<?php
// Proxy endpoint that forwards ticket creation to the ticket microservice via the gateway.
// This keeps browser requests same-origin and allows session reuse.
session_start();
require_once "../auth_token.php";
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$data = [
    'titulo' => $_POST['titulo'] ?? '',
    'descricao' => $_POST['descricao'] ?? '',
    'categoria_id' => $_POST['categoria_id'] ?? null,
    'servico_impactado' => $_POST['servico'] ?? '',
    'tipo' => $_POST['tipo'] ?? 'Incidente',
    'prioridade' => $_POST['prioridade'] ?? 'Baixo',
    'risco' => $_POST['risco'] ?? 'Baixo',
    'user_id' => $_SESSION['user_id'],
    'assigned_to' => $_POST['assigned_to'] ?? null,
    'assigned_team_id' => $_POST['assigned_team'] ?? null
];

$ch = curl_init('http://gateway:80/tickets');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . ($_SESSION['jwt'] ?? '')
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    $msg = 'Falha ao criar chamado';
    if ($curl_err) {
        $msg .= ': ' . $curl_err;
    }
    echo json_encode(['error' => $msg, 'response' => $response, 'code' => $http_code]);
}
?>
