<?php
// modules/configuracao/usuarios.php
// Gerenciamento de usuários da fazenda - VERSÃO COM CADASTRO DIRETO

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    redirect(BASE_URL . 'modules/fazendas/selector.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Gerenciar Usuários';
$error = '';
$success = '';

// Verificar se o usuário atual é proprietário da fazenda
$checkPerm = "SELECT papel FROM fazenda_usuarios WHERE id_fazenda = $farmId AND id_usuario = $userId";
$resultPerm = executeQuery($checkPerm);
$userPerm = $resultPerm->fetch_assoc();
$isOwner = ($userPerm['papel'] == 'proprietario');

if (!$isOwner) {
    setAlert('Apenas o proprietário pode gerenciar usuários.', 'danger');
    redirect('index.php');
}

// Processar ações
if (isset($_GET['remove'])) {
    $removeUserId = intval($_GET['remove']);
    
    if ($removeUserId == $userId) {
        setAlert('Você não pode remover a si mesmo.', 'danger');
    } else {
        $sql = "UPDATE fazenda_usuarios SET ativo = 0 WHERE id_fazenda = $farmId AND id_usuario = $removeUserId";
        if (executeQuery($sql)) {
            setAlert('Usuário removido com sucesso!', 'success');
        } else {
            setAlert('Erro ao remover usuário.', 'danger');
        }
    }
    redirect('usuarios.php');
}

// Processar formulário de adicionar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    
    $email = escapeString(trim($_POST['email']));
    $papel = $_POST['papel'];
    $nome = escapeString(trim($_POST['nome']));
    $telefone = !empty($_POST['telefone']) ? escapeString($_POST['telefone']) : '';
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if (empty($email) || empty($papel) || empty($nome)) {
        $error = 'Nome, e-mail e papel são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } elseif (!empty($senha) && $senha !== $confirmar_senha) {
        $error = 'As senhas não conferem.';
    } elseif (!empty($senha) && strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Verificar se usuário já existe
            $sqlCheck = "SELECT id, nome FROM usuarios WHERE email = '$email'";
            $resultCheck = executeQuery($sqlCheck);
            $novoUserId = null;
            
            if ($resultCheck && $resultCheck->num_rows > 0) {
                // Usuário já existe
                $usuario = $resultCheck->fetch_assoc();
                $novoUserId = $usuario['id'];
                $mensagem = "Usuário {$usuario['nome']} já existia e foi adicionado à fazenda!";
            } else {
                // Criar novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sqlInsert = "INSERT INTO usuarios (nome, email, telefone, senha, nivel, email_confirmado, ativo) 
                              VALUES ('$nome', '$email', " . ($telefone ? "'$telefone'" : "NULL") . ", 
                                      '$senha_hash', 'usuario', 1, 1)";
                
                if (!$conn->query($sqlInsert)) {
                    throw new Exception('Erro ao criar usuário.');
                }
                
                $novoUserId = $conn->insert_id;
                $mensagem = "Usuário $nome criado e adicionado à fazenda com sucesso!";
            }
            
            // Verificar se já está vinculado à fazenda
            $checkVinculo = "SELECT id FROM fazenda_usuarios WHERE id_fazenda = $farmId AND id_usuario = $novoUserId";
            $resultVinculo = $conn->query($checkVinculo);
            
            if ($resultVinculo && $resultVinculo->num_rows > 0) {
                throw new Exception('Este usuário já tem acesso a esta fazenda.');
            }
            
            // Vincular à fazenda
            $sqlLink = "INSERT INTO fazenda_usuarios (id_fazenda, id_usuario, papel) 
                        VALUES ($farmId, $novoUserId, '$papel')";
            
            if (!$conn->query($sqlLink)) {
                throw new Exception('Erro ao vincular usuário à fazenda.');
            }
            
            $conn->commit();
            setAlert($mensagem, 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
        
        redirect('usuarios.php');
    }
}

// Processar edição de papel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_role') {
    $editUserId = intval($_POST['user_id']);
    $novoPapel = $_POST['papel'];
    
    if ($editUserId == $userId) {
        $error = 'Você não pode alterar seu próprio papel.';
    } else {
        $sql = "UPDATE fazenda_usuarios SET papel = '$novoPapel' 
                WHERE id_fazenda = $farmId AND id_usuario = $editUserId";
        
        if (executeQuery($sql)) {
            setAlert('Papel do usuário atualizado com sucesso!', 'success');
        } else {
            setAlert('Erro ao atualizar papel.', 'danger');
        }
    }
    redirect('usuarios.php');
}

// Buscar usuários da fazenda
$sql = "SELECT u.*, fu.papel, fu.data_atribuicao
        FROM usuarios u
        JOIN fazenda_usuarios fu ON u.id = fu.id_usuario
        WHERE fu.id_fazenda = $farmId AND fu.ativo = 1
        ORDER BY FIELD(fu.papel, 'proprietario', 'gerente', 'funcionario', 'consultor'), u.nome";
$usuarios = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-people me-2 text-success"></i>
                Gerenciar Usuários
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Usuários</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUsuarioModal">
            <i class="bi bi-person-plus me-2"></i>
            Adicionar Usuário
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Cards de Permissões -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="bi bi-shield-lock display-6"></i>
                    <h5>Proprietário</h5>
                    <small>Acesso total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-shield display-6"></i>
                    <h5>Gerente</h5>
                    <small>Pode tudo, exceto usuários</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-person display-6"></i>
                    <h5>Funcionário</h5>
                    <small>Registra e edita</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-eye display-6"></i>
                    <h5>Consultor</h5>
                    <small>Apenas visualização</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Usuários -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Usuários com Acesso à Fazenda</h6>
        </div>
        <div class="card-body p-0">
            <?php if ($usuarios && $usuarios->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Usuário</th>
                            <th>E-mail</th>
                            <th>Papel</th>
                            <th>Desde</th>
                            <th>Último Acesso</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <i class="bi bi-person-circle me-2 fs-5"></i>
                                <strong><?php echo $user['nome']; ?></strong>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="edit_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="papel" class="form-select form-select-sm" style="width: auto; display: inline-block;"
                                            <?php echo ($user['id'] == $userId) ? 'disabled' : ''; ?>
                                            onchange="this.form.submit()">
                                        <option value="consultor" <?php echo $user['papel'] == 'consultor' ? 'selected' : ''; ?>>Consultor</option>
                                        <option value="funcionario" <?php echo $user['papel'] == 'funcionario' ? 'selected' : ''; ?>>Funcionário</option>
                                        <option value="gerente" <?php echo $user['papel'] == 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                                        <option value="proprietario" <?php echo $user['papel'] == 'proprietario' ? 'selected' : ''; ?>>Proprietário</option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['data_atribuicao'])); ?></td>
                            <td><?php echo $user['ultimo_login'] ? date('d/m/Y H:i', strtotime($user['ultimo_login'])) : 'Nunca'; ?></td>
                            <td>
                                <?php if ($user['id'] != $userId): ?>
                                <a href="?remove=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Remover acesso deste usuário?')">
                                    <i class="bi bi-person-x"></i> Remover
                                </a>
                                <?php else: ?>
                                <span class="text-muted">(você)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum usuário encontrado</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Adicionar Usuário - VERSÃO COMPLETA -->
<div class="modal fade" id="addUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Adicionar Usuário</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formAddUsuario">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Se o e-mail já estiver cadastrado, o usuário será apenas vinculado à fazenda.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Usuário *</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control phone-mask" name="telefone">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" class="form-control" name="senha" id="senha">
                        <small class="text-muted">Deixe em branco se o usuário já existir</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" name="confirmar_senha" id="confirmar_senha">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Papel *</label>
                        <select class="form-select" name="papel" required>
                            <option value="consultor">Consultor (apenas visualização)</option>
                            <option value="funcionario">Funcionário (pode registrar e editar)</option>
                            <option value="gerente">Gerente (pode tudo, exceto usuários)</option>
                            <option value="proprietario">Proprietário (acesso total)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adicionar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

// Validação de senha no formulário
document.getElementById('formAddUsuario').addEventListener('submit', function(e) {
    var senha = document.getElementById('senha').value;
    var confirmar = document.getElementById('confirmar_senha').value;
    
    if (senha && senha !== confirmar) {
        e.preventDefault();
        alert('As senhas não conferem!');
        return false;
    }
    
    if (senha && senha.length < 6) {
        e.preventDefault();
        alert('A senha deve ter pelo menos 6 caracteres!');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>