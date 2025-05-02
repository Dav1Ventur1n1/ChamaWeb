<?php
session_start();
require_once 'inc/connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['analista','administrador'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'];

// Se quiser exportar CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_chamados.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Título','Estado','Prioridade','Data Abertura','Data Fechamento']);
    
    // Pega todos chamados ou filtra
    $stmt = $pdo->query("SELECT id, titulo, estado, prioridade, data_abertura, data_fechamento FROM tickets");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Consultas para indicadores
// 1) Total de chamados
$stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM tickets");
$totalChamados = $stmtTotal->fetchColumn();

// 2) Chamados abertos
$stmtAbertos = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado NOT IN ('Fechado')");
$abertos = $stmtAbertos->fetchColumn();

// 3) Chamados fechados
$stmtFechados = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado='Fechado'");
$fechados = $stmtFechados->fetchColumn();

// 4) Tempo médio de resolução (exemplo: diferença data_fechamento - data_abertura)
$stmtMedia = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, data_abertura, data_fechamento)) as media_horas
                          FROM tickets 
                          WHERE estado='Fechado'");
$mediaHoras = $stmtMedia->fetchColumn();
$mediaHoras = round($mediaHoras, 2);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <title>Relatórios</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<h1>Relatórios</h1>
<p>Total de Chamados: <?php echo $totalChamados; ?></p>
<p>Chamados em Aberto: <?php echo $abertos; ?></p>
<p>Chamados Fechados: <?php echo $fechados; ?></p>
<p>Tempo Médio de Resolução (horas): <?php echo $mediaHoras; ?></p>

<p><a href="?export=csv">Exportar CSV</a></p>
<a href="dashboard.php">Voltar</a>
</body>
</html>
