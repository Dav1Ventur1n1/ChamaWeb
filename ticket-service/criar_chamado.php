<?php
session_start();
require_once 'inc/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
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

// Se POST, inserir no banco
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo   = $_POST['titulo'] ?? '';
    $descricao= $_POST['descricao']?? '';
    $servico  = $_POST['servico']  ?? '';
    $tipo     = $_POST['tipo'] ?? 'Incidente';
    $categoria_id = $_POST['categoria_id'] ?? null;

    // Se analista/admin, pode definir prioridade, risco, atribuição
    if ($role === 'analista' || $role === 'administrador') {
        $prioridade   = $_POST['prioridade'] ?? 'Baixo';
        $risco        = $_POST['risco'] ?? 'Baixo';
        $assigned_to  = $_POST['assigned_to'] ?? null;
        $assigned_team= $_POST['assigned_team'] ?? null;
    } else {
        // Usuário comum
        $prioridade = 'Baixo';
        $risco = 'Baixo';
        $assigned_to = null;
        $assigned_team = null;
    }

    // Insert
    $stmtInsert = $pdo->prepare("INSERT INTO tickets
    (titulo, descricao, categoria_id, servico_impactado, tipo, prioridade, risco, user_id, assigned_to, assigned_team_id, data_abertura)
    VALUES
    (:titulo, :descricao, :cat, :servico, :tipo, :prio, :risco, :uid, :assig, :team, NOW())");

    $stmtInsert->execute([
        'titulo' => $titulo,
        'descricao' => $descricao,
        'cat' => $categoria_id,
        'servico' => $servico,
        'tipo' => $tipo,
        'prio' => $prioridade,
        'risco' => $risco,
        'uid' => $user_id,
        'assig' => $assigned_to ?: null,
        'team' => $assigned_team ?: null
    ]);

    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <title>Abrir Chamado</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
  <h1>Abrir Novo Chamado</h1>
  <form method="POST">
    <label for="titulo">Título</label>
    <input type="text" id="titulo" name="titulo" required />

    <label for="descricao">Descrição</label>
    <textarea id="descricao" name="descricao" required></textarea>

    <label for="tipo">Tipo</label>
    <select id="tipo" name="tipo">
      <option value="Incidente">Incidente</option>
      <option value="Requisicao">Requisição</option>
    </select>

    <label for="categoria_id">Categoria</label>
    <select id="categoria_id" name="categoria_id">
      <option value="">-- Selecione --</option>
      <?php foreach($categorias as $cat): ?>
        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nome']; ?></option>
      <?php endforeach; ?>
    </select>

    <label for="servico">Serviço Impactado</label>
    <input type="text" id="servico" name="servico" />

    <?php if ($role === 'analista' || $role === 'administrador'): ?>
      <label for="prioridade">Prioridade</label>
      <select id="prioridade" name="prioridade">
        <option value="Baixo">Baixo</option>
        <option value="Medio">Médio</option>
        <option value="Alto">Alto</option>
        <option value="Critico">Crítico</option>
      </select>

      <label for="risco">Risco</label>
      <select id="risco" name="risco">
        <option value="Baixo">Baixo</option>
        <option value="Medio">Médio</option>
        <option value="Alto">Alto</option>
      </select>

      <label for="assigned_to">Atribuir a Analista</label>
      <select id="assigned_to" name="assigned_to">
        <option value="">-- Ninguém --</option>
        <?php foreach($analistas as $an): ?>
          <option value="<?php echo $an['id']; ?>"><?php echo $an['nome']; ?></option>
        <?php endforeach; ?>
      </select>

      <label for="assigned_team">Atribuir a Equipe</label>
      <select id="assigned_team" name="assigned_team">
        <option value="">-- Nenhuma --</option>
        <?php foreach($teams as $tm): ?>
          <option value="<?php echo $tm['id']; ?>"><?php echo $tm['nome']; ?></option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <!-- Usuário comum não escolhe prioridade, risco, analista, equipe -->
      <p>Prioridade Padrão: Baixo</p>
      <p>Risco Padrão: Baixo</p>
    <?php endif; ?>

    <button type="submit">Abrir Chamado</button>
    <br><br>
    <a href="../auth-service/dashboard.php">Voltar ao Dashboard</a>
  </form>
</body>
</html>
