<?php
session_start();
require_once 'inc/connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['analista','administrador'])) {
    header('Location: index.php');
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

// Buscar estatísticas do microserviço via gateway
require_once 'auth_token.php';
$ch = curl_init('http://gateway:80/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['jwt'] ?? '')
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

$totalChamados = $data['total'] ?? 0;
$abertos = $data['abertos'] ?? 0;
$fechados = $data['fechados'] ?? 0;
$mediaHoras = $data['tempo_medio_resolucao'] ?? 0;
$chamadosPorEstado = $data['por_estado'] ?? [];
$chamadosPorPrioridade = $data['por_prioridade'] ?? [];
$chamadosPorMes = $data['por_mes'] ?? [];
$topCategorias = $data['top_categorias'] ?? [];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatórios</title>
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="/css/animations.css" />
  <link rel="stylesheet" href="/css/enhanced.css" />
  <link rel="stylesheet" href="/css/theme.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
  <h1>Portal de Chamados</h1>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="criar_chamado.php">Abrir Novo Chamado</a>
    <?php if ($role === 'administrador'): ?>
      <a href="admin.php">Gerenciar Usuários</a>
    <?php endif; ?>
    <a href="relatorios.php" class="active">Relatórios</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<main>
  <div class="dashboard-header">
    <h2>Relatórios de Chamados</h2>
    <div class="report-actions">
      <a href="?export=csv" class="button">Exportar CSV</a>
      <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
    </div>
  </div>
  
  <!-- Cards de indicadores -->
  <div class="indicators">
    <div class="card indicator-card">
      <div class="indicator-value"><?php echo $totalChamados; ?></div>
      <div class="indicator-label">Total de Chamados</div>
    </div>
    
    <div class="card indicator-card">
      <div class="indicator-value"><?php echo $abertos; ?></div>
      <div class="indicator-label">Chamados em Aberto</div>
    </div>
    
    <div class="card indicator-card">
      <div class="indicator-value"><?php echo $fechados; ?></div>
      <div class="indicator-label">Chamados Fechados</div>
    </div>
    
    <div class="card indicator-card">
      <div class="indicator-value"><?php echo $mediaHoras; ?></div>
      <div class="indicator-label">Tempo Médio (horas)</div>
    </div>
  </div>
  
  <!-- Gráficos visuais -->
  <div class="charts-grid">
    <!-- Gráfico de chamados por estado -->
    <div class="card chart-card">
      <div class="card-header">Chamados por Estado</div>
      <div class="card-content">
        <div class="chart-container">
          <canvas id="estadosChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Gráfico de chamados por prioridade -->
    <div class="card chart-card">
      <div class="card-header">Chamados por Prioridade</div>
      <div class="card-content">
        <div class="chart-container">
          <canvas id="prioridadesChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Gráfico de chamados por mês -->
    <div class="card chart-card wide-chart">
      <div class="card-header">Chamados por Mês (Últimos 6 meses)</div>
      <div class="card-content">
        <div class="chart-container">
          <canvas id="mesesChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Gráfico de top categorias -->
    <div class="card chart-card">
      <div class="card-header">Top 5 Categorias</div>
      <div class="card-content">
        <div class="chart-container">
          <canvas id="categoriasChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</main>

<div id="theme-toggle-container" class="theme-toggle-container">
  <button id="theme-toggle" class="theme-toggle" title="Alternar tema claro/escuro">🌓</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Configuração de cores
  const isDarkTheme = !document.body.classList.contains('light-theme');
  
  // Cores para temas escuro e claro
  const colors = {
    dark: {
      text: '#f0f0f0',
      grid: 'rgba(255, 255, 255, 0.1)',
      estados: ['#2196F3', '#9C27B0', '#FF9800', '#4CAF50', '#9E9E9E'],
      prioridades: ['#F44336', '#FF5722', '#FFC107', '#8BC34A'],
      meses: '#ffe300',
      categorias: ['#FF5722', '#2196F3', '#4CAF50', '#9C27B0', '#FF9800']
    },
    light: {
      text: '#333333',
      grid: 'rgba(0, 0, 0, 0.1)',
      estados: ['#1565C0', '#6A1B9A', '#EF6C00', '#2E7D32', '#616161'],
      prioridades: ['#C62828', '#E64A19', '#F57F17', '#558B2F'],
      meses: '#f57c00',
      categorias: ['#E64A19', '#0D47A1', '#2E7D32', '#6A1B9A', '#EF6C00']
    }
  };
  
  const theme = isDarkTheme ? 'dark' : 'light';
  
  // Configuração comum para os gráficos
  Chart.defaults.color = colors[theme].text;
  Chart.defaults.scale.grid.color = colors[theme].grid;
  
  // Dados para os gráficos
  const estadosData = {
    labels: <?php echo json_encode(array_column($chamadosPorEstado, 'estado')); ?>,
    datasets: [{
      data: <?php echo json_encode(array_column($chamadosPorEstado, 'quantidade')); ?>,
      backgroundColor: colors[theme].estados,
      borderWidth: 1
    }]
  };
  
  const prioridadesData = {
    labels: <?php echo json_encode(array_column($chamadosPorPrioridade, 'prioridade')); ?>,
    datasets: [{
      data: <?php echo json_encode(array_column($chamadosPorPrioridade, 'quantidade')); ?>,
      backgroundColor: colors[theme].prioridades,
      borderWidth: 1
    }]
  };
  
  // Processar dados de meses para exibição mais amigável
  const meses = <?php echo json_encode(array_column($chamadosPorMes, 'mes')); ?>;
  const mesesFormatados = meses.map(mes => {
    const [year, month] = mes.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
  });
  
  const mesesData = {
    labels: mesesFormatados,
    datasets: [{
      label: 'Chamados Abertos',
      data: <?php echo json_encode(array_column($chamadosPorMes, 'quantidade')); ?>,
      backgroundColor: colors[theme].meses,
      borderColor: colors[theme].meses,
      borderWidth: 2,
      tension: 0.3,
      fill: false
    }]
  };
  
  const categoriasData = {
    labels: <?php echo json_encode(array_column($topCategorias, 'categoria')); ?>,
    datasets: [{
      data: <?php echo json_encode(array_column($topCategorias, 'quantidade')); ?>,
      backgroundColor: colors[theme].categorias,
      borderWidth: 1
    }]
  };
  
  // Criar os gráficos
  new Chart(document.getElementById('estadosChart'), {
    type: 'doughnut',
    data: estadosData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right'
        }
      }
    }
  });
  
  new Chart(document.getElementById('prioridadesChart'), {
    type: 'pie',
    data: prioridadesData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right'
        }
      }
    }
  });
  
  new Chart(document.getElementById('mesesChart'), {
    type: 'line',
    data: mesesData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
  
  new Chart(document.getElementById('categoriasChart'), {
    type: 'bar',
    data: categoriasData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
  
  // Alternar tema claro/escuro
  const themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function() {
      document.body.classList.toggle('light-theme');
      const isDark = !document.body.classList.contains('light-theme');
      localStorage.setItem('darkTheme', isDark);
      
      // Recarregar a página para atualizar os gráficos com o novo tema
      location.reload();
    });
    
    // Restaurar tema
    if (localStorage.getItem('darkTheme') === 'false') {
      document.body.classList.add('light-theme');
    }
  }
});
</script>

</body>
</html>