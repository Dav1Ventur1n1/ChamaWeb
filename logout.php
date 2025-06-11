<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'inc/connect.php';
require_once 'shared/log.php';

if (isset($_SESSION['user_id'])) {
    registrarLog($pdo, 'LOGOUT', 'Usuário saiu', $_SESSION['user_id']);
}

$wasCognitoAuth = isset($_SESSION['cognito_auth']) && $_SESSION['cognito_auth'];

session_destroy();

if ($wasCognitoAuth) {
    $domain = 'us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com';
    $clientId = '5drp597e5uk101sbcsqqcgsmmn';
    $logoutUri = 'http://localhost:8080/index.php';
    $logoutUrl = 'https://' . $domain . '/logout?client_id=' . $clientId . '&logout_uri=' . urlencode($logoutUri);
    header('Location: ' . $logoutUrl);
    exit;
}

header('Location: index.php');
exit;
?>
