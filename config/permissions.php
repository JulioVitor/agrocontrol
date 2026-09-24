<?php
// config/permissions.php
// Sistema de permissões do AgroControl

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/tenant.php';

class PermissionManager {
    
    /**
     * Hierarquia de papéis (quanto maior o número, mais permissões)
     */
    private static $roleHierarchy = [
        'consultor' => 10,
        'funcionario' => 20,
        'gerente' => 30,
        'proprietario' => 40
    ];
    
    /**
     * Permissões por papel
     */
    private static $permissions = [
        // Consultor (apenas visualização)
        'consultor' => [
            'view_animais' => true,
            'view_producao' => true,
            'view_estoque' => true,
            'view_financeiro' => true,
            'view_relatorios' => true,
            'create_animais' => false,
            'edit_animais' => false,
            'delete_animais' => false,
            'create_producao' => false,
            'edit_producao' => false,
            'delete_producao' => false,
            'create_estoque' => false,
            'edit_estoque' => false,
            'delete_estoque' => false,
            'create_financeiro' => false,
            'edit_financeiro' => false,
            'delete_financeiro' => false,
            'manage_users' => false,
            'manage_config' => false
        ],
        
        // Funcionário (pode registrar e editar, mas não excluir)
        'funcionario' => [
            'view_animais' => true,
            'view_producao' => true,
            'view_estoque' => true,
            'view_financeiro' => true,
            'view_relatorios' => true,
            'create_animais' => true,
            'edit_animais' => true,
            'delete_animais' => false,
            'create_producao' => true,
            'edit_producao' => true,
            'delete_producao' => false,
            'create_estoque' => true,
            'edit_estoque' => true,
            'delete_estoque' => false,
            'create_financeiro' => true,
            'edit_financeiro' => true,
            'delete_financeiro' => false,
            'manage_users' => false,
            'manage_config' => false
        ],
        
        // Gerente (pode tudo, exceto gerenciar usuários)
        'gerente' => [
            'view_animais' => true,
            'view_producao' => true,
            'view_estoque' => true,
            'view_financeiro' => true,
            'view_relatorios' => true,
            'create_animais' => true,
            'edit_animais' => true,
            'delete_animais' => true,
            'create_producao' => true,
            'edit_producao' => true,
            'delete_producao' => true,
            'create_estoque' => true,
            'edit_estoque' => true,
            'delete_estoque' => true,
            'create_financeiro' => true,
            'edit_financeiro' => true,
            'delete_financeiro' => true,
            'manage_users' => false,
            'manage_config' => true
        ],
        
        // Proprietário (tudo)
        'proprietario' => [
            'view_animais' => true,
            'view_producao' => true,
            'view_estoque' => true,
            'view_financeiro' => true,
            'view_relatorios' => true,
            'create_animais' => true,
            'edit_animais' => true,
            'delete_animais' => true,
            'create_producao' => true,
            'edit_producao' => true,
            'delete_producao' => true,
            'create_estoque' => true,
            'edit_estoque' => true,
            'delete_estoque' => true,
            'create_financeiro' => true,
            'edit_financeiro' => true,
            'delete_financeiro' => true,
            'manage_users' => true,
            'manage_config' => true
        ]
    ];
    
    /**
     * Obtém o papel do usuário na fazenda atual
     */
    public static function getUserRole($userId = null, $farmId = null) {
        if ($userId === null) {
            $userId = $_SESSION['usuario_id'] ?? 0;
        }
        
        if ($farmId === null) {
            $farmId = getActiveFarmId();
        }
        
        if (!$userId || !$farmId) {
            return null;
        }
        
        global $conn;
        $sql = "SELECT papel FROM fazenda_usuarios 
                WHERE id_usuario = $userId AND id_fazenda = $farmId AND ativo = 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc()['papel'];
        }
        
        return null;
    }
    
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public static function hasPermission($permission, $userId = null, $farmId = null) {
        $role = self::getUserRole($userId, $farmId);
        
        if (!$role || !isset(self::$permissions[$role])) {
            return false;
        }
        
        // Verifica se tem a permissão específica
        if (isset(self::$permissions[$role][$permission])) {
            return self::$permissions[$role][$permission];
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário tem pelo menos um determinado papel
     */
    public static function hasRole($minRole, $userId = null, $farmId = null) {
        $role = self::getUserRole($userId, $farmId);
        
        if (!$role) {
            return false;
        }
        
        $userLevel = self::$roleHierarchy[$role] ?? 0;
        $requiredLevel = self::$roleHierarchy[$minRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Redireciona se não tiver permissão
     */
    public static function requirePermission($permission) {
        if (!self::hasPermission($permission)) {
            setAlert('Você não tem permissão para realizar esta ação.', 'danger');
            redirect(BASE_URL . 'modules/dashboard/index.php');
            exit;
        }
    }
    
    /**
     * Redireciona se não tiver o papel mínimo
     */
    public static function requireRole($minRole) {
        if (!self::hasRole($minRole)) {
            setAlert('Você não tem permissão para acessar esta página.', 'danger');
            redirect(BASE_URL . 'modules/dashboard/index.php');
            exit;
        }
    }
    
    /**
     * Verifica se pode visualizar (para esconder botões)
     */
    public static function canView($module) {
        return self::hasPermission("view_$module");
    }
    
    /**
     * Verifica se pode criar
     */
    public static function canCreate($module) {
        return self::hasPermission("create_$module");
    }
    
    /**
     * Verifica se pode editar
     */
    public static function canEdit($module) {
        return self::hasPermission("edit_$module");
    }
    
    /**
     * Verifica se pode excluir
     */
    public static function canDelete($module) {
        return self::hasPermission("delete_$module");
    }
    
    /**
     * Lista todos os papéis disponíveis
     */
    public static function getAvailableRoles() {
        return array_keys(self::$roleHierarchy);
    }
    
    /**
     * Obtém as permissões de um papel
     */
    public static function getRolePermissions($role) {
        return self::$permissions[$role] ?? [];
    }
}
?>