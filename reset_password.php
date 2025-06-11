<?php
session_start();
require_once 'inc/connect.php';

if (isset($_POST['email'])) {
    // Em produção, gerar token, guardar em tabela, enviar e-mail com link
    $email = $_POST['email'];

    // Verificar se existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email=:e");
    $stmt->execute(['e'=>$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Exemplo simplificado: redefinir senha para "1234" (hash)
        // Em produção, gere token e envie e-mail com link
        $novaSenhaHash = hash('sha256', '1234');
        $stmtUpd = $pdo->prepare("UPDATE users SET senha=:s WHERE id=:id");
        $stmtUpd->execute(['s'=>$novaSenhaHash, 'id'=>$user['id']]);
        echo "Senha redefinida. <a href='index.php'>Fazer login</a>";
        exit;
    } else {
        echo "E-mail não encontrado. <a href='reset_password.php'>Tentar novamente</a>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Senha</title>
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="/css/animations.css" />
  <link rel="stylesheet" href="/css/enhanced.css" />
  <link rel="stylesheet" href="/css/theme.css" />
</head>
<body>
    <h1>Recuperar Senha</h1>
    <form method="POST">
        <label>E-mail cadastrado:</label>
        <input type="email" name="email" required />
        <button type="submit">Recuperar</button>
    </form>
    <p><a href="index.php">Voltar ao Login</a></p>
</body>
</html>
