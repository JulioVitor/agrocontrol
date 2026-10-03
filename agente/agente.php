<?php
// agente.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/consultas.php';
require_once __DIR__ . '/intencoes.php';
require_once __DIR__ . '/respostas.php';

session_start();

$input      = json_decode(file_get_contents('php://input'), true);
$pergunta   = trim($input['texto'] ?? '');
$fazenda_id = (int)($input['fazenda_id'] ?? ($_SESSION['fazenda_id'] ?? 0));
$historico  = $_SESSION['agente_historico'] ?? [];

if ($pergunta === '' || !$fazenda_id) {
    echo json_encode(['erro' => 'Pergunta ou fazenda inválida.']);
    exit;
}

try {
    // 1) reconhece intenção via regex
    $parsed   = reconhecer_intencao($pergunta, $historico);
    $intencao = $parsed['intencao'];
    $params   = $parsed['params'];

    // 2) consulta o banco
    $dados = executar_intencao($fazenda_id, $intencao, $params);

    // 3) monta a resposta em texto
    if ($intencao === 'desconhecida') {
        $resposta = montar_resposta('desconhecida', [], $pergunta);
    } else {
        $resposta = montar_resposta($intencao, $dados, $pergunta);
    }

    // 4) salva histórico
    $historico[] = ['pergunta' => $pergunta, 'intencao' => $intencao];
    $_SESSION['agente_historico'] = array_slice($historico, -5);

    echo json_encode([
        'resposta' => $resposta,
        'intencao' => $intencao,
        'params'   => $params,
        'dados'    => $dados,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha no agente: ' . $e->getMessage()]);
}