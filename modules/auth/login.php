<?php
// modules/auth/login.php
// Página de login do sistema
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Incluir arquivos de configuração
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'modules/dashboard/index.php');
}

$error = '';
$email = '';

// Processar o formulário de login quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitizar e validar campos
    $email = escapeString(trim($_POST['email']));
    $senha = $_POST['senha']; // Não precisa escapar, vamos usar password_verify
    
    // Validar se campos não estão vazios
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        // Buscar usuário no banco de dados
        $sql = "SELECT id, nome, email, senha, nivel FROM usuarios WHERE email = '$email' AND ativo = 1";
        $result = executeQuery($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verificar a senha usando password_verify
            if (password_verify($senha, $user['senha'])) {
                // Senha correta - iniciar sessão
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['usuario_nivel'] = $user['nivel'];
                $_SESSION['login_time'] = time();
                
                // Registrar o login (opcional - para log de atividades)
                $userId = $user['id'];
                $ip = $_SERVER['REMOTE_ADDR'];
                $logSql = "INSERT INTO logs (usuario_id, acao, ip, data) VALUES ($userId, 'login', '$ip', NOW())";
                executeQuery($logSql);
                
                // Redirecionar para o dashboard
                setAlert('Login realizado com sucesso! Bem-vindo(a) ' . $user['nome'] . '!', 'success');
                redirect(BASE_URL . 'modules/dashboard/index.php');
            } else {
                $error = 'E-mail ou senha incorretos.';
            }
        } else {
            $error = 'E-mail ou senha incorretos.';
        }
    }
}

// Título da página
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 90%;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #667eea;
        }
        
        .input-group-text {
            border-radius: 25px 0 0 25px;
            background: white;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 25px 25px 0;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .logo i {
            font-size: 40px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <div class="logo">
                    <i class="bi bi-tree-fill"></i>
                </div>
                <h3>AgroControl</h3>
                <p class="mb-0">Sistema de Gestão Rural</p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required 
                                   placeholder="seu@email.com">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" 
                                   required placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar no Sistema
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="#" class="text-muted small">Esqueceu sua senha?</a>
                    </div>
                </form>
            </div>
            
            <div class="card-footer text-center bg-transparent py-3">
                <small class="text-muted">
                    &copy; <?php echo date('Y'); ?> AgroControl - Todos os direitos reservados
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>