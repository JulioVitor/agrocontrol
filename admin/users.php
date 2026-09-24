<?php
// modules/admin/users.php
// Gerenciar todos os usuários do sistema

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once 'auth.php'; 

// Verificar se é admin
$admin_emails = ['admin@agrocontrol.com', 'seu@email.com'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_email'], $admin_emails)) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$pageTitle = 'Gerenciar Usuários';

// Processar ações
if (isset($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($_GET['action'] == 'toggle_admin' && $id > 0) {
        $sql = "UPDATE usuarios SET nivel = IF(nivel = 'admin', 'usuario', 'admin') WHERE id = $id";
        executeQuery($sql);
        setAlert('Nível do usuário alterado!', 'success');
        redirect('users.php');
    }
    
    if ($_GET['action'] == 'delete' && $id > 0 && $id != $_SESSION['usuario_id']) {
        $sql = "UPDATE usuarios SET ativo = 0 WHERE id = $id";
        executeQuery($sql);
        setAlert('Usuário desativado!', 'success');
        redirect('users.php');
    }
}

// Filtros
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';
$nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';

// Construir WHERE
$where = "1=1";

if ($search) {
    $where .= " AND (nome LIKE '%$search%' OR email LIKE '%$search%')";
}

if ($nivel) {
    $where .= " AND nivel = '$nivel'";
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Contar total
$countSql = "SELECT COUNT(*) as total FROM usuarios WHERE $where";
$countResult = executeQuery($countSql);
$totalRegistros = $countResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar usuários
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM fazendas WHERE id_proprietario = u.id) as total_fazendas,
               (SELECT COUNT(*) FROM fazenda_usuarios WHERE id_usuario = u.id) as total_acessos
        FROM usuarios u
        WHERE $where
        ORDER BY u.data_cadastro DESC
        LIMIT $offset, $limit";
$usuarios = executeQuery($sql);

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
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people me-2"></i>
                            Usuários
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
                <h1 class="h2">Gerenciar Usuários</h1>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Nome ou email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nível</label>
                            <select class="form-select" name="nivel">
                                <option value="">Todos</option>
                                <option value="admin" <?php echo $nivel == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="usuario" <?php echo $nivel == 'usuario' ? 'selected' : ''; ?>>Usuário</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success mt-4">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Usuários -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Nível</th>
                                    <th>Fazendas</th>
                                    <th>Acessos</th>
                                    <th>Cadastro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo $user['nome']; ?></strong>
                                        <?php if ($user['telefone']): ?>
                                            <br><small><?php echo $user['telefone']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <?php if ($user['nivel'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Usuário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['total_fazendas']; ?></td>
                                    <td><?php echo $user['total_acessos']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['data_cadastro'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                                <a href="?action=toggle_admin&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-<?php echo $user['nivel'] == 'admin' ? 'warning' : 'success'; ?>"
                                                   title="<?php echo $user['nivel'] == 'admin' ? 'Remover admin' : 'Tornar admin'; ?>">
                                                    <i class="bi bi-shield"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Desativar"
                                                   onclick="return confirm('Desativar este usuário?')">
                                                    <i class="bi bi-person-x"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($totalPaginas > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">Anterior</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $totalPaginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($_GET); ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people display-1 text-muted"></i>
                        <h4 class="mt-3">Nenhum usuário encontrado</h4>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>