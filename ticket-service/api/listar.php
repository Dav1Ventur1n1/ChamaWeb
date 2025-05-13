<?php
header('Content-Type: application/json');
require_once '../inc/connect.php';

$id = $_GET['id'] ?? null;
$role = $_GET['role'] ?? 'usuario';

$where = [];
$params = [];

if (!$id) {
    http_response_code(400);
    echo json_encode(["erro" => "ID não fornecido"]);
    exit;
}

if ($role !== 'analista' && $role !== 'administrador') {
    $where[] = "user_id = :uid";
    $params['uid'] = $id;
}

$sql = "SELECT * FROM tickets";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY data_abertura DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($tickets);
