<?php
// modules/estoque/relatorios.php
// Relatórios de estoque

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

$pageTitle = 'Relatórios de Estoque';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// ============================================
// 1. RESUMO GERAL
// ============================================
$sqlResumo = "SELECT 
                COUNT(*) as total_produtos,
                SUM(quantidade_atual) as total_itens,
                SUM(quantidade_atual * preco_custo) as valor_total
              FROM estoque_produtos
              WHERE id_fazenda = $farmId AND ativo = 1";
$resumo = executeQuery($sqlResumo)->fetch_assoc();

// ============================================
// 2. MOVIMENTAÇÕES DO PERÍODO
// ============================================
$sqlMovPeriodo = "SELECT 
                    COUNT(*) as total_mov,
                    SUM(CASE WHEN tipo_movimento = 'entrada' THEN quantidade ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN tipo_movimento = 'saida' THEN quantidade ELSE 0 END) as total_saidas
                  FROM estoque_movimentacoes
                  WHERE id_fazenda = $farmId
                    AND DATE(data_movimento) BETWEEN '$data_inicio' AND '$data_fim'";
$movPeriodo = executeQuery($sqlMovPeriodo)->fetch_assoc();

// ============================================
// 3. PRODUTOS MAIS MOVIMENTADOS
// ============================================
$sqlTopMov = "SELECT 
                p.nome_produto,
                p.codigo,
                c.nome_categoria,
                COUNT(m.id) as total_mov,
                SUM(CASE WHEN m.tipo_movimento = 'entrada' THEN m.quantidade ELSE 0 END) as total_entradas,
                SUM(CASE WHEN m.tipo_movimento = 'saida' THEN m.quantidade ELSE 0 END) as total_saidas
              FROM estoque_produtos p
              JOIN estoque_categorias c ON p.id_categoria = c.id
              LEFT JOIN estoque_movimentacoes m ON p.id = m.id_produto
                AND DATE(m.data_movimento) BETWEEN '$data_inicio' AND '$data_fim'
              WHERE p.id_fazenda = $farmId AND p.ativo = 1
              GROUP BY p.id
              HAVING total_mov > 0
              ORDER BY total_mov DESC
              LIMIT 10";
$topMovimentados = executeQuery($sqlTopMov);

// ============================================
// 4. VALOR POR CATEGORIA
// ============================================
$sqlValorCategoria = "SELECT 
                        c.nome_categoria,
                        c.cor,
                        COUNT(p.id) as qtd_produtos,
                        SUM(p.quantidade_atual) as total_itens,
                        SUM(p.quantidade_atual * p.preco_custo) as valor_total
                      FROM estoque_categorias c
                      LEFT JOIN estoque_produtos p ON c.id = p.id_categoria AND p.ativo = 1
                      WHERE c.id_fazenda = $farmId
                      GROUP BY c.id
                      ORDER BY valor_total DESC";
$valorCategoria = executeQuery($sqlValorCategoria);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-file-text me-2 text-success"></i>
                Relatórios de Estoque
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item active">Relatórios</li>
                </ol>
            </nav>
        </div>
        <button onclick="window.print()" class="btn btn-info">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>

    <!-- Filtros de Período -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success mt-4">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Produtos</h6>
                    <h2><?php echo $resumo['total_produtos']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Valor em Estoque</h6>
                    <h2>R$ <?php echo number_format($resumo['valor_total'] ?? 0, 2, ',', '.'); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Movimentações</h6>
                    <h2><?php echo $movPeriodo['total_mov'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Saldo Período</h6>
                    <h2>
                        <?php 
                        $saldo = ($movPeriodo['total_entradas'] ?? 0) - ($movPeriodo['total_saidas'] ?? 0);
                        echo number_format($saldo, 2, ',', '.');
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Valor por Categoria -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Valor em Estoque por Categoria</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoValorCategoria" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Quantidade por Categoria</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoQtdCategoria" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos Mais Movimentados -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Produtos Mais Movimentados no Período</h6>
        </div>
        <div class="card-body p-0">
            <?php if ($topMovimentados && $topMovimentados->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th class="text-center">Total Mov.</th>
                            <th class="text-end">Entradas</th>
                            <th class="text-end">Saídas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while $tm = $topMovimentados->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo $tm['nome_produto']; ?></strong>
                                <?php if ($tm['codigo']): ?>
                                    <br><small><?php echo $tm['codigo']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $tm['nome_categoria']; ?></td>
                            <td class="text-center"><?php echo $tm['total_mov']; ?></td>
                            <td class="text-end text-success"><?php echo number_format($tm['total_entradas'], 2, ',', '.'); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($tm['total_saidas'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">Nenhuma movimentação no período.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Valor por Categoria (Tabela) -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Detalhamento por Categoria</h6>
        </div>
        <div class="card-body p-0">
            <?php if ($valorCategoria && $valorCategoria->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-center">Produtos</th>
                            <th class="text-end">Itens</th>
                            <th class="text-end">Valor Total</th>
                            <th class="text-end">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($vc = $valorCategoria->fetch_assoc()): 
                            $percentual = $resumo['valor_total'] > 0 ? ($vc['valor_total'] / $resumo['valor_total']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="badge" style="background-color: <?php echo $vc['cor']; ?>">
                                    <?php echo $vc['nome_categoria']; ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo $vc['qtd_produtos']; ?></td>
                            <td class="text-end"><?php echo number_format($vc['total_itens'], 2, ',', '.'); ?></td>
                            <td class="text-end">R$ <?php echo number_format($vc['valor_total'], 2, ',', '.'); ?></td>
                            <td class="text-end"><?php echo number_format($percentual, 1); ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">Nenhuma categoria cadastrada.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
<?php
// Preparar dados para os gráficos
$categorias = [];
$valores = [];
$quantidades = [];
$cores = [];

if ($valorCategoria && $valorCategoria->num_rows > 0) {
    $valorCategoria->data_seek(0);
    while ($vc = $valorCategoria->fetch_assoc()) {
        $categorias[] = $vc['nome_categoria'];
        $valores[] = $vc['valor_total'];
        $quantidades[] = $vc['total_itens'];
        $cores[] = $vc['cor'];
    }
}
?>

// Gráfico de Valor por Categoria
const ctxValor = document.getElementById('graficoValorCategoria').getContext('2d');
new Chart(ctxValor, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($categorias); ?>,
        datasets: [{
            data: <?php echo json_encode($valores); ?>,
            backgroundColor: <?php echo json_encode($cores); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': R$ ' + context.raw.toFixed(2).replace('.', ',');
                    }
                }
            }
        }
    }
});

// Gráfico de Quantidade por Categoria
const ctxQtd = document.getElementById('graficoQtdCategoria').getContext('2d');
new Chart(ctxQtd, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($categorias); ?>,
        datasets: [{
            label: 'Quantidade de Itens',
            data: <?php echo json_encode($quantidades); ?>,
            backgroundColor: <?php echo json_encode($cores); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>