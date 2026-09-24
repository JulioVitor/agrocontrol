<?php
// modules/bovinos/acoes/leite/graficos.php
// Gráficos de produção de leite

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

$pageTitle = 'Gráficos de Produção de Leite';

// Filtro de período
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '30';
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;

// Definir intervalo de datas
$dataFim = date('Y-m-d');
$dataInicio = date('Y-m-d', strtotime("-$periodo days"));

// Buscar produção do período
if ($bovino_id > 0) {
    // Gráfico de um animal específico
    $sql = "SELECT pl.*, b.brinco, b.nome as nome_bovino
            FROM producao_leite pl
            JOIN bovinos b ON pl.id_bovino = b.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            AND pl.id_bovino = $bovino_id
            AND pl.data_producao BETWEEN '$dataInicio' AND '$dataFim'
            ORDER BY pl.data_producao, FIELD(pl.turno, 'manha', 'tarde', 'noite', 'unico')";
    
    $sqlBovino = "SELECT id, brinco, nome FROM bovinos WHERE id = $bovino_id";
    $resBovino = executeQuery($sqlBovino);
    $bovino = $resBovino->fetch_assoc();
} else {
    // Gráfico geral da fazenda
    $sql = "SELECT pl.*, b.brinco, b.nome as nome_bovino
            FROM producao_leite pl
            JOIN bovinos b ON pl.id_bovino = b.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            AND pl.data_producao BETWEEN '$dataInicio' AND '$dataFim'
            ORDER BY pl.data_producao";
}

$result = executeQuery($sql);

// Organizar dados para os gráficos
$dados = [];
$totaisPorDia = [];
$topAnimais = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data = $row['data_producao'];
        
        if (!isset($totaisPorDia[$data])) {
            $totaisPorDia[$data] = 0;
        }
        $totaisPorDia[$data] += $row['quantidade_litros'];
        
        if (!isset($topAnimais[$row['brinco']])) {
            $topAnimais[$row['brinco']] = 0;
        }
        $topAnimais[$row['brinco']] += $row['quantidade_litros'];
    }
}

// Preparar arrays para o Chart.js
$datas = array_keys($totaisPorDia);
$valores = array_values($totaisPorDia);

// Ordenar top animais
arsort($topAnimais);
$topAnimais = array_slice($topAnimais, 0, 10, true);

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-graph-up me-2 text-success"></i>
                Gráficos de Produção de Leite
                <?php if (isset($bovino)): ?>
                    - <?php echo $bovino['brinco']; ?>
                <?php endif; ?>
            </h1>
        </div>
        <div>
            <a href="diario.php" class="btn btn-outline-info">
                <i class="bi bi-calendar"></i> Produção Diária
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Período</label>
                    <select class="form-select" name="periodo">
                        <option value="7" <?php echo $periodo == '7' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                        <option value="15" <?php echo $periodo == '15' ? 'selected' : ''; ?>>Últimos 15 dias</option>
                        <option value="30" <?php echo $periodo == '30' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                        <option value="60" <?php echo $periodo == '60' ? 'selected' : ''; ?>>Últimos 60 dias</option>
                        <option value="90" <?php echo $periodo == '90' ? 'selected' : ''; ?>>Últimos 90 dias</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Animal</label>
                    <select class="form-select" name="bovino_id">
                        <option value="0">Todos os animais</option>
                        <?php
                        $sqlAnimais = "SELECT id, brinco, nome FROM bovinos 
                                      WHERE " . TenantManager::addTenantFilter() . " 
                                      AND sexo = 'F' AND ativo = 1 
                                      ORDER BY brinco";
                        $animais = executeQuery($sqlAnimais);
                        if ($animais && $animais->num_rows > 0):
                            while ($a = $animais->fetch_assoc()):
                        ?>
                        <option value="<?php echo $a['id']; ?>" 
                            <?php echo ($bovino_id == $a['id']) ? 'selected' : ''; ?>>
                            <?php echo $a['brinco']; ?> - <?php echo $a['nome'] ?: 'Sem nome'; ?>
                        </option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
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

    <!-- Gráficos -->
    <div class="row g-4">
        <!-- Gráfico de Produção Diária -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>
                        Produção Diária
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoProducao" style="height: 400px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Top Animais -->
        <?php if ($bovino_id == 0 && !empty($topAnimais)): ?>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-trophy me-2 text-warning"></i>
                        Top 10 Animais (Total do Período)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoTop" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Card de Resumo -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2 text-info"></i>
                        Resumo do Período
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $totalPeriodo = array_sum($valores);
                    $mediaDiaria = count($valores) > 0 ? $totalPeriodo / count($valores) : 0;
                    $diasComProducao = count($valores);
                    ?>
                    <table class="table table-borderless">
                        <tr>
                            <th>Total Produzido:</th>
                            <td><strong><?php echo number_format($totalPeriodo, 2, ',', '.'); ?> L</strong></td>
                        </tr>
                        <tr>
                            <th>Média Diária:</th>
                            <td><strong><?php echo number_format($mediaDiaria, 2, ',', '.'); ?> L/dia</strong></td>
                        </tr>
                        <tr>
                            <th>Dias com Produção:</th>
                            <td><strong><?php echo $diasComProducao; ?> dias</strong></td>
                        </tr>
                        <tr>
                            <th>Melhor Dia:</th>
                            <td>
                                <?php
                                if (!empty($valores)) {
                                    $maxIndex = array_search(max($valores), $valores);
                                    echo date('d/m/Y', strtotime($datas[$maxIndex])) . ' - ';
                                    echo number_format($valores[$maxIndex], 2, ',', '.') . ' L';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- CDN Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script>
    // Gráfico de Produção Diária
    const ctxProducao = document.getElementById('graficoProducao').getContext('2d');
    new Chart(ctxProducao, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($data) {
                return date('d/m', strtotime($data));
            }, $datas)); ?>,
            datasets: [{
                label: 'Litros',
                data: <?php echo json_encode($valores); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 3,
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

    <?php if ($bovino_id == 0 && !empty($topAnimais)): ?>
    // Gráfico de Top Animais
    const ctxTop = document.getElementById('graficoTop').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($topAnimais)); ?>,
            datasets: [{
                label: 'Total (L)',
                data: <?php echo json_encode(array_values($topAnimais)); ?>,
                backgroundColor: '#ffc107',
                borderColor: '#ffc107',
                borderWidth: 1
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
    <?php endif; ?>
    </script>
</main>

<?php include '../../../../includes/footer.php'; ?>