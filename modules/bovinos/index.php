<?php
// modules/bovinos/index.php
// Listagem de bovinos com filtros e ações

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

$pageTitle = 'Bovinos';

// Processar filtros
$where = TenantManager::addTenantFilter('b') . " AND b.ativo = 1";
$filtros = [];

$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';
if (!empty($search)) {
    $where .= " AND (b.brinco LIKE '%$search%' OR b.nome LIKE '%$search%')";
    $filtros['search'] = $search;
}

$raca = isset($_GET['raca']) ? intval($_GET['raca']) : 0;
if ($raca > 0) {
    $where .= " AND b.id_raca = $raca";
    $filtros['raca'] = $raca;
}

$situacao = isset($_GET['situacao']) ? intval($_GET['situacao']) : 0;
if ($situacao > 0) {
    $where .= " AND b.id_situacao = $situacao";
    $filtros['situacao'] = $situacao;
}

$sexo = isset($_GET['sexo']) ? $_GET['sexo'] : '';
if (!empty($sexo) && in_array($sexo, ['M', 'F'])) {
    $where .= " AND b.sexo = '$sexo'";
    $filtros['sexo'] = $sexo;
}

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Contar total de registros
$countSql = "SELECT COUNT(*) as total FROM bovinos b WHERE $where";
$countResult = executeQuery($countSql);
$totalRegistros = $countResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $limit);

// Buscar bovinos
$sql = "SELECT b.*, 
               r.nome_raca, 
               r.tipo as tipo_raca,
               s.nome as situacao_nome,
               s.cor as situacao_cor,
               p.nome_piquete,
               (SELECT COUNT(*) FROM pesagens WHERE id_bovino = b.id) as total_pesagens
        FROM bovinos b
        LEFT JOIN racas r ON b.id_raca = r.id
        LEFT JOIN situacoes s ON b.id_situacao = s.id
        LEFT JOIN piquetes p ON b.id_piquete_atual = p.id
        WHERE $where
        ORDER BY b.data_cadastro DESC
        LIMIT $offset, $limit";

$bovinos = executeQuery($sql);

// Buscar raças para o filtro
$racasSql = "SELECT id, nome_raca FROM racas WHERE " . TenantManager::addTenantFilter() . " ORDER BY nome_raca";
$racas = executeQuery($racasSql);

// Buscar situações para o filtro
$situacoesSql = "SELECT id, nome, cor FROM situacoes WHERE " . TenantManager::addTenantFilter() . " ORDER BY ordem";
$situacoes = executeQuery($situacoesSql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between flex-wrap align-items-center mb-4">
        <h1 class="h2">
            <i class="bi bi-tree-fill me-2 text-success"></i>
            Bovinos
        </h1>
        <div>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Novo Bovino
            </a>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filtroModal">
                <i class="bi bi-funnel me-2"></i>
                Filtros
            </button>
        </div>
    </div>

    <!-- Estatísticas rápidas -->
    <div class="row g-3 mb-4">
        <?php
        // Total de machos
        $sqlM = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND sexo = 'M' AND ativo = 1";
        $resM = executeQuery($sqlM);
        $totalM = $resM->fetch_assoc()['total'];
        
        // Total de fêmeas
        $sqlF = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND sexo = 'F' AND ativo = 1";
        $resF = executeQuery($sqlF);
        $totalF = $resF->fetch_assoc()['total'];
        ?>
        
        <div class="col-md-3 col-sm-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Animais</h6>
                    <h2><?php echo $totalRegistros; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Machos</h6>
                    <h2><?php echo $totalM; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Fêmeas</h6>
                    <h2><?php echo $totalF; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Peso Médio</h6>
                    <h2>--- kg</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de bovinos -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($bovinos && $bovinos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Brinco</th>
                            <th>Nome</th>
                            <th>Raça</th>
                            <th>Sexo</th>
                            <th>Idade</th>
                            <th>Peso</th>
                            <th>Situação</th>
                            <th>Piquete</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bovino = $bovinos->fetch_assoc()): 
                            // Calcular idade
                            $idade = '';
                            if ($bovino['data_nascimento']) {
                                $nascimento = new DateTime($bovino['data_nascimento']);
                                $hoje = new DateTime();
                                $diferenca = $hoje->diff($nascimento);
                                $idade = $diferenca->y . ' anos, ' . $diferenca->m . ' meses';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $bovino['brinco']; ?></strong>
                                <?php if ($bovino['brinco_eletronico']): ?>
                                    <br><small class="text-muted">Eletrônico: <?php echo $bovino['brinco_eletronico']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $bovino['nome'] ?: '-'; ?></td>
                            <td>
                                <?php echo $bovino['nome_raca'] ?: '-'; ?>
                                <?php if ($bovino['tipo_raca']): ?>
                                    <br><small class="badge bg-info"><?php echo $bovino['tipo_raca']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bovino['sexo'] == 'M'): ?>
                                    <span class="badge bg-primary"><i class="bi bi-gender-male"></i> Macho</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><i class="bi bi-gender-female"></i> Fêmea</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $idade ?: '-'; ?>
                                <?php if ($bovino['data_nascimento']): ?>
                                    <br><small class="text-muted"><?php echo date('d/m/Y', strtotime($bovino['data_nascimento'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bovino['peso_atual']): ?>
                                    <strong><?php echo number_format($bovino['peso_atual'], 2, ',', '.'); ?> kg</strong>
                                    <?php if ($bovino['total_pesagens'] > 0): ?>
                                        <br><small class="text-muted"><?php echo $bovino['total_pesagens']; ?> pesagens</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $bovino['situacao_cor'] ?: 'secondary'; ?>">
                                    <?php echo $bovino['situacao_nome']; ?>
                                </span>
                            </td>
                            <td><?php echo $bovino['nome_piquete'] ?: '-'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="visualizar.php?id=<?php echo $bovino['id']; ?>" class="btn btn-outline-info" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $bovino['id']; ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Excluir" 
                                            onclick="confirmarExclusao(<?php echo $bovino['id']; ?>, '<?php echo $bovino['brinco']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo http_build_query($filtros); ?>">Anterior</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo http_build_query($filtros); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo http_build_query($filtros); ?>">Próxima</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-tree display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum bovino cadastrado</h4>
                <p class="text-muted">Comece cadastrando o primeiro animal da sua fazenda.</p>
                <a href="cadastrar.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>
                    Cadastrar Primeiro Bovino
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal de Filtros -->
<div class="modal fade" id="filtroModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-funnel me-2"></i>
                    Filtros
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Brinco ou nome do animal">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Raça</label>
                        <select class="form-select" name="raca">
                            <option value="">Todas</option>
                            <?php if ($racas && $racas->num_rows > 0): ?>
                                <?php while ($r = $racas->fetch_assoc()): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo ($raca == $r['id']) ? 'selected' : ''; ?>>
                                    <?php echo $r['nome_raca']; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" name="situacao">
                            <option value="">Todas</option>
                            <?php if ($situacoes && $situacoes->num_rows > 0): ?>
                                <?php while ($s = $situacoes->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($situacao == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo $s['nome']; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sexo</label>
                        <select class="form-select" name="sexo">
                            <option value="">Todos</option>
                            <option value="M" <?php echo ($sexo == 'M') ? 'selected' : ''; ?>>Macho</option>
                            <option value="F" <?php echo ($sexo == 'F') ? 'selected' : ''; ?>>Fêmea</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php" class="btn btn-secondary">Limpar Filtros</a>
                    <button type="submit" class="btn btn-success">Aplicar Filtros</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="confirmarExclusaoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o bovino <strong id="animalBrinco"></strong>?</p>
                <p class="text-danger small">
                    <i class="bi bi-info-circle"></i>
                    Esta ação não poderá ser desfeita!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, brinco) {
    document.getElementById('animalBrinco').textContent = brinco;
    document.getElementById('btnConfirmarExclusao').href = 'excluir.php?id=' + id;
    new bootstrap.Modal(document.getElementById('confirmarExclusaoModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>