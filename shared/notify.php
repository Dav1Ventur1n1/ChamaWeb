<?php
require_once __DIR__ . '/connect.php';
/**
 * Envia notificação ao usuário respeitando suas preferências.
 */
function enviarNotificacao($userId, $assunto, $mensagem) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.email, COALESCE(p.receive_email,1) AS receive_email
                           FROM users u
                           LEFT JOIN user_preferences p ON u.id = p.user_id
                           WHERE u.id = ?");
    $stmt->execute([$userId]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$info) {
        return false;
    }
    if ($info['receive_email']) {
        @mail($info['email'], $assunto, $mensagem);
    }
    return true;
}
