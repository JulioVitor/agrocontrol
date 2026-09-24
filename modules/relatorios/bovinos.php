<?php
// modules/relatorios/bovinos.php
// Relatório completo do rebanho

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

$pageTitle = 'Relatório do Rebanho';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-1 year'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Estatísticas gerais
$stats = [];

// Total por sexo
$sql = "SELECT sexo, COUNT(*) as total 
        FROM bovinos 
        WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1
        GROUP BY sexo";
$result = executeQuery($sql);
$stats['sexo'] = ['M' => 0, 'F' => 0];
while ($row = $result->fetch_assoc()) {
    $stats['sexo'][$row['sexo']] = $row['total'];
}

// Total por situação
$sql = "SELECT s.nome, s.cor, COUNT(b.id) as total 
        FROM situacoes s
        LEFT JOIN bovinos b ON s.id = b.id_situacao AND " . TenantManager::addTenantFilter('b')
        . " WHERE s.id_fazenda = $farmId
        GROUP BY s.id
        ORDER BY s.ordem";
$result = executeQuery($sql);
$stats['situacoes'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['situacoes'][] = $row;
}

// Total por raça
$sql = "SELECT r.nome_raca, COUNT(b.id) as total 
        FROM racas r
        LEFT JOIN bovinos b ON r.id = b.id_raca AND " . TenantManager::addTenantFilter('b')
        . " WHERE r.id_fazenda = $farmId
        GROUP BY r.id
        ORDER BY total DESC";
$result = executeQuery($sql);
$stats['racas'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['racas'][] = $row;
}

// Faixa etária
$sql = "SELECT 
            SUM(CASE WHEN TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) < 12 THEN 1 ELSE 0 END) as bezerros,
            SUM(CASE WHEN TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) BETWEEN 12 AND 24 THEN 1 ELSE 0 END) as jovens,
            SUM(CASE WHEN TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) > 24 THEN 1 ELSE 0 END) as adultos
        FROM bovinos 
        WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 AND data_nascimento IS NOT NULL";
$result = executeQuery($sql);
$stats['idade'] = $result->fetch_assoc();

// Nascimentos no período
$sql = "SELECT COUNT(*) as total 
        FROM bovinos 
        WHERE " . TenantManager::addTenantFilter() . " 
        AND data_nascimento BETWEEN '$data_inicio' AND '$data_fim'";
$result = executeQuery($sql);
$stats['nascimentos'] = $result->fetch_assoc()['total'];

// Mortalidade no período
$sql = "SELECT COUNT(*) as total 
        FROM bovinos 
        WHERE " . TenantManager::addTenantFilter() . " 
        AND data_saida BETWEEN '$data_inicio' AND '$data_fim'
        AND motivo_saida = 'Morto'";
$result = executeQuery($sql);
$stats['mortalidade'] = $result->fetch_assoc()['total'];

// Listagem detalhada para tabela
$sql = "SELECT b.*, 
               r.nome_raca,
               s.nome as situacao_nome,
               s.cor as situacao_cor,
               TIMESTAMPDIFF(MONTH, b.data_nascimento, CURDATE()) as idade_meses
        FROM bovinos b
        LEFT JOIN racas r ON b.id_raca = r.id
        LEFT JOIN situacoes s ON b.id_situacao = s.id
        WHERE " . TenantManager::addTenantFilter('b') . " AND b.ativo = 1
        ORDER BY b.data_cadastro DESC";
$bovinos = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-tree-fill me-2 text-success"></i>
                Relatório do Rebanho
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Rebanho</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="exportar.php?tipo=pdf&relatorio=bovinos" class="btn btn-success">
                <i class="bi bi-file-pdf"></i> Exportar PDF
            </a>
        </div>
    </div>

    <!-- Filtros -->
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
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total do Rebanho</h6>
                    <h2><?php echo array_sum($stats['sexo']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Machos</h6>
                    <h2><?php echo $stats['sexo']['M']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Fêmeas</h6>
                    <h2><?php echo $stats['sexo']['F']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Nascimentos</h6>
                    <h2><?php echo $stats['nascimentos']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Distribuição -->
    <div class="row g-3 mb-4">
        <!-- Distribuição por Situação -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Distribuição por Situação</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoSituacao" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribuição por Idade -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Faixa Etária</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoIdade" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Raças -->
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Composição por Raça</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Raça</th>
                                <th>Quantidade</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalRacas = array_sum(array_column($stats['racas'], 'total'));
                            foreach ($stats['racas'] as $raca): 
                                $percentual = $totalRacas > 0 ? ($raca['total'] / $totalRacas) * 100 : 0;
                            ?>
                            <tr>
                                <td><strong><?php echo $raca['nome_raca']; ?></strong></td>
                                <td><?php echo $raca['total']; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $percentual; ?>%">
                                            <?php echo number_format($percentual, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Listagem Detalhada -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Listagem Detalhada do Rebanho</h6>
            <span class="badge bg-success"><?php echo $bovinos ? $bovinos->num_rows : 0; ?> animais</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Brinco</th>
                            <th>Nome</th>
                            <th>Raça</th>
                            <th>Sexo</th>
                            <th>Idade</th>
                            <th>Peso</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bovinos && $bovinos->num_rows > 0): ?>
                            <?php while ($b = $bovinos->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $b['brinco']; ?></strong></td>
                                <td><?php echo $b['nome'] ?: '-'; ?></td>
                                <td><?php echo $b['nome_raca'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($b['sexo'] == 'M'): ?>
                                        <span class="badge bg-primary">Macho</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Fêmea</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($b['data_nascimento']) {
                                        $anos = floor($b['idade_meses'] / 12);
                                        $meses = $b['idade_meses'] % 12;
                                        echo $anos . 'a ' . $meses . 'm';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $b['peso_atual'] ? number_format($b['peso_atual'], 2, ',', '.') . ' kg' : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $b['situacao_cor'] ?: 'secondary'; ?>">
                                        <?php echo $b['situacao_nome']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    Nenhum animal encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
// Gráfico de Situação
const ctxSit = document.getElementById('graficoSituacao').getContext('2d');
new Chart(ctxSit, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($stats['situacoes'], 'nome')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($stats['situacoes'], 'total')); ?>,
            backgroundColor: [
                '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de Idade
const ctxIdade = document.getElementById('graficoIdade').getContext('2d');
new Chart(ctxIdade, {
    type: 'bar',
    data: {
        labels: ['Bezerros (< 12m)', 'Jovens (12-24m)', 'Adultos (> 24m)'],
        datasets: [{
            data: [
                <?php echo $stats['idade']['bezerros'] ?? 0; ?>,
                <?php echo $stats['idade']['jovens'] ?? 0; ?>,
                <?php echo $stats['idade']['adultos'] ?? 0; ?>
            ],
            backgroundColor: ['#28a745', '#ffc107', '#17a2b8']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Quantidade'
                }
            }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>