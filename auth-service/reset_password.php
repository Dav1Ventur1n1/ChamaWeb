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
        // Exemplo grosseiro: redefinir senha direto p/ "1234"
        // Em real, geramos token, enviamos link com esse token, etc.
        $stmtUpd = $pdo->prepare("UPDATE users SET senha='1234' WHERE id=:id");
        $stmtUpd->execute(['id'=>$user['id']]);
        echo "Senha redefinida para '1234'. <a href='index.html'>Fazer login</a>";
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
    <meta charset="UTF-8" />
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <h1>Recuperar Senha</h1>
    <form method="POST">
        <label>E-mail cadastrado:</label>
        <input type="email" name="email" required />
        <button type="submit">Recuperar</button>
    </form>
    <p><a href="../frontend/index.html">Voltar ao Login</a></p>
</body>
</html>
