<?php
// modules/bovinos/acoes/pesagem/graficos.php
// Gráficos de evolução de peso

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

// Verificar se recebeu ID do bovino
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;
if ($bovino_id <= 0) {
    setAlert('ID do bovino inválido.', 'danger');
    redirect(BASE_URL . 'modules/bovinos/index.php');
}

// Buscar dados do bovino
$sqlBovino = "SELECT id, brinco, nome, peso_atual, data_nascimento FROM bovinos WHERE id = $bovino_id AND " . TenantManager::addTenantFilter();
$resultBovino = executeQuery($sqlBovino);

if (!$resultBovino || $resultBovino->num_rows == 0) {
    setAlert('Bovino não encontrado.', 'danger');
    redirect(BASE_URL . 'modules/bovinos/index.php');
}

$bovino = $resultBovino->fetch_assoc();
$pageTitle = 'Gráficos - ' . ($bovino['nome'] ?: $bovino['brinco']);

// Buscar todas as pesagens ordenadas por data
$sql = "SELECT * FROM pesagens 
        WHERE id_bovino = $bovino_id 
        ORDER BY data_pesagem ASC";
$pesagens = executeQuery($sql);

// Preparar dados para os gráficos
$datas = [];
$pesos = [];
$ganhos = [];
$pesoAnterior = null;

if ($pesagens && $pesagens->num_rows > 0) {
    while ($p = $pesagens->fetch_assoc()) {
        $datas[] = date('d/m/Y', strtotime($p['data_pesagem']));
        $pesos[] = $p['peso'];
        
        if ($pesoAnterior) {
            $ganhos[] = $p['peso'] - $pesoAnterior;
        } else {
            $ganhos[] = 0;
        }
        $pesoAnterior = $p['peso'];
    }
    $pesagens->data_seek(0);
}

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-graph-up me-2 text-info"></i>
                Gráficos de Peso - <?php echo $bovino['brinco']; ?>
                <?php if ($bovino['nome']): ?>
                    <small class="text-muted">(<?php echo $bovino['nome']; ?>)</small>
                <?php endif; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $bovino_id; ?>">Detalhes</a></li>
                    <li class="breadcrumb-item"><a href="index.php?bovino_id=<?php echo $bovino_id; ?>">Pesagens</a></li>
                    <li class="breadcrumb-item active">Gráficos</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="index.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>

    <?php if ($pesagens && $pesagens->num_rows > 1): ?>
    
    <!-- Gráfico de Evolução de Peso -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-line-chart me-2 text-success"></i>
                Evolução do Peso
            </h5>
        </div>
        <div class="card-body">
            <canvas id="graficoPeso" style="height: 400px;"></canvas>
        </div>
    </div>

    <!-- Gráfico de Ganho por Período -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-bar-chart me-2 text-warning"></i>
                Ganho de Peso por Período
            </h5>
        </div>
        <div class="card-body">
            <canvas id="graficoGanho" style="height: 400px;"></canvas>
        </div>
    </div>

    <!-- CDN Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script>
    // Gráfico de Evolução do Peso
    const ctxPeso = document.getElementById('graficoPeso').getContext('2d');
    new Chart(ctxPeso, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($datas); ?>,
            datasets: [{
                label: 'Peso (kg)',
                data: <?php echo json_encode($pesos); ?>,
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
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' kg';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Peso (kg)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Data'
                    }
                }
            }
        }
    });

    // Gráfico de Ganho por Período
    const ctxGanho = document.getElementById('graficoGanho').getContext('2d');
    new Chart(ctxGanho, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_slice($datas, 1)); ?>,
            datasets: [{
                label: 'Ganho de Peso (kg)',
                data: <?php echo json_encode(array_slice($ganhos, 1)); ?>,
                backgroundColor: function(context) {
                    const value = context.raw;
                    return value >= 0 ? 'rgba(40, 167, 69, 0.7)' : 'rgba(220, 53, 69, 0.7)';
                },
                borderColor: function(context) {
                    const value = context.raw;
                    return value >= 0 ? '#28a745' : '#dc3545';
                },
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y;
                            return (value >= 0 ? '+' : '') + value + ' kg';
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Ganho (kg)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Período'
                    }
                }
            }
        }
    });
    </script>

    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-graph-up display-1 text-muted"></i>
        <h4 class="mt-3">Dados insuficientes para gerar gráficos</h4>
        <p class="text-muted">São necessárias pelo menos 2 pesagens para gerar os gráficos.</p>
        <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Pesagem
        </a>
    </div>
    <?php endif; ?>
</main>

<?php include '../../../../includes/footer.php'; ?>