<?php
session_start();
require_once 'inc/connect.php';

// Função de registro de mudança de campo
function registrarMudanca($pdo, $ticket_id, $user_id, $campo, $old, $new) {
    if ($old !== $new) {
        $stmt = $pdo->prepare("INSERT INTO changes (ticket_id, user_id, campo, valor_anterior, valor_novo) 
                               VALUES (:tid, :uid, :c, :old, :new)");
        $stmt->execute([
            'tid' => $ticket_id,
            'uid' => $user_id,
            'c'   => $campo,
            'old' => $old,
            'new' => $new
        ]);
    }
}

// Função de enviar notificações (exemplo extremamente simples)
function enviarNotificacao($destinatarioEmail, $assunto, $mensagem) {
    // Em produção, configure mail() ou PHPMailer/SMTP etc.
    // mail($destinatarioEmail, $assunto, $mensagem);
    // Aqui, só simulamos:
    // echo "DEBUG: Enviando e-mail para $destinatarioEmail: $assunto - $mensagem\n";
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$ticket_id = $_GET['id'];

// Buscar o chamado
$stmtT = $pdo->prepare("SELECT t.*, u.email AS user_email, u.nome AS user_nome
                        FROM tickets t
                        JOIN users u ON t.user_id = u.id
                        WHERE t.id = :id");
$stmtT->execute(['id'=>$ticket_id]);
$ticket = $stmtT->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "Chamado não encontrado.";
    exit;
}

// Se postamos novo comentário ou atualizações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se é comentário ou mudança de estado/encerramento
    if (isset($_POST['comentario'])) {
        // Adicionar Comentário
        $conteudo = $_POST['comentario'];
        $visivel_usuario = (isset($_POST['worknote']) && $_POST['worknote'] == '1') ? 0 : 1;
        $anexo = null;

        // Upload de anexo (RF04)
        if (!empty($_FILES['anexo']['name'])) {
            $arquivoTmp = $_FILES['anexo']['tmp_name'];
            $nomeArq = $_FILES['anexo']['name'];
            $destino = 'uploads/' . uniqid() . '_' . $nomeArq;
            move_uploaded_file($arquivoTmp, $destino);
            $anexo = $destino;
        }

        $stmtCom = $pdo->prepare("INSERT INTO comentarios (ticket_id, user_id, conteudo, visivel_usuario, anexo) 
                                  VALUES (:tid, :uid, :c, :vu, :a)");
        $stmtCom->execute([
            'tid' => $ticket_id,
            'uid' => $user_id,
            'c'   => $conteudo,
            'vu'  => $visivel_usuario,
            'a'   => $anexo
        ]);

        // Notificação simples
        if ($visivel_usuario) {
            // Envia e-mail para o solicitante, se não for ele mesmo
            if ($ticket['user_id'] != $user_id) {
                enviarNotificacao($ticket['user_email'], 
                    "Seu chamado #{$ticket_id} recebeu um comentário",
                    "Um novo comentário foi adicionado ao chamado: {$conteudo}"
                );
            }
        }

    } else if (isset($_POST['acao'])) {
        // Pode ser encerrar, reabrir, atualizar estado, etc.
        $acao = $_POST['acao'];

        if ($acao === 'encerrar' && ($role === 'analista' || $role === 'administrador')) {
            $oldEstado = $ticket['estado'];
            $newEstado = 'Fechado';
            // Atualiza
            $stmtUpd = $pdo->prepare("UPDATE tickets SET estado=:e, data_fechamento=NOW() WHERE id=:id");
            $stmtUpd->execute(['e'=>$newEstado, 'id'=>$ticket_id]);
            registrarMudanca($pdo, $ticket_id, $user_id, 'estado', $oldEstado, $newEstado);
            // Notifica solicitante
            enviarNotificacao($ticket['user_email'], 
                "Chamado #$ticket_id foi encerrado",
                "O chamado foi encerrado pelo analista."
            );

        } else if ($acao === 'confirmar_encerramento' && $ticket['user_id'] == $user_id) {
            // O usuário solicitante confirma encerramento
            // Ex: mudamos estado para "Fechado" se estava "Resolvido" ou algo assim
            $oldEstado = $ticket['estado'];
            $newEstado = 'Fechado';
            $stmtUpd = $pdo->prepare("UPDATE tickets SET estado=:e, data_fechamento=NOW() WHERE id=:id");
            $stmtUpd->execute(['e'=>$newEstado, 'id'=>$ticket_id]);
            registrarMudanca($pdo, $ticket_id, $user_id, 'estado', $oldEstado, $newEstado);

        } else if ($acao === 'reabrir' && $ticket['user_id'] == $user_id) {
            // Usuário reabre
            $oldEstado = $ticket['estado'];
            $newEstado = 'Aberto';
            $stmtUpd = $pdo->prepare("UPDATE tickets SET estado=:e, data_fechamento=NULL WHERE id=:id");
            $stmtUpd->execute(['e'=>$newEstado, 'id'=>$ticket_id]);
            registrarMudanca($pdo, $ticket_id, $user_id, 'estado', $oldEstado, $newEstado);

        } else if ($acao === 'atualizar' && ($role === 'analista' || $role === 'administrador')) {
            // Analista atualiza prioridade, estado, atribuição, etc.
            $oldPrioridade = $ticket['prioridade'];
            $oldEstado     = $ticket['estado'];
            $oldRisco      = $ticket['risco'];
            $oldAssigned   = $ticket['assigned_to'];

            $novaPrioridade = $_POST['nova_prioridade'] ?? $oldPrioridade;
            $novoEstado     = $_POST['novo_estado'] ?? $oldEstado;
            $novoRisco      = $_POST['novo_risco'] ?? $oldRisco;
            $novoAssigned   = $_POST['novo_assigned_to'] ?? $oldAssigned;

            $stmtUpd = $pdo->prepare("UPDATE tickets 
                SET prioridade=:p, estado=:e, risco=:r, assigned_to=:a 
                WHERE id=:id");
            $stmtUpd->execute([
                'p'=>$novaPrioridade,
                'e'=>$novoEstado,
                'r'=>$novoRisco,
                'a'=>$novoAssigned,
                'id'=>$ticket_id
            ]);

            registrarMudanca($pdo, $ticket_id, $user_id, 'prioridade', $oldPrioridade, $novaPrioridade);
            registrarMudanca($pdo, $ticket_id, $user_id, 'estado',     $oldEstado,     $novoEstado);
            registrarMudanca($pdo, $ticket_id, $user_id, 'risco',      $oldRisco,      $novoRisco);
            registrarMudanca($pdo, $ticket_id, $user_id, 'assigned_to',$oldAssigned,   $novoAssigned);
        }
    }

    // Redirecionar para evitar re-post
    header("Location: ticket.php?id=$ticket_id");
    exit;
}

// Buscar novamente dados do chamado (podem ter mudado se teve POST)
$stmtT->execute(['id'=>$ticket_id]);
$ticket = $stmtT->fetch(PDO::FETCH_ASSOC);

// Buscar comentários
if ($role === 'analista' || $role === 'administrador') {
    $stmtC = $pdo->prepare("SELECT c.*, u.nome FROM comentarios c
                            JOIN users u ON c.user_id = u.id
                            WHERE ticket_id = :tid
                            ORDER BY data_criacao DESC");
    $stmtC->execute(['tid'=>$ticket_id]);
} else {
    // Usuário só vê visivel_usuario=1
    $stmtC = $pdo->prepare("SELECT c.*, u.nome FROM comentarios c
                            JOIN users u ON c.user_id = u.id
                            WHERE ticket_id = :tid AND visivel_usuario=1
                            ORDER BY data_criacao DESC");
    $stmtC->execute(['tid'=>$ticket_id]);
}
$comentarios = $stmtC->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <title>Chamado #<?php echo $ticket_id; ?></title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
  <h1>Chamado #<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['titulo']); ?></h1>
  <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($ticket['descricao'])); ?></p>
  <p><strong>Estado:</strong> <?php echo $ticket['estado']; ?></p>
  <p><strong>Prioridade:</strong> <?php echo $ticket['prioridade']; ?></p>
  <p><strong>Risco:</strong> <?php echo $ticket['risco']; ?></p>
  <p><strong>Tipo:</strong> <?php echo $ticket['tipo']; ?></p>
  <p><strong>Data Abertura:</strong> <?php echo $ticket['data_abertura']; ?></p>
  <?php if ($ticket['data_fechamento']): ?>
  <p><strong>Data Fechamento:</strong> <?php echo $ticket['data_fechamento']; ?></p>
  <?php endif; ?>

  <?php
    // Se for analista/admin, permitir atualizar prioridade, estado, atribuição
    if (($role === 'analista' || $role === 'administrador') && $ticket['estado'] !== 'Fechado') :
  ?>
  <h3>Atualizar Chamado</h3>
  <form method="POST">
    <input type="hidden" name="acao" value="atualizar" />
    
    <label for="novo_prioridade">Prioridade</label>
    <select id="novo_prioridade" name="nova_prioridade">
      <option value="Baixo"   <?php if($ticket['prioridade']=='Baixo') echo 'selected';?>>Baixo</option>
      <option value="Medio"   <?php if($ticket['prioridade']=='Medio') echo 'selected';?>>Médio</option>
      <option value="Alto"    <?php if($ticket['prioridade']=='Alto') echo 'selected';?>>Alto</option>
      <option value="Critico" <?php if($ticket['prioridade']=='Critico') echo 'selected';?>>Crítico</option>
    </select>

    <label for="novo_estado">Estado</label>
    <select id="novo_estado" name="novo_estado">
      <option value="Aberto"             <?php if($ticket['estado']=='Aberto') echo 'selected';?>>Aberto</option>
      <option value="Em Analise"         <?php if($ticket['estado']=='Em Analise') echo 'selected';?>>Em Análise</option>
      <option value="Aguardando Usuario" <?php if($ticket['estado']=='Aguardando Usuario') echo 'selected';?>>Aguardando Usuário</option>
      <option value="Resolvido"          <?php if($ticket['estado']=='Resolvido') echo 'selected';?>>Resolvido</option>
      <option value="Fechado"            <?php if($ticket['estado']=='Fechado') echo 'selected';?>>Fechado</option>
    </select>

    <label for="novo_risco">Risco</label>
    <select id="novo_risco" name="novo_risco">
      <option value="Baixo" <?php if($ticket['risco']=='Baixo') echo 'selected';?>>Baixo</option>
      <option value="Medio" <?php if($ticket['risco']=='Medio') echo 'selected';?>>Médio</option>
      <option value="Alto"  <?php if($ticket['risco']=='Alto') echo 'selected';?>>Alto</option>
    </select>

    <!-- Atribuir a outro analista (ou remover) -->
    <?php
    $stmtAn = $pdo->query("SELECT id, nome FROM users WHERE role='analista' ORDER BY nome");
    $allAnalistas = $stmtAn->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <label for="novo_assigned_to">Atribuir a</label>
    <select id="novo_assigned_to" name="novo_assigned_to">
      <option value="">-- Ninguém --</option>
      <?php foreach($allAnalistas as $an): ?>
        <option value="<?php echo $an['id']; ?>" 
          <?php if($ticket['assigned_to'] == $an['id']) echo 'selected'; ?>>
          <?php echo $an['nome']; ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit">Salvar Alterações</button>
  </form>

  <?php if ($ticket['estado'] !== 'Fechado'): ?>
  <form method="POST" style="margin-top:10px;">
    <input type="hidden" name="acao" value="encerrar" />
    <button type="submit">Encerrar Chamado</button>
  </form>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Se estado for Resolvido, permitir que o usuário confirm ou reabra (RF05) -->
  <?php if ($ticket['estado'] === 'Resolvido' && $ticket['user_id'] == $user_id): ?>
    <form method="POST">
      <input type="hidden" name="acao" value="confirmar_encerramento" />
      <button type="submit">Confirmar Encerramento</button>
    </form>
    <form method="POST">
      <input type="hidden" name="acao" value="reabrir" />
      <button type="submit">Reabrir Chamado</button>
    </form>
  <?php elseif ($ticket['estado'] === 'Fechado' && $ticket['user_id'] == $user_id): ?>
    <!-- Caso queira permitir reabrir mesmo depois de Fechado, mas depende da sua regra de negócio. -->
    <form method="POST">
      <input type="hidden" name="acao" value="reabrir" />
      <button type="submit">Reabrir Chamado</button>
    </form>
  <?php endif; ?>

  <h2>Comentários</h2>
  <?php foreach ($comentarios as $c): ?>
    <div class="comentario">
      <strong><?php echo htmlspecialchars($c['nome']); ?></strong> 
      <?php echo $c['data_criacao']; ?>
      <?php if (!$c['visivel_usuario']) echo ' <em>(Work Note)</em>'; ?>
      <p><?php echo nl2br(htmlspecialchars($c['conteudo'])); ?></p>
      <?php if ($c['anexo']): ?>
        <p><a href="<?php echo $c['anexo']; ?>" target="_blank">Ver Anexo</a></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <!-- Form para adicionar comentário ou work note (RF04) -->
  <!-- Se o chamado não estiver Fechado, podemos comentar -->
  <?php if ($ticket['estado'] !== 'Fechado'): ?>
  <h3>Adicionar Comentário</h3>
  <form method="POST" enctype="multipart/form-data">
    <textarea name="comentario" required></textarea>
    <br/>
    <!-- Se for analista/admin, pode marcar como Work Note -->
    <?php if ($role === 'analista' || $role === 'administrador'): ?>
      <label><input type="checkbox" name="worknote" value="1" /> Work Note (privado)</label>
    <?php endif; ?>
    <br/>
    <label>Anexo (opcional): <input type="file" name="anexo" /></label>
    <br/>
    <button type="submit">Enviar</button>
  </form>
  <?php endif; ?>

  <br/>
  <a href="dashboard.php">Voltar ao Dashboard</a>
</body>
</html>
