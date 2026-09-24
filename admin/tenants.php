<?php
// modules/admin/tenants.php
// Gerenciar todas as fazendas do sistema

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once 'auth.php'; 

// Verificar se é admin (suporte pode ver, mas não suspender)
AdminAuth::requireAdmin();

// Verificar permissão específica para suspender
$canSuspend = AdminAuth::hasLevel('admin'); // Apenas admin e super_admin podem suspender

// Verificar se é admin
$admin_emails = ['admin@agrocontrol.com', 'seu@email.com'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_email'], $admin_emails)) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$pageTitle = 'Gerenciar Fazendas';

// Processar ações
if (isset($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($_GET['action'] == 'suspend' && $id > 0) {
        $sql = "UPDATE fazendas SET status = 'suspenso' WHERE id = $id";
        executeQuery($sql);
        setAlert('Fazenda suspensa com sucesso!', 'success');
        redirect('tenants.php');
    }
    
    if ($_GET['action'] == 'activate' && $id > 0) {
        $sql = "UPDATE fazendas SET status = 'ativo' WHERE id = $id";
        executeQuery($sql);
        setAlert('Fazenda ativada com sucesso!', 'success');
        redirect('tenants.php');
    }
    
    if ($_GET['action'] == 'delete' && $id > 0) {
        // Soft delete - apenas marca como inativo
        $sql = "UPDATE fazendas SET ativo = 0 WHERE id = $id";
        executeQuery($sql);
        setAlert('Fazenda removida com sucesso!', 'success');
        redirect('tenants.php');
    }
}

// Filtros
$status = isset($_GET['status']) ? $_GET['status'] : '';
$plano = isset($_GET['plano']) ? intval($_GET['plano']) : 0;
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';

// Construir WHERE
$where = "1=1";

if ($status) {
    $where .= " AND f.status = '$status'";
}

if ($plano > 0) {
    $where .= " AND f.id_plano = $plano";
}

if ($search) {
    $where .= " AND (f.nome_fazenda LIKE '%$search%' OR u.nome LIKE '%$search%' OR u.email LIKE '%$search%')";
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Contar total
$countSql = "SELECT COUNT(*) as total 
             FROM fazendas f
             JOIN usuarios u ON f.id_proprietario = u.id
             WHERE $where";
$countResult = executeQuery($countSql);
$totalRegistros = $countResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar fazendas
$sql = "SELECT f.*, u.nome as proprietario_nome, u.email as proprietario_email,
               p.nome_plano, p.preco_mensal,
               (SELECT COUNT(*) FROM bovinos WHERE id_fazenda = f.id) as total_animais
        FROM fazendas f
        JOIN usuarios u ON f.id_proprietario = u.id
        JOIN planos p ON f.id_plano = p.id
        WHERE $where
        ORDER BY f.data_cadastro DESC
        LIMIT $offset, $limit";
$fazendas = executeQuery($sql);

// Buscar planos para filtro
$sqlPlanos = "SELECT id, nome_plano FROM planos ORDER BY preco_mensal";
$planos = executeQuery($sqlPlanos);

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
                        <a class="nav-link active" href="tenants.php">
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
                <h1 class="h2">Gerenciar Fazendas</h1>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Todos</option>
                                <option value="trial" <?php echo $status == 'trial' ? 'selected' : ''; ?>>Trial</option>
                                <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inadimplente" <?php echo $status == 'inadimplente' ? 'selected' : ''; ?>>Inadimplente</option>
                                <option value="suspenso" <?php echo $status == 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                                <option value="cancelado" <?php echo $status == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Plano</label>
                            <select class="form-select" name="plano">
                                <option value="0">Todos</option>
                                <?php if ($planos && $planos->num_rows > 0): ?>
                                    <?php while ($p = $planos->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $plano == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo $p['nome_plano']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Nome da fazenda, proprietário ou email">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success mt-4">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Fazendas -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if ($fazendas && $fazendas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fazenda</th>
                                    <th>Proprietário</th>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Animais</th>
                                    <th>Cadastro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($farm = $fazendas->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $farm['id']; ?></td>
                                    <td>
                                        <strong><?php echo $farm['nome_fazenda']; ?></strong>
                                        <?php if ($farm['cidade']): ?>
                                            <br><small><?php echo $farm['cidade']; ?>/<?php echo $farm['estado']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $farm['proprietario_nome']; ?>
                                        <br><small><?php echo $farm['proprietario_email']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo $farm['nome_plano']; ?>
                                        <br><small>R$ <?php echo number_format($farm['preco_mensal'], 2, ',', '.'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $farm['status'] == 'ativo' ? 'success' : 
                                                ($farm['status'] == 'trial' ? 'info' : 
                                                ($farm['status'] == 'inadimplente' ? 'warning' : 
                                                ($farm['status'] == 'suspenso' ? 'danger' : 'secondary'))); 
                                        ?>">
                                            <?php echo $farm['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $farm['total_animais']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($farm['data_cadastro'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="tenant_view.php?id=<?php echo $farm['id']; ?>" class="btn btn-outline-info" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($farm['status'] != 'suspenso'): ?>
                                            <a href="?action=suspend&id=<?php echo $farm['id']; ?>" class="btn btn-outline-warning" title="Suspender"
                                               onclick="return confirm('Suspender esta fazenda?')">
                                                <i class="bi bi-pause"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $farm['id']; ?>" class="btn btn-outline-success" title="Ativar"
                                               onclick="return confirm('Ativar esta fazenda?')">
                                                <i class="bi bi-play"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $farm['id']; ?>" class="btn btn-outline-danger" title="Remover"
                                               onclick="return confirm('Remover esta fazenda? Esta ação não pode ser desfeita.')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
                        <i class="bi bi-building display-1 text-muted"></i>
                        <h4 class="mt-3">Nenhuma fazenda encontrada</h4>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>