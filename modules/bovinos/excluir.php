<?php
// modules/bovinos/excluir.php
// Exclusão de bovino (soft delete)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    redirect(BASE_URL . 'modules/fazendas/selector.php');
}

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Verificar se o bovino pertence à fazenda atual
$checkSql = "SELECT id FROM bovinos WHERE id = $id AND " . TenantManager::addTenantFilter();
$checkResult = executeQuery($checkSql);

if (!$checkResult || $checkResult->num_rows == 0) {
    setAlert('Bovino não encontrado ou não pertence à sua fazenda.', 'danger');
    redirect('index.php');
}

// Soft delete (marcar como inativo)
$sql = "UPDATE bovinos SET ativo = 0 WHERE id = $id";

if (executeQuery($sql)) {
    setAlert('Bovino excluído com sucesso!', 'success');
} else {
    setAlert('Erro ao excluir bovino.', 'danger');
}

redirect('index.php');
?>