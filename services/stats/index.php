<?php
require_once 'inc/connect.php';
require_once 'auth_token.php';

// Verifica token JWT para autenticação
$headers = getallheaders();
$token = '';
if (isset($headers['Authorization']) && preg_match('/Bearer\s+(.*)/', $headers['Authorization'], $m)) {
    $token = $m[1];
}
$payload = $token ? jwt_decode($token) : false;
if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Consultas utilizadas na página de relatórios
$stats = [];

// 1) Total de chamados
$stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM tickets");
$stats['total'] = (int)$stmtTotal->fetchColumn();

// 2) Chamados abertos
$stmtAbertos = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado NOT IN ('Fechado')");
$stats['abertos'] = (int)$stmtAbertos->fetchColumn();

// 3) Chamados fechados
$stmtFechados = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado='Fechado'");
$stats['fechados'] = (int)$stmtFechados->fetchColumn();

// 4) Tempo médio de resolução
$stmtMedia = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, data_abertura, data_fechamento)) as media_horas
                         FROM tickets
                         WHERE estado='Fechado'");
$media = $stmtMedia->fetchColumn();
$stats['tempo_medio_resolucao'] = round($media, 2);

// 5) Chamados por estado
$stmtEstados = $pdo->query("SELECT estado, COUNT(*) as quantidade
                           FROM tickets
                           GROUP BY estado
                           ORDER BY quantidade DESC");
$stats['por_estado'] = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

// 6) Chamados por prioridade
$stmtPrioridades = $pdo->query("SELECT prioridade, COUNT(*) as quantidade
                               FROM tickets
                               GROUP BY prioridade
                               ORDER BY FIELD(prioridade, 'Critico', 'Alto', 'Medio', 'Baixo')");
$stats['por_prioridade'] = $stmtPrioridades->fetchAll(PDO::FETCH_ASSOC);

// 7) Chamados por mês (últimos 6 meses)
$stmtMeses = $pdo->query("SELECT
                            DATE_FORMAT(data_abertura, '%Y-%m') as mes,
                            COUNT(*) as quantidade
                          FROM
                            tickets
                          WHERE
                            data_abertura >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                          GROUP BY
                            DATE_FORMAT(data_abertura, '%Y-%m')
                          ORDER BY
                            mes ASC");
$stats['por_mes'] = $stmtMeses->fetchAll(PDO::FETCH_ASSOC);

// 8) Top 5 categorias mais frequentes
$stmtCategorias = $pdo->query("SELECT
                                c.nome as categoria,
                                COUNT(t.id) as quantidade
                              FROM
                                tickets t
                              LEFT JOIN
                                categories c ON t.categoria_id = c.id
                              GROUP BY
                                t.categoria_id
                              ORDER BY
                                quantidade DESC
                              LIMIT 5");
$stats['top_categorias'] = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Retornar estatísticas em JSON
header('Content-Type: application/json');
echo json_encode($stats);

