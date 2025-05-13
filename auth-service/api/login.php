<?php
header('Content-Type: application/json');
require_once '../inc/connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$senha = $data['senha'] ?? '';

// Buscar usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

function registrarLog($pdo, $tipo, $descricao, $userId = null) {
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, tipo, descricao) VALUES (:uid, :t, :d)");
    $stmtLog->execute([
        'uid' => $userId,
        't'   => $tipo,
        'd'   => $descricao
    ]);
}

if ($user && !$user['blocked']) {
    if ($senha === $user['senha']) {
        registrarLog($pdo, 'LOGIN', 'Login bem-sucedido', $user['id']);

        // Enviar dados básicos para frontend salvar localmente
        echo json_encode([
            "status" => "success",
            "nome"   => $user['nome'],
            "role"   => $user['role'],
            "id"     => $user['id']
        ]);
        exit;
    } else {
        registrarLog($pdo, 'ERRO_LOGIN', 'Senha incorreta para ' . $email);
    }
} else {
    registrarLog($pdo, 'ERRO_LOGIN', 'Tentativa de login inválido: ' . $email);
}

echo json_encode(["status" => "fail"]);
http_response_code(401);
