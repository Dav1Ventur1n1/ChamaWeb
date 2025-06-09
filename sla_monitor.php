<?php
require_once 'shared/connect.php';
require_once 'shared/notify.php';
require_once 'shared/log.php';

$stmt = $pdo->query("SELECT t.id, t.sla_due, t.user_id, u.email FROM tickets t JOIN users u ON t.user_id=u.id WHERE t.estado != 'Fechado' AND t.sla_due IS NOT NULL AND t.sla_due <= DATE_ADD(NOW(), INTERVAL 1 HOUR)");

foreach ($stmt as $ticket) {
    enviarNotificacao($ticket['user_id'], 'Aviso de SLA', 'Seu chamado #' . $ticket['id'] . ' está próximo do vencimento do SLA.');
    registrarLog($pdo, 'ACAO', 'Notificação de SLA enviada para ticket '.$ticket['id'], $ticket['user_id']);
}
?>
