<?php
session_start();
require_once 'inc/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Carregar lista de categorias
$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY nome");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Carregar lista de analistas
$stmtAnalistas = $pdo->query("SELECT id, nome FROM users WHERE role='analista' ORDER BY nome");
$analistas = $stmtAnalistas->fetchAll(PDO::FETCH_ASSOC);

// Carregar lista de equipes
$stmtTeams = $pdo->query("SELECT * FROM teams ORDER BY nome");
$teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Abrir Novo Chamado</title>
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
      <a href="criar_chamado.php" class="active">Abrir Novo Chamado</a>
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
      <h2>Abrir Novo Chamado</h2>
      <div class="action-buttons">
        <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header">Informações do Chamado</div>
      <div class="card-content">
        <form method="POST" id="criar-chamado-form" action="#">
          <div class="form-field">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" required placeholder="Digite um título descritivo para o chamado" />
            <div class="error-message">Por favor, informe um título para o chamado.</div>
          </div>

          <div class="form-field">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" required rows="6" placeholder="Descreva o problema ou solicitação detalhadamente..."></textarea>
            <div class="error-message">Por favor, forneça uma descrição detalhada.</div>
          </div>

          <div class="form-row">
            <div class="form-field">
              <label for="tipo">Tipo</label>
              <select id="tipo" name="tipo">
                <option value="Incidente">Incidente</option>
                <option value="Requisicao">Requisição</option>
              </select>
            </div>

            <div class="form-field">
              <label for="categoria_id">Categoria</label>
              <select id="categoria_id" name="categoria_id" required>
                <option value="">-- Selecione --</option>
                <?php foreach($categorias as $cat): ?>
                  <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nome']; ?></option>
                <?php endforeach; ?>
              </select>
              <div class="error-message">Por favor, selecione uma categoria.</div>
            </div>
          </div>

          <div class="form-field">
            <label for="servico">Serviço Impactado</label>
            <input type="text" id="servico" name="servico" placeholder="Ex: Sistema ERP, Site Institucional, etc." />
          </div>

          <?php if ($role === 'analista' || $role === 'administrador'): ?>
            <div class="card-subheader">Opções Avançadas</div>
            
            <div class="form-row">
              <div class="form-field">
                <label for="prioridade">Prioridade</label>
                <select id="prioridade" name="prioridade">
                  <option value="Baixo">Baixo</option>
                  <option value="Medio">Médio</option>
                  <option value="Alto">Alto</option>
                  <option value="Critico">Crítico</option>
                </select>
              </div>

              <div class="form-field">
                <label for="risco">Risco</label>
                <select id="risco" name="risco">
                  <option value="Baixo">Baixo</option>
                  <option value="Medio">Médio</option>
                  <option value="Alto">Alto</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-field">
                <label for="assigned_to">Atribuir a Analista</label>
                <select id="assigned_to" name="assigned_to">
                  <option value="">-- Ninguém --</option>
                  <?php foreach($analistas as $an): ?>
                    <option value="<?php echo $an['id']; ?>"><?php echo $an['nome']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-field">
                <label for="assigned_team">Atribuir a Equipe</label>
                <select id="assigned_team" name="assigned_team">
                  <option value="">-- Nenhuma --</option>
                  <?php foreach($teams as $tm): ?>
                    <option value="<?php echo $tm['id']; ?>"><?php echo $tm['nome']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          <?php else: ?>
            <!-- Usuário comum não escolhe prioridade, risco, analista, equipe -->
            <div class="form-info">
              <p>Este chamado será aberto com prioridade e risco "Baixo" e será encaminhado para a equipe de triagem.</p>
            </div>
          <?php endif; ?>

          <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

          <div class="form-actions">
            <button type="submit" id="submit-button" class="action-button">Abrir Chamado</button>
          </div>
        </form>
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
    
    // Validação de formulário
    const form = document.getElementById('criar-chamado-form');
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;
        
        // Validar campos obrigatórios
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            e.preventDefault();
            isValid = false;
            const formField = field.closest('.form-field');
            formField.classList.add('error');
          } else {
            const formField = field.closest('.form-field');
            formField.classList.remove('error');
            formField.classList.add('success');
          }
        });
        
        // Adicionar evento de input para remover erro enquanto digita
        requiredFields.forEach(field => {
          field.addEventListener('input', function() {
            const formField = this.closest('.form-field');
            formField.classList.remove('error', 'success');
          });
        });
        
        if (!isValid) {
          showToast('error', 'Por favor, preencha todos os campos obrigatórios');
          return;
        }

        document.getElementById('submit-button').disabled = true;
        document.getElementById('submit-button').innerHTML = '<span class="spinner"></span> Enviando...';

        const formData = new FormData(form);
        fetch('api/create_ticket.php', {
          method: 'POST',
          body: formData
        })
        .then(resp => resp.json())
        .then(data => {
          if (data.success) {
            window.location = 'dashboard.php';
          } else {
            showToast('error', data.error || 'Erro ao criar chamado');
            document.getElementById('submit-button').disabled = false;
            document.getElementById('submit-button').innerHTML = 'Abrir Chamado';
          }
        })
        .catch(() => {
          showToast('error', 'Falha na comunicação com o servidor');
          document.getElementById('submit-button').disabled = false;
          document.getElementById('submit-button').innerHTML = 'Abrir Chamado';
        });
      });
    }
    
    // Função para mostrar toast
    function showToast(type, message) {
      // Verificar se o container existe
      let container = document.querySelector('.toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
      }
      
      // Criar o toast
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.textContent = message;
      
      // Adicionar ao container
      container.appendChild(toast);
      
      // Mostrar o toast
      setTimeout(() => {
        toast.classList.add('show');
      }, 10);
      
      // Remover após alguns segundos
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
          container.removeChild(toast);
        }, 300);
      }, 3000);
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