<?php
require_once 'inc/connect.php';
require_once 'auth_token.php';

// Verifica token simples para autenticação
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';
if ($auth !== 'Bearer ' . API_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Preparar array para estatísticas
$stats = [];

// Base da consulta
$baseWhere = "";
$params = [];

// Neste exemplo simples, todas as estatísticas são globais

// 1. Total de chamados
$sqlTotal = "SELECT COUNT(*) as total FROM tickets " . $baseWhere;
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$stats['total'] = $stmtTotal->fetchColumn();

// 2. Chamados abertos (não fechados)
$whereAbertos = $baseWhere ? $baseWhere . " AND estado != 'Fechado'" : "WHERE estado != 'Fechado'";
$sqlAbertos = "SELECT COUNT(*) as abertos FROM tickets " . $whereAbertos;
$stmtAbertos = $pdo->prepare($sqlAbertos);
$stmtAbertos->execute($params);
$stats['abertos'] = $stmtAbertos->fetchColumn();

// 3. Chamados de alta prioridade
$whereAlta = $baseWhere ? 
    $baseWhere . " AND (prioridade = 'Alto' OR prioridade = 'Critico') AND estado != 'Fechado'" : 
    "WHERE (prioridade = 'Alto' OR prioridade = 'Critico') AND estado != 'Fechado'";
$sqlAlta = "SELECT COUNT(*) as alta FROM tickets " . $whereAlta;
$stmtAlta = $pdo->prepare($sqlAlta);
$stmtAlta->execute($params);
$stats['alta_prioridade'] = $stmtAlta->fetchColumn();

// 4. Chamados aguardando usuário
$whereAguardando = $baseWhere ? 
    $baseWhere . " AND estado = 'Aguardando Usuario'" : 
    "WHERE estado = 'Aguardando Usuario'";
$sqlAguardando = "SELECT COUNT(*) as aguardando FROM tickets " . $whereAguardando;
$stmtAguardando = $pdo->prepare($sqlAguardando);
$stmtAguardando->execute($params);
$stats['aguardando'] = $stmtAguardando->fetchColumn();

// 5. Tempo médio de resolução
$sqlMedia = "SELECT AVG(TIMESTAMPDIFF(HOUR, data_abertura, data_fechamento)) as media
             FROM tickets
             WHERE estado = 'Fechado' AND data_fechamento IS NOT NULL";
$stmtMedia = $pdo->prepare($sqlMedia);
$stmtMedia->execute();
$media = $stmtMedia->fetchColumn();
$stats['tempo_medio_resolucao'] = round($media, 1);

// 6. Chamados por categoria
$sqlCategorias = "SELECT c.nome, COUNT(t.id) as total
                  FROM tickets t
                  LEFT JOIN categories c ON t.categoria_id = c.id
                  GROUP BY t.categoria_id
                  ORDER BY total DESC";
$stmtCategorias = $pdo->query($sqlCategorias);
$stats['categorias'] = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Adicionar timestamp para caching do lado do cliente
$stats['timestamp'] = time();

// Retornar estatísticas em JSON
header('Content-Type: application/json');
echo json_encode($stats);
