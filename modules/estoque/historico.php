<?php
// modules/estoque/historico.php
// Histórico de movimentações

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

$pageTitle = 'Histórico de Movimentações';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$produto_id = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Construir WHERE
$where = "m.id_fazenda = $farmId";

if ($data_inicio && $data_fim) {
    $where .= " AND DATE(m.data_movimento) BETWEEN '$data_inicio' AND '$data_fim'";
}

if ($produto_id > 0) {
    $where .= " AND m.id_produto = $produto_id";
}

if (!empty($tipo)) {
    $where .= " AND m.tipo_movimento = '$tipo'";
}

// Buscar movimentações
$sql = "SELECT m.*, p.nome_produto, p.codigo, p.unidade, u.nome as usuario_nome,
               b.brinco as bovino_brinco
        FROM estoque_movimentacoes m
        JOIN estoque_produtos p ON m.id_produto = p.id
        LEFT JOIN usuarios u ON m.id_usuario = u.id
        LEFT JOIN bovinos b ON m.id_bovino = b.id
        WHERE $where
        ORDER BY m.data_movimento DESC
        LIMIT 500";
$movimentacoes = executeQuery($sql);

// Buscar produtos para filtro
$sqlProdutos = "SELECT id, nome_produto FROM estoque_produtos WHERE id_fazenda = $farmId ORDER BY nome_produto";
$produtos = executeQuery($sqlProdutos);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-clock-history me-2 text-success"></i>
                Histórico de Movimentações
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item active">Histórico</li>
                </ol>
            </nav>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
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
                    <label class="form-label">Produto</label>
                    <select class="form-select" name="produto_id">
                        <option value="0">Todos</option>
                        <?php if ($produtos && $produtos->num_rows > 0): ?>
                            <?php while ($p = $produtos->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $produto_id == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo $p['nome_produto']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="entrada" <?php echo $tipo == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                        <option value="saida" <?php echo $tipo == 'saida' ? 'selected' : ''; ?>>Saída</option>
                        <option value="ajuste" <?php echo $tipo == 'ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                        <option value="perda" <?php echo $tipo == 'perda' ? 'selected' : ''; ?>>Perda</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Filtrar</button>
                    <a href="historico.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Movimentações -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Movimentações</h6>
            <span class="badge bg-info"><?php echo $movimentacoes ? $movimentacoes->num_rows : 0; ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($movimentacoes && $movimentacoes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Produto</th>
                                <th>Tipo</th>
                                <th class="text-end">Quantidade</th>
                                <th>Saldo Anterior</th>
                                <th>Saldo Atual</th>
                                <th>Motivo</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($m = $movimentacoes->fetch_assoc()):
                                $classeTipo = [
                                    'entrada' => 'success',
                                    'saida' => 'danger',
                                    'ajuste' => 'warning',
                                    'perda' => 'dark'
                                ][$m['tipo_movimento']] ?? 'secondary';
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?></td>
                                    <td>
                                        <strong><?php echo $m['nome_produto']; ?></strong>
                                        <?php if ($m['codigo']): ?>
                                            <br><small>Cód: <?php echo $m['codigo']; ?></small>
                                        <?php endif; ?>
                                        <?php if ($m['bovino_brinco']): ?>
                                            <br><small><i class="bi bi-tree"></i> <?php echo $m['bovino_brinco']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $classeTipo; ?>">
                                            <?php echo ucfirst($m['tipo_movimento']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?php echo $m['tipo_movimento'] == 'entrada' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $m['tipo_movimento'] == 'entrada' ? '+' : '-'; ?>
                                            <?php echo number_format($m['quantidade'], 2, ',', '.'); ?> <?php echo $m['unidade']; ?>
                                        </strong>
                                    </td>
                                    <td><?php echo number_format($m['quantidade_anterior'], 2, ',', '.'); ?> <?php echo $m['unidade']; ?></td>
                                    <td><?php echo number_format($m['quantidade_posterior'], 2, ',', '.'); ?> <?php echo $m['unidade']; ?></td>
                                    <td><?php echo $m['motivo'] ?: '-'; ?></td>
                                    <td><?php echo $m['usuario_nome'] ?: '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-clock-history display-1 text-muted"></i>
                    <h4 class="mt-3">Nenhuma movimentação encontrada</h4>
                    <p class="text-muted">Não há registros para o período selecionado.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>