<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'auth_token.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Chamada à API via gateway
$apiUrl = 'http://gateway:80/tickets';
$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['jwt'] ?? '')
]);

$response = curl_exec($ch);
curl_close($ch);

$tickets = json_decode($response, true);
if (!is_array($tickets)) $tickets = [];

// Buscar equipes (ainda direto do banco)
require_once 'inc/connect.php';
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
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="/css/animations.css" />
  <link rel="stylesheet" href="/css/enhanced.css" />
<link rel="stylesheet" href="/css/theme.css" />
  <script>
    window.JWT_TOKEN = '<?php echo $_SESSION['jwt']; ?>';
  </script>
</head>
<body>
<header>
  <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
  <nav>
    <a href="criar_chamado.php">Abrir Novo Chamado</a>
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
    <h2>Lista de Chamados</h2>
    <div class="dashboard-actions">
      <button id="refresh-tickets" type="button" class="action-button">Atualizar via API</button>
      <span id="last-update-time" class="last-update">Última atualização: agora</span>
    </div>
  </div>

  <form method="GET" class="filter-form" id="filter-form">
    <div class="filter-group">
      <input type="text" name="pesquisa" id="pesquisa" placeholder="Pesquisar..." />
      <?php if ($role !== 'usuario'): ?>
        <select name="team_id" id="team_id">
          <option value="">-- Equipe --</option>
          <?php foreach ($teams as $t): ?>
            <option value="<?php echo $t['id']; ?>"><?php echo $t['nome']; ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <button type="submit">Filtrar</button>
    </div>
  </form>

  <table id="tickets-table" class="data-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Estado</th>
        <th>Prioridade</th>
        <th>Tipo</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $ticket): ?>
        <tr data-id="<?php echo $ticket['id']; ?>"
            class="<?php
              echo $ticket['estado'] === 'Fechado' ? 'status-closed ' : '';
              echo $ticket['prioridade'] === 'Critico' ? 'priority-critical ' : '';
              echo $ticket['prioridade'] === 'Alto' ? 'priority-high ' : '';
            ?>">
          <td><?php echo $ticket['id']; ?></td>
          <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
          <td data-field="estado"><?php echo $ticket['estado']; ?></td>
          <td data-field="prioridade"><?php echo $ticket['prioridade']; ?></td>
          <td><?php echo $ticket['tipo']; ?></td>
          <td><a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="action-link">Ver Detalhes</a></td>
        </tr>
      <?php endforeach; ?>

      <?php if (count($tickets) === 0): ?>
        <tr><td colspan="6" class="no-records">Nenhum chamado encontrado</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>

<div id="theme-toggle-container" class="theme-toggle-container">
  <button id="theme-toggle" class="theme-toggle" title="Alternar tema claro/escuro">🌓</button>
</div>

<script src="/js/script.js"></script>
</body>
</html>
