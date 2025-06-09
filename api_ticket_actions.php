<?php
session_start();
require_once 'inc/connect.php';
require_once 'shared/log.php';
require_once 'shared/notify.php';

function registrarMudanca($pdo, $ticket_id, $user_id, $campo, $old, $new) {
    if ($old !== $new) {
        $stmt = $pdo->prepare("INSERT INTO changes (ticket_id, user_id, campo, valor_anterior, valor_novo) VALUES (:tid, :uid, :c, :old, :new)");
        $stmt->execute([
            'tid' => $ticket_id,
            'uid' => $user_id,
            'c'   => $campo,
            'old' => $old,
            'new' => $new
        ]);
        return true;
    }
    return false;
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ajax_action = $_POST['ajax_action'] ?? '';

    if ($ajax_action === 'add_comment') {
        $ticket_id = $_POST['ticket_id'] ?? '';
        $comentario = $_POST['comentario'] ?? '';
        $visivel_usuario = isset($_POST['worknote']) && $_POST['worknote'] == '1' ? 0 : 1;
        $anexo = null;

        if (empty($ticket_id) || empty($comentario)) {
            $response = [
                'success' => false,
                'message' => 'Dados incompletos'
            ];
        } else {
            $stmtCheck = $pdo->prepare("SELECT t.*, u.email AS user_email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = :id");
            $stmtCheck->execute(['id' => $ticket_id]);
            $ticket = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$ticket) {
                $response = [
                    'success' => false,
                    'message' => 'Chamado não encontrado'
                ];
            } else {
                if (!empty($_FILES['anexo']['name'])) {
                    $arquivoTmp = $_FILES['anexo']['tmp_name'];
                    $nomeArq = $_FILES['anexo']['name'];
                    $destino = 'uploads/' . uniqid() . '_' . $nomeArq;
                    if (move_uploaded_file($arquivoTmp, $destino)) {
                        $anexo = $destino;
                    }
                }

                $stmtCom = $pdo->prepare("INSERT INTO comentarios (ticket_id, user_id, conteudo, visivel_usuario, anexo) VALUES (:tid, :uid, :c, :vu, :a)");
                $stmtCom->execute([
                    'tid' => $ticket_id,
                    'uid' => $user_id,
                    'c'   => $comentario,
                    'vu'  => $visivel_usuario,
                    'a'   => $anexo
                ]);
                $comment_id = $pdo->lastInsertId();
                $stmtUser = $pdo->prepare("SELECT nome FROM users WHERE id = :id");
                $stmtUser->execute(['id' => $user_id]);
                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($visivel_usuario && $ticket['user_id'] != $user_id) {
                    enviarNotificacao($ticket['user_id'], 'Seu chamado #' . $ticket_id . ' recebeu um comentário', $comentario);
                }
                registrarLog($pdo, 'ACAO', 'Comentário adicionado ao ticket '.$ticket_id, $user_id);

                $stmtComment = $pdo->prepare("SELECT c.*, u.nome FROM comentarios c JOIN users u ON c.user_id = u.id WHERE c.id = :id");
                $stmtComment->execute(['id' => $comment_id]);
                $commentData = $stmtComment->fetch(PDO::FETCH_ASSOC);
                $response = [
                    'success' => true,
                    'message' => 'Comentário adicionado com sucesso',
                    'comment' => $commentData
                ];
            }
        }
    } elseif ($ajax_action === 'update_ticket' && ($role === 'analista' || $role === 'administrador')) {
        $ticket_id = $_POST['ticket_id'] ?? '';
        if (empty($ticket_id)) {
            $response = ['success' => false, 'message' => 'ID do chamado não especificado'];
        } else {
            $stmtCheck = $pdo->prepare("SELECT * FROM tickets WHERE id = :id");
            $stmtCheck->execute(['id' => $ticket_id]);
            $ticket = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$ticket) {
                $response = ['success' => false, 'message' => 'Chamado não encontrado'];
            } else {
                $oldPrioridade = $ticket['prioridade'];
                $oldEstado     = $ticket['estado'];
                $oldRisco      = $ticket['risco'];
                $oldAssigned   = $ticket['assigned_to'];

                $novaPrioridade = $_POST['nova_prioridade'] ?? $oldPrioridade;
                $novoEstado     = $_POST['novo_estado'] ?? $oldEstado;
                $novoRisco      = $_POST['novo_risco'] ?? $oldRisco;
                $novoAssigned   = $_POST['novo_assigned_to'] ?? $oldAssigned;

                $stmtUpd = $pdo->prepare("UPDATE tickets SET prioridade=:p, estado=:e, risco=:r, assigned_to=:a WHERE id=:id");
                $result = $stmtUpd->execute([
                    'p'=>$novaPrioridade,
                    'e'=>$novoEstado,
                    'r'=>$novoRisco,
                    'a'=>$novoAssigned,
                    'id'=>$ticket_id
                ]);
                if ($result) {
                    $changedFields = [];
                    if (registrarMudanca($pdo, $ticket_id, $user_id, 'prioridade', $oldPrioridade, $novaPrioridade)) { $changedFields[] = 'prioridade'; }
                    if (registrarMudanca($pdo, $ticket_id, $user_id, 'estado', $oldEstado, $novoEstado)) { $changedFields[] = 'estado'; }
                    if (registrarMudanca($pdo, $ticket_id, $user_id, 'risco', $oldRisco, $novoRisco)) { $changedFields[] = 'risco'; }
                    if (registrarMudanca($pdo, $ticket_id, $user_id, 'assigned_to', $oldAssigned, $novoAssigned)) { $changedFields[] = 'assigned_to'; }

                    $stmtTicket = $pdo->prepare("SELECT t.*, u.nome as assigned_nome FROM tickets t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.id = :id");
                    $stmtTicket->execute(['id' => $ticket_id]);
                    $updatedTicket = $stmtTicket->fetch(PDO::FETCH_ASSOC);
                    $updatedTicket['changed_fields'] = $changedFields;

                    if ($novoAssigned && $novoAssigned != $oldAssigned) {
                        enviarNotificacao($novoAssigned, 'Novo chamado atribuído', 'Você foi designado ao chamado #' . $ticket_id);
                    }
                    registrarLog($pdo, 'ACAO', 'Ticket atualizado '.$ticket_id, $user_id);

                    $response = [
                        'success' => true,
                        'message' => 'Chamado atualizado com sucesso',
                        'ticket' => $updatedTicket
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Erro ao atualizar chamado'];
                }
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'check_comments') {
        $ticket_id = $_GET['ticket_id'] ?? '';
        $since = $_GET['since'] ?? '0';
        if (empty($ticket_id)) {
            $response = ['success' => false, 'message' => 'ID do chamado não especificado'];
        } else {
            $sinceDate = date('Y-m-d H:i:s', intval($since / 1000));
            if ($role === 'analista' || $role === 'administrador') {
                $stmtComments = $pdo->prepare("SELECT c.*, u.nome FROM comentarios c JOIN users u ON c.user_id = u.id WHERE c.ticket_id = :tid AND c.data_criacao > :since AND c.user_id != :uid ORDER BY c.data_criacao DESC");
            } else {
                $stmtComments = $pdo->prepare("SELECT c.*, u.nome FROM comentarios c JOIN users u ON c.user_id = u.id WHERE c.ticket_id = :tid AND c.data_criacao > :since AND c.user_id != :uid AND c.visivel_usuario = 1 ORDER BY c.data_criacao DESC");
            }
            $stmtComments->execute([
                'tid' => $ticket_id,
                'since' => $sinceDate,
                'uid' => $user_id
            ]);
            $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'comments' => $comments];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
