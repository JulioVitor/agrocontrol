<?php
// modules/auth/logout.php
// Encerrar sessão do usuário
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Registrar o logout (opcional)
if (TenantManager::isLoggedIn()) {
    $userId = $_SESSION['usuario_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    global $conn;
    
    // Verificar a estrutura da tabela logs
    $checkSql = "SHOW COLUMNS FROM logs LIKE 'id_fazenda'";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        // Coluna id_fazenda existe - verificar se permite NULL
        $colInfo = $checkResult->fetch_assoc();
        if ($colInfo['Null'] == 'NO') {
            // É NOT NULL, precisamos de um valor
            $farmId = isset($_SESSION['fazenda_ativa_id']) ? $_SESSION['fazenda_ativa_id'] : 0;
            $logSql = "INSERT INTO logs (usuario_id, id_fazenda, acao, ip, data) 
                       VALUES ($userId, $farmId, 'logout', '$ip', NOW())";
        } else {
            // Permite NULL
            $logSql = "INSERT INTO logs (usuario_id, id_fazenda, acao, ip, data) 
                       VALUES ($userId, NULL, 'logout', '$ip', NOW())";
        }
    } else {
        // Coluna não existe - insert simples
        $logSql = "INSERT INTO logs (usuario_id, acao, ip, data) 
                   VALUES ($userId, 'logout', '$ip', NOW())";
    }
    
    // Executar query (com @ para suprimir erros)
    @$conn->query($logSql);
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login com mensagem
setAlert('Você saiu do sistema com sucesso!', 'info');
redirect('login.php');
exit;
?>