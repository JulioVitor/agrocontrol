<?php
// modules/fazendas/excluir.php
// Excluir fazenda (soft delete)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Verificar se o usuário é proprietário da fazenda
$checkSql = "SELECT f.*, ufp.papel 
             FROM fazendas f
             INNER JOIN fazenda_usuarios ufp ON f.id = ufp.id_fazenda
             WHERE f.id = $id AND ufp.id_usuario = $userId AND ufp.ativo = 1";
$result = executeQuery($checkSql);

if (!$result || $result->num_rows == 0) {
    setAlert('Fazenda não encontrada ou você não tem permissão.', 'danger');
    redirect('index.php');
}

$fazenda = $result->fetch_assoc();

// Verificar se é proprietário (só proprietário pode excluir)
if ($fazenda['papel'] != 'proprietario') {
    setAlert('Apenas o proprietário pode excluir a fazenda.', 'danger');
    redirect('visualizar.php?id=' . $id);
}

// Verificar se tem animais cadastrados
$checkAnimais = "SELECT COUNT(*) as total FROM bovinos WHERE id_fazenda = $id AND ativo = 1";
$resAnimais = executeQuery($checkAnimais);
$totalAnimais = $resAnimais->fetch_assoc()['total'];

if ($totalAnimais > 0) {
    setAlert('Não é possível excluir uma fazenda com animais cadastrados. Transfira ou exclua os animais primeiro.', 'danger');
    redirect('visualizar.php?id=' . $id);
}

// Soft delete (marcar como inativo)
$sql = "UPDATE fazendas SET ativo = 0 WHERE id = $id";

if (executeQuery($sql)) {
    // Remover todos os usuários vinculados
    $sql2 = "UPDATE fazenda_usuarios SET ativo = 0 WHERE id_fazenda = $id";
    executeQuery($sql2);
    
    setAlert('Fazenda excluída com sucesso!', 'success');
    
    // Se era a fazenda ativa, limpar da sessão
    if ($id == getActiveFarmId()) {
        unset($_SESSION['fazenda_ativa_id']);
        unset($_SESSION['fazenda_ativa_nome']);
    }
} else {
    setAlert('Erro ao excluir fazenda.', 'danger');
}

redirect('index.php');
?>