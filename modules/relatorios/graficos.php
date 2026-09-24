<?php
// modules/relatorios/graficos.php
// Painel de gráficos gerais da fazenda

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

$pageTitle = 'Gráficos Gerais';

// Período padrão (últimos 12 meses)
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : 0;

// ============================================
// 1. COMPOSIÇÃO DO REBANHO
// ============================================

// Total de bovinos
$sqlTotalBovinos = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1";
$resultTotal = executeQuery($sqlTotalBovinos);
$totalBovinos = $resultTotal->fetch_assoc()['total'];

// Distribuição por sexo
$sqlSexo = "SELECT sexo, COUNT(*) as total 
            FROM bovinos 
            WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
            GROUP BY sexo";
$resultSexo = executeQuery($sqlSexo);
$dadosSexo = ['M' => 0, 'F' => 0];
while ($row = $resultSexo->fetch_assoc()) {
    $dadosSexo[$row['sexo']] = $row['total'];
}

// Distribuição por raça
$sqlRaca = "SELECT r.nome_raca, COUNT(b.id) as total 
            FROM racas r
            LEFT JOIN bovinos b ON r.id = b.id_raca AND b.ativo = 1
            WHERE r.id_fazenda = $farmId
            GROUP BY r.id
            ORDER BY total DESC
            LIMIT 10";
$resultRaca = executeQuery($sqlRaca);
$racas = [];
while ($row = $resultRaca->fetch_assoc()) {
    if ($row['total'] > 0) {
        $racas[] = $row;
    }
}

// Distribuição por situação
$sqlSituacao = "SELECT s.nome, s.cor, COUNT(b.id) as total 
                FROM situacoes s
                LEFT JOIN bovinos b ON s.id = b.id_situacao AND b.ativo = 1
                WHERE s.id_fazenda = $farmId
                GROUP BY s.id
                ORDER BY total DESC";
$resultSituacao = executeQuery($sqlSituacao);
$situacoes = [];
while ($row = $resultSituacao->fetch_assoc()) {
    $situacoes[] = $row;
}

// Faixa etária
$sqlIdade = "SELECT 
                SUM(CASE WHEN TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) < 12 THEN 1 ELSE 0 END) as bezerros,
                SUM(CASE WHEN TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) BETWEEN 12 AND 24 THEN 1 ELSE 0 END) as jovens,
                SUM(CASE WHEN TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) > 24 THEN 1 ELSE 0 END) as adultos
            FROM bovinos 
            WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 AND data_nascimento IS NOT NULL";
$resultIdade = executeQuery($sqlIdade);
$faixaEtaria = $resultIdade->fetch_assoc();

// ============================================
// 2. PRODUÇÃO DE LEITE
// ============================================

// Produção mensal (últimos 12 meses)
$sqlProducaoMensal = "SELECT 
                        DATE_FORMAT(data_producao, '%Y-%m') as mes,
                        DATE_FORMAT(data_producao, '%m/%Y') as mes_label,
                        SUM(quantidade_litros) as total
                      FROM producao_leite pl
                      JOIN bovinos b ON pl.id_bovino = b.id
                      WHERE " . TenantManager::addTenantFilter('b') . "
                      AND data_producao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(data_producao, '%Y-%m')
                      ORDER BY mes ASC";
$resultProducao = executeQuery($sqlProducaoMensal);
$producaoMensal = [];
while ($row = $resultProducao->fetch_assoc()) {
    $producaoMensal[] = $row;
}

// Top produtoras
$sqlTopProdutoras = "SELECT 
                        b.id,
                        b.brinco,
                        b.nome,
                        SUM(pl.quantidade_litros) as total,
                        AVG(pl.quantidade_litros) as media
                     FROM producao_leite pl
                     JOIN bovinos b ON pl.id_bovino = b.id
                     WHERE " . TenantManager::addTenantFilter('b') . "
                     AND pl.data_producao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                     GROUP BY b.id
                     ORDER BY total DESC
                     LIMIT 10";
$resultTopProdutoras = executeQuery($sqlTopProdutoras);
$topProdutoras = [];
while ($row = $resultTopProdutoras->fetch_assoc()) {
    $topProdutoras[] = $row;
}

// ============================================
// 3. FINANCEIRO
// ============================================

// Receitas vs Despesas mensais
$sqlFinanceiroMensal = "SELECT 
                            DATE_FORMAT(l.data_emissao, '%Y-%m') as mes,
                            DATE_FORMAT(l.data_emissao, '%m/%Y') as mes_label,
                            SUM(CASE WHEN c.tipo = 'receita' AND l.status = 'pago' THEN l.valor ELSE 0 END) as receitas,
                            SUM(CASE WHEN c.tipo = 'despesa' AND l.status = 'pago' THEN l.valor ELSE 0 END) as despesas
                         FROM lancamentos_financeiros l
                         LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
                         WHERE " . TenantManager::addTenantFilter('l') . "
                         AND l.data_emissao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                         GROUP BY DATE_FORMAT(l.data_emissao, '%Y-%m')
                         ORDER BY mes ASC";
$resultFinanceiro = executeQuery($sqlFinanceiroMensal);
$financeiroMensal = [];
while ($row = $resultFinanceiro->fetch_assoc()) {
    $financeiroMensal[] = $row;
}

// Totais acumulados
$sqlTotaisFinanceiro = "SELECT 
                            SUM(CASE WHEN c.tipo = 'receita' AND l.status = 'pago' THEN l.valor ELSE 0 END) as total_receitas,
                            SUM(CASE WHEN c.tipo = 'despesa' AND l.status = 'pago' THEN l.valor ELSE 0 END) as total_despesas,
                            SUM(CASE WHEN l.status = 'pendente' THEN l.valor ELSE 0 END) as total_pendente
                         FROM lancamentos_financeiros l
                         LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
                         WHERE " . TenantManager::addTenantFilter('l');
$resultTotais = executeQuery($sqlTotaisFinanceiro);
$totaisFinanceiro = $resultTotais->fetch_assoc();

// ============================================
// 4. PESAGENS E GANHO DE PESO
// ============================================

// Média de peso por mês
$sqlPesoMedio = "SELECT 
                    DATE_FORMAT(p.data_pesagem, '%Y-%m') as mes,
                    DATE_FORMAT(p.data_pesagem, '%m/%Y') as mes_label,
                    AVG(p.peso) as peso_medio,
                    COUNT(DISTINCT p.id_bovino) as animais_pesados
                 FROM pesagens p
                 JOIN bovinos b ON p.id_bovino = b.id
                 WHERE " . TenantManager::addTenantFilter('b') . "
                 AND p.data_pesagem >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                 GROUP BY DATE_FORMAT(p.data_pesagem, '%Y-%m')
                 ORDER BY mes ASC";
$resultPesoMedio = executeQuery($sqlPesoMedio);
$pesoMedioMensal = [];
while ($row = $resultPesoMedio->fetch_assoc()) {
    $pesoMedioMensal[] = $row;
}

// ============================================
// 5. VACINAS
// ============================================

// Aplicações de vacinas por mês
$sqlVacinasMensal = "SELECT 
                        DATE_FORMAT(av.data_aplicacao, '%Y-%m') as mes,
                        DATE_FORMAT(av.data_aplicacao, '%m/%Y') as mes_label,
                        COUNT(av.id) as total_aplicacoes,
                        COUNT(DISTINCT av.id_bovino) as animais_vacinados
                     FROM aplicacoes_vacinas av
                     JOIN bovinos b ON av.id_bovino = b.id
                     WHERE " . TenantManager::addTenantFilter('b') . "
                     AND av.data_aplicacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                     GROUP BY DATE_FORMAT(av.data_aplicacao, '%Y-%m')
                     ORDER BY mes ASC";
$resultVacinas = executeQuery($sqlVacinasMensal);
$vacinasMensal = [];
while ($row = $resultVacinas->fetch_assoc()) {
    $vacinasMensal[] = $row;
}

// Status de vacinação
$sqlStatusVacinas = "SELECT 
                        SUM(CASE WHEN av.proxima_dose < CURDATE() THEN 1 ELSE 0 END) as atrasadas,
                        SUM(CASE WHEN av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as proximas,
                        SUM(CASE WHEN av.proxima_dose > DATE_ADD(CURDATE(), INTERVAL 30 DAY) OR av.proxima_dose IS NULL THEN 1 ELSE 0 END) as em_dia
                     FROM aplicacoes_vacinas av
                     JOIN bovinos b ON av.id_bovino = b.id
                     WHERE " . TenantManager::addTenantFilter('b');
$resultStatusVacinas = executeQuery($sqlStatusVacinas);
$statusVacinas = $resultStatusVacinas->fetch_assoc();

// ============================================
// 6. REPRODUÇÃO
// ============================================

// Eventos reprodutivos por mês
$sqlReproducaoMensal = "SELECT 
                            DATE_FORMAT(data_evento, '%Y-%m') as mes,
                            DATE_FORMAT(data_evento, '%m/%Y') as mes_label,
                            SUM(CASE WHEN tipo_evento = 'cio' THEN 1 ELSE 0 END) as cios,
                            SUM(CASE WHEN tipo_evento = 'inseminacao' THEN 1 ELSE 0 END) as inseminacoes,
                            SUM(CASE WHEN tipo_evento = 'parto' THEN 1 ELSE 0 END) as partos
                         FROM reproducao r
                         JOIN bovinos b ON r.id_bovino_femea = b.id
                         WHERE " . TenantManager::addTenantFilter('b') . "
                         AND data_evento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                         GROUP BY DATE_FORMAT(data_evento, '%Y-%m')
                         ORDER BY mes ASC";
$resultReproducao = executeQuery($sqlReproducaoMensal);
$reproducaoMensal = [];
while ($row = $resultReproducao->fetch_assoc()) {
    $reproducaoMensal[] = $row;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-pie-chart-fill me-2 text-success"></i>
                Gráficos Gerais
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Gráficos</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- LINHA 1: COMPOSIÇÃO DO REBANHO -->
    <!-- ======================================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="text-success border-bottom pb-2">
                <i class="bi bi-tree-fill"></i> Composição do Rebanho
            </h5>
        </div>
        
        <!-- Card: Total de Animais -->
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <i class="bi bi-tree display-4"></i>
                    <h2><?php echo $totalBovinos; ?></h2>
                    <h6>Total de Animais</h6>
                </div>
            </div>
        </div>
        
        <!-- Gráfico: Distribuição por Sexo -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Por Sexo</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoSexo" style="height: 150px;"></canvas>
                    <div class="text-center mt-2">
                        <small class="text-primary"><i class="bi bi-gender-male"></i> Machos: <?php echo $dadosSexo['M']; ?></small><br>
                        <small class="text-warning"><i class="bi bi-gender-female"></i> Fêmeas: <?php echo $dadosSexo['F']; ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico: Distribuição por Situação -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Por Situação</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoSituacao" style="height: 150px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico: Faixa Etária -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Faixa Etária</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoIdade" style="height: 150px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico: Raças -->
    <?php if (!empty($racas)): ?>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Distribuição por Raça</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoRacas" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================================== -->
    <!-- LINHA 2: PRODUÇÃO DE LEITE -->
    <!-- ======================================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="text-success border-bottom pb-2">
                <i class="bi bi-cup-fill"></i> Produção de Leite
            </h5>
        </div>
        
        <!-- Gráfico: Produção Mensal -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Produção Mensal (Últimos 12 meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoProducaoLeite" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Produtoras -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Top 10 Produtoras (Últimos 30 dias)</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($topProdutoras)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($topProdutoras as $tp): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $tp['brinco']; ?></strong>
                                    <?php if ($tp['nome']): ?>
                                        <br><small><?php echo $tp['nome']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary"><?php echo number_format($tp['total'], 2, ',', '.'); ?> L</span>
                                    <br>
                                    <small class="text-muted">Média: <?php echo number_format($tp['media'], 2, ',', '.'); ?> L/dia</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Sem dados no período</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- LINHA 3: FINANCEIRO -->
    <!-- ======================================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="text-success border-bottom pb-2">
                <i class="bi bi-cash-stack"></i> Financeiro
            </h5>
        </div>
        
        <!-- Cards Financeiros -->
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Total Receitas</h6>
                    <h3>R$ <?php echo number_format($totaisFinanceiro['total_receitas'] ?? 0, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>Total Despesas</h6>
                    <h3>R$ <?php echo number_format($totaisFinanceiro['total_despesas'] ?? 0, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Saldo</h6>
                    <h3>R$ <?php echo number_format(($totaisFinanceiro['total_receitas'] ?? 0) - ($totaisFinanceiro['total_despesas'] ?? 0), 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>Pendente</h6>
                    <h3>R$ <?php echo number_format($totaisFinanceiro['total_pendente'] ?? 0, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Gráfico: Receitas vs Despesas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Receitas vs Despesas (Últimos 12 meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoFinanceiro" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- LINHA 4: PESAGENS E VACINAS -->
    <!-- ======================================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="text-success border-bottom pb-2">
                <i class="bi bi-bar-chart"></i> Pesagens e Vacinas
            </h5>
        </div>
        
        <!-- Gráfico: Peso Médio -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Evolução do Peso Médio</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoPesoMedio" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico: Vacinas Mensais -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Aplicações de Vacinas por Mês</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoVacinas" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Status Vacinas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Status de Vacinação</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoStatusVacinas" style="height: 200px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- LINHA 5: REPRODUÇÃO -->
    <!-- ======================================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="text-success border-bottom pb-2">
                <i class="bi bi-heart"></i> Reprodução
            </h5>
        </div>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Eventos Reprodutivos (Últimos 12 meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoReproducao" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
// ============================================
// GRÁFICO 1: Distribuição por Sexo
// ============================================
const ctxSexo = document.getElementById('graficoSexo').getContext('2d');
new Chart(ctxSexo, {
    type: 'doughnut',
    data: {
        labels: ['Machos', 'Fêmeas'],
        datasets: [{
            data: [<?php echo $dadosSexo['M']; ?>, <?php echo $dadosSexo['F']; ?>],
            backgroundColor: ['#0d6efd', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// ============================================
// GRÁFICO 2: Distribuição por Situação
// ============================================
const ctxSituacao = document.getElementById('graficoSituacao').getContext('2d');
new Chart(ctxSituacao, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($situacoes, 'nome')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($situacoes, 'total')); ?>,
            backgroundColor: <?php echo json_encode(array_column($situacoes, 'cor')); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// ============================================
// GRÁFICO 3: Faixa Etária
// ============================================
const ctxIdade = document.getElementById('graficoIdade').getContext('2d');
new Chart(ctxIdade, {
    type: 'doughnut',
    data: {
        labels: ['Bezerros (<12m)', 'Jovens (12-24m)', 'Adultos (>24m)'],
        datasets: [{
            data: [<?php echo $faixaEtaria['bezerros'] ?? 0; ?>, <?php echo $faixaEtaria['jovens'] ?? 0; ?>, <?php echo $faixaEtaria['adultos'] ?? 0; ?>],
            backgroundColor: ['#28a745', '#ffc107', '#17a2b8']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

<?php if (!empty($racas)): ?>
// ============================================
// GRÁFICO 4: Raças
// ============================================
const ctxRacas = document.getElementById('graficoRacas').getContext('2d');
new Chart(ctxRacas, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($racas, 'nome_raca')); ?>,
        datasets: [{
            label: 'Quantidade',
            data: <?php echo json_encode(array_column($racas, 'total')); ?>,
            backgroundColor: '#28a745'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
<?php endif; ?>

// ============================================
// GRÁFICO 5: Produção de Leite
// ============================================
const ctxLeite = document.getElementById('graficoProducaoLeite').getContext('2d');
new Chart(ctxLeite, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($producaoMensal, 'mes_label')); ?>,
        datasets: [{
            label: 'Litros',
            data: <?php echo json_encode(array_column($producaoMensal, 'total')); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' L';
                    }
                }
            }
        }
    }
});

// ============================================
// GRÁFICO 6: Financeiro
// ============================================
const ctxFin = document.getElementById('graficoFinanceiro').getContext('2d');
new Chart(ctxFin, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($financeiroMensal, 'mes_label')); ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?php echo json_encode(array_column($financeiroMensal, 'receitas')); ?>,
                backgroundColor: '#28a745'
            },
            {
                label: 'Despesas',
                data: <?php echo json_encode(array_column($financeiroMensal, 'despesas')); ?>,
                backgroundColor: '#dc3545'
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

// ============================================
// GRÁFICO 7: Peso Médio
// ============================================
const ctxPeso = document.getElementById('graficoPesoMedio').getContext('2d');
new Chart(ctxPeso, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($pesoMedioMensal, 'mes_label')); ?>,
        datasets: [{
            label: 'Peso Médio (kg)',
            data: <?php echo json_encode(array_column($pesoMedioMensal, 'peso_medio')); ?>,
            borderColor: '#17a2b8',
            backgroundColor: 'rgba(23, 162, 184, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// ============================================
// GRÁFICO 8: Vacinas Mensais
// ============================================
const ctxVacs = document.getElementById('graficoVacinas').getContext('2d');
new Chart(ctxVacs, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($vacinasMensal, 'mes_label')); ?>,
        datasets: [
            {
                label: 'Aplicações',
                data: <?php echo json_encode(array_column($vacinasMensal, 'total_aplicacoes')); ?>,
                backgroundColor: '#28a745'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// ============================================
// GRÁFICO 9: Status Vacinas
// ============================================
const ctxStatusVacs = document.getElementById('graficoStatusVacinas').getContext('2d');
new Chart(ctxStatusVacs, {
    type: 'doughnut',
    data: {
        labels: ['Em dia', 'Próximas (30d)', 'Atrasadas'],
        datasets: [{
            data: [
                <?php echo $statusVacinas['em_dia'] ?? 0; ?>,
                <?php echo $statusVacinas['proximas'] ?? 0; ?>,
                <?php echo $statusVacinas['atrasadas'] ?? 0; ?>
            ],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// ============================================
// GRÁFICO 10: Reprodução
// ============================================
const ctxRep = document.getElementById('graficoReproducao').getContext('2d');
new Chart(ctxRep, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($reproducaoMensal, 'mes_label')); ?>,
        datasets: [
            {
                label: 'Cios',
                data: <?php echo json_encode(array_column($reproducaoMensal, 'cios')); ?>,
                borderColor: '#dc3545',
                tension: 0.1
            },
            {
                label: 'Inseminações',
                data: <?php echo json_encode(array_column($reproducaoMensal, 'inseminacoes')); ?>,
                borderColor: '#ffc107',
                tension: 0.1
            },
            {
                label: 'Partos',
                data: <?php echo json_encode(array_column($reproducaoMensal, 'partos')); ?>,
                borderColor: '#28a745',
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>