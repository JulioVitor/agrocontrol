<?php
// admin/auth.php - Autenticação do Super Admin usando TABELA
session_start();

// Configurações
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hora

// Conectar ao banco de dados
require_once __DIR__ . '/../config/database.php';

class SuperAdmin {
    
    private static $instance = null;
    private $db;
    
    private function __construct() {
        global $conn;
        $this->db = $conn;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Verificar login na tabela super_admin
     */
    public function login($email, $senha) {
        $email = $this->db->real_escape_string($email);
        
        $sql = "SELECT * FROM super_admin WHERE email = '$email'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verificar senha
            if (password_verify($senha, $admin['senha'])) {
                $_SESSION['super_admin'] = [
                    'id' => $admin['id'],
                    'nome' => $admin['nome'],
                    'email' => $admin['email'],
                    'login_time' => time()
                ];
                
                // Atualizar último acesso
                $updateSql = "UPDATE super_admin SET ultimo_acesso = NOW() WHERE id = " . $admin['id'];
                $this->db->query($updateSql);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar se está logado
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['super_admin'])) {
            return false;
        }
        
        // Verificar timeout
        if (time() - $_SESSION['super_admin']['login_time'] > ADMIN_SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Fazer logout
     */
    public function logout() {
        unset($_SESSION['super_admin']);
        session_destroy();
    }
    
    /**
     * Redirecionar se não estiver logado
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Obter dados do admin logado
     */
    public function getAdmin() {
        return $_SESSION['super_admin'] ?? null;
    }
}

$superAdmin = SuperAdmin::getInstance();

// Se não estiver logado e não for página de login, redireciona
if (!$superAdmin->isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: login.php');
    exit;
}
?>