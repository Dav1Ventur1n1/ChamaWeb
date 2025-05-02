<?php
session_start();
require_once 'inc/connect.php';

function registrarLog($pdo, $tipo, $descricao, $userId = null) {
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, tipo, descricao) VALUES (:uid, :t, :d)");
    $stmtLog->execute([
        'uid' => $userId,
        't'   => $tipo,
        'd'   => $descricao
    ]);
}

if (isset($_SESSION['user_id'])) {
    registrarLog($pdo, 'LOGOUT', 'Usuário saiu', $_SESSION['user_id']);
}

session_destroy();
header('Location: index.html');
exit;
