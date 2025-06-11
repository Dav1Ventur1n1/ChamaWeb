<?php
// Simple JWT helper functions
const JWT_SECRET = 'my_super_secret_key';

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret = JWT_SECRET): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload))
    ];
    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

function jwt_decode(string $token, string $secret = JWT_SECRET) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    list($b64Header, $b64Payload, $b64Signature) = $parts;
    $signature = base64url_decode($b64Signature);
    $valid = hash_equals($signature, hash_hmac('sha256', "$b64Header.$b64Payload", $secret, true));
    if (!$valid) return false;
    $payload = json_decode(base64url_decode($b64Payload), true);
    if (!$payload) return false;
    if (isset($payload['exp']) && time() > $payload['exp']) return false;
    return $payload;
}
