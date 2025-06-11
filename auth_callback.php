<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'inc/CognitoAuth.php';
require_once 'inc/connect.php';
require_once 'shared/log.php';

if (!isset($_GET['code'])) {
    header('Location: /index.php?erro=2');
    exit;
}

$auth = new CognitoAuth();
$code = $_GET['code'];
$tokens = $auth->getTokens($code);
if (!$tokens) {
    header('Location: /index.php?erro=3');
    exit;
}

$userInfo = $auth->verifyToken($tokens['id_token'] ?? '');
if (!$userInfo) {
    $userInfo = $auth->getUserInfo($tokens['access_token'] ?? '');
    if (!$userInfo) {
        header('Location: /index.php?erro=4');
        exit;
    }
}

$email = $userInfo['email'] ?? '';
$name = $userInfo['name'] ?? ($userInfo['given_name'] ?? 'Usuário Cognito');
$sub  = $userInfo['sub'] ?? '';

if (empty($email)) {
    header('Location: /index.php?erro=5');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $senhaTemp = bin2hex(random_bytes(8));
    $senhaHash = hash('sha256', $senhaTemp);
    $stmtNewUser = $pdo->prepare('INSERT INTO users (nome, email, senha, role) VALUES (:nome, :email, :senha, "usuario")');
    $stmtNewUser->execute([
        'nome' => $name,
        'email' => $email,
        'senha' => $senhaHash
    ]);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($user['blocked']) {
    registrarLog($pdo, 'ERRO_LOGIN', 'Tentativa de login de usuário bloqueado via Cognito: ' . $email);
    header('Location: /index.php?erro=6');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['nome'] = $user['nome'];
$_SESSION['role'] = $user['role'];
$_SESSION['cognito_auth'] = true;

registrarLog($pdo, 'LOGIN', 'Login via Cognito', $user['id']);
header('Location: dashboard.php');
exit;
?>
