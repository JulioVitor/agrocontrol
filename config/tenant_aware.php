<?php
// config/tenant_aware.php
// Incluir após database.php em todas as páginas que precisam filtrar por tenant

require_once __DIR__ . '/../core/TenantManager.php';

$tenantManager = TenantManager::getInstance();
$currentTenant = $tenantManager->getCurrentTenant();

if (!$currentTenant && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'register.php') {
    // Se não tem tenant e não está na página de login/registro, redireciona
    if (isset($_SESSION['usuario_id'])) {
        // Usuário logado mas sem tenant - mostrar seletor
        redirect(BASE_URL . 'modules/farm/selector.php');
    }
}

// Função para adicionar filtro de tenant em queries SELECT
function withTenant($table, $alias = null) {
    global $tenantManager;
    $tenantId = $tenantManager->getCurrentTenantId();
    
    if (!$tenantId) return " 1=0 "; // Não retorna nada
    
    $tableName = $alias ? $alias : $table;
    return " $tableName.id_fazenda = $tenantId ";
}

// Função para garantir que INSERT tenha id_fazenda
function prepareTenantInsert($data) {
    global $tenantManager;
    $tenantId = $tenantManager->getCurrentTenantId();
    
    if (!$tenantId) {
        throw new Exception("Nenhuma fazenda selecionada");
    }
    
    $data['id_fazenda'] = $tenantId;
    return $data;
}