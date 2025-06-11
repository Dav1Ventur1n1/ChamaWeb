<?php
session_start();
require_once 'inc/connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
    header('Location: index.php');
    exit;
}

// Gerenciamento de usuários
if (isset($_POST['acao']) && $_POST['acao'] === 'criar_usuario') {
    $nome  = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $senhaHash = hash('sha256', $senha);
    $role  = $_POST['role']  ?? 'usuario';

    $stmt = $pdo->prepare("INSERT INTO users (nome,email,senha,role) VALUES (:n,:e,:s,:r)");
    $stmt->execute(['n'=>$nome, 'e'=>$email, 's'=>$senhaHash, 'r'=>$role]);
    
    // Mensagem de sucesso via sessão
    $_SESSION['admin_message'] = 'Usuário criado com sucesso!';
    $_SESSION['admin_message_type'] = 'success';
    
    // Redirecionar para evitar resubmissão em F5
    header('Location: admin.php');
    exit;
}

if (isset($_GET['block_user'])) {
    $uid = $_GET['block_user'];
    $stmt = $pdo->prepare("UPDATE users SET blocked=1 WHERE id=:id");
    $stmt->execute(['id'=>$uid]);
    
    $_SESSION['admin_message'] = 'Usuário bloqueado com sucesso!';
    $_SESSION['admin_message_type'] = 'warning';
    
    header('Location: admin.php');
    exit;
}

if (isset($_GET['unblock_user'])) {
    $uid = $_GET['unblock_user'];
    $stmt = $pdo->prepare("UPDATE users SET blocked=0 WHERE id=:id");
    $stmt->execute(['id'=>$uid]);
    
    $_SESSION['admin_message'] = 'Usuário desbloqueado com sucesso!';
    $_SESSION['admin_message_type'] = 'success';
    
    header('Location: admin.php');
    exit;
}

// Gerenciamento de categorias (RF11)
if (isset($_POST['acao']) && $_POST['acao']==='criar_categoria') {
    $catNome = $_POST['cat_nome'] ?? '';
    $stmtCat = $pdo->prepare("INSERT INTO categories (nome) VALUES (:n)");
    $stmtCat->execute(['n'=>$catNome]);
    
    $_SESSION['admin_message'] = 'Categoria criada com sucesso!';
    $_SESSION['admin_message_type'] = 'success';
    
    header('Location: admin.php');
    exit;
}

if (isset($_GET['del_cat'])) {
    $catId = $_GET['del_cat'];
    $stmtDC = $pdo->prepare("DELETE FROM categories WHERE id=:id");
    $stmtDC->execute(['id'=>$catId]);
    
    $_SESSION['admin_message'] = 'Categoria excluída com sucesso!';
    $_SESSION['admin_message_type'] = 'warning';
    
    header('Location: admin.php');
    exit;
}

// Carregar usuários
$stmtUsers = $pdo->query("SELECT * FROM users ORDER BY nome");
$usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Carregar categorias
$stmtCats = $pdo->query("SELECT * FROM categories ORDER BY nome");
$cats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administração</title>
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
      <a href="admin.php" class="active">Gerenciar Usuários</a>
      <a href="relatorios.php">Relatórios</a>
      <a href="logout.php">Sair</a>
    </nav>
  </header>

  <main>
    <div class="dashboard-header">
      <h2>Painel do Administrador</h2>
      <div class="action-buttons">
        <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
      </div>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
    <div class="admin-notification <?php echo $_SESSION['admin_message_type'] ?? 'info'; ?>">
      <?php echo $_SESSION['admin_message']; ?>
      <?php unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); ?>
    </div>
    <?php endif; ?>
    
    <div class="admin-grid">
      <!-- Seção de Gerenciamento de Usuários -->
      <div class="card">
        <div class="card-header">Gerenciar Usuários</div>
        <div class="card-content">
          <div class="accordion">
            <div class="accordion-header" id="new-user-toggle">
              <span class="icon-plus"></span> Adicionar Novo Usuário
            </div>
            <div class="accordion-content" id="new-user-form">
              <form method="POST" id="criar-usuario-form">
                <input type="hidden" name="acao" value="criar_usuario" />
                
                <div class="form-row">
                  <div class="form-field">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" required />
                    <div class="error-message">Por favor, informe o nome do usuário.</div>
                  </div>
                  
                  <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required />
                    <div class="error-message">Por favor, informe um e-mail válido.</div>
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-field">
                    <label for="senha">Senha</label>
                    <input type="text" id="senha" name="senha" required />
                    <div class="error-message">Por favor, defina uma senha.</div>
                  </div>
                  
                  <div class="form-field">
                    <label for="role">Perfil</label>
                    <select id="role" name="role" required>
                      <option value="usuario">Usuário</option>
                      <option value="analista">Analista</option>
                      <option value="administrador">Administrador</option>
                    </select>
                  </div>
                </div>
                
                <div class="form-actions">
                  <button type="submit" class="action-button">Criar Usuário</button>
                </div>
              </form>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="data-table admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nome</th>
                  <th>E-mail</th>
                  <th>Perfil</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                  <td><?php echo $u['id']; ?></td>
                  <td><?php echo htmlspecialchars($u['nome']); ?></td>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td>
                    <span class="badge badge-<?php echo $u['role']; ?>">
                      <?php echo ucfirst($u['role']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($u['blocked']): ?>
                      <span class="badge badge-blocked">Bloqueado</span>
                    <?php else: ?>
                      <span class="badge badge-active">Ativo</span>
                    <?php endif; ?>
                  </td>
                  <td class="action-column">
                    <?php if (!$u['blocked']): ?>
                      <a href="?block_user=<?php echo $u['id']; ?>" 
                         class="action-button small-button warning-button" 
                         onclick="return confirm('Tem certeza que deseja bloquear este usuário?')">
                        Bloquear
                      </a>
                    <?php else: ?>
                      <a href="?unblock_user=<?php echo $u['id']; ?>" 
                         class="action-button small-button success-button">
                        Desbloquear
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Seção de Gerenciamento de Categorias -->
      <div class="card">
        <div class="card-header">Gerenciar Categorias</div>
        <div class="card-content">
          <form method="POST" class="inline-form">
            <input type="hidden" name="acao" value="criar_categoria" />
            
            <div class="form-row">
              <div class="form-field flex-grow">
                <label for="cat_nome">Nome da Categoria</label>
                <input type="text" id="cat_nome" name="cat_nome" required />
                <div class="error-message">Por favor, informe o nome da categoria.</div>
              </div>
              
              <div class="form-field align-end">
                <button type="submit" class="action-button">Adicionar</button>
              </div>
            </div>
          </form>
          
          <div class="categories-list">
            <?php if (!empty($cats)): ?>
              <ul class="admin-list">
                <?php foreach($cats as $c): ?>
                  <li>
                    <div class="category-item">
                      <span class="category-name"><?php echo htmlspecialchars($c['nome']); ?></span>
                      <a href="?del_cat=<?php echo $c['id']; ?>" 
                         class="delete-button" 
                         onclick="return confirm('Tem certeza que deseja excluir esta categoria?')">
                        ×
                      </a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="no-records">Nenhuma categoria cadastrada.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
  
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
    
    // Accordion para o formulário de novo usuário
    const accordionHeader = document.getElementById('new-user-toggle');
    const accordionContent = document.getElementById('new-user-form');
    
    if (accordionHeader && accordionContent) {
      accordionHeader.addEventListener('click', function() {
        accordionContent.classList.toggle('expanded');
        accordionHeader.classList.toggle('active');
      });
    }
    
    // Validação de formulário de usuário
    const userForm = document.getElementById('criar-usuario-form');
    if (userForm) {
      userForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar campos obrigatórios
        const requiredFields = userForm.querySelectorAll('[required]');
        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            e.preventDefault();
            isValid = false;
            const formField = field.closest('.form-field');
            formField.classList.add('error');
          } else {
            const formField = field.closest('.form-field');
            formField.classList.remove('error');
          }
        });
        
        // Validar email
        const email = document.getElementById('email');
        if (email && email.value.trim() && !/\S+@\S+\.\S+/.test(email.value)) {
          e.preventDefault();
          isValid = false;
          const formField = email.closest('.form-field');
          formField.classList.add('error');
          formField.querySelector('.error-message').textContent = 'E-mail inválido';
        }
        
        // Adicionar evento de input para remover erro enquanto digita
        requiredFields.forEach(field => {
          field.addEventListener('input', function() {
            const formField = this.closest('.form-field');
            formField.classList.remove('error');
          });
        });
        
        if (!isValid) {
          // Mostrar alerta de erro
          showNotification('Por favor, corrija os erros no formulário.', 'error');
        }
      });
    }
    
    // Função para mostrar uma notificação
    function showNotification(message, type = 'info') {
      // Verificar se já existe uma notificação
      let notification = document.querySelector('.admin-notification');
      
      // Se não existe, criar uma nova
      if (!notification) {
        notification = document.createElement('div');
        notification.className = `admin-notification ${type}`;
        
        // Inserir antes do primeiro card
        const firstCard = document.querySelector('.card');
        if (firstCard && firstCard.parentNode) {
          firstCard.parentNode.insertBefore(notification, firstCard);
        } else {
          // Fallback: inserir no início do main
          const main = document.querySelector('main');
          if (main) {
            main.insertBefore(notification, main.firstChild);
          }
        }
      } else {
        // Atualizar a classe da notificação existente
        notification.className = `admin-notification ${type}`;
      }
      
      // Definir a mensagem
      notification.textContent = message;
      
      // Animação
      notification.style.animation = 'none';
      setTimeout(() => {
        notification.style.animation = '';
      }, 10);
      
      // Auto-remover após 5 segundos
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 5000);
    }
    
    // Verificar se existe uma notificação da sessão e animá-la
    const sessionNotification = document.querySelector('.admin-notification');
    if (sessionNotification) {
      setTimeout(() => {
        sessionNotification.style.opacity = '0';
        setTimeout(() => {
          if (sessionNotification.parentNode) {
            sessionNotification.parentNode.removeChild(sessionNotification);
          }
        }, 500);
      }, 5000);
    }
  });
  </script>
</body>
</html>