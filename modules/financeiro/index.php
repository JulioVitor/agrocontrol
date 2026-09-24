<?php
// modules/financeiro/index.php
// Dashboard financeiro

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

$pageTitle = 'Dashboard Financeiro';

// Definir períodos
$hoje = date('Y-m-d');
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');
$inicioAno = date('Y-01-01');
$fimAno = date('Y-12-31');

// Estatísticas do mês atual
$stats = [];

// Receitas do mês
$sql = "SELECT COALESCE(SUM(valor), 0) as total 
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . "
        AND c.tipo = 'receita'
        AND l.data_emissao BETWEEN '$inicioMes' AND '$fimMes'
        AND l.status = 'pago'";
$result = executeQuery($sql);
$stats['receitas_mes'] = $result->fetch_assoc()['total'];

// Despesas do mês
$sql = "SELECT COALESCE(SUM(valor), 0) as total 
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . "
        AND c.tipo = 'despesa'
        AND l.data_emissao BETWEEN '$inicioMes' AND '$fimMes'
        AND l.status = 'pago'";
$result = executeQuery($sql);
$stats['despesas_mes'] = $result->fetch_assoc()['total'];

$stats['saldo_mes'] = $stats['receitas_mes'] - $stats['despesas_mes'];

// Contas a pagar (vencidas e a vencer)
$sql = "SELECT 
            SUM(CASE WHEN data_vencimento < CURDATE() AND status = 'pendente' THEN valor ELSE 0 END) as vencidas,
            SUM(CASE WHEN data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'pendente' THEN valor ELSE 0 END) as proximas
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . "
        AND c.tipo = 'despesa'
        AND status IN ('pendente', 'atrasado')";
$result = executeQuery($sql);
$contas = $result->fetch_assoc();
$stats['contas_vencidas'] = $contas['vencidas'] ?? 0;
$stats['contas_proximas'] = $contas['proximas'] ?? 0;

// Saldo em caixa (considerando todos os pagos)
$sql = "SELECT 
            SUM(CASE WHEN c.tipo = 'receita' THEN l.valor ELSE 0 END) as total_receitas,
            SUM(CASE WHEN c.tipo = 'despesa' THEN l.valor ELSE 0 END) as total_despesas
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . "
        AND l.status = 'pago'";
$result = executeQuery($sql);
$totais = $result->fetch_assoc();
$stats['saldo_total'] = ($totais['total_receitas'] ?? 0) - ($totais['total_despesas'] ?? 0);

// Últimos lançamentos
$sql = "SELECT l.*, c.nome_categoria, c.tipo, c.cor,
               b.brinco as bovino_brinco
        FROM lancamentos_financeiros l
        LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
        LEFT JOIN bovinos b ON l.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('l') . "
        ORDER BY l.data_emissao DESC
        LIMIT 10";
$ultimosLancamentos = executeQuery($sql);

// Receitas vs Despesas por mês (últimos 6 meses)
$dadosGrafico = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $inicio = date('Y-m-01', strtotime("-$i months"));
    $fim = date('Y-m-t', strtotime("-$i months"));
    
    // Receitas
    $sql = "SELECT COALESCE(SUM(valor), 0) as total 
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON l.id_categoria = c.id
            WHERE " . TenantManager::addTenantFilter('l') . "
            AND c.tipo = 'receita'
            AND l.data_emissao BETWEEN '$inicio' AND '$fim'
            AND l.status = 'pago'";
    $result = executeQuery($sql);
    $receitas = $result->fetch_assoc()['total'];
    
    // Despesas
    $sql = "SELECT COALESCE(SUM(valor), 0) as total 
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON l.id_categoria = c.id
            WHERE " . TenantManager::addTenantFilter('l') . "
            AND c.tipo = 'despesa'
            AND l.data_emissao BETWEEN '$inicio' AND '$fim'
            AND l.status = 'pago'";
    $result = executeQuery($sql);
    $despesas = $result->fetch_assoc()['total'];
    
    $dadosGrafico[] = [
        'mes' => strftime('%b/%Y', strtotime($mes . '-01')),
        'receitas' => $receitas,
        'despesas' => $despesas
    ];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-cash-stack me-2 text-success"></i>
                Financeiro
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Financeiro</li>
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
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="categorias.php"><i class="bi bi-tags"></i> Gerenciar Categorias</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Receitas do Mês</h6>
                    <h3>R$ <?php echo number_format($stats['receitas_mes'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Despesas do Mês</h6>
                    <h3>R$ <?php echo number_format($stats['despesas_mes'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $stats['saldo_mes'] >= 0 ? 'bg-info' : 'bg-warning'; ?> text-white">
                <div class="card-body">
                    <h6>Saldo do Mês</h6>
                    <h3>R$ <?php echo number_format($stats['saldo_mes'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Saldo Total</h6>
                    <h3>R$ <?php echo number_format($stats['saldo_total'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Contas a pagar -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Contas Vencidas</h6>
                            <h3>R$ <?php echo number_format($stats['contas_vencidas'], 2, ',', '.'); ?></h3>
                        </div>
                        <i class="bi bi-exclamation-triangle-fill fs-1 opacity-50"></i>
                    </div>
                    <a href="lancamentos.php?status=atrasado" class="text-white stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>A Vencer (7 dias)</h6>
                            <h3>R$ <?php echo number_format($stats['contas_proximas'], 2, ',', '.'); ?></h3>
                        </div>
                        <i class="bi bi-clock-history fs-1 opacity-50"></i>
                    </div>
                    <a href="lancamentos.php?status=pendente" class="text-white stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Receitas vs Despesas (Últimos 6 meses)</h6>
        </div>
        <div class="card-body">
            <canvas id="graficoFinanceiro" style="height: 400px;"></canvas>
        </div>
    </div>

    <!-- Últimos lançamentos -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Últimos Lançamentos</h6>
            <a href="lancamentos.php" class="btn btn-sm btn-outline-success">Ver Todos</a>
        </div>
        <div class="card-body p-0">
            <?php if ($ultimosLancamentos && $ultimosLancamentos->num_rows > 0): ?>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($l = $ultimosLancamentos->fetch_assoc()): 
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
                                    <br><small class="text-muted">Bovino: <?php echo $l['bovino_brinco']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $l['cor'] ?: '#6c757d'; ?>">
                                    <?php echo $l['nome_categoria']; ?>
                                </span>
                            </td>
                            <td class="<?php echo $l['tipo'] == 'receita' ? 'text-success' : 'text-danger'; ?>">
                                <strong>
                                    <?php echo $l['tipo'] == 'receita' ? '+' : '-'; ?>
                                    R$ <?php echo number_format($l['valor'], 2, ',', '.'); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo ucfirst($l['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($l['data_vencimento']): ?>
                                    <?php echo date('d/m/Y', strtotime($l['data_vencimento'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-cash-stack display-4 text-muted"></i>
                <p class="text-muted mt-2">Nenhum lançamento financeiro encontrado.</p>
                <a href="receita.php" class="btn btn-success btn-sm">Primeira Receita</a>
                <a href="despesa.php" class="btn btn-danger btn-sm">Primeira Despesa</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
const ctx = document.getElementById('graficoFinanceiro').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($dadosGrafico, 'mes')); ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?php echo json_encode(array_column($dadosGrafico, 'receitas')); ?>,
                backgroundColor: '#28a745',
                borderRadius: 5
            },
            {
                label: 'Despesas',
                data: <?php echo json_encode(array_column($dadosGrafico, 'despesas')); ?>,
                backgroundColor: '#dc3545',
                borderRadius: 5
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
</script>

<?php include '../../includes/footer.php'; ?>