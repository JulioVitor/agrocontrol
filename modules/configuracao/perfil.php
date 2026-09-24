<?php
// modules/configuracao/perfil.php
// Perfil do usuário logado

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Meu Perfil';
$error = '';
$success = '';

// Buscar dados do usuário
$sql = "SELECT * FROM usuarios WHERE id = $userId";
$result = executeQuery($sql);
$usuario = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = escapeString(trim($_POST['nome']));
    $email = escapeString(trim($_POST['email']));
    $telefone = !empty($_POST['telefone']) ? escapeString($_POST['telefone']) : '';
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nome) || empty($email)) {
        $error = 'Nome e e-mail são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } else {
        
        // Verificar se e-mail já existe para outro usuário
        $checkSql = "SELECT id FROM usuarios WHERE email = '$email' AND id != $userId";
        $checkResult = executeQuery($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $error = 'Este e-mail já está em uso por outro usuário.';
        } else {
            
            $conn->begin_transaction();
            
            try {
                // Atualizar dados básicos
                $sql = "UPDATE usuarios SET 
                        nome = '$nome',
                        email = '$email',
                        telefone = " . ($telefone ? "'$telefone'" : "NULL") . "
                        WHERE id = $userId";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Erro ao atualizar perfil.');
                }
                
                // Alterar senha se solicitado
                if (!empty($nova_senha)) {
                    
                    if (empty($senha_atual)) {
                        throw new Exception('Digite a senha atual para alterar a senha.');
                    }
                    
                    if ($nova_senha != $confirmar_senha) {
                        throw new Exception('A nova senha e a confirmação não conferem.');
                    }
                    
                    if (strlen($nova_senha) < 6) {
                        throw new Exception('A nova senha deve ter pelo menos 6 caracteres.');
                    }
                    
                    // Verificar senha atual
                    $sqlSenha = "SELECT senha FROM usuarios WHERE id = $userId";
                    $resultSenha = $conn->query($sqlSenha);
                    $rowSenha = $resultSenha->fetch_assoc();
                    
                    if (!password_verify($senha_atual, $rowSenha['senha'])) {
                        throw new Exception('Senha atual incorreta.');
                    }
                    
                    $novaSenhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    $sqlUpdateSenha = "UPDATE usuarios SET senha = '$novaSenhaHash' WHERE id = $userId";
                    if (!$conn->query($sqlUpdateSenha)) {
                        throw new Exception('Erro ao alterar senha.');
                    }
                }
                
                $conn->commit();
                
                // Atualizar nome na sessão
                $_SESSION['usuario_nome'] = $nome;
                
                $success = 'Perfil atualizado com sucesso!';
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-person-circle me-2 text-success"></i>
                Meu Perfil
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Perfil</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <!-- Card do Usuário -->
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-person-circle display-1 text-success"></i>
                    <h4 class="mt-3"><?php echo $usuario['nome']; ?></h4>
                    <p class="text-muted">
                        <i class="bi bi-envelope"></i> <?php echo $usuario['email']; ?><br>
                        <?php if ($usuario['telefone']): ?>
                            <i class="bi bi-telephone"></i> <?php echo $usuario['telefone']; ?>
                        <?php endif; ?>
                    </p>
                    <hr>
                    <p class="small text-muted">
                        <i class="bi bi-calendar"></i> Cadastrado em: <?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Formulário de Edição -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Editar Perfil</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome completo *</label>
                                <input type="text" class="form-control" name="nome" 
                                       value="<?php echo $usuario['nome']; ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">E-mail *</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo $usuario['email']; ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control phone-mask" name="telefone" 
                                       value="<?php echo $usuario['telefone'] ?? ''; ?>">
                            </div>

                            <hr class="my-3">
                            <h6>Alterar Senha (deixe em branco para manter)</h6>
                            
                            <div class="col-12">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" name="senha_atual">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" name="nova_senha" 
                                       minlength="6">
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" name="confirmar_senha">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-2"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Máscara de telefone
document.querySelectorAll('.phone-mask').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        
        if (value.length > 6) {
            value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length > 2) {
            value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
        }
        e.target.value = value;
    });
});
</script>

<?php include '../../includes/footer.php'; ?>