<?php
// modules/calendario/api.php
// API REST para o calendário - VERSÃO CORRIGIDA

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

header('Content-Type: application/json');

// Verificar login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhuma fazenda selecionada']);
    exit;
}

// GET - Buscar eventos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $start = isset($_GET['start']) ? $_GET['start'] : null;
    $end = isset($_GET['end']) ? $_GET['end'] : null;
    
    // CORREÇÃO: Especificar a tabela 'e' para id_fazenda
    $where = "e.id_fazenda = $farmId AND e.concluido = 0";
    
    if ($start && $end) {
        // Ajustar formato das datas se necessário
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $where .= " AND DATE(e.data_inicio) BETWEEN '$start' AND '$end'";
    }
    
    $sql = "SELECT e.*, 
                   b.brinco as bovino_brinco,
                   b.nome as bovino_nome,
                   u.nome as usuario_nome
            FROM eventos e
            LEFT JOIN bovinos b ON e.id_bovino = b.id
            LEFT JOIN usuarios u ON e.id_usuario = u.id
            WHERE $where
            ORDER BY e.data_inicio";
    
    // Debug - descomente para ver a SQL
    // file_put_contents('/tmp/sql_debug.txt', $sql . PHP_EOL, FILE_APPEND);
    
    $result = executeQuery($sql);
    $eventos = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            // Definir cores por tipo
            $cores = [
                'vacina' => '#28a745',
                'parto' => '#dc3545',
                'inseminacao' => '#ffc107',
                'desmama' => '#17a2b8',
                'venda' => '#fd7e14',
                'compra' => '#6f42c1',
                'manutencao' => '#6c757d',
                'consulta' => '#20c997',
                'outro' => '#6c757d'
            ];
            
            $eventos[] = [
                'id' => $row['id'],
                'title' => $row['titulo'],
                'start' => $row['data_inicio'],
                'end' => $row['data_fim'],
                'allDay' => $row['dia_inteiro'] ? true : false,
                'color' => $row['cor'] ?: $cores[$row['tipo']],
                'extendedProps' => [
                    'tipo' => $row['tipo'],
                    'descricao' => $row['descricao'],
                    'local' => $row['local'],
                    'bovino' => $row['bovino_brinco'],
                    'bovino_nome' => $row['bovino_nome'],
                    'usuario' => $row['usuario_nome'],
                    'concluido' => $row['concluido']
                ]
            ];
        }
    }
    
    echo json_encode($eventos);
}

// POST - Atualizar evento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'updateDate') {
        
        $id = intval($input['id']);
        $data_inicio = $input['data_inicio'];
        $data_fim = $input['data_fim'];
        
        // Verificar se o evento pertence à fazenda
        $checkSql = "SELECT id FROM eventos WHERE id = $id AND id_fazenda = $farmId";
        $checkResult = executeQuery($checkSql);
        
        if (!$checkResult || $checkResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Evento não encontrado']);
            exit;
        }
        
        $sql = "UPDATE eventos SET 
                data_inicio = '$data_inicio',
                data_fim = " . ($data_fim ? "'$data_fim'" : "NULL") . "
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar evento']);
        }
    }
}
?>