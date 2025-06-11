<?php
// Manter todo o PHP original
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
    header('Location: index.php');
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
    // Lógica de processamento do POST original
    // Manter todo o código original aqui
    
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chamado #<?php echo $ticket_id; ?></title>
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="/css/animations.css" />
  <link rel="stylesheet" href="/css/enhanced.css" />
  <link rel="stylesheet" href="/css/theme.css" />
</head>
<body>
  <header>
    <h1>Portal de Chamados</h1>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="criar_chamado.php">Abrir Novo Chamado</a>
      <?php if ($role === 'administrador'): ?>
        <a href="admin.php">Gerenciar Usuários</a>
        <a href="relatorios.php">Relatórios</a>
      <?php elseif ($role === 'analista'): ?>
        <a href="relatorios.php">Relatórios</a>
      <?php endif; ?>
      <a href="logout.php">Sair</a>
    </nav>
  </header>
  
  <main>
    <div class="dashboard-header">
      <h2>Chamado #<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['titulo']); ?></h2>
      <div class="ticket-actions">
        <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
      </div>
    </div>
    
    <div class="card ticket-details">
      <div class="card-header">Detalhes do Chamado</div>
      <div class="card-content">
        <div class="ticket-info-grid">
          <div class="info-group">
            <div class="info-label">Descrição:</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($ticket['descricao'])); ?></div>
          </div>
          
          <div class="info-group">
            <div class="info-label">Estado:</div>
            <div class="info-value <?php echo 'status-' . strtolower($ticket['estado']); ?>" data-field="estado">
              <?php echo $ticket['estado']; ?>
            </div>
          </div>
          
          <div class="info-group">
            <div class="info-label">Prioridade:</div>
            <div class="info-value <?php echo 'priority-' . strtolower($ticket['prioridade']); ?>" data-field="prioridade">
              <?php echo $ticket['prioridade']; ?>
            </div>
          </div>
          
          <div class="info-group">
            <div class="info-label">Risco:</div>
            <div class="info-value <?php echo 'risk-' . strtolower($ticket['risco']); ?>" data-field="risco">
              <?php echo $ticket['risco']; ?>
            </div>
          </div>
          
          <div class="info-group">
            <div class="info-label">Tipo:</div>
            <div class="info-value"><?php echo $ticket['tipo']; ?></div>
          </div>
          
          <div class="info-group">
            <div class="info-label">Data Abertura:</div>
            <div class="info-value timestamp">
              <?php echo date('d/m/Y H:i', strtotime($ticket['data_abertura'])); ?>
            </div>
          </div>
          
          <?php if ($ticket['data_fechamento']): ?>
          <div class="info-group">
            <div class="info-label">Data Fechamento:</div>
            <div class="info-value timestamp">
              <?php echo date('d/m/Y H:i', strtotime($ticket['data_fechamento'])); ?>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="info-group">
            <div class="info-label">Solicitante:</div>
            <div class="info-value"><?php echo htmlspecialchars($ticket['user_nome']); ?></div>
          </div>
        </div>
      </div>
    </div>
  
    <?php
      // Se for analista/admin, permitir atualizar prioridade, estado, atribuição
      if (($role === 'analista' || $role === 'administrador') && $ticket['estado'] !== 'Fechado') :
    ?>
    <div class="card">
      <div class="card-header">Atualizar Chamado</div>
      <div class="card-content">
        <form method="POST" class="update-form">
          <input type="hidden" name="acao" value="atualizar" />
          
          <div class="form-row">
            <div class="form-field">
              <label for="novo_prioridade">Prioridade</label>
              <select id="novo_prioridade" name="nova_prioridade">
                <option value="Baixo"   <?php if($ticket['prioridade']=='Baixo') echo 'selected';?>>Baixo</option>
                <option value="Medio"   <?php if($ticket['prioridade']=='Medio') echo 'selected';?>>Médio</option>
                <option value="Alto"    <?php if($ticket['prioridade']=='Alto') echo 'selected';?>>Alto</option>
                <option value="Critico" <?php if($ticket['prioridade']=='Critico') echo 'selected';?>>Crítico</option>
              </select>
            </div>
            
            <div class="form-field">
              <label for="novo_estado">Estado</label>
              <select id="novo_estado" name="novo_estado">
                <option value="Aberto"             <?php if($ticket['estado']=='Aberto') echo 'selected';?>>Aberto</option>
                <option value="Em Analise"         <?php if($ticket['estado']=='Em Analise') echo 'selected';?>>Em Análise</option>
                <option value="Aguardando Usuario" <?php if($ticket['estado']=='Aguardando Usuario') echo 'selected';?>>Aguardando Usuário</option>
                <option value="Resolvido"          <?php if($ticket['estado']=='Resolvido') echo 'selected';?>>Resolvido</option>
                <option value="Fechado"            <?php if($ticket['estado']=='Fechado') echo 'selected';?>>Fechado</option>
              </select>
            </div>
            
            <div class="form-field">
              <label for="novo_risco">Risco</label>
              <select id="novo_risco" name="novo_risco">
                <option value="Baixo" <?php if($ticket['risco']=='Baixo') echo 'selected';?>>Baixo</option>
                <option value="Medio" <?php if($ticket['risco']=='Medio') echo 'selected';?>>Médio</option>
                <option value="Alto"  <?php if($ticket['risco']=='Alto') echo 'selected';?>>Alto</option>
              </select>
            </div>
          </div>

          <!-- Atribuir a outro analista (ou remover) -->
          <?php
          $stmtAn = $pdo->query("SELECT id, nome FROM users WHERE role='analista' ORDER BY nome");
          $allAnalistas = $stmtAn->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <div class="form-field">
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
          </div>

          <div class="form-actions">
            <button type="submit" class="icon-edit">Salvar Alterações</button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($ticket['estado'] !== 'Fechado'): ?>
    <div class="card action-card">
      <div class="card-content">
        <form method="POST">
          <input type="hidden" name="acao" value="encerrar" />
          <button type="submit" class="action-button icon-close">Encerrar Chamado</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Se estado for Resolvido, permitir que o usuário confirme ou reabra (RF05) -->
    <?php if ($ticket['estado'] === 'Resolvido' && $ticket['user_id'] == $user_id): ?>
    <div class="card action-card">
      <div class="card-header">Ações do Solicitante</div>
      <div class="card-content action-buttons">
        <form method="POST">
          <input type="hidden" name="acao" value="confirmar_encerramento" />
          <button type="submit" class="action-button success-button">Confirmar Encerramento</button>
        </form>
        <form method="POST">
          <input type="hidden" name="acao" value="reabrir" />
          <button type="submit" class="action-button warning-button">Reabrir Chamado</button>
        </form>
      </div>
    </div>
    <?php elseif ($ticket['estado'] === 'Fechado' && $ticket['user_id'] == $user_id): ?>
    <div class="card action-card">
      <div class="card-header">Ações do Solicitante</div>
      <div class="card-content">
        <form method="POST">
          <input type="hidden" name="acao" value="reabrir" />
          <button type="submit" class="action-button warning-button">Reabrir Chamado</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Comentários</div>
      <div class="card-content">
        <div class="comentarios-container">
          <?php foreach ($comentarios as $c): ?>
            <div class="comentario <?php echo (!$c['visivel_usuario']) ? 'work-note' : ''; ?>">
              <div class="comentario-header">
                <strong><?php echo htmlspecialchars($c['nome']); ?></strong> 
                <span class="timestamp"><?php echo date('d/m/Y H:i', strtotime($c['data_criacao'])); ?></span>
                <?php if (!$c['visivel_usuario']) echo ' <em>(Work Note)</em>'; ?>
              </div>
              <p><?php echo nl2br(htmlspecialchars($c['conteudo'])); ?></p>
              <?php if ($c['anexo']): ?>
                <p class="attachment"><a href="<?php echo $c['anexo']; ?>" target="_blank" class="icon-attachment">Ver Anexo</a></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          
          <?php if (empty($comentarios)): ?>
            <p class="no-records">Nenhum comentário encontrado.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Form para adicionar comentário ou work note (RF04) -->
    <!-- Se o chamado não estiver Fechado, podemos comentar -->
    <?php if ($ticket['estado'] !== 'Fechado'): ?>
    <div class="card">
      <div class="card-header icon-comment">Adicionar Comentário</div>
      <div class="card-content">
        <form method="POST" enctype="multipart/form-data" id="comment-form">
          <div class="form-field">
            <textarea name="comentario" id="comentario" placeholder="Digite seu comentário aqui..." required></textarea>
            <div class="error-message">Por favor, digite um comentário.</div>
          </div>
          
          <!-- Se for analista/admin, pode marcar como Work Note -->
          <?php if ($role === 'analista' || $role === 'administrador'): ?>
            <div class="form-field checkbox-field">
              <label>
                <input type="checkbox" name="worknote" value="1" /> 
                Work Note (visível apenas para analistas)
              </label>
            </div>
          <?php endif; ?>
          
          <div class="form-field">
            <label for="anexo" class="file-input-label">
              <span class="file-input-text">Anexo (opcional)</span>
              <input type="file" name="anexo" id="anexo" class="file-input" />
            </label>
            <div id="file-selected" class="file-selected"></div>
            <div id="upload-progress" class="upload-progress-container">
              <div class="upload-progress-bar"></div>
              <div class="upload-progress-text">0%</div>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="action-button">Enviar Comentário</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </main>

<div id="theme-toggle-container">
  <button id="theme-toggle" class="theme-toggle" title="Alternar tema claro/escuro">🌓</button>
</div>

<!-- Script para upload de arquivos com barra de progresso -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Tema claro/escuro
  const themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function() {
      document.body.classList.toggle('light-theme');
      const isDark = !document.body.classList.contains('light-theme');
      localStorage.setItem('darkTheme', isDark);
    });
    
    // Restaurar tema
    if (localStorage.getItem('darkTheme') === 'false') {
      document.body.classList.add('light-theme');
    }
  }
  
  // Upload de arquivos com barra de progresso
  const fileInput = document.getElementById('anexo');
  const fileSelected = document.getElementById('file-selected');
  const uploadProgress = document.getElementById('upload-progress');
  const progressBar = uploadProgress.querySelector('.upload-progress-bar');
  const progressText = uploadProgress.querySelector('.upload-progress-text');
  
  if (fileInput) {
    uploadProgress.style.display = 'none';
    
    fileInput.addEventListener('change', function() {
      if (this.files.length > 0) {
        const fileName = this.files[0].name;
        const fileSize = (this.files[0].size / 1024).toFixed(2) + ' KB';
        fileSelected.textContent = fileName + ' (' + fileSize + ')';
        fileSelected.style.display = 'block';
      } else {
        fileSelected.style.display = 'none';
      }
    });
    
    // Formulário de comentário
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
      commentForm.addEventListener('submit', function(e) {
        const comentario = document.getElementById('comentario');
        
        // Validação básica
        if (!comentario.value.trim()) {
          e.preventDefault();
          comentario.closest('.form-field').classList.add('error');
          return;
        }
        
        // Se tem arquivo anexado, mostrar barra de progresso
        if (fileInput.files.length > 0) {
          // Simular upload com progresso (Em uma implementação real, você usaria AJAX/FormData)
          e.preventDefault();
          
          uploadProgress.style.display = 'block';
          commentForm.querySelector('button[type="submit"]').disabled = true;
          
          let progress = 0;
          const interval = setInterval(function() {
            progress += 5;
            progressBar.style.width = progress + '%';
            progressText.textContent = progress + '%';
            
            if (progress >= 100) {
              clearInterval(interval);
              // Enviar o formulário após "upload"
              setTimeout(function() {
                commentForm.submit();
              }, 500);
            }
          }, 100);
        }
      });
    }
  }
});
</script>

<div id="theme-toggle-container" class="theme-toggle-container">
  <button id="theme-toggle" class="theme-toggle" title="Alternar tema claro/escuro">🌓</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Tema claro/escuro
  const themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function() {
      document.body.classList.toggle('light-theme');
      const isDark = !document.body.classList.contains('light-theme');
      localStorage.setItem('darkTheme', isDark);
    });
    
    // Restaurar tema
    if (localStorage.getItem('darkTheme') === 'false') {
      document.body.classList.add('light-theme');
    }
  }
});
</script>

</body>
</html>