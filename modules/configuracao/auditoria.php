<?php
// modules/configuracao/auditoria.php
// Logs de atividades do sistema

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se é admin
$userId = $_SESSION['usuario_id'];
$sqlCheck = "SELECT nivel FROM usuarios WHERE id = $userId";
$resultCheck = executeQuery($sqlCheck);
$user = $resultCheck->fetch_assoc();

if ($user['nivel'] != 'admin') {
    setAlert('Apenas administradores podem acessar esta página.', 'danger');
    redirect('index.php');
}

$pageTitle = 'Logs de Atividades';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-7 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;
$acao = isset($_GET['acao']) ? escapeString($_GET['acao']) : '';

// Construir WHERE
$where = "1=1";

if ($data_inicio && $data_fim) {
    $where .= " AND DATE(l.data) BETWEEN '$data_inicio' AND '$data_fim'";
}

if ($usuario_id > 0) {
    $where .= " AND l.usuario_id = $usuario_id";
}

if (!empty($acao)) {
    $where .= " AND l.acao LIKE '%$acao%'";
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Contar total
$countSql = "SELECT COUNT(*) as total FROM logs l WHERE $where";
$countResult = executeQuery($countSql);
$totalRegistros = $countResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar logs
$sql = "SELECT l.*, u.nome as usuario_nome
        FROM logs l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        WHERE $where
        ORDER BY l.data DESC
        LIMIT $offset, $limit";
$logs = executeQuery($sql);

// Buscar usuários para filtro
$sqlUsuarios = "SELECT id, nome FROM usuarios ORDER BY nome";
$usuarios = executeQuery($sqlUsuarios);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-clock-history me-2 text-success"></i>
                Logs de Atividades
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Auditoria</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Usuário</label>
                    <select class="form-select" name="usuario_id">
                        <option value="0">Todos</option>
                        <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                            <?php while ($u = $usuarios->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $usuario_id == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo $u['nome']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ação</label>
                    <input type="text" class="form-control" name="acao" value="<?php echo htmlspecialchars($acao); ?>" 
                           placeholder="Buscar ação...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Filtrar</button>
                    <a href="auditoria.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Logs -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Registros de Atividades</h6>
            <span class="badge bg-primary"><?php echo $totalRegistros; ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($logs && $logs->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['data'])); ?></td>
                            <td><strong><?php echo $log['usuario_nome'] ?: 'Sistema'; ?></strong></td>
                            <td>
                                <span class="badge bg-info"><?php echo $log['acao']; ?></span>
                            </td>
                            <td><?php echo $log['descricao'] ?: '-'; ?></td>
                            <td><code><?php echo $log['ip'] ?: '-'; ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
            <div class="card-footer bg-white">
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
                <i class="bi bi-clock-history display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum registro encontrado</h4>
                <p class="text-muted">Não há logs para o período selecionado.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>