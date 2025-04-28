<?php
session_start();
require_once 'inc/connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
    header('Location: index.html');
    exit;
}

// Gerenciamento de usuários
if (isset($_POST['acao']) && $_POST['acao'] === 'criar_usuario') {
    $nome  = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $role  = $_POST['role']  ?? 'usuario';

    $stmt = $pdo->prepare("INSERT INTO users (nome,email,senha,role) VALUES (:n,:e,:s,:r)");
    $stmt->execute(['n'=>$nome, 'e'=>$email, 's'=>$senha, 'r'=>$role]);
}
if (isset($_GET['block_user'])) {
    $uid = $_GET['block_user'];
    $stmt = $pdo->prepare("UPDATE users SET blocked=1 WHERE id=:id");
    $stmt->execute(['id'=>$uid]);
}
if (isset($_GET['unblock_user'])) {
    $uid = $_GET['unblock_user'];
    $stmt = $pdo->prepare("UPDATE users SET blocked=0 WHERE id=:id");
    $stmt->execute(['id'=>$uid]);
}

// Gerenciamento de categorias (RF11)
if (isset($_POST['acao']) && $_POST['acao']==='criar_categoria') {
    $catNome = $_POST['cat_nome'] ?? '';
    $stmtCat = $pdo->prepare("INSERT INTO categories (nome) VALUES (:n)");
    $stmtCat->execute(['n'=>$catNome]);
}
if (isset($_GET['del_cat'])) {
    $catId = $_GET['del_cat'];
    $stmtDC = $pdo->prepare("DELETE FROM categories WHERE id=:id");
    $stmtDC->execute(['id'=>$catId]);
}

// Carregar usuários
$stmtUsers = $pdo->query("SELECT * FROM users ORDER BY nome");
$usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Carregar categorias
$stmtCats = $pdo->query("SELECT * FROM categories ORDER BY nome");
$cats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Administração</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
  <h1>Painel do Administrador</h1>
  <a href="dashboard.php">Voltar ao Dashboard</a>

  <h2>Gerenciar Usuários</h2>
  <form method="POST">
    <input type="hidden" name="acao" value="criar_usuario" />
    <label>Nome: <input type="text" name="nome" required></label>
    <label>E-mail: <input type="email" name="email" required></label>
    <label>Senha: <input type="text" name="senha" required></label>
    <label>Role:
      <select name="role">
        <option value="usuario">Usuário</option>
        <option value="analista">Analista</option>
        <option value="administrador">Administrador</option>
      </select>
    </label>
    <button type="submit">Criar</button>
  </form>

  <table>
    <tr>
      <th>ID</th>
      <th>Nome</th>
      <th>E-mail</th>
      <th>Role</th>
      <th>Blocked?</th>
      <th>Ações</th>
    </tr>
    <?php foreach($usuarios as $u): ?>
    <tr>
      <td><?php echo $u['id']; ?></td>
      <td><?php echo htmlspecialchars($u['nome']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><?php echo $u['role']; ?></td>
      <td><?php echo $u['blocked'] ? 'Sim' : 'Não'; ?></td>
      <td>
        <?php if (!$u['blocked']): ?>
          <a href="?block_user=<?php echo $u['id']; ?>">Bloquear</a>
        <?php else: ?>
          <a href="?unblock_user=<?php echo $u['id']; ?>">Desbloquear</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h2>Gerenciar Categorias</h2>
  <form method="POST">
    <input type="hidden" name="acao" value="criar_categoria" />
    <label>Nome da Categoria: <input type="text" name="cat_nome" required></label>
    <button type="submit">Criar Categoria</button>
  </form>

  <ul>
    <?php foreach($cats as $c): ?>
      <li>
        <?php echo htmlspecialchars($c['nome']); ?>
        <a href="?del_cat=<?php echo $c['id']; ?>">[Excluir]</a>
      </li>
    <?php endforeach; ?>
  </ul>

</body>
</html>
