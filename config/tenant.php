<?php
// config/tenant.php
// Gerenciamento do tenant (fazenda ativa) - Versão simplificada

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/constants.php';

// Iniciar sessão se necessário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class TenantManager {
    private static $currentTenant = null;
    
    /**
     * Verifica se usuário está logado
     */
    public static function isLoggedIn() {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Obtém o ID da fazenda ativa
     */
    public static function getActiveFarmId() {
        return $_SESSION['fazenda_ativa_id'] ?? null;
    }
    
    /**
     * Obtém o nome da fazenda ativa
     */
    public static function getActiveFarmName() {
        return $_SESSION['fazenda_ativa_nome'] ?? 'Selecione uma fazenda';
    }
    
    /**
     * Obtém os dados completos da fazenda ativa
     */
    public static function getActiveFarm() {
        $farmId = self::getActiveFarmId();
        if (!$farmId) return null;
        
        // Usar cache estático
        if (self::$currentTenant && self::$currentTenant['id'] == $farmId) {
            return self::$currentTenant;
        }
        
        global $conn;
        $sql = "SELECT * FROM fazendas WHERE id = " . intval($farmId) . " AND ativo = 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            self::$currentTenant = $result->fetch_assoc();
            return self::$currentTenant;
        }
        
        return null;
    }
    
    /**
     * Lista todas as fazendas que o usuário tem acesso
     */
    public static function getUserFarms($userId = null) {
        if (!$userId) $userId = $_SESSION['usuario_id'] ?? 0;
        if (!$userId) return [];
        
        global $conn;
        $sql = "SELECT f.*, ufp.papel 
                FROM fazendas f
                INNER JOIN fazenda_usuarios ufp ON f.id = ufp.id_fazenda
                WHERE ufp.id_usuario = " . intval($userId) . " 
                AND ufp.ativo = 1
                AND f.ativo = 1
                ORDER BY f.nome_fazenda";
        
        $result = $conn->query($sql);
        $farms = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $farms[] = $row;
            }
        }
        
        return $farms;
    }
    
    /**
     * Muda a fazenda ativa
     */
    public static function setActiveFarm($farmId) {
        $userId = $_SESSION['usuario_id'] ?? 0;
        if (!$userId) return false;
        
        global $conn;
        
        // Verificar se usuário tem acesso
        $sql = "SELECT * FROM fazenda_usuarios 
                WHERE id_usuario = " . intval($userId) . " 
                AND id_fazenda = " . intval($farmId) . " 
                AND ativo = 1";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $_SESSION['fazenda_ativa_id'] = intval($farmId);
            
            // Buscar nome da fazenda
            $farmSql = "SELECT nome_fazenda FROM fazendas WHERE id = " . intval($farmId);
            $farmResult = $conn->query($farmSql);
            if ($farmResult && $farmResult->num_rows > 0) {
                $farm = $farmResult->fetch_assoc();
                $_SESSION['fazenda_ativa_nome'] = $farm['nome_fazenda'];
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Adiciona filtro de tenant em queries SQL
     */
    public static function addTenantFilter($tableAlias = '') {
        $farmId = self::getActiveFarmId();
        if (!$farmId) return " 1=0 "; // Não retorna nada se não tiver fazenda
        
        if ($tableAlias) {
            return " $tableAlias.id_fazenda = $farmId ";
        }
        
        return " id_fazenda = $farmId ";
    }
}

// Funções globais para facilitar o uso
function isLoggedIn() {
    return TenantManager::isLoggedIn();
}

function getActiveFarmId() {
    return TenantManager::getActiveFarmId();
}

// Verificar se precisa redirecionar para seleção de fazenda
function ensureActiveFarm() {
    if (!isLoggedIn()) {
        return;
    }
    
    $currentPage = basename($_SERVER['PHP_SELF']);
    $publicPages = ['login.php', 'logout.php', 'register.php', 'selector.php', 'ajax_change_farm.php'];
    
    if (in_array($currentPage, $publicPages)) {
        return;
    }
    
    $farms = TenantManager::getUserFarms();
    
    if (empty($farms)) {
        // Usuário não tem nenhuma fazenda - redirecionar para cadastro
        if ($currentPage != 'cadastrar.php') {
            header('Location: ' . BASE_URL . 'modules/fazendas/cadastrar.php');
            exit;
        }
    } elseif (count($farms) == 1 && !getActiveFarmId()) {
        // Tem apenas uma fazenda, seleciona automaticamente
        TenantManager::setActiveFarm($farms[0]['id']);
    } elseif (count($farms) > 1 && !getActiveFarmId()) {
        // Tem múltiplas fazendas mas nenhuma selecionada
        if ($currentPage != 'selector.php' && strpos($currentPage, 'fazendas/') === false) {
            header('Location: ' . BASE_URL . 'modules/fazendas/selector.php');
            exit;
        }
    }
}

// Executar verificação (exceto em páginas específicas)
ensureActiveFarm();
?>