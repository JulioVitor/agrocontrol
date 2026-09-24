<?php
// modules/fazendas/ajax_change_farm.php
// Endpoint AJAX para mudar a fazenda ativa

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Verificar se recebeu o ID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['farm_id'])) {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit;
}

$farmId = intval($_POST['farm_id']);

// Tentar mudar a fazenda ativa
if (TenantManager::setActiveFarm($farmId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Você não tem acesso a esta fazenda']);
}
?>