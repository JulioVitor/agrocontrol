<?php
// modules/pastejos/acoes/manutencao/index.php
// Listagem de manutenções de um piquete

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

// Buscar dados do piquete
$sqlPiquete = "SELECT * FROM piquetes WHERE id = $piquete_id AND " . TenantManager::addTenantFilter();
$resultPiquete = executeQuery($sqlPiquete);

if (!$resultPiquete || $resultPiquete->num_rows == 0) {
    setAlert('Piquete não encontrado.', 'danger');
    redirect('../../index.php');
}

$piquete = $resultPiquete->fetch_assoc();

// Processar exclusão
if (isset($_GET['delete'])) {
    $manutencao_id = intval($_GET['delete']);
    $sql = "DELETE FROM manutencao_piquete WHERE id = $manutencao_id AND id_piquete = $piquete_id";
    if (executeQuery($sql)) {
        setAlert('Manutenção excluída com sucesso!', 'success');
    } else {
        setAlert('Erro ao excluir manutenção.', 'danger');
    }
    redirect("index.php?piquete_id=$piquete_id");
}

// Buscar manutenções do piquete
$sql = "SELECT * FROM manutencao_piquete 
        WHERE id_piquete = $piquete_id 
        ORDER BY data_manutencao DESC";
$manutencoes = executeQuery($sql);

// Estatísticas
$totalManutencoes = $manutencoes ? $manutencoes->num_rows : 0;
$custoTotal = 0;
$tiposManutencao = [];

if ($manutencoes && $manutencoes->num_rows > 0) {
    while ($m = $manutencoes->fetch_assoc()) {
        $custoTotal += $m['custo'] ?: 0;
        $tiposManutencao[$m['tipo_manutencao']] = ($tiposManutencao[$m['tipo_manutencao']] ?? 0) + 1;
    }
    $manutencoes->data_seek(0); // Reset pointer
}

$pageTitle = 'Manutenções - ' . $piquete['nome_piquete'];

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-tools me-2 text-success"></i>
                Manutenções - <?php echo $piquete['nome_piquete']; ?>
                <?php if ($piquete['codigo']): ?>
                    <small class="text-muted">(<?php echo $piquete['codigo']; ?>)</small>
                <?php endif; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $piquete_id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item active">Manutenções</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="cadastrar.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Nova Manutenção
            </a>
            <a href="relatorio.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-info me-2">
                <i class="bi bi-file-text"></i> Relatório
            </a>
            <a href="../../visualizar.php?id=<?php echo $piquete_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Manutenções</h6>
                    <h3><?php echo $totalManutencoes; ?></h3>
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
                    <h6>Última Manutenção</h6>
                    <h3>
                        <?php 
                        if ($totalManutencoes > 0) {
                            $ultima = $manutencoes->fetch_assoc();
                            echo date('d/m/Y', strtotime($ultima['data_manutencao']));
                            $manutencoes->data_seek(0);
                        } else {
                            echo '---';
                        }
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de tipos de manutenção -->
    <?php if (!empty($tiposManutencao)): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Tipos de Manutenção</h6>
                </div>
                <div class="card-body">
                    <canvas id="graficoTipos" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Resumo por Tipo</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <?php 
                        $tiposNomes = [
                            'adubacao' => 'Adubação',
                            'calagem' => 'Calagem',
                            'roçada' => 'Roçada',
                            'plantio' => 'Plantio',
                            'limpeza' => 'Limpeza'
                        ];
                        foreach ($tiposManutencao as $tipo => $quantidade): 
                        ?>
                        <tr>
                            <td><span class="badge bg-info"><?php echo $tiposNomes[$tipo] ?? $tipo; ?></span></td>
                            <td><?php echo $quantidade; ?> vez(es)</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?php echo ($quantidade / $totalManutencoes) * 100; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabela de manutenções -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Histórico de Manutenções</h5>
            <span class="badge bg-primary"><?php echo $totalManutencoes; ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($manutencoes && $manutencoes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Custo (R$)</th>
                            <th>Responsável</th>
                            <th>Dias</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = $manutencoes->fetch_assoc()): 
                            $diasDesde = (new DateTime())->diff(new DateTime($m['data_manutencao']))->days;
                            $corDias = $diasDesde > 90 ? 'danger' : ($diasDesde > 60 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td><strong><?php echo date('d/m/Y', strtotime($m['data_manutencao'])); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $m['tipo_manutencao'] == 'adubacao' ? 'success' : 
                                        ($m['tipo_manutencao'] == 'calagem' ? 'info' : 
                                        ($m['tipo_manutencao'] == 'roçada' ? 'warning' : 
                                        ($m['tipo_manutencao'] == 'plantio' ? 'primary' : 'secondary'))); 
                                ?>">
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
                            <td><?php echo $m['custo'] ? number_format($m['custo'], 2, ',', '.') : '-'; ?></td>
                            <td><?php echo $m['responsavel'] ?: '-'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $corDias; ?>">
                                    <?php echo $diasDesde; ?> dias
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="editar.php?id=<?php echo $m['id']; ?>&piquete_id=<?php echo $piquete_id; ?>" 
                                       class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?piquete_id=<?php echo $piquete_id; ?>&delete=<?php echo $m['id']; ?>" 
                                       class="btn btn-outline-danger" title="Excluir"
                                       onclick="return confirm('Excluir esta manutenção?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-tools display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma manutenção registrada</h4>
                <p class="text-muted">Registre a primeira manutenção deste piquete.</p>
                <a href="cadastrar.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nova Manutenção
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<?php if (!empty($tiposManutencao)): ?>
<script>
const ctx = document.getElementById('graficoTipos').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($t) {
            $nomes = ['adubacao' => 'Adubação', 'calagem' => 'Calagem', 'roçada' => 'Roçada', 'plantio' => 'Plantio', 'limpeza' => 'Limpeza'];
            return $nomes[$t] ?? $t;
        }, array_keys($tiposManutencao))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($tiposManutencao)); ?>,
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
</script>
<?php endif; ?>

<?php include '../../../../includes/footer.php'; ?>