<?php
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];

if ($requestUri === '/' || $requestUri === '') {
    echo json_encode([
        'message' => 'API Gateway',
        'endpoints' => ['/tickets', '/stats']
    ]);
    return;
} elseif (strpos($requestUri, '/tickets') === 0) {
    $service = 'http://tickets:80/index.php' . substr($requestUri, 8);
} elseif (strpos($requestUri, '/stats') === 0) {
    $service = 'http://stats:80/index.php' . substr($requestUri, 6);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Rota nao encontrada']);
    return;
}

// Log simples para verificar o encaminhamento das rotas
$log = sprintf("[%s] %s %s -> %s\n", date('c'), $method, $requestUri, isset($service) ? $service : 'N/A');
file_put_contents('php://stdout', $log, FILE_APPEND);

$options = [
    'http' => [
        'method' => $method,
        'header' => '',
        'content' => file_get_contents('php://input'),
    ]
];
foreach (getallheaders() as $name => $value) {
    if ($name === 'Host') continue;
    $options['http']['header'] .= "$name: $value\r\n";
}
$context = stream_context_create($options);
$response = file_get_contents($service . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''), false, $context);

$httpCode = 500;
if (isset($http_response_header[0]) && preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $matches)) {
    $httpCode = (int)$matches[1];
}
http_response_code($httpCode);
echo $response;
?>
