<?php
session_start();
require_once 'inc/connect.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Preparar array para estatísticas
$stats = [];

// Base da consulta
$baseWhere = "";
$params = [];

// Se não for analista ou admin, filtra pelos próprios chamados
if ($role !== 'analista' && $role !== 'administrador') {
    $baseWhere = "WHERE user_id = :uid";
    $params['uid'] = $user_id;
}

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

// 5. Tempo médio de resolução (somente para admin/analista)
if ($role === 'analista' || $role === 'administrador') {
    $sqlMedia = "SELECT AVG(TIMESTAMPDIFF(HOUR, data_abertura, data_fechamento)) as media 
                 FROM tickets 
                 WHERE estado = 'Fechado' AND data_fechamento IS NOT NULL";
    $stmtMedia = $pdo->prepare($sqlMedia);
    $stmtMedia->execute();
    $media = $stmtMedia->fetchColumn();
    $stats['tempo_medio_resolucao'] = round($media, 1); // Arredondado para 1 decimal
}

// 6. Chamados por categoria (somente para admin/analista)
if ($role === 'analista' || $role === 'administrador') {
    $sqlCategorias = "SELECT c.nome, COUNT(t.id) as total
                      FROM tickets t
                      LEFT JOIN categories c ON t.categoria_id = c.id
                      GROUP BY t.categoria_id
                      ORDER BY total DESC";
    $stmtCategorias = $pdo->query($sqlCategorias);
    $stats['categorias'] = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
}

// Adicionar timestamp para caching do lado do cliente
$stats['timestamp'] = time();

// Retornar estatísticas em JSON
header('Content-Type: application/json');
echo json_encode($stats);