<?php
// modules/calendario/acoes/concluir.php
// Marcar evento como concluído

require_once '../../../config/database.php';
require_once '../../../config/constants.php';
require_once '../../../includes/functions.php';
require_once '../../../config/tenant.php';

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
    redirect('../eventos.php');
}

// Verificar se o evento pertence à fazenda
$checkSql = "SELECT id FROM eventos WHERE id = $id AND " . TenantManager::addTenantFilter();
$checkResult = executeQuery($checkSql);

if (!$checkResult || $checkResult->num_rows == 0) {
    setAlert('Evento não encontrado.', 'danger');
    redirect('../eventos.php');
}

// Marcar como concluído
$sql = "UPDATE eventos SET 
        concluido = 1,
        data_conclusao = NOW()
        WHERE id = $id";

if (executeQuery($sql)) {
    setAlert('Evento marcado como concluído!', 'success');
} else {
    setAlert('Erro ao concluir evento.', 'danger');
}

redirect('../visualizar.php?id=' . $id);
?>