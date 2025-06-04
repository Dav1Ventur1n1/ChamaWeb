<?php
session_start();
require_once 'inc/connect.php';

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND auth_provider = 'local' AND blocked = 0 LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nome'] = $user['nome'];

            header('Location: dashboard.php');
            exit;
        } else {
            echo "Senha inválida!";
        }
    } else {
        echo "Nenhum usuário localizado.";
    }
} catch (PDOException $e) {
    echo "Erro de PDO: " . $e->getMessage();
}
exit;
?>
