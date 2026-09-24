<?php
// config/admin_auth.php
// Verificação de autenticação para área administrativa

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// Iniciar sessão se necessário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Classe para gerenciar autenticação de administradores
 */
class AdminAuth {
    
    /**
     * Verifica se o usuário atual é um administrador
     */
    public static function isAdmin() {
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }
        
        $userId = $_SESSION['usuario_id'];
        
        global $conn;
        $sql = "SELECT a.*, u.nome, u.email 
                FROM admin_usuarios a
                JOIN usuarios u ON a.id_usuario = u.id
                WHERE a.id_usuario = $userId AND a.ativo = 1";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $_SESSION['admin_nivel'] = $admin['nivel'];
            $_SESSION['admin_permissoes'] = json_decode($admin['permissoes'], true);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário tem um nível específico
     */
    public static function hasLevel($level) {
        if (!self::isAdmin()) {
            return false;
        }
        
        $hierarchy = [
            'suporte' => 1,
            'admin' => 2,
            'super_admin' => 3
        ];
        
        $userLevel = $hierarchy[$_SESSION['admin_nivel']] ?? 0;
        $requiredLevel = $hierarchy[$level] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public static function hasPermission($permission) {
        if (!self::isAdmin()) {
            return false;
        }
        
        // Super admin tem todas as permissões
        if ($_SESSION['admin_nivel'] == 'super_admin') {
            return true;
        }
        
        $permissoes = $_SESSION['admin_permissoes'] ?? [];
        return in_array($permission, $permissoes);
    }
    
    /**
     * Redireciona se não for admin
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            $_SESSION['error_message'] = 'Acesso negado. Área restrita a administradores.';
            header('Location: ' . BASE_URL . 'modules/auth/login.php');
            exit;
        }
    }
    
    /**
     * Redireciona se não tiver o nível necessário
     */
    public static function requireLevel($level) {
        self::requireAdmin();
        
        if (!self::hasLevel($level)) {
            $_SESSION['error_message'] = 'Acesso negado. Nível de permissão insuficiente.';
            header('Location: ' . BASE_URL . 'modules/admin/index.php');
            exit;
        }
    }
    
    /**
     * Lista todos os administradores
     */
    public static function getAdmins() {
        global $conn;
        
        $sql = "SELECT a.*, u.nome, u.email, u.data_cadastro,
                       (SELECT nome FROM usuarios WHERE id = a.criado_por) as criado_por_nome
                FROM admin_usuarios a
                JOIN usuarios u ON a.id_usuario = u.id
                ORDER BY a.nivel, u.nome";
        
        $result = $conn->query($sql);
        $admins = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $admins[] = $row;
            }
        }
        
        return $admins;
    }
    
    /**
     * Adicionar um novo administrador
     */
    public static function addAdmin($userId, $nivel = 'admin', $permissoes = null) {
        global $conn;
        
        $criado_por = $_SESSION['usuario_id'] ?? 'NULL';
        $permissoes_json = $permissoes ? "'" . json_encode($permissoes) . "'" : "NULL";
        
        $sql = "INSERT INTO admin_usuarios (id_usuario, nivel, permissoes, criado_por) 
                VALUES ($userId, '$nivel', $permissoes_json, $criado_por)";
        
        return $conn->query($sql);
    }
    
    /**
     * Remover administrador
     */
    public static function removeAdmin($userId) {
        global $conn;
        $sql = "DELETE FROM admin_usuarios WHERE id_usuario = $userId";
        return $conn->query($sql);
    }
    
    /**
     * Atualizar último acesso
     */
    public static function updateLastAccess() {
        if (!isset($_SESSION['usuario_id'])) {
            return;
        }
        
        $userId = $_SESSION['usuario_id'];
        global $conn;
        $sql = "UPDATE admin_usuarios SET ultimo_acesso = NOW() WHERE id_usuario = $userId";
        $conn->query($sql);
    }
}

// Verificar se é admin e atualizar último acesso
if (AdminAuth::isAdmin()) {
    AdminAuth::updateLastAccess();
}
?>