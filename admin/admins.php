<?php
// modules/admin/admins.php
// Gerenciar administradores do sistema

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/admin_auth.php';
require_once 'auth.php'; 

// Apenas super_admin pode gerenciar admins
AdminAuth::requireLevel('super_admin');

$pageTitle = 'Gerenciar Administradores';
$error = '';
$success = '';

// Processar ações
if (isset($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($_GET['action'] == 'remove' && $id > 0 && $id != $_SESSION['usuario_id']) {
        if (AdminAuth::removeAdmin($id)) {
            setAlert('Administrador removido com sucesso!', 'success');
        } else {
            setAlert('Erro ao remover administrador.', 'danger');
        }
        redirect('admins.php');
    }
}

// Processar formulário de adicionar admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $user_id = intval($_POST['user_id']);
    $nivel = $_POST['nivel'];
    
    // Verificar se usuário existe
    $checkUser = "SELECT id, nome FROM usuarios WHERE id = $user_id";
    $resultUser = executeQuery($checkUser);
    
    if ($resultUser && $resultUser->num_rows > 0) {
        $user = $resultUser->fetch_assoc();
        
        if (AdminAuth::addAdmin($user_id, $nivel)) {
            $success = "Usuário {$user['nome']} adicionado como administrador!";
        } else {
            $error = "Erro ao adicionar administrador. Talvez já seja admin.";
        }
    } else {
        $error = "Usuário não encontrado.";
    }
}

// Listar administradores atuais
$admins = AdminAuth::getAdmins();

// Buscar usuários comuns para adicionar como admin
$sqlUsers = "SELECT u.id, u.nome, u.email 
             FROM usuarios u
             LEFT JOIN admin_usuarios a ON u.id = a.id_usuario
             WHERE a.id_usuario IS NULL AND u.ativo = 1
             ORDER BY u.nome";
$usuarios = executeQuery($sqlUsers);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Admin -->
        <nav class="col-md-2 d-md-block bg-light sidebar" style="min-height: 100vh;">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tenants.php">
                            <i class="bi bi-building me-2"></i>
                            Fazendas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="planos.php">
                            <i class="bi bi-tags me-2"></i>
                            Planos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people me-2"></i>
                            Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admins.php">
                            <i class="bi bi-shield-lock me-2"></i>
                            Administradores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system.php">
                            <i class="bi bi-gear me-2"></i>
                            Sistema
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gerenciar Administradores</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Formulário para adicionar admin -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-person-plus"></i> Adicionar Novo Administrador
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Selecionar Usuário</label>
                            <select class="form-select" name="user_id" required>
                                <option value="">Selecione...</option>
                                <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                                    <?php while ($u = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo $u['nome']; ?> (<?php echo $u['email']; ?>)
                                    </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>Todos os usuários já são administradores</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nível</label>
                            <select class="form-select" name="nivel" required>
                                <option value="admin">Admin</option>
                                <option value="suporte">Suporte</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_admin" class="btn btn-success mt-4">
                                <i class="bi bi-plus-circle"></i> Adicionar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabela de Administradores -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-shield"></i> Administradores Atuais
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($admins)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Nível</th>
                                    <th>Adicionado por</th>
                                    <th>Data</th>
                                    <th>Último Acesso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td>#<?php echo $admin['id_usuario']; ?></td>
                                    <td>
                                        <strong><?php echo $admin['nome']; ?></strong>
                                    </td>
                                    <td><?php echo $admin['email']; ?></td>
                                    <td>
                                        <?php if ($admin['nivel'] == 'super_admin'): ?>
                                            <span class="badge bg-danger">Super Admin</span>
                                        <?php elseif ($admin['nivel'] == 'admin'): ?>
                                            <span class="badge bg-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Suporte</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $admin['criado_por_nome'] ?? 'Sistema'; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($admin['data_criacao'])); ?></td>
                                    <td>
                                        <?php if ($admin['ultimo_acesso']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($admin['ultimo_acesso'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($admin['id_usuario'] != $_SESSION['usuario_id']): ?>
                                            <a href="?action=remove&id=<?php echo $admin['id_usuario']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Remover este administrador?')">
                                                <i class="bi bi-person-x"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">(você)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-shield-lock display-1 text-muted"></i>
                        <h4 class="mt-3">Nenhum administrador encontrado</h4>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Níveis de Acesso -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-info-circle"></i> Níveis de Acesso
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5>Super Admin</h5>
                                    <small>Acesso total ao sistema, pode gerenciar outros admins</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Admin</h5>
                                    <small>Pode gerenciar fazendas, usuários e planos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Suporte</h5>
                                    <small>Acesso de visualização apenas, não pode alterar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>