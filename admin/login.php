<?php
// admin/login.php
require_once 'auth.php';

// Se já estiver logado, redireciona
if ($superAdmin->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    if ($superAdmin->login($email, $senha)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'E-mail ou senha inválidos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 400px;">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h4>Acesso Restrito</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="email" name="email" class="form-control mb-3" 
                           placeholder="E-mail" value="super@agrocontrol.com" required>
                    <input type="password" name="senha" class="form-control mb-3" 
                           placeholder="Senha" value="admin123" required>
                    <button type="submit" class="btn btn-dark w-100">
                        Entrar
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>