<?php
// modules/relatorios/producao.php
// Relatório de produção de leite

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

$pageTitle = 'Relatório de Produção de Leite';

// Filtros
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;

// Definir intervalo
$data_inicio = "$ano-$mes-01";
$data_fim = date('Y-m-t', strtotime($data_inicio));

// Totais do período
$sql = "SELECT 
            SUM(pl.quantidade_litros) as total_litros,
            COUNT(DISTINCT pl.data_producao) as dias_com_producao,
            COUNT(DISTINCT pl.id_bovino) as animais_em_lactacao,
            AVG(pl.quantidade_litros) as media_por_registro
        FROM producao_leite pl
        JOIN bovinos b ON pl.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND pl.data_producao BETWEEN '$data_inicio' AND '$data_fim'";

if ($bovino_id > 0) {
    $sql .= " AND pl.id_bovino = $bovino_id";
}

$result = executeQuery($sql);
$totais = $result->fetch_assoc();

// Produção por dia
$sqlDias = "SELECT 
                pl.data_producao,
                SUM(pl.quantidade_litros) as total_dia,
                COUNT(DISTINCT pl.id_bovino) as animais_dia
            FROM producao_leite pl
            JOIN bovinos b ON pl.id_bovino = b.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            AND pl.data_producao BETWEEN '$data_inicio' AND '$data_fim'";

if ($bovino_id > 0) {
    $sqlDias .= " AND pl.id_bovino = $bovino_id";
}

$sqlDias .= " GROUP BY pl.data_producao ORDER BY pl.data_producao";
$dias = executeQuery($sqlDias);

// Top produtoras
$sqlTop = "SELECT 
                b.id,
                b.brinco,
                b.nome,
                SUM(pl.quantidade_litros) as total_animal,
                COUNT(DISTINCT pl.data_producao) as dias_animal,
                AVG(pl.quantidade_litros) as media_animal
            FROM producao_leite pl
            JOIN bovinos b ON pl.id_bovino = b.id
            WHERE " . TenantManager::addTenantFilter('b') . "
            AND pl.data_producao BETWEEN '$data_inicio' AND '$data_fim'
            GROUP BY b.id
            ORDER BY total_animal DESC
            LIMIT 10";
$topProdutoras = executeQuery($sqlTop);

// Animais em lactação (para filtro)
$sqlAnimais = "SELECT DISTINCT b.id, b.brinco, b.nome
               FROM bovinos b
               JOIN producao_leite pl ON b.id = pl.id_bovino
               WHERE " . TenantManager::addTenantFilter('b') . "
               AND b.sexo = 'F'
               ORDER BY b.brinco";
$animais = executeQuery($sqlAnimais);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-cup-fill me-2 text-success"></i>
                Relatório de Produção de Leite
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Produção de Leite</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="exportar.php?tipo=pdf&relatorio=producao&ano=<?php echo $ano; ?>&mes=<?php echo $mes; ?>" class="btn btn-success">
                <i class="bi bi-file-pdf"></i> Exportar PDF
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Ano</label>
                    <select class="form-select" name="ano">
                        <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $ano == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mês</label>
                    <select class="form-select" name="mes">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $mes == $m ? 'selected' : ''; ?>>
                            <?php echo strftime('%B', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Animal</label>
                    <select class="form-select" name="bovino_id">
                        <option value="0">Todos os animais</option>
                        <?php if ($animais && $animais->num_rows > 0): ?>
                            <?php while ($a = $animais->fetch_assoc()): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo $bovino_id == $a['id'] ? 'selected' : ''; ?>>
                                <?php echo $a['brinco']; ?> - <?php echo $a['nome'] ?: 'Sem nome'; ?>
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
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Produzido</h6>
                    <h3><?php echo number_format($totais['total_litros'] ?? 0, 2, ',', '.'); ?> L</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Média Diária</h6>
                    <h3>
                        <?php 
                        $mediaDiaria = ($totais['dias_com_producao'] ?? 0) > 0 
                            ? ($totais['total_litros'] ?? 0) / $totais['dias_com_producao'] 
                            : 0;
                        echo number_format($mediaDiaria, 2, ',', '.'); ?> L
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Dias com Produção</h6>
                    <h3><?php echo $totais['dias_com_producao'] ?? 0; ?> dias</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Animais em Lactação</h6>
                    <h3><?php echo $totais['animais_em_lactacao'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Produção Diária -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Produção Diária</h6>
        </div>
        <div class="card-body">
            <canvas id="graficoProducao" style="height: 400px;"></canvas>
        </div>
    </div>

    <!-- Top Produtoras -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Top 10 Produtoras</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Animal</th>
                                <th>Total (L)</th>
                                <th>Dias</th>
                                <th>Média (L/dia)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topProdutoras && $topProdutoras->num_rows > 0): ?>
                                <?php while ($top = $topProdutoras->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $top['brinco']; ?></strong>
                                        <?php if ($top['nome']): ?><br><small><?php echo $top['nome']; ?></small><?php endif; ?>
                                    </td>
                                    <td><strong><?php echo number_format($top['total_animal'], 2, ',', '.'); ?> L</strong></td>
                                    <td><?php echo $top['dias_animal']; ?></td>
                                    <td><?php echo number_format($top['media_animal'], 2, ',', '.'); ?> L</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">
                                        Nenhum dado disponível.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resumo Mensal -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Resumo do Período</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Total de litros:</th>
                            <td><strong><?php echo number_format($totais['total_litros'] ?? 0, 2, ',', '.'); ?> L</strong></td>
                        </tr>
                        <tr>
                            <th>Média por dia:</th>
                            <td><strong><?php echo number_format($mediaDiaria, 2, ',', '.'); ?> L/dia</strong></td>
                        </tr>
                        <tr>
                            <th>Média por animal:</th>
                            <td>
                                <strong>
                                    <?php 
                                    $mediaAnimal = ($totais['animais_em_lactacao'] ?? 0) > 0 
                                        ? ($totais['total_litros'] ?? 0) / $totais['animais_em_lactacao'] 
                                        : 0;
                                    echo number_format($mediaAnimal, 2, ',', '.'); ?> L/animal
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <th>Melhor dia:</th>
                            <td>
                                <?php
                                if ($dias && $dias->num_rows > 0) {
                                    $melhorDia = null;
                                    $maiorProducao = 0;
                                    while ($d = $dias->fetch_assoc()) {
                                        if ($d['total_dia'] > $maiorProducao) {
                                            $maiorProducao = $d['total_dia'];
                                            $melhorDia = $d['data_producao'];
                                        }
                                    }
                                    if ($melhorDia) {
                                        echo date('d/m/Y', strtotime($melhorDia)) . ' - ';
                                        echo number_format($maiorProducao, 2, ',', '.') . ' L';
                                    }
                                    $dias->data_seek(0);
                                }
                                ?>
                            </td>
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
<?php if ($dias && $dias->num_rows > 0): ?>
// Preparar dados para o gráfico
const datas = [];
const valores = [];

<?php while ($dia = $dias->fetch_assoc()): ?>
datas.push('<?php echo date('d/m', strtotime($dia['data_producao'])); ?>');
valores.push(<?php echo $dia['total_dia']; ?>);
<?php endwhile; ?>

// Gráfico de Produção Diária
const ctx = document.getElementById('graficoProducao').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: datas,
        datasets: [{
            label: 'Litros',
            data: valores,
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
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>