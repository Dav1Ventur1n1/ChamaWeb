<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CognitoAuth {
    private $config;
    private $client;

    public function __construct() {
        $this->config = require 'inc/cognito_config.php';
        $this->config = $this->config['cognito'];
        $this->client = new Client();
    }

    /**
     * Gera a URL de autorização do Cognito
     */
    public function getAuthorizationUrl() {
        $params = [
            'client_id' => $this->config['client_id'],
            'response_type' => 'code',
            'scope' => $this->config['scope'],
            'redirect_uri' => $this->config['redirect_uri']
        ];

        // Usar o endpoint específico de authorize
        $authorizeUrl = $this->config['authorize_endpoint'];
        
        return $authorizeUrl . '?' . http_build_query($params);
    }

    /**
     * Troca o código de autorização por tokens
     */
    public function getTokens($code) {
        try {
            // Usar o endpoint específico de token
            $tokenUrl = $this->config['token_endpoint'];
            
            $response = $this->client->post($tokenUrl, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'code' => $code,
                    'redirect_uri' => $this->config['redirect_uri']
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            error_log("Erro ao obter tokens: " . $e->getMessage());
            if ($e->hasResponse()) {
                error_log($e->getResponse()->getBody());
            }
            return false;
        }
    }

    /**
     * Obtém informações do usuário usando o token de acesso
     */
    public function getUserInfo($accessToken) {
        try {
            // Usar o endpoint específico de userInfo
            $userInfoUrl = $this->config['userinfo_endpoint'];
            
            $response = $this->client->get($userInfoUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            error_log("Erro ao obter informações do usuário: " . $e->getMessage());
            if ($e->hasResponse()) {
                error_log($e->getResponse()->getBody());
            }
            return false;
        }
    }

    /**
     * Gera a URL de logout
     * O formato correto da URL de logout do Cognito é:
     * https://<domínio>/logout?client_id=<app_client_id>&logout_uri=<url_de_redirecionamento>
     */
    public function getLogoutUrl($redirectUri = null) {
        if (!$redirectUri) {
            $redirectUri = 'http://localhost:8080/index.php';
        }
        
        // Construir a URL de logout corretamente
        // Usando a URL de logout específica do domínio
        $logoutUrl = 'https://' . $this->config['cognito_domain'] . '/logout';
        
        // Adicionar parâmetros
        $params = [
            'client_id' => $this->config['client_id'],
            'logout_uri' => $redirectUri
        ];
        
        return $logoutUrl . '?' . http_build_query($params);
    }

    /**
     * Verifica o token JWT e extrai as informações
     */
    public function verifyToken($idToken) {
        // Em uma implementação completa, você verificaria a assinatura do token
        // Aqui, estamos apenas decodificando para fins didáticos
        
        $parts = explode('.', $idToken);
        if (count($parts) != 3) {
            return false;
        }

        // Decodificar payload
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        return json_decode($payload, true);
    }
}