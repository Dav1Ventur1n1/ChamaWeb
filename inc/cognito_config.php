<?php
// Configurações do Amazon Cognito
return [
    'cognito' => [
        'region' => 'us-east-2',
        'user_pool_id' => 'us-east-2_nGsr1zSvz',
        'client_id' => '5drp597e5uk101sbcsqqcgsmmn',
        'client_secret' => 'l0c2bbuk3l63h0u2d25cec4is0a05koj791bu5k2gfesrc8ntre',
        'redirect_uri' => 'http://localhost:8080/auth_callback.php',
        'scope' => 'email openid phone',
        
        // Domínio completo do Cognito (sem https://)
        'cognito_domain' => 'us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com',
        
        // URLs completos para os endpoints
        'authorize_endpoint' => 'https://us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com/oauth2/authorize',
        'token_endpoint' => 'https://us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com/oauth2/token',
        'userinfo_endpoint' => 'https://us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com/oauth2/userInfo',
        'logout_endpoint' => 'https://us-east-2ngsr1zsvz.auth.us-east-2.amazoncognito.com/logout'
    ]
];