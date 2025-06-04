<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'inc/CognitoAuth.php';
require_once 'inc/connect.php';

// Função para registrar logs
function registrarLog($pdo, $tipo, $descricao, $userId = null) {
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, tipo, descricao) VALUES (:uid, :t, :d)");
    $stmtLog->execute([
        'uid' => $userId,
        't'   => $tipo,
        'd'   => $descricao
    ]);
}

// Verificar se recebemos o código do Cognito
if (!isset($_GET['code'])) {
    header('Location: index.html?erro=2');
    exit;
}

$auth = new CognitoAuth();
$code = $_GET['code'];

// Trocar o código por tokens
$tokens = $auth->getTokens($code);
if (!$tokens) {
    header('Location: index.html?erro=3');
    exit;
}

// Verificar e obter informações do usuário do token
$userInfo = $auth->verifyToken($tokens['id_token'] ?? '');
if (!$userInfo) {
    // Tentar obter via endpoint de userInfo se o decode falhar
    $userInfo = $auth->getUserInfo($tokens['access_token'] ?? '');
    if (!$userInfo) {
        header('Location: index.html?erro=4');
        exit;
    }
}

// Extrair informações do usuário (ajuste conforme os atributos retornados pelo Cognito)
$email = $userInfo['email'] ?? '';
$name = $userInfo['name'] ?? ($userInfo['given_name'] ?? 'Usuário Cognito');
$sub = $userInfo['sub'] ?? ''; // ID único do usuário no Cognito

// Se não tiver email, redirecionar para erro
if (empty($email)) {
    header('Location: index.html?erro=5');
    exit;
}

// Verificar se o usuário já existe no sistema
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir, criar um novo usuário
if (!$user) {
    $stmtNewUser = $pdo->prepare("INSERT INTO users (nome, email, senha, role) VALUES (:nome, :email, :senha, 'usuario')");
    $stmtNewUser->execute([
        'nome' => $name,
        'email' => $email,
        // Senha aleatória, usuário acessará apenas via Cognito
        'senha' => bin2hex(random_bytes(8))
    ]);
    
    // Buscar o ID do usuário recém-criado
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verificar se o usuário está bloqueado
if ($user['blocked']) {
    registrarLog($pdo, 'ERRO_LOGIN', 'Tentativa de login de usuário bloqueado via Cognito: ' . $email);
    header('Location: index.html?erro=6');
    exit;
}

// Se chegou até aqui, fazer login
$_SESSION['user_id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];
$_SESSION['role'] = $user['role'];
$_SESSION['cognito_auth'] = true; // Marcador para identificar login via Cognito

// Registrar login bem-sucedido
registrarLog($pdo, 'LOGIN', 'Login via Cognito', $user['id']);

// Redirecionar para o dashboard
header('Location: dashboard.php');
exit;