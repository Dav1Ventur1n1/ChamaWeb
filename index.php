<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal de Chamados - Login</title>
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="/css/animations.css" />
  <link rel="stylesheet" href="/css/enhanced.css" />
  <link rel="stylesheet" href="/css/theme.css" />
</head>
<body>
  <div class="login-container">
    <div class="login-logo">
      <img src="img/logo-chamados.svg" alt="Portal de Chamados" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22100%22%20height%3D%22100%22%20viewBox%3D%220%200%20100%20100%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Ccircle%20cx%3D%2250%22%20cy%3D%2250%22%20r%3D%2245%22%20fill%3D%22%23222%22%20stroke%3D%22%23ffe300%22%20stroke-width%3D%225%22%2F%3E%3Ctext%20x%3D%2250%22%20y%3D%2255%22%20font-family%3D%22Arial%22%20font-size%3D%2220%22%20text-anchor%3D%22middle%22%20fill%3D%22%23ffe300%22%3ESupport%3C%2Ftext%3E%3C%2Fsvg%3E'; this.style.width='100px'" />
    </div>
    
    <h1>Portal de Chamados</h1>
    
    <div class="login-tabs">
      <div class="login-tab active" data-tab="traditional">Login Padrão</div>
      <div class="login-tab" data-tab="cognito">Login com Cognito</div>
    </div>
    
    <div class="login-forms">
      <!-- Form de login tradicional , esta incorreto -->
      <!--<form action="dashboard.php" method="POST" id="traditional-form" class="login-form active"> -->
      <form action="/login.php" method="POST" id="traditional-form" class="login-form active">

        <div class="form-field">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required placeholder="Digite seu e-mail" />
          <div class="error-message">Por favor, digite um e-mail válido</div>
        </div>
        
        <div class="form-field">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required placeholder="Digite sua senha" />
          <div class="error-message">Por favor, digite sua senha</div>
        </div>
        
        <button type="submit" class="button-primary">Entrar</button>
      </form>
      
      <!-- Opção de login com Cognito -->
      <div id="cognito-form" class="login-form">
        <div class="cognito-info">
          <p>O login com Amazon Cognito oferece:</p>
          <ul>
            <li>Autenticação segura e confiável</li>
            <li>Possibilidade de usar sua conta corporativa</li>
            <li>Login único para múltiplos sistemas</li>
          </ul>
        </div>
        
        <a href="/cognito_login.php" class="button-cognito">
          <span class="cognito-icon">
            <!-- Ícone simples AWS -->
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
              <path d="M2 17l10 5 10-5"></path>
              <path d="M2 12l10 5 10-5"></path>
            </svg>
          </span>
          Entrar com Amazon Cognito
        </a>
      </div>
    </div>
    
    <div class="login-footer">
      <a href="/reset_password.php" class="forget-link">Esqueci minha senha</a>
    </div>
  </div>
  
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
    
    // Validação de formulário de login
    const loginForm = document.getElementById('traditional-form');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar e-mail
        const email = document.getElementById('email');
        if (!email.value.trim() || !/\S+@\S+\.\S+/.test(email.value)) {
          isValid = false;
          email.closest('.form-field').classList.add('error');
        } else {
          email.closest('.form-field').classList.remove('error');
        }
        
        // Validar senha
        const senha = document.getElementById('senha');
        if (!senha.value.trim()) {
          isValid = false;
          senha.closest('.form-field').classList.add('error');
        } else {
          senha.closest('.form-field').classList.remove('error');
        }
        
        // Prevenir envio se inválido
        if (!isValid) {
          e.preventDefault();
          showLoginError();
        }
      });
      
      // Remover erro enquanto digita
      const inputs = loginForm.querySelectorAll('input');
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          this.closest('.form-field').classList.remove('error');
          hideLoginError();
        });
      });
    }
    
    // Verificar se há erro na URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('erro')) {
      showLoginError();
    }
    
    // Função para mostrar erro de login
    function showLoginError() {
      let errorBox = document.querySelector('.login-error');
      if (!errorBox) {
        errorBox = document.createElement('div');
        errorBox.className = 'login-error';
        errorBox.textContent = 'E-mail ou senha incorretos';
        
        const loginForms = document.querySelector('.login-forms');
        if (loginForms) {
          loginForms.insertBefore(errorBox, loginForms.firstChild);
        }
      }
    }
    
    // Função para esconder erro de login
    function hideLoginError() {
      const errorBox = document.querySelector('.login-error');
      if (errorBox) {
        errorBox.remove();
      }
    }
    
    // Alternância entre abas de login
    const loginTabs = document.querySelectorAll('.login-tab');
    if (loginTabs.length) {
      loginTabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remover classe ativa de todas as abas
          loginTabs.forEach(t => t.classList.remove('active'));
          
          // Adicionar classe ativa à aba clicada
          this.classList.add('active');
          
          // Alternar entre formulários
          const tabId = this.getAttribute('data-tab');
          document.querySelectorAll('.login-form').forEach(form => {
            form.classList.remove('active');
          });
          
          document.getElementById(tabId + '-form').classList.add('active');
          
          // Remover mensagem de erro ao mudar de aba
          hideLoginError();
        });
      });
    }
    
    // Detectar parâmetro na URL para abrir aba específica
    if (urlParams.has('cognito')) {
      const cognitoTab = document.querySelector('.login-tab[data-tab="cognito"]');
      if (cognitoTab) {
        cognitoTab.click();
      }
    }
  });
  </script>
</body>
</html>
