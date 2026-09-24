<?php
// modules/estoque/produtos.php
// Lista de produtos

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

$pageTitle = 'Produtos';

// Filtros
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'todos';
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';

// Construir WHERE
$where = "p.id_fazenda = $farmId AND p.ativo = 1";

if ($categoria > 0) {
    $where .= " AND p.id_categoria = $categoria";
}

if ($status == 'baixo') {
    $where .= " AND p.quantidade_atual <= p.quantidade_minima AND p.quantidade_minima > 0";
} elseif ($status == 'vencido') {
    $where .= " AND p.data_validade IS NOT NULL AND p.data_validade < CURDATE()";
} elseif ($status == 'proximo_vencer') {
    $where .= " AND p.data_validade IS NOT NULL AND p.data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

if (!empty($search)) {
    $where .= " AND (p.nome_produto LIKE '%$search%' OR p.codigo LIKE '%$search%' OR p.lote LIKE '%$search%')";
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Contar total
$countSql = "SELECT COUNT(*) as total FROM estoque_produtos p WHERE $where";
$countResult = executeQuery($countSql);
$totalRegistros = $countResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar produtos
$sql = "SELECT p.*, c.nome_categoria, c.cor as categoria_cor
        FROM estoque_produtos p
        JOIN estoque_categorias c ON p.id_categoria = c.id
        WHERE $where
        ORDER BY 
            CASE 
                WHEN p.data_validade IS NOT NULL AND p.data_validade < CURDATE() THEN 1
                WHEN p.quantidade_atual <= p.quantidade_minima AND p.quantidade_minima > 0 THEN 2
                ELSE 3
            END,
            p.nome_produto ASC
        LIMIT $offset, $limit";
$produtos = executeQuery($sql);

// Buscar categorias para filtro
$sqlCats = "SELECT id, nome_categoria FROM estoque_categorias WHERE id_fazenda = $farmId ORDER BY nome_categoria";
$categorias = executeQuery($sqlCats);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-boxes me-2 text-success"></i>
                Produtos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item active">Produtos</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Novo Produto
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria">
                        <option value="0">Todas</option>
                        <?php if ($categorias && $categorias->num_rows > 0): ?>
                            <?php while ($c = $categorias->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $categoria == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['nome_categoria']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="todos">Todos</option>
                        <option value="baixo" <?php echo $status == 'baixo' ? 'selected' : ''; ?>>Estoque Baixo</option>
                        <option value="vencido" <?php echo $status == 'vencido' ? 'selected' : ''; ?>>Vencidos</option>
                        <option value="proximo_vencer" <?php echo $status == 'proximo_vencer' ? 'selected' : ''; ?>>Próximos a Vencer</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nome, código ou lote">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success mt-4">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Produtos -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($produtos && $produtos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Lote</th>
                            <th>Validade</th>
                            <th class="text-end">Quantidade</th>
                            <th class="text-end">Mínimo</th>
                            <th>Status</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $produtos->fetch_assoc()): 
                            $statusClass = 'success';
                            $statusText = 'Normal';
                            
                            if ($p['data_validade'] && $p['data_validade'] < date('Y-m-d')) {
                                $statusClass = 'danger';
                                $statusText = 'Vencido';
                            } elseif ($p['data_validade'] && $p['data_validade'] <= date('Y-m-d', strtotime('+30 days'))) {
                                $statusClass = 'warning';
                                $statusText = 'Próx. Vencer';
                            } elseif ($p['quantidade_atual'] <= $p['quantidade_minima'] && $p['quantidade_minima'] > 0) {
                                $statusClass = 'warning';
                                $statusText = 'Estoque Baixo';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo $p['codigo'] ?: '---'; ?></strong></td>
                            <td>
                                <a href="visualizar.php?id=<?php echo $p['id']; ?>">
                                    <strong><?php echo $p['nome_produto']; ?></strong>
                                </a>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $p['categoria_cor']; ?>">
                                    <?php echo $p['nome_categoria']; ?>
                                </span>
                            </td>
                            <td><?php echo $p['lote'] ?: '-'; ?></td>
                            <td>
                                <?php if ($p['data_validade']): ?>
                                    <?php echo date('d/m/Y', strtotime($p['data_validade'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><strong><?php echo number_format($p['quantidade_atual'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></strong></td>
                            <td class="text-end"><?php echo number_format($p['quantidade_minima'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="entrada.php?produto_id=<?php echo $p['id']; ?>" class="btn btn-outline-success" title="Entrada">
                                        <i class="bi bi-arrow-down-circle"></i>
                                    </a>
                                    <a href="saida.php?produto_id=<?php echo $p['id']; ?>" class="btn btn-outline-warning" title="Saída">
                                        <i class="bi bi-arrow-up-circle"></i>
                                    </a>
                                    <a href="visualizar.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-info" title="Detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
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
                <i class="bi bi-box display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum produto encontrado</h4>
                <p class="text-muted">Cadastre produtos para começar a controlar seu estoque.</p>
                <a href="cadastrar.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Novo Produto
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>