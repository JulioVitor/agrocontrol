<?php
// modules/relatorios/pesagens.php
// Relatório de pesagens e ganho de peso

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

$pageTitle = 'Relatório de Ganho de Peso';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-3 months'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;
$raca_id = isset($_GET['raca_id']) ? intval($_GET['raca_id']) : 0;
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'ganho_diario_desc';

// Buscar bovinos para filtro
$sqlBovinos = "SELECT id, brinco, nome FROM bovinos 
               WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
               ORDER BY brinco";
$bovinos = executeQuery($sqlBovinos);

// Buscar raças para filtro
$sqlRacas = "SELECT id, nome_raca FROM racas 
             WHERE " . TenantManager::addTenantFilter() . " 
             ORDER BY nome_raca";
$racas = executeQuery($sqlRacas);

// ============================================
// 1. ESTATÍSTICAS GERAIS DE PESAGENS
// ============================================
$sqlStats = "SELECT 
                COUNT(DISTINCT p.id_bovino) as total_animais_pesados,
                COUNT(p.id) as total_pesagens,
                AVG(p.peso) as peso_medio,
                MAX(p.peso) as peso_maximo,
                MIN(p.peso) as peso_minimo
             FROM pesagens p
             JOIN bovinos b ON p.id_bovino = b.id
             WHERE " . TenantManager::addTenantFilter('b') . "
             AND p.data_pesagem BETWEEN '$data_inicio' AND '$data_fim'";
$resultStats = executeQuery($sqlStats);
$stats = $resultStats->fetch_assoc();

// ============================================
// 2. RANKING DE GANHO DE PESO
// ============================================
$sqlRanking = "SELECT 
                    b.id,
                    b.brinco,
                    b.nome,
                    r.nome_raca,
                    MIN(p.peso) as peso_inicial,
                    MAX(p.peso) as peso_final,
                    DATEDIFF(MAX(p.data_pesagem), MIN(p.data_pesagem)) as dias,
                    (MAX(p.peso) - MIN(p.peso)) as ganho_total,
                    (MAX(p.peso) - MIN(p.peso)) / DATEDIFF(MAX(p.data_pesagem), MIN(p.data_pesagem)) as ganho_diario,
                    COUNT(p.id) as num_pesagens
                FROM bovinos b
                JOIN pesagens p ON b.id = p.id_bovino
                LEFT JOIN racas r ON b.id_raca = r.id
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND p.data_pesagem BETWEEN '$data_inicio' AND '$data_fim'
                GROUP BY b.id
                HAVING dias > 0";

if ($raca_id > 0) {
    $sqlRanking .= " AND b.id_raca = $raca_id";
}

// Ordenação
switch ($ordenar) {
    case 'ganho_diario_desc':
        $sqlRanking .= " ORDER BY ganho_diario DESC";
        break;
    case 'ganho_diario_asc':
        $sqlRanking .= " ORDER BY ganho_diario ASC";
        break;
    case 'ganho_total_desc':
        $sqlRanking .= " ORDER BY ganho_total DESC";
        break;
    case 'peso_final_desc':
        $sqlRanking .= " ORDER BY peso_final DESC";
        break;
    default:
        $sqlRanking .= " ORDER BY ganho_diario DESC";
}

$ranking = executeQuery($sqlRanking);

// ============================================
// 3. EVOLUÇÃO DE PESO POR ANIMAL (se selecionado)
// ============================================
$evolucao = null;
if ($bovino_id > 0) {
    $sqlEvolucao = "SELECT 
                        p.data_pesagem,
                        p.peso,
                        DATEDIFF(p.data_pesagem, MIN(p.data_pesagem) OVER()) as dias,
                        p.peso - FIRST_VALUE(p.peso) OVER(ORDER BY p.data_pesagem) as ganho_acumulado
                    FROM pesagens p
                    WHERE p.id_bovino = $bovino_id
                    AND p.data_pesagem BETWEEN '$data_inicio' AND '$data_fim'
                    ORDER BY p.data_pesagem ASC";
    $evolucao = executeQuery($sqlEvolucao);
    
    // Dados do bovino selecionado
    $sqlBovinoSel = "SELECT b.*, r.nome_raca 
                     FROM bovinos b
                     LEFT JOIN racas r ON b.id_raca = r.id
                     WHERE b.id = $bovino_id";
    $resultBovinoSel = executeQuery($sqlBovinoSel);
    $bovinoSel = $resultBovinoSel->fetch_assoc();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-bar-chart me-2 text-success"></i>
                Relatório de Ganho de Peso
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Ganho de Peso</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="exportar.php?tipo=csv&relatorio=pesagens" class="btn btn-success">
                <i class="bi bi-download"></i> Exportar
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
                <div class="col-md-3">
                    <label class="form-label">Animal</label>
                    <select class="form-select" name="bovino_id">
                        <option value="0">Todos</option>
                        <?php if ($bovinos && $bovinos->num_rows > 0): ?>
                            <?php while ($b = $bovinos->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $bovino_id == $b['id'] ? 'selected' : ''; ?>>
                                <?php echo $b['brinco']; ?> - <?php echo $b['nome'] ?: 'Sem nome'; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Raça</label>
                    <select class="form-select" name="raca_id">
                        <option value="0">Todas</option>
                        <?php if ($racas && $racas->num_rows > 0): ?>
                            <?php while ($r = $racas->fetch_assoc()): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $raca_id == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo $r['nome_raca']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordenar por</label>
                    <select class="form-select" name="ordenar">
                        <option value="ganho_diario_desc" <?php echo $ordenar == 'ganho_diario_desc' ? 'selected' : ''; ?>>Maior ganho diário</option>
                        <option value="ganho_diario_asc" <?php echo $ordenar == 'ganho_diario_asc' ? 'selected' : ''; ?>>Menor ganho diário</option>
                        <option value="ganho_total_desc" <?php echo $ordenar == 'ganho_total_desc' ? 'selected' : ''; ?>>Maior ganho total</option>
                        <option value="peso_final_desc" <?php echo $ordenar == 'peso_final_desc' ? 'selected' : ''; ?>>Maior peso final</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                    <h6>Animais Pesados</h6>
                    <h3><?php echo $stats['total_animais_pesados'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total de Pesagens</h6>
                    <h3><?php echo $stats['total_pesagens'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Peso Médio</h6>
                    <h3><?php echo number_format($stats['peso_medio'] ?? 0, 2, ',', '.'); ?> kg</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Maior Peso</h6>
                    <h3><?php echo number_format($stats['peso_maximo'] ?? 0, 2, ',', '.'); ?> kg</h3>
                </div>
            </div>
        </div>
    </div>

    <?php if ($bovino_id > 0 && $evolucao && $evolucao->num_rows > 0): ?>
    <!-- Gráfico de Evolução do Animal Selecionado -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">
                Evolução de Peso - <?php echo $bovinoSel['brinco']; ?> 
                (<?php echo $bovinoSel['nome'] ?: ''; ?>)
            </h6>
        </div>
        <div class="card-body">
            <canvas id="graficoEvolucao" style="height: 300px;"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ranking de Ganho de Peso -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Ranking de Ganho de Peso</h6>
            <span class="badge bg-primary"><?php echo $ranking ? $ranking->num_rows : 0; ?> animais</span>
        </div>
        <div class="card-body p-0">
            <?php if ($ranking && $ranking->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Animal</th>
                            <th>Raça</th>
                            <th>Pesagens</th>
                            <th>Peso Inicial</th>
                            <th>Peso Final</th>
                            <th>Ganho Total</th>
                            <th>Dias</th>
                            <th>Ganho Diário</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $ranking->fetch_assoc()): 
                            $corGanho = $r['ganho_diario'] >= 0.5 ? 'success' : ($r['ganho_diario'] >= 0.3 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $r['id']; ?>">
                                    <strong><?php echo $r['brinco']; ?></strong>
                                </a>
                                <?php if ($r['nome']): ?>
                                    <br><small><?php echo $r['nome']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $r['nome_raca'] ?: '-'; ?></td>
                            <td><?php echo $r['num_pesagens']; ?></td>
                            <td><?php echo number_format($r['peso_inicial'], 2, ',', '.'); ?> kg</td>
                            <td><strong><?php echo number_format($r['peso_final'], 2, ',', '.'); ?> kg</strong></td>
                            <td class="text-<?php echo $r['ganho_total'] >= 0 ? 'success' : 'danger'; ?>">
                                <?php echo $r['ganho_total'] >= 0 ? '+' : ''; ?>
                                <?php echo number_format($r['ganho_total'], 2, ',', '.'); ?> kg
                            </td>
                            <td><?php echo $r['dias']; ?> dias</td>
                            <td>
                                <span class="badge bg-<?php echo $corGanho; ?>">
                                    <?php echo number_format($r['ganho_diario'], 3, ',', '.'); ?> kg/dia
                                </span>
                            </td>
                            <td>
                                <a href="?bovino_id=<?php echo $r['id']; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="bi bi-graph-up"></i> Ver Gráfico
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bar-chart display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum dado encontrado</h4>
                <p class="text-muted">Não há pesagens no período selecionado.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php if ($bovino_id > 0 && $evolucao && $evolucao->num_rows > 0): ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
<?php
$dadosGrafico = [];
while ($e = $evolucao->fetch_assoc()) {
    $dadosGrafico[] = $e;
}
?>
const ctx = document.getElementById('graficoEvolucao').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($dadosGrafico, 'data_pesagem')); ?>,
        datasets: [
            {
                label: 'Peso (kg)',
                data: <?php echo json_encode(array_column($dadosGrafico, 'peso')); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.1,
                fill: true,
                yAxisID: 'y'
            },
            {
                label: 'Ganho Acumulado (kg)',
                data: <?php echo json_encode(array_column($dadosGrafico, 'ganho_acumulado')); ?>,
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                tension: 0.1,
                fill: true,
                yAxisID: 'y1'
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
                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' kg';
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Peso (kg)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Ganho Acumulado (kg)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>