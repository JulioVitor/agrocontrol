<?php
// modules/bovinos/acoes/vacinas/calendario.php
// Calendário de vacinas para todos os bovinos da fazenda

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

$pageTitle = 'Calendário de Vacinas';

// Buscar todas as vacinas a vencer nos próximos 60 dias
$sql = "SELECT av.*, 
               v.nome_vacina, 
               v.fabricante,
               b.brinco, 
               b.nome as nome_bovino,
               b.id as bovino_id
        FROM aplicacoes_vacinas av
        JOIN vacinas v ON av.id_vacina = v.id
        JOIN bovinos b ON av.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND av.proxima_dose IS NOT NULL
        AND av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        ORDER BY av.proxima_dose ASC";

$proximasVacinas = executeQuery($sql);

// Agrupar por mês
$vacinasPorMes = [];
$hoje = new DateTime();

if ($proximasVacinas && $proximasVacinas->num_rows > 0) {
    while ($v = $proximasVacinas->fetch_assoc()) {
        $mes = date('Y-m', strtotime($v['proxima_dose']));
        if (!isset($vacinasPorMes[$mes])) {
            $vacinasPorMes[$mes] = [];
        }
        $vacinasPorMes[$mes][] = $v;
    }
}

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-calendar-check me-2 text-success"></i>
                Calendário de Vacinas
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item active">Calendário de Vacinas</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="javascript:window.print()" class="btn btn-info">
                <i class="bi bi-printer me-2"></i>
                Imprimir
            </a>
        </div>
    </div>

    <!-- Próximas Vacinas -->
    <div class="row">
        <?php if (!empty($vacinasPorMes)): ?>
            <?php foreach ($vacinasPorMes as $mes => $vacinas): 
                $mesNome = strftime('%B de %Y', strtotime($mes . '-01'));
                $mesNome = ucfirst($mesNome);
            ?>
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-month me-2"></i>
                            <?php echo $mesNome; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Bovino</th>
                                        <th>Vacina</th>
                                        <th>Fabricante</th>
                                        <th>Dias</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vacinas as $vacina): 
                                        $dataProxima = new DateTime($vacina['proxima_dose']);
                                        $diasRestantes = $hoje->diff($dataProxima)->days;
                                        
                                        if ($dataProxima < $hoje) {
                                            $statusClass = 'danger';
                                            $statusText = 'Atrasada';
                                        } elseif ($diasRestantes <= 7) {
                                            $statusClass = 'warning';
                                            $statusText = 'Urgente';
                                        } elseif ($diasRestantes <= 15) {
                                            $statusClass = 'info';
                                            $statusText = 'Próxima';
                                        } else {
                                            $statusClass = 'success';
                                            $statusText = 'Agendada';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($vacina['proxima_dose'])); ?></strong>
                                        </td>
                                        <td>
                                            <a href="../../visualizar.php?id=<?php echo $vacina['bovino_id']; ?>">
                                                <strong><?php echo $vacina['brinco']; ?></strong>
                                                <?php if ($vacina['nome_bovino']): ?>
                                                    <br><small><?php echo $vacina['nome_bovino']; ?></small>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td><?php echo $vacina['nome_vacina']; ?></td>
                                        <td><?php echo $vacina['fabricante'] ?: '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $diasRestantes; ?> dias
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="cadastrar.php?bovino_id=<?php echo $vacina['bovino_id']; ?>" 
                                               class="btn btn-sm btn-success" title="Registrar Aplicação">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                            <a href="index.php?bovino_id=<?php echo $vacina['bovino_id']; ?>" 
                                               class="btn btn-sm btn-info" title="Ver Histórico">
                                                <i class="bi bi-clock-history"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-calendar-check display-1 text-muted"></i>
                    <h4 class="mt-3">Nenhuma vacina programada</h4>
                    <p class="text-muted">Nenhuma vacina com próxima dose nos próximos 60 dias.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Resumo -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Atrasadas</h6>
                    <h3>
                        <?php
                        $sqlAtrasadas = "SELECT COUNT(*) as total 
                                         FROM aplicacoes_vacinas av
                                         JOIN bovinos b ON av.id_bovino = b.id
                                         WHERE " . TenantManager::addTenantFilter('b') . "
                                         AND av.proxima_dose < CURDATE()";
                        $resAtrasadas = executeQuery($sqlAtrasadas);
                        echo $resAtrasadas->fetch_assoc()['total'];
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Próximos 7 dias</h6>
                    <h3>
                        <?php
                        $sqlProximas = "SELECT COUNT(*) as total 
                                       FROM aplicacoes_vacinas av
                                       JOIN bovinos b ON av.id_bovino = b.id
                                       WHERE " . TenantManager::addTenantFilter('b') . "
                                       AND av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                        $resProximas = executeQuery($sqlProximas);
                        echo $resProximas->fetch_assoc()['total'];
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Em dia</h6>
                    <h3>
                        <?php
                        $sqlEmDia = "SELECT COUNT(*) as total 
                                    FROM bovinos 
                                    WHERE " . TenantManager::addTenantFilter() . "
                                    AND id NOT IN (
                                        SELECT DISTINCT id_bovino 
                                        FROM aplicacoes_vacinas 
                                        WHERE proxima_dose < CURDATE()
                                    )";
                        $resEmDia = executeQuery($sqlEmDia);
                        echo $resEmDia->fetch_assoc()['total'];
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../../../includes/footer.php'; ?>