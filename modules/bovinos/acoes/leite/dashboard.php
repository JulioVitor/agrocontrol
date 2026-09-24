<?php
// modules/bovinos/acoes/leite/dashboard.php
// Dashboard completo de produção de leite

require_once '../../../../config/database.php';
require_once '../../../../config/constants.php';
require_once '../../../../includes/functions.php';
require_once '../../../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    redirect(BASE_URL . 'modules/fazendas/selector.php');
}

$pageTitle = 'Dashboard de Produção de Leite';

// Data selecionada (hoje ou escolhida)
$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$data_ontem = date('Y-m-d', strtotime($data . ' -1 day'));
$data_amanha = date('Y-m-d', strtotime($data . ' +1 day'));

// ============================================
// 1. PRODUÇÃO DO DIA (TOTAL)
// ============================================
$sqlTotalDia = "SELECT 
                    COALESCE(SUM(pl.quantidade_litros), 0) as total_dia,
                    COUNT(DISTINCT pl.id_bovino) as animais_em_producao,
                    COUNT(pl.id) as total_registros,
                    AVG(pl.quantidade_litros) as media_por_registro
                FROM producao_leite pl
                JOIN bovinos b ON pl.id_bovino = b.id
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND pl.data_producao = '$data'";

$resultTotalDia = executeQuery($sqlTotalDia);
$totalDia = $resultTotalDia->fetch_assoc();

// ============================================
// 2. PRODUÇÃO DO MÊS
// ============================================
$inicioMes = date('Y-m-01', strtotime($data));
$fimMes = date('Y-m-t', strtotime($data));

$sqlMes = "SELECT 
                COALESCE(SUM(pl.quantidade_litros), 0) as total_mes,
                COUNT(DISTINCT pl.data_producao) as dias_com_producao
           FROM producao_leite pl
           JOIN bovinos b ON pl.id_bovino = b.id
           WHERE " . TenantManager::addTenantFilter('b') . "
           AND pl.data_producao BETWEEN '$inicioMes' AND '$fimMes'";
$resultMes = executeQuery($sqlMes);
$producaoMes = $resultMes->fetch_assoc();

// ============================================
// 3. PRODUÇÃO DO ANO
// ============================================
$sqlAno = "SELECT COALESCE(SUM(pl.quantidade_litros), 0) as total_ano
           FROM producao_leite pl
           JOIN bovinos b ON pl.id_bovino = b.id
           WHERE " . TenantManager::addTenantFilter('b') . "
           AND YEAR(pl.data_producao) = YEAR(CURDATE())";
$resultAno = executeQuery($sqlAno);
$totalAno = $resultAno->fetch_assoc()['total_ano'];

// ============================================
// 4. PRODUÇÃO POR ANIMAL (DIA ATUAL)
// ============================================
$sqlAnimaisDia = "SELECT 
                    b.id,
                    b.brinco,
                    b.nome,
                    r.nome_raca,
                    COALESCE(SUM(CASE WHEN pl.turno = 'manha' THEN pl.quantidade_litros ELSE 0 END), 0) as manha,
                    COALESCE(SUM(CASE WHEN pl.turno = 'tarde' THEN pl.quantidade_litros ELSE 0 END), 0) as tarde,
                    COALESCE(SUM(CASE WHEN pl.turno = 'noite' THEN pl.quantidade_litros ELSE 0 END), 0) as noite,
                    COALESCE(SUM(pl.quantidade_litros), 0) as total
                FROM bovinos b
                LEFT JOIN racas r ON b.id_raca = r.id
                LEFT JOIN producao_leite pl ON b.id = pl.id_bovino AND pl.data_producao = '$data'
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND b.sexo = 'F' AND b.ativo = 1
                GROUP BY b.id
                ORDER BY total DESC, b.brinco ASC";

$animaisDia = executeQuery($sqlAnimaisDia);

// ============================================
// 5. PRODUÇÃO DOS ÚLTIMOS 7 DIAS
// ============================================
$sqlUltimos7 = "SELECT 
                    pl.data_producao,
                    DATE_FORMAT(pl.data_producao, '%d/%m') as data_label,
                    COALESCE(SUM(pl.quantidade_litros), 0) as total,
                    COUNT(DISTINCT pl.id_bovino) as animais
                FROM producao_leite pl
                JOIN bovinos b ON pl.id_bovino = b.id
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND pl.data_producao BETWEEN DATE_SUB('$data', INTERVAL 6 DAY) AND '$data'
                GROUP BY pl.data_producao
                ORDER BY pl.data_producao ASC";

$ultimos7 = executeQuery($sqlUltimos7);

// ============================================
// 6. TOP 10 PRODUTORAS DO MÊS
// ============================================
$sqlTopMes = "SELECT 
                    b.id,
                    b.brinco,
                    b.nome,
                    r.nome_raca,
                    COALESCE(SUM(pl.quantidade_litros), 0) as total_mes,
                    COUNT(DISTINCT pl.data_producao) as dias_produzidos,
                    COALESCE(SUM(pl.quantidade_litros) / COUNT(DISTINCT pl.data_producao), 0) as media_diaria
                FROM bovinos b
                LEFT JOIN racas r ON b.id_raca = r.id
                LEFT JOIN producao_leite pl ON b.id = pl.id_bovino 
                    AND pl.data_producao BETWEEN '$inicioMes' AND '$fimMes'
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND b.sexo = 'F' AND b.ativo = 1
                GROUP BY b.id
                HAVING total_mes > 0
                ORDER BY total_mes DESC
                LIMIT 10";

$topMes = executeQuery($sqlTopMes);

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-cup-fill me-2 text-info"></i>
                Dashboard de Produção de Leite
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Produção de Leite</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nova Produção
            </a>
            <a href="diario.php" class="btn btn-outline-info">
                <i class="bi bi-calendar"></i> Produção Diária
            </a>
            <a href="graficos.php" class="btn btn-outline-warning">
                <i class="bi bi-graph-up"></i> Gráficos
            </a>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Produção Hoje</h6>
                    <h2><?php echo number_format($totalDia['total_dia'] ?? 0, 2, ',', '.'); ?> L</h2>
                    <small><?php echo $totalDia['animais_em_producao'] ?? 0; ?> animais</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Produção do Mês</h6>
                    <h2><?php echo number_format($producaoMes['total_mes'] ?? 0, 2, ',', '.'); ?> L</h2>
                    <small><?php echo $producaoMes['dias_com_producao'] ?? 0; ?> dias</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Média Diária</h6>
                    <?php 
                    $mediaDiaria = ($producaoMes['dias_com_producao'] ?? 0) > 0 
                        ? ($producaoMes['total_mes'] ?? 0) / $producaoMes['dias_com_producao'] 
                        : 0;
                    ?>
                    <h2><?php echo number_format($mediaDiaria, 2, ',', '.'); ?> L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total no Ano</h6>
                    <h2><?php echo number_format($totalAno, 2, ',', '.'); ?> L</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Seletor de Data e Navegação -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <form method="GET" action="" class="row g-2">
                        <div class="col-8">
                            <input type="date" class="form-control" name="data" value="<?php echo $data; ?>">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-success w-100">Ver</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-8 text-end">
                    <a href="?data=<?php echo $data_ontem; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-left"></i> Dia Anterior
                    </a>
                    <a href="?data=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar"></i> Hoje
                    </a>
                    <a href="?data=<?php echo $data_amanha; ?>" class="btn btn-outline-secondary btn-sm">
                        Próximo Dia <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Título da seção -->
    <h5 class="text-success border-bottom pb-2 mb-3">
        <i class="bi bi-list-check"></i> Produção por Animal - <?php echo date('d/m/Y', strtotime($data)); ?>
    </h5>

    <!-- Tabela de Produção por Animal -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <?php if ($animaisDia && $animaisDia->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Animal</th>
                            <th>Raça</th>
                            <th class="text-center">Manhã</th>
                            <th class="text-center">Tarde</th>
                            <th class="text-center">Noite</th>
                            <th class="text-center">Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($animal = $animaisDia->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="index.php?bovino_id=<?php echo $animal['id']; ?>">
                                    <strong><?php echo $animal['brinco']; ?></strong>
                                </a>
                                <?php if ($animal['nome']): ?>
                                    <br><small><?php echo $animal['nome']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $animal['nome_raca'] ?: '-'; ?></td>
                            <td class="text-center">
                                <?php if ($animal['manha'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo number_format($animal['manha'], 2, ',', '.'); ?> L</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($animal['tarde'] > 0): ?>
                                    <span class="badge bg-info"><?php echo number_format($animal['tarde'], 2, ',', '.'); ?> L</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($animal['noite'] > 0): ?>
                                    <span class="badge bg-secondary"><?php echo number_format($animal['noite'], 2, ',', '.'); ?> L</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <strong class="text-success"><?php echo number_format($animal['total'], 2, ',', '.'); ?> L</strong>
                            </td>
                            <td>
                                <a href="index.php?bovino_id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-clock-history"></i> Histórico
                                </a>
                                <a href="cadastrar.php?bovino_id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-plus-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-success">
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total do Dia:</strong></td>
                            <td class="text-center"><strong><?php echo number_format($totalDia['total_dia'] ?? 0, 2, ',', '.'); ?> L</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-cup display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma produção registrada para esta data</h4>
                <p class="text-muted">Clique em "Nova Produção" para registrar a produção do dia.</p>
                <a href="cadastrar.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nova Produção
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gráfico dos Últimos 7 Dias -->
    <?php if ($ultimos7 && $ultimos7->num_rows > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-bar-chart"></i> Produção dos Últimos 7 Dias
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoUltimos7" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top 10 Produtoras do Mês -->
    <?php if ($topMes && $topMes->num_rows > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-trophy"></i> Top 10 Produtoras do Mês
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Posição</th>
                                    <th>Animal</th>
                                    <th>Raça</th>
                                    <th class="text-end">Total Mês (L)</th>
                                    <th class="text-end">Dias</th>
                                    <th class="text-end">Média (L/dia)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $posicao = 1;
                                while ($top = $topMes->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($posicao == 1): ?>
                                            <span class="badge bg-warning">🥇 1º</span>
                                        <?php elseif ($posicao == 2): ?>
                                            <span class="badge bg-secondary">🥈 2º</span>
                                        <?php elseif ($posicao == 3): ?>
                                            <span class="badge bg-bronze" style="background-color: #cd7f32;">🥉 3º</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark"><?php echo $posicao; ?>º</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?bovino_id=<?php echo $top['id']; ?>">
                                            <strong><?php echo $top['brinco']; ?></strong>
                                        </a>
                                        <?php if ($top['nome']): ?>
                                            <br><small><?php echo $top['nome']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $top['nome_raca'] ?: '-'; ?></td>
                                    <td class="text-end"><strong><?php echo number_format($top['total_mes'], 2, ',', '.'); ?> L</strong></td>
                                    <td class="text-end"><?php echo $top['dias_produzidos']; ?></td>
                                    <td class="text-end"><?php echo number_format($top['media_diaria'], 2, ',', '.'); ?> L</td>
                                </tr>
                                <?php 
                                $posicao++;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Links rápidos -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-around flex-wrap">
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-clock-history text-success"></i> Histórico por Animal
                        </a>
                        <a href="diario.php" class="text-decoration-none">
                            <i class="bi bi-calendar text-info"></i> Produção Diária
                        </a>
                        <a href="graficos.php" class="text-decoration-none">
                            <i class="bi bi-graph-up text-warning"></i> Gráficos
                        </a>
                        <a href="cadastrar.php" class="text-decoration-none">
                            <i class="bi bi-plus-circle text-success"></i> Novo Registro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<?php if ($ultimos7 && $ultimos7->num_rows > 0): ?>
<script>
<?php
$labels = [];
$valores = [];
while ($dia = $ultimos7->fetch_assoc()) {
    $labels[] = $dia['data_label'];
    $valores[] = $dia['total'];
}
?>
const ctx = document.getElementById('graficoUltimos7').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Produção (Litros)',
            data: <?php echo json_encode($valores); ?>,
            backgroundColor: '#28a745',
            borderRadius: 5
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
                    text: 'Litros'
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../../../../includes/footer.php'; ?>