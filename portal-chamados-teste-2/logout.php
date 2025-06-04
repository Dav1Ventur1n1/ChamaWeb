<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'inc/connect.php';

function registrarLog($pdo, $tipo, $descricao, $userId = null) {
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, tipo, descricao) VALUES (:uid, :t, :d)");
    $stmtLog->execute([
        'uid' => $userId,
        't'   => $tipo,
        'd'   => $descricao
    ]);
}

// Registrar o logout nos logs
if (isset($_SESSION['user_id'])) {
    registrarLog($pdo, 'LOGOUT', 'Usuário saiu', $_SESSION['user_id']);
}

// Verificar se o login foi feito via Cognito
$wasCognitoAuth = isset($_SESSION['cognito_auth']) && $_SESSION['cognito_auth'];

// Destruir a sessão local
session_destroy();

// Se autenticado via Cognito, redirecionar para o logout do Cognito
if ($wasCognitoAuth) {
    // Definir variáveis para a URL de logout
    $domain = 'us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com';
    $clientId = '5drp597e5uk101sbcsqqcgsmmn';
    $logoutUri = 'http://localhost:8080/index.html';
    
    // Construir a URL de logout
    $logoutUrl = 'https://' . $domain . '/logout?client_id=' . $clientId . '&logout_uri=' . urlencode($logoutUri);
    
    // Redirecionar para o logout do Cognito
    header('Location: ' . $logoutUrl);
    exit;
}

// Para login normal (não-Cognito), redirecionar para a página inicial
header('Location: index.html');
exit;