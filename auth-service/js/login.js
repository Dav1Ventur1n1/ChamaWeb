document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const senha = document.getElementById('senha').value;

    const response = await fetch('/auth-service/api/login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ email, senha })
    });

    const result = await response.json();
    if (result.status === "success") {
      localStorage.setItem("user", JSON.stringify(result));
      window.location.href = "/dashboard.html";
    } else {
      document.getElementById('mensagem').textContent = "Credenciais inválidas";
    }
  });
