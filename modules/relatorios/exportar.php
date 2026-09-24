<?php
// modules/relatorios/exportar.php
// Exportar dados em diversos formatos

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    die('Acesso negado');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    die('Nenhuma fazenda selecionada');
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'csv';
$relatorio = isset($_GET['relatorio']) ? $_GET['relatorio'] : 'bovinos';

// Definir nome do arquivo
$filename = $relatorio . '_' . date('Y-m-d_H-i-s') . '.' . $tipo;

switch ($relatorio) {
    case 'bovinos':
        exportarBovinos($tipo, $filename);
        break;
    case 'producao':
        exportarProducao($tipo, $filename);
        break;
    case 'vacinas':
        exportarVacinas($tipo, $filename);
        break;
    default:
        die('Relatório não encontrado');
}

function exportarBovinos($tipo, $filename) {
    global $farmId;
    
    $sql = "SELECT 
                b.brinco,
                b.nome,
                r.nome_raca as raca,
                b.sexo,
                b.data_nascimento,
                b.peso_atual,
                s.nome as situacao,
                b.data_entrada,
                b.observacoes
            FROM bovinos b
            LEFT JOIN racas r ON b.id_raca = r.id
            LEFT JOIN situacoes s ON b.id_situacao = s.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            ORDER BY b.brinco";
    
    $result = executeQuery($sql);
    
    if ($tipo == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Brinco', 'Nome', 'Raça', 'Sexo', 'Data Nascimento', 'Peso (kg)', 'Situação', 'Data Entrada', 'Observações']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['brinco'],
                $row['nome'],
                $row['raca'],
                $row['sexo'] == 'M' ? 'Macho' : 'Fêmea',
                $row['data_nascimento'] ? date('d/m/Y', strtotime($row['data_nascimento'])) : '',
                $row['peso_atual'],
                $row['situacao'],
                $row['data_entrada'] ? date('d/m/Y', strtotime($row['data_entrada'])) : '',
                $row['observacoes']
            ]);
        }
        
        fclose($output);
    }
}

function exportarProducao($tipo, $filename) {
    global $farmId;
    
    $ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
    $mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
    
    $data_inicio = "$ano-$mes-01";
    $data_fim = date('Y-m-t', strtotime($data_inicio));
    
    $sql = "SELECT 
                b.brinco,
                b.nome,
                pl.data_producao,
                pl.turno,
                pl.quantidade_litros,
                pl.observacoes
            FROM producao_leite pl
            JOIN bovinos b ON pl.id_bovino = b.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            AND pl.data_producao BETWEEN '$data_inicio' AND '$data_fim'
            ORDER BY pl.data_producao, b.brinco";
    
    $result = executeQuery($sql);
    
    if ($tipo == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Brinco', 'Nome', 'Data', 'Turno', 'Litros', 'Observações']);
        
        while ($row = $result->fetch_assoc()) {
            $turno = [
                'manha' => 'Manhã',
                'tarde' => 'Tarde',
                'noite' => 'Noite',
                'unico' => 'Único'
            ][$row['turno']] ?? $row['turno'];
            
            fputcsv($output, [
                $row['brinco'],
                $row['nome'],
                date('d/m/Y', strtotime($row['data_producao'])),
                $turno,
                $row['quantidade_litros'],
                $row['observacoes']
            ]);
        }
        
        fclose($output);
    }
}

function exportarVacinas($tipo, $filename) {
    global $farmId;
    
    $sql = "SELECT 
                b.brinco,
                b.nome,
                v.nome_vacina,
                av.data_aplicacao,
                av.dose_ml,
                av.lote,
                av.via_aplicacao,
                av.responsavel,
                av.proxima_dose,
                av.observacoes
            FROM aplicacoes_vacinas av
            JOIN bovinos b ON av.id_bovino = b.id
            JOIN vacinas v ON av.id_vacina = v.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            ORDER BY av.data_aplicacao DESC";
    
    $result = executeQuery($sql);
    
    if ($tipo == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Brinco', 'Nome', 'Vacina', 'Data Aplicação', 'Dose (ml)', 'Lote', 'Via', 'Responsável', 'Próxima Dose', 'Observações']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['brinco'],
                $row['nome'],
                $row['nome_vacina'],
                date('d/m/Y', strtotime($row['data_aplicacao'])),
                $row['dose_ml'],
                $row['lote'],
                $row['via_aplicacao'],
                $row['responsavel'],
                $row['proxima_dose'] ? date('d/m/Y', strtotime($row['proxima_dose'])) : '',
                $row['observacoes']
            ]);
        }
        
        fclose($output);
    }
}
?>