<?php
// core/TenantManager.php
// Classe para gerenciar o tenant (fazenda) atual

class TenantManager {
    private static $instance = null;
    private $currentTenant = null;
    private $db;
    
    private function __construct() {
        global $conn;
        $this->db = $conn;
        $this->loadCurrentTenant();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carrega o tenant atual baseado na sessão ou subdomínio
     */
    private function loadCurrentTenant() {
        // Estratégia 1: Por subdomínio (ex: fazenda1.agrocontrol.com)
        $host = $_SERVER['HTTP_HOST'];
        if (strpos($host, '.') !== false && substr_count($host, '.') >= 2) {
            $subdomain = explode('.', $host)[0];
            if ($subdomain && $subdomain != 'www' && $subdomain != 'app') {
                $this->loadTenantBySubdomain($subdomain);
                return;
            }
        }
        
        // Estratégia 2: Por sessão (usuário logado)
        if (isset($_SESSION['tenant_id'])) {
            $this->loadTenantById($_SESSION['tenant_id']);
            return;
        }
        
        // Estratégia 3: Por usuário (se tiver apenas uma fazenda)
        if (isset($_SESSION['usuario_id'])) {
            $this->loadTenantByUser($_SESSION['usuario_id']);
        }
    }
    
    /**
     * Carrega tenant por ID
     */
    private function loadTenantById($tenantId) {
        $sql = "SELECT f.*, p.nome_plano, p.preco_mensal 
                FROM fazendas f
                JOIN planos p ON f.id_plano = p.id
                WHERE f.id = " . intval($tenantId) . " 
                AND f.status IN ('trial', 'ativo')";
        
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $this->currentTenant = $result->fetch_assoc();
            $_SESSION['tenant_id'] = $this->currentTenant['id'];
            $_SESSION['tenant_nome'] = $this->currentTenant['nome_fazenda'];
        }
    }
    
    /**
     * Carrega tenant por subdomínio
     */
    private function loadTenantBySubdomain($subdomain) {
        $sql = "SELECT f.*, p.nome_plano, p.preco_mensal 
                FROM fazendas f
                JOIN planos p ON f.id_plano = p.id
                WHERE f.subdomain = '" . $this->db->real_escape_string($subdomain) . "'
                AND f.status IN ('trial', 'ativo')";
        
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $this->currentTenant = $result->fetch_assoc();
            $_SESSION['tenant_id'] = $this->currentTenant['id'];
            $_SESSION['tenant_nome'] = $this->currentTenant['nome_fazenda'];
        }
    }
    
    /**
     * Carrega a primeira fazenda do usuário
     */
    private function loadTenantByUser($userId) {
        $sql = "SELECT f.*, p.nome_plano, p.preco_mensal 
                FROM fazendas f
                JOIN planos p ON f.id_plano = p.id
                JOIN fazenda_usuarios fu ON f.id = fu.id_fazenda
                WHERE fu.id_usuario = " . intval($userId) . "
                AND fu.ativo = 1
                AND f.status IN ('trial', 'ativo')
                LIMIT 1";
        
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $this->currentTenant = $result->fetch_assoc();
            $_SESSION['tenant_id'] = $this->currentTenant['id'];
            $_SESSION['tenant_nome'] = $this->currentTenant['nome_fazenda'];
        }
    }
    
    /**
     * Retorna o tenant atual
     */
    public function getCurrentTenant() {
        return $this->currentTenant;
    }
    
    /**
     * Retorna o ID do tenant atual
     */
    public function getCurrentTenantId() {
        return $this->currentTenant ? $this->currentTenant['id'] : null;
    }
    
    /**
     * Verifica se o usuário atual tem acesso ao tenant
     */
    public function checkUserAccess($userId, $tenantId = null) {
        if (!$tenantId) {
            $tenantId = $this->getCurrentTenantId();
        }
        
        if (!$tenantId) return false;
        
        $sql = "SELECT * FROM fazenda_usuarios 
                WHERE id_fazenda = " . intval($tenantId) . "
                AND id_usuario = " . intval($userId) . "
                AND ativo = 1";
        
        $result = $this->db->query($sql);
        return ($result && $result->num_rows > 0);
    }
    
    /**
     * Retorna o papel do usuário no tenant atual
     */
    public function getUserRole($userId, $tenantId = null) {
        if (!$tenantId) {
            $tenantId = $this->getCurrentTenantId();
        }
        
        if (!$tenantId) return null;
        
        $sql = "SELECT papel FROM fazenda_usuarios 
                WHERE id_fazenda = " . intval($tenantId) . "
                AND id_usuario = " . intval($userId) . "
                AND ativo = 1";
        
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc()['papel'];
        }
        
        return null;
    }
    
    /**
     * Verifica se o tenant tem um módulo ativo
     */
    public function hasModule($moduleCode) {
        if (!$this->currentTenant) return false;
        
        $sql = "SELECT * FROM plano_modulos pm
                JOIN modulos m ON pm.id_modulo = m.id
                WHERE pm.id_plano = " . $this->currentTenant['id_plano'] . "
                AND m.codigo = '" . $this->db->real_escape_string($moduleCode) . "'";
        
        $result = $this->db->query($sql);
        return ($result && $result->num_rows > 0);
    }
    
    /**
     * Verifica limite de animais
     */
    public function checkAnimalLimit($currentCount = null) {
        if (!$this->currentTenant) return false;
        
        $plano = $this->currentTenant;
        if ($plano['max_animais'] === null) return true; // Ilimitado
        
        if ($currentCount === null) {
            $sql = "SELECT COUNT(*) as total FROM bovinos 
                    WHERE id_fazenda = " . $plano['id'] . " AND ativo = 1";
            $result = $this->db->query($sql);
            $currentCount = $result->fetch_assoc()['total'];
        }
        
        return $currentCount < $plano['max_animais'];
    }
    
    /**
     * Aplica filtro de tenant em queries SQL
     */
    public function addTenantFilter($table, $alias = '') {
        $tenantId = $this->getCurrentTenantId();
        if (!$tenantId) return " 1=0 "; // Não retorna nada
        
        $table = $this->db->real_escape_string($table);
        return " $table.id_fazenda = $tenantId ";
    }
    
    /**
     * Define um tenant manualmente (admin)
     */
    public function setTenant($tenantId) {
        $this->loadTenantById($tenantId);
        return $this->currentTenant !== null;
    }
}