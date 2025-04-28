<?php
session_start();
require_once 'inc/connect.php';

function registrarLog($pdo, $tipo, $descricao, $userId = null) {
    $stmtLog = $pdo->prepare("INSERT INTO logs (user_id, tipo, descricao) VALUES (:uid, :t, :d)");
    $stmtLog->execute([
        'uid' => $userId,
        't'   => $tipo,
        'd'   => $descricao
    ]);
}

// Se chegou por POST (tentando logar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Buscar usuário
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !$user['blocked']) {
        // Comparar senhas (ideal usar password_verify)
        // Exemplo (senha em texto puro, NÃO recomendável para produção):
        if ($senha === $user['senha']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nome']    = $user['nome'];
            $_SESSION['role']    = $user['role'];

            registrarLog($pdo, 'LOGIN', 'Login bem-sucedido', $user['id']);
        } else {
            // Senha incorreta
            registrarLog($pdo, 'ERRO_LOGIN', 'Senha incorreta para ' . $email);
            header('Location: index.html?erro=1');
            exit;
        }
    } else {
        // Usuário não existe ou bloqueado
        registrarLog($pdo, 'ERRO_LOGIN', 'Tentativa de login para usuário inexistente/bloqueado: ' . $email);
        header('Location: index.html?erro=1');
        exit;
    }
} else {
    // Se não veio via POST, checa se tá logado
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.html');
        exit;
    }
}

// Preparar variáveis
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Filtrar chamados
$where   = [];
$params  = [];

// Se não for analista ou admin, filtra pelos próprios chamados
if ($role !== 'analista' && $role !== 'administrador') {
    $where[] = "user_id = :uid";
    $params['uid'] = $user_id;
}

// Pesquisa por palavra-chave (título ou descrição)
$pesquisa = $_GET['pesquisa'] ?? '';
if (!empty($pesquisa)) {
    $where[] = "(titulo LIKE :pesq OR descricao LIKE :pesq)";
    $params['pesq'] = "%$pesquisa%";
}

// Filtrar por equipe se for analista ou admin
$team_id = $_GET['team_id'] ?? '';
if ($role !== 'usuario' && !empty($team_id)) {
    $where[] = "assigned_team_id = :tid";
    $params['tid'] = $team_id;
}

// Montar SQL final
$sql = "SELECT * FROM tickets";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY data_abertura DESC";

// Executar
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Carregar equipes para exibir no filtro (se for analista ou admin)
if ($role !== 'usuario') {
    $stmtTeams = $pdo->query("SELECT * FROM teams ORDER BY nome");
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <title>Portal de Chamados - Dashboard</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<header>
  <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
  <nav>
    <a href="criar_chamado.php">Abrir Novo Chamado</a>
    <?php if ($role === 'administrador'): ?>
      <a href="admin.php">Gerenciar Usuários</a>
      <a href="relatorios.php">Relatórios</a> <!-- Geração de relatórios (RF07, RF15) -->
    <?php elseif ($role === 'analista'): ?>
      <a href="relatorios.php">Relatórios</a>
    <?php endif; ?>
    <a href="logout.php">Sair</a>
  </nav>
</header>
<main>
  <h2>Lista de Chamados</h2>

  <!-- Filtro de pesquisa (RF12) -->
  <form method="GET">
    <input type="text" name="pesquisa" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($pesquisa); ?>" />
    <?php if ($role !== 'usuario'): ?>
      <select name="team_id">
        <option value="">-- Equipe --</option>
        <?php foreach ($teams as $t): ?>
          <option value="<?php echo $t['id']; ?>" <?php if ($team_id == $t['id']) echo 'selected'; ?>>
            <?php echo $t['nome']; ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <button type="submit">Filtrar</button>
  </form>

  <table>
    <tr>
      <th>ID</th>
      <th>Título</th>
      <th>Estado</th>
      <th>Prioridade</th>
      <th>Tipo</th>
      <th>Ações</th>
    </tr>
    <?php foreach ($tickets as $ticket): ?>
    <tr>
      <td><?php echo $ticket['id']; ?></td>
      <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
      <td><?php echo $ticket['estado']; ?></td>
      <td><?php echo $ticket['prioridade']; ?></td>
      <td><?php echo $ticket['tipo']; ?></td>
      <td>
        <a href="ticket.php?id=<?php echo $ticket['id']; ?>">Ver Detalhes</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</main>
</body>
</html>
