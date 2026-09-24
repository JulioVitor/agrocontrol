<?php
// modules/pastejos/acoes/manutencao/relatorio.php
// Relatório de manutenções do piquete

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

// Verificar se recebeu ID do piquete
$piquete_id = isset($_GET['piquete_id']) ? intval($_GET['piquete_id']) : 0;
if ($piquete_id <= 0) {
    setAlert('ID do piquete inválido.', 'danger');
    redirect('../../index.php');
}

// Filtros
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Buscar dados do piquete
$sqlPiquete = "SELECT * FROM piquetes WHERE id = $piquete_id AND " . TenantManager::addTenantFilter();
$resultPiquete = executeQuery($sqlPiquete);
$piquete = $resultPiquete->fetch_assoc();

// Construir query com filtros
$where = "id_piquete = $piquete_id";
if ($ano) {
    $where .= " AND YEAR(data_manutencao) = $ano";
}
if ($tipo) {
    $where .= " AND tipo_manutencao = '$tipo'";
}

// Buscar manutenções
$sql = "SELECT * FROM manutencao_piquete 
        WHERE $where 
        ORDER BY data_manutencao DESC";
$manutencoes = executeQuery($sql);

// Estatísticas
$totalGeral = 0;
$custoTotal = 0;
$porTipo = [];
$porMes = array_fill(1, 12, 0);
$porAno = [];

if ($manutencoes && $manutencoes->num_rows > 0) {
    while ($m = $manutencoes->fetch_assoc()) {
        $totalGeral++;
        $custoTotal += $m['custo'] ?: 0;
        
        $t = $m['tipo_manutencao'];
        $porTipo[$t] = ($porTipo[$t] ?? 0) + 1;
        
        $mes = date('n', strtotime($m['data_manutencao']));
        $porMes[$mes] = ($porMes[$mes] ?? 0) + 1;
        
        $a = date('Y', strtotime($m['data_manutencao']));
        $porAno[$a] = ($porAno[$a] ?? 0) + 1;
    }
    $manutencoes->data_seek(0);
}

// Anos disponíveis para filtro
$anosDisponiveis = array_keys($porAno);
if (empty($anosDisponiveis)) {
    $anosDisponiveis = [date('Y')];
}

$pageTitle = 'Relatório de Manutenções - ' . $piquete['nome_piquete'];

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-file-text me-2 text-success"></i>
                Relatório de Manutenções - <?php echo $piquete['nome_piquete']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $piquete_id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?piquete_id=<?php echo $piquete_id; ?>">Manutenções</a></li>
                    <li class="breadcrumb-item active">Relatório</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="index.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="piquete_id" value="<?php echo $piquete_id; ?>">
                
                <div class="col-md-4">
                    <label class="form-label">Ano</label>
                    <select class="form-select" name="ano">
                        <option value="">Todos</option>
                        <?php foreach ($anosDisponiveis as $a): ?>
                        <option value="<?php echo $a; ?>" <?php echo $ano == $a ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="adubacao" <?php echo $tipo == 'adubacao' ? 'selected' : ''; ?>>Adubação</option>
                        <option value="calagem" <?php echo $tipo == 'calagem' ? 'selected' : ''; ?>>Calagem</option>
                        <option value="roçada" <?php echo $tipo == 'roçada' ? 'selected' : ''; ?>>Roçada</option>
                        <option value="plantio" <?php echo $tipo == 'plantio' ? 'selected' : ''; ?>>Plantio</option>
                        <option value="limpeza" <?php echo $tipo == 'limpeza' ? 'selected' : ''; ?>>Limpeza</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success mt-4">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Manutenções</h6>
                    <h3><?php echo $totalGeral; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Custo Total</h6>
                    <h3>R$ <?php echo number_format($custoTotal, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Custo Médio</h6>
                    <h3>
                        R$ <?php 
                        $media = $totalGeral > 0 ? $custoTotal / $totalGeral : 0;
                        echo number_format($media, 2, ',', '.'); 
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Manutenções por Tipo</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoTipos" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Manutenções por Mês</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoMeses" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela detalhada -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Detalhamento das Manutenções</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Custo</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($manutencoes && $manutencoes->num_rows > 0): ?>
                            <?php while ($m = $manutencoes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($m['data_manutencao'])); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php 
                                        switch($m['tipo_manutencao']) {
                                            case 'adubacao': echo 'Adubação'; break;
                                            case 'calagem': echo 'Calagem'; break;
                                            case 'roçada': echo 'Roçada'; break;
                                            case 'plantio': echo 'Plantio'; break;
                                            case 'limpeza': echo 'Limpeza'; break;
                                            default: echo $m['tipo_manutencao'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $m['descricao'] ?: '-'; ?></td>
                                <td><?php echo $m['custo'] ? 'R$ ' . number_format($m['custo'], 2, ',', '.') : '-'; ?></td>
                                <td><?php echo $m['responsavel'] ?: '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    Nenhuma manutenção encontrada para os filtros selecionados.
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
<?php if (!empty($porTipo)): ?>
// Gráfico de Tipos
const ctxTipos = document.getElementById('graficoTipos').getContext('2d');
new Chart(ctxTipos, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_map(function($t) {
            $nomes = ['adubacao' => 'Adubação', 'calagem' => 'Calagem', 'roçada' => 'Roçada', 'plantio' => 'Plantio', 'limpeza' => 'Limpeza'];
            return $nomes[$t] ?? $t;
        }, array_keys($porTipo))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($porTipo)); ?>,
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#007bff', '#6c757d']
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
<?php endif; ?>

<?php if (array_sum($porMes) > 0): ?>
// Gráfico de Meses
const ctxMeses = document.getElementById('graficoMeses').getContext('2d');
new Chart(ctxMeses, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        datasets: [{
            data: <?php echo json_encode(array_values($porMes)); ?>,
            backgroundColor: '#28a745'
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
<?php endif; ?>
</script>

<?php include '../../../../includes/footer.php'; ?>