<?php  
header("Content-Type: application/json");
require_once 'inc/connect.php';
require_once 'auth_token.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

// Verificação de token
$headers = getallheaders();
$token = '';
if (isset($headers['Authorization']) && preg_match('/Bearer\s+(.*)/', $headers['Authorization'], $m)) {
    $token = $m[1];
}
$payload = $token ? jwt_decode($token) : false;
if (!$payload) {
    http_response_code(401);
    echo json_encode(["error" => "Acesso não autorizado"]);
    exit;
}

switch ($method) {
    case 'GET':
        $pesquisa = $_GET['pesquisa'] ?? '';
        $team_id = $_GET['team_id'] ?? '';

        $where = [];
        $params = [];

        if (!empty($pesquisa)) {
            $where[] = "(titulo LIKE :pesq OR descricao LIKE :pesq)";
            $params[':pesq'] = "%$pesquisa%";
        }

        if (!empty($team_id)) {
            $where[] = "assigned_team_id = :tid";
            $params[':tid'] = $team_id;
        }

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } else {
            $sql = "SELECT * FROM tickets";
            if (count($where) > 0) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY data_abertura DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $camposObrigatorios = ['titulo', 'descricao', 'tipo', 'prioridade', 'risco', 'user_id'];
        foreach ($camposObrigatorios as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '') {
                http_response_code(400);
                echo json_encode(["error" => "Campo obrigatório '$campo' ausente ou vazio"]);
                exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO tickets 
            (titulo, descricao, categoria_id, servico_impactado, tipo, prioridade, risco, user_id, assigned_to, assigned_team_id, data_abertura) 
            VALUES 
            (:titulo, :descricao, :categoria_id, :servico, :tipo, :prioridade, :risco, :user_id, :assigned_to, :assigned_team_id, NOW())");

        $ok = $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'],
            ':categoria_id' => $data['categoria_id'] ?? null,
            ':servico' => $data['servico_impactado'] ?? '',
            ':tipo' => $data['tipo'],
            ':prioridade' => $data['prioridade'],
            ':risco' => $data['risco'],
            ':user_id' => $data['user_id'],
            ':assigned_to' => !empty($data['assigned_to']) ? $data['assigned_to'] : null,
            ':assigned_team_id' => !empty($data['assigned_team_id']) ? $data['assigned_team_id'] : null
        ]);

        if ($ok) {
            echo json_encode(["message" => "Chamado criado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao inserir no banco"]);
        }
        break;




    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID não informado"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE tickets SET 
            titulo = :titulo,
            descricao = :descricao,
            prioridade = :prioridade,
            tipo = :tipo,
            estado = :estado
            WHERE id = :id");
        $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'],
            ':prioridade' => $data['prioridade'],
            ':tipo' => $data['tipo'],
            ':estado' => $data['estado'],
            ':id' => $id
        ]);
        echo json_encode(["message" => "Chamado atualizado"]);
        break;

    case 'DELETE':
    if (!$id) {
        http_response_code(400);
        echo json_encode(["error" => "ID não informado"]);
        break;
    }

    // Deleta primeiro os comentários ligados ao chamado
    $stmt = $pdo->prepare("DELETE FROM comentarios WHERE ticket_id = ?");
    $stmt->execute([$id]);

    // Agora deleta o chamado
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["message" => "Chamado excluído"]);
    break;

}
