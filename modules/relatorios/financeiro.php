<?php
// modules/relatorios/financeiro.php
// Relatório financeiro completo


ini_set('display_errors', 1);
error_reporting(E_ALL);


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

$pageTitle = 'Relatório Financeiro';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Construir WHERE
$where = TenantManager::addTenantFilter('l');

if ($data_inicio && $data_fim) {
    $where .= " AND l.data_emissao BETWEEN '$data_inicio' AND '$data_fim'";
}

if ($tipo != 'todos') {
    $where .= " AND c.tipo = '$tipo'";
}

if ($categoria > 0) {
    $where .= " AND l.id_categoria = $categoria";
}

// ============================================
// 1. RESUMO GERAL
// ============================================
$sqlResumo = "SELECT 
                COALESCE(SUM(CASE WHEN c.tipo = 'receita' AND l.status = 'pago' THEN l.valor ELSE 0 END), 0) as total_receitas,
                COALESCE(SUM(CASE WHEN c.tipo = 'despesa' AND l.status = 'pago' THEN l.valor ELSE 0 END), 0) as total_despesas,
                COALESCE(SUM(CASE WHEN c.tipo = 'receita' AND l.status = 'pendente' THEN l.valor ELSE 0 END), 0) as receitas_pendentes,
                COALESCE(SUM(CASE WHEN c.tipo = 'despesa' AND l.status = 'pendente' THEN l.valor ELSE 0 END), 0) as despesas_pendentes,
                COUNT(DISTINCT l.id) as total_lancamentos,
                COUNT(DISTINCT CASE WHEN l.status = 'pago' THEN l.id END) as lancamentos_pagos,
                COUNT(DISTINCT CASE WHEN l.status = 'pendente' THEN l.id END) as lancamentos_pendentes
              FROM lancamentos_financeiros l
              LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
              WHERE $where";

$resultResumo = executeQuery($sqlResumo);
$resumo = $resultResumo->fetch_assoc();

$saldo_periodo = $resumo['total_receitas'] - $resumo['total_despesas'];

// ============================================
// 2. RECEITAS POR CATEGORIA
// ============================================
$sqlReceitas = "SELECT 
                    c.nome_categoria,
                    c.cor,
                    COUNT(l.id) as quantidade,
                    COALESCE(SUM(l.valor), 0) as total,
                    COALESCE(SUM(CASE WHEN l.status = 'pago' THEN l.valor ELSE 0 END), 0) as recebido,
                    COALESCE(SUM(CASE WHEN l.status = 'pendente' THEN l.valor ELSE 0 END), 0) as pendente
                FROM categorias_financeiras c
                LEFT JOIN lancamentos_financeiros l ON c.id = l.id_categoria AND l.data_emissao BETWEEN '$data_inicio' AND '$data_fim'
                WHERE c.id_fazenda = $farmId AND c.tipo = 'receita'
                GROUP BY c.id
                ORDER BY total DESC";
$receitas = executeQuery($sqlReceitas);

// ============================================
// 3. DESPESAS POR CATEGORIA
// ============================================
$sqlDespesas = "SELECT 
                    c.nome_categoria,
                    c.cor,
                    COUNT(l.id) as quantidade,
                    COALESCE(SUM(l.valor), 0) as total,
                    COALESCE(SUM(CASE WHEN l.status = 'pago' THEN l.valor ELSE 0 END), 0) as pago,
                    COALESCE(SUM(CASE WHEN l.status = 'pendente' THEN l.valor ELSE 0 END), 0) as pendente
                FROM categorias_financeiras c
                LEFT JOIN lancamentos_financeiros l ON c.id = l.id_categoria AND l.data_emissao BETWEEN '$data_inicio' AND '$data_fim'
                WHERE c.id_fazenda = $farmId AND c.tipo = 'despesa'
                GROUP BY c.id
                ORDER BY total DESC";
$despesas = executeQuery($sqlDespesas);

// ============================================
// 4. EVOLUÇÃO MENSAL (últimos 12 meses)
// ============================================
$sqlEvolucao = "SELECT 
                    DATE_FORMAT(l.data_emissao, '%Y-%m') as mes,
                    DATE_FORMAT(l.data_emissao, '%m/%Y') as mes_label,
                    COALESCE(SUM(CASE WHEN c.tipo = 'receita' AND l.status = 'pago' THEN l.valor ELSE 0 END), 0) as receitas,
                    COALESCE(SUM(CASE WHEN c.tipo = 'despesa' AND l.status = 'pago' THEN l.valor ELSE 0 END), 0) as despesas
                FROM lancamentos_financeiros l
                LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
                WHERE " . TenantManager::addTenantFilter('l') . "
                AND l.data_emissao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(l.data_emissao, '%Y-%m')
                ORDER BY mes ASC";
$evolucao = executeQuery($sqlEvolucao);

// ============================================
// 5. TOP 10 MAIORES RECEITAS
// ============================================
$sqlTopReceitas = "SELECT 
                        l.*,
                        c.nome_categoria,
                        c.cor,
                        b.brinco as bovino_brinco
                    FROM lancamentos_financeiros l
                    LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
                    LEFT JOIN bovinos b ON l.id_bovino = b.id
                    WHERE $where AND c.tipo = 'receita'
                    ORDER BY l.valor DESC
                    LIMIT 10";
$topReceitas = executeQuery($sqlTopReceitas);

// ============================================
// 6. TOP 10 MAIORES DESPESAS
// ============================================
$sqlTopDespesas = "SELECT 
                        l.*,
                        c.nome_categoria,
                        c.cor,
                        b.brinco as bovino_brinco
                    FROM lancamentos_financeiros l
                    LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
                    LEFT JOIN bovinos b ON l.id_bovino = b.id
                    WHERE $where AND c.tipo = 'despesa'
                    ORDER BY l.valor DESC
                    LIMIT 10";
$topDespesas = executeQuery($sqlTopDespesas);

// ============================================
// 7. CATEGORIAS PARA FILTRO
// ============================================
$sqlCategorias = "SELECT id, nome_categoria, tipo FROM categorias_financeiras 
                  WHERE id_fazenda = $farmId AND ativo = 1 
                  ORDER BY tipo, nome_categoria";
$categorias = executeQuery($sqlCategorias);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-cash-stack me-2 text-success"></i>
                Relatório Financeiro
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Financeiro</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="exportar.php?tipo=pdf&relatorio=financeiro&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>" 
               class="btn btn-success">
                <i class="bi bi-file-pdf"></i> Exportar PDF
            </a>
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
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="todos">Todos</option>
                        <option value="receita" <?php echo $tipo == 'receita' ? 'selected' : ''; ?>>Receitas</option>
                        <option value="despesa" <?php echo $tipo == 'despesa' ? 'selected' : ''; ?>>Despesas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria">
                        <option value="0">Todas</option>
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
                    <button type="submit" class="btn btn-success mt-4">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Receitas (Período)</h6>
                    <h3>R$ <?php echo number_format($resumo['total_receitas'], 2, ',', '.'); ?></h3>
                    <small><?php echo $resumo['receitas_pendentes'] > 0 ? 'Pendente: R$ ' . number_format($resumo['receitas_pendentes'], 2, ',', '.') : ''; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Despesas (Período)</h6>
                    <h3>R$ <?php echo number_format($resumo['total_despesas'], 2, ',', '.'); ?></h3>
                    <small><?php echo $resumo['despesas_pendentes'] > 0 ? 'Pendente: R$ ' . number_format($resumo['despesas_pendentes'], 2, ',', '.') : ''; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $saldo_periodo >= 0 ? 'bg-info' : 'bg-warning'; ?> text-white">
                <div class="card-body">
                    <h6>Saldo do Período</h6>
                    <h3>R$ <?php echo number_format($saldo_periodo, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Lançamentos</h6>
                    <h3><?php echo $resumo['total_lancamentos']; ?></h3>
                    <small><?php echo $resumo['lancamentos_pagos']; ?> pagos / <?php echo $resumo['lancamentos_pendentes']; ?> pendentes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
        <!-- Gráfico de Evolução Mensal -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Evolução Mensal (Últimos 12 meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoEvolucao" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Distribuição -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Distribuição</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoDistribuicao" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Receitas e Despesas por Categoria -->
    <div class="row g-3 mb-4">
        <!-- Receitas por Categoria -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">Receitas por Categoria</h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($receitas && $receitas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Qtd</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Recebido</th>
                                    <th class="text-end">Pendente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $receitas->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $r['cor'] ?: '#28a745'; ?>">
                                            <?php echo $r['nome_categoria']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?php echo $r['quantidade']; ?></td>
                                    <td class="text-end"><strong>R$ <?php echo number_format($r['total'], 2, ',', '.'); ?></strong></td>
                                    <td class="text-end text-success">R$ <?php echo number_format($r['recebido'], 2, ',', '.'); ?></td>
                                    <td class="text-end text-warning">R$ <?php echo number_format($r['pendente'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma receita encontrada no período.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Despesas por Categoria -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="card-title mb-0">Despesas por Categoria</h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($despesas && $despesas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Qtd</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Pago</th>
                                    <th class="text-end">Pendente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = $despesas->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $d['cor'] ?: '#dc3545'; ?>">
                                            <?php echo $d['nome_categoria']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?php echo $d['quantidade']; ?></td>
                                    <td class="text-end"><strong>R$ <?php echo number_format($d['total'], 2, ',', '.'); ?></strong></td>
                                    <td class="text-end text-success">R$ <?php echo number_format($d['pago'], 2, ',', '.'); ?></td>
                                    <td class="text-end text-warning">R$ <?php echo number_format($d['pendente'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma despesa encontrada no período.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Lançamentos -->
    <div class="row g-3 mb-4">
        <!-- Top 10 Receitas -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">Top 10 Maiores Receitas</h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($topReceitas && $topReceitas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tr = $topReceitas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($tr['data_emissao'])); ?></td>
                                    <td>
                                        <?php echo $tr['descricao']; ?>
                                        <?php if ($tr['bovino_brinco']): ?>
                                            <br><small><?php echo $tr['bovino_brinco']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $tr['cor'] ?: '#28a745'; ?>">
                                            <?php echo $tr['nome_categoria']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-success">
                                        <strong>R$ <?php echo number_format($tr['valor'], 2, ',', '.'); ?></strong>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma receita encontrada.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top 10 Despesas -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="card-title mb-0">Top 10 Maiores Despesas</h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($topDespesas && $topDespesas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($td = $topDespesas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($td['data_emissao'])); ?></td>
                                    <td>
                                        <?php echo $td['descricao']; ?>
                                        <?php if ($td['bovino_brinco']): ?>
                                            <br><small><?php echo $td['bovino_brinco']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $td['cor'] ?: '#dc3545'; ?>">
                                            <?php echo $td['nome_categoria']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-danger">
                                        <strong>R$ <?php echo number_format($td['valor'], 2, ',', '.'); ?></strong>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma despesa encontrada.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Totais do Período -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Resumo do Período</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th>Total de Receitas:</th>
                            <td class="text-success"><strong>R$ <?php echo number_format($resumo['total_receitas'], 2, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Total de Despesas:</th>
                            <td class="text-danger"><strong>R$ <?php echo number_format($resumo['total_despesas'], 2, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Saldo do Período:</th>
                            <td class="<?php echo $saldo_periodo >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <strong>R$ <?php echo number_format($saldo_periodo, 2, ',', '.'); ?></strong>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th>Total de Lançamentos:</th>
                            <td><strong><?php echo $resumo['total_lancamentos']; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Lançamentos Pagos:</th>
                            <td><strong><?php echo $resumo['lancamentos_pagos']; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Lançamentos Pendentes:</th>
                            <td><strong><?php echo $resumo['lancamentos_pendentes']; ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
<?php
// Preparar dados para o gráfico de evolução
$dadosEvolucao = [];
if ($evolucao && $evolucao->num_rows > 0) {
    while ($e = $evolucao->fetch_assoc()) {
        $dadosEvolucao[] = $e;
    }
}
?>

// Gráfico de Evolução Mensal
const ctxEvolucao = document.getElementById('graficoEvolucao').getContext('2d');
new Chart(ctxEvolucao, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($dadosEvolucao, 'mes_label')); ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?php echo json_encode(array_column($dadosEvolucao, 'receitas')); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.1,
                fill: true
            },
            {
                label: 'Despesas',
                data: <?php echo json_encode(array_column($dadosEvolucao, 'despesas')); ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.1,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value.toFixed(2).replace('.', ',');
                    }
                }
            }
        }
    }
});

// Gráfico de Distribuição
const ctxDist = document.getElementById('graficoDistribuicao').getContext('2d');
new Chart(ctxDist, {
    type: 'doughnut',
    data: {
        labels: ['Receitas', 'Despesas'],
        datasets: [{
            data: [
                <?php echo $resumo['total_receitas']; ?>,
                <?php echo $resumo['total_despesas']; ?>
            ],
            backgroundColor: ['#28a745', '#dc3545']
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
</script>

<?php include '../../includes/footer.php'; ?>