<?php
// modules/financeiro/lancamentos.php
// Lista de lançamentos financeiros

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

$pageTitle = 'Lançamentos Financeiros';

// Filtros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-t');
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';

// Construir WHERE
$where = TenantManager::addTenantFilter('l');

if ($tipo) {
    $where .= " AND c.tipo = '$tipo'";
}
if ($categoria > 0) {
    $where .= " AND l.id_categoria = $categoria";
}
if ($status) {
    $where .= " AND l.status = '$status'";
}
if ($data_inicio && $data_fim) {
    $where .= " AND l.data_emissao BETWEEN '$data_inicio' AND '$data_fim'";
}
if ($search) {
    $where .= " AND (l.descricao LIKE '%$search%' OR b.brinco LIKE '%$search%')";
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Contar total
$countSql = "SELECT COUNT(*) as total 
             FROM lancamentos_financeiros l
             LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
             LEFT JOIN bovinos b ON l.id_bovino = b.id
             WHERE $where";
$countResult = executeQuery($countSql);
$totalRegistros = $countResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar lançamentos
$sql = "SELECT l.*, c.nome_categoria, c.tipo as tipo_categoria, c.cor,
               b.brinco as bovino_brinco, b.nome as bovino_nome
        FROM lancamentos_financeiros l
        LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
        LEFT JOIN bovinos b ON l.id_bovino = b.id
        WHERE $where
        ORDER BY l.data_emissao DESC, l.id DESC
        LIMIT $offset, $limit";
$lancamentos = executeQuery($sql);

// Buscar categorias para filtro
$sqlCats = "SELECT id, nome_categoria, tipo FROM categorias_financeiras 
            WHERE id_fazenda = $farmId AND ativo = 1 
            ORDER BY tipo, nome_categoria";
$categorias = executeQuery($sqlCats);

// Totais do período filtrado
$sqlTotais = "SELECT 
                SUM(CASE WHEN c.tipo = 'receita' AND l.status = 'pago' THEN l.valor ELSE 0 END) as total_receitas,
                SUM(CASE WHEN c.tipo = 'despesa' AND l.status = 'pago' THEN l.valor ELSE 0 END) as total_despesas,
                SUM(CASE WHEN l.status = 'pendente' THEN l.valor ELSE 0 END) as total_pendente
              FROM lancamentos_financeiros l
              LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
              WHERE $where";
$resultTotais = executeQuery($sqlTotais);
$totais = $resultTotais->fetch_assoc();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-list-ul me-2 text-success"></i>
                Lançamentos Financeiros
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Lançamentos</li>
                </ol>
            </nav>
        </div>
        <div>
            <div class="btn-group">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-circle"></i> Novo Lançamento
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="receita.php"><i class="bi bi-arrow-up-circle text-success"></i> Receita</a></li>
                    <li><a class="dropdown-item" href="despesa.php"><i class="bi bi-arrow-down-circle text-danger"></i> Despesa</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Totais do período -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Receitas (Período)</h6>
                    <h3>R$ <?php echo number_format($totais['total_receitas'] ?? 0, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Despesas (Período)</h6>
                    <h3>R$ <?php echo number_format($totais['total_despesas'] ?? 0, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Pendente</h6>
                    <h3>R$ <?php echo number_format($totais['total_pendente'] ?? 0, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="receita" <?php echo $tipo == 'receita' ? 'selected' : ''; ?>>Receitas</option>
                        <option value="despesa" <?php echo $tipo == 'despesa' ? 'selected' : ''; ?>>Despesas</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria">
                        <option value="">Todas</option>
                        <?php if ($categorias && $categorias->num_rows > 0): ?>
                            <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['nome_categoria']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="pago" <?php echo $status == 'pago' ? 'selected' : ''; ?>>Pago</option>
                        <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="atrasado" <?php echo $status == 'atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Descrição ou animal">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="lancamentos.php" class="btn btn-secondary">Limpar Filtros</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($lancamentos && $lancamentos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($l = $lancamentos->fetch_assoc()): 
                            $statusClass = [
                                'pago' => 'success',
                                'pendente' => 'warning',
                                'atrasado' => 'danger',
                                'cancelado' => 'secondary'
                            ][$l['status']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($l['data_emissao'])); ?></td>
                            <td>
                                <strong><?php echo $l['descricao']; ?></strong>
                                <?php if ($l['bovino_brinco']): ?>
                                    <br><small class="text-muted">
                                        <i class="bi bi-tree"></i> <?php echo $l['bovino_brinco']; ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($l['observacoes']): ?>
                                    <br><small class="text-muted"><?php echo $l['observacoes']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $l['cor'] ?: '#6c757d'; ?>">
                                    <?php echo $l['nome_categoria']; ?>
                                </span>
                            </td>
                            <td class="<?php echo $l['tipo_categoria'] == 'receita' ? 'text-success' : 'text-danger'; ?>">
                                <strong>
                                    <?php echo $l['tipo_categoria'] == 'receita' ? '+' : '-'; ?>
                                    R$ <?php echo number_format($l['valor'], 2, ',', '.'); ?>
                                </strong>
                                <?php if ($l['valor_pago'] && $l['valor_pago'] != $l['valor']): ?>
                                    <br><small>Pago: R$ <?php echo number_format($l['valor_pago'], 2, ',', '.'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo ucfirst($l['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($l['data_vencimento']): ?>
                                    <?php echo date('d/m/Y', strtotime($l['data_vencimento'])); ?>
                                    <?php if ($l['status'] == 'pendente' && strtotime($l['data_vencimento']) < time()): ?>
                                        <br><small class="text-danger">Vencido</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="editar.php?id=<?php echo $l['id']; ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($l['status'] == 'pendente'): ?>
                                    <a href="acoes/baixar.php?id=<?php echo $l['id']; ?>" class="btn btn-outline-success" title="Baixar Pagamento">
                                        <i class="bi bi-check-circle"></i>
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
                <i class="bi bi-cash-stack display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum lançamento encontrado</h4>
                <p class="text-muted">Tente ajustar os filtros ou crie um novo lançamento.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>