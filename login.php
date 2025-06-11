<?php
session_start();
require_once 'inc/connect.php';
require_once 'shared/log.php';
require_once 'auth_token.php';

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND auth_provider = 'local' AND blocked = 0 LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $senhaCorreta = false;
        // Verifica senha utilizando password_hash (bcrypt)
        if (password_verify($senha, $user['senha'])) {
            $senhaCorreta = true;
        } elseif (hash_equals($user['senha'], hash('sha256', $senha))) {
            // Compatibilidade com hashes antigos em SHA-256
            $senhaCorreta = true;
        } elseif ($senha === $user['senha']) {
            // Último recurso: senha armazenada em texto puro
            $senhaCorreta = true;
        }

        if ($senhaCorreta) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['nome']    = $user['nome'];
            $_SESSION['jwt']     = jwt_encode([
                'id' => $user['id'],
                'role' => $user['role'],
                'exp' => time() + 3600
            ]);

            registrarLog($pdo, 'LOGIN', 'Login local', $user['id']);
            header('Location: dashboard.php');
            exit;
        } else {
            registrarLog($pdo, 'ERRO_LOGIN', 'Senha incorreta', $user['id']);
            echo "Senha inválida!";
        }
    } else {
        registrarLog($pdo, 'ERRO_LOGIN', 'Usuário inexistente: '.$email);
        echo "Nenhum usuário localizado.";
    }
} catch (PDOException $e) {
    echo "Erro de PDO: " . $e->getMessage();
}
exit;
?>
