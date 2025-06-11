<?php
session_start();
require_once '../vendor/autoload.php'; 
require_once '../inc/CognitoAuth.php';

// Cria a instância de autenticação
$auth = new CognitoAuth();

// Gera uma URL de autorização para o Cognito
$authUrl = $auth->getAuthorizationUrl();

// Redireciona para a URL do Cognito
header('Location: ' . $authUrl);
exit;