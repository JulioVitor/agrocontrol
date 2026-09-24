<?php
// modules/reproducao/index.php
// Dashboard de reprodução


ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$pageTitle = 'Reprodução';

// Estatísticas
$stats = [];

// Total de matrizes (fêmeas ativas)
$sql = "SELECT COUNT(*) as total FROM bovinos 
        WHERE " . TenantManager::addTenantFilter() . " 
        AND sexo = 'F' AND ativo = 1";
$result = executeQuery($sql);
$stats['total_matrizes'] = $result->fetch_assoc()['total'];

// Matrizes por status
$sql = "SELECT status_reprodutivo, COUNT(*) as total 
        FROM matrizes m
        JOIN bovinos b ON m.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        GROUP BY status_reprodutivo";
$result = executeQuery($sql);
$stats['status'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['status'][$row['status_reprodutivo']] = $row['total'];
}

// Próximos partos (próximos 30 dias)
$sql = "SELECT r.*, b.brinco, b.nome 
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND r.tipo_evento = 'prenhez'
        AND r.confirmada = 1
        AND r.data_prevista_parto BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY r.data_prevista_parto ASC";
$proximosPartos = executeQuery($sql);

// Últimos cios registrados
$sql = "SELECT r.*, b.brinco, b.nome 
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND r.tipo_evento = 'cio'
        ORDER BY r.data_evento DESC
        LIMIT 10";
$ultimosCios = executeQuery($sql);

// Últimas inseminações
$sql = "SELECT r.*, b.brinco, b.nome, m.brinco as touro_brinco
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        LEFT JOIN bovinos m ON r.id_bovino_macho = m.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND r.tipo_evento = 'inseminacao'
        ORDER BY r.data_evento DESC
        LIMIT 10";
$ultimasInseminacoes = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-heart me-2 text-success"></i>
                Reprodução
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reprodução</li>
                </ol>
            </nav>
        </div>
        <div>
            <div class="btn-group">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-circle"></i> Novo Registro
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="novo_cio.php"><i class="bi bi-heart text-danger"></i> Registrar Cio</a></li>
                    <li><a class="dropdown-item" href="nova_inseminacao.php"><i class="bi bi-syringe text-warning"></i> Inseminação</a></li>
                    <li><a class="dropdown-item" href="novo_parto.php"><i class="bi bi-egg text-success"></i> Registrar Parto</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="matrizes.php"><i class="bi bi-person-arms-up"></i> Matrizes</a></li>
                    <li><a class="dropdown-item" href="touros.php"><i class="bi bi-gender-male"></i> Touros</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Matrizes</h6>
                    <h2><?php echo $stats['total_matrizes']; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Prenhes</h6>
                    <h2><?php echo $stats['status']['prenhe'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Em Lactação</h6>
                    <h2><?php echo $stats['status']['lactacao'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Vazias</h6>
                    <h2><?php echo $stats['status']['vazia'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Próximos Partos -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="bi bi-calendar-event text-success me-2"></i>
                Próximos Partos (30 dias)
            </h6>
            <a href="gestacoes.php" class="btn btn-sm btn-outline-success">Ver Todas</a>
        </div>
        <div class="card-body p-0">
            <?php if ($proximosPartos && $proximosPartos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Matriz</th>
                            <th>Data Prevista</th>
                            <th>Dias</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hoje = new DateTime();
                        while ($parto = $proximosPartos->fetch_assoc()): 
                            $dataPrevista = new DateTime($parto['data_prevista_parto']);
                            $diasRestantes = $hoje->diff($dataPrevista)->days;
                            $classe = $diasRestantes <= 7 ? 'danger' : ($diasRestantes <= 15 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $parto['brinco']; ?></strong>
                                <?php if ($parto['nome']): ?>
                                    <br><small><?php echo $parto['nome']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $dataPrevista->format('d/m/Y'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $classe; ?>">
                                    <?php echo $diasRestantes; ?> dias
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($diasRestantes <= 0) {
                                    echo '<span class="badge bg-danger">Atrasado</span>';
                                } elseif ($diasRestantes <= 7) {
                                    echo '<span class="badge bg-warning">Próximo</span>';
                                } else {
                                    echo '<span class="badge bg-success">Normal</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="novo_parto.php?femea_id=<?php echo $parto['id_bovino_femea']; ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="bi bi-egg"></i> Registrar Parto
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-calendar-check display-4 text-muted"></i>
                <p class="text-muted mt-2">Nenhum parto previsto para os próximos 30 dias.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Últimos Eventos -->
    <div class="row g-3">
        <!-- Últimos Cios -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-heart text-danger me-2"></i>
                        Últimos Cios
                    </h6>
                    <a href="cios.php" class="btn btn-sm btn-outline-danger">Ver Todos</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($ultimosCios && $ultimosCios->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($cio = $ultimosCios->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $cio['brinco']; ?></strong>
                                    <?php if ($cio['nome']): ?>
                                        <br><small><?php echo $cio['nome']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?php echo date('d/m/Y', strtotime($cio['data_evento'])); ?></span>
                                    <br>
                                    <small class="text-muted"><?php echo $cio['observacoes'] ?: '-'; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhum cio registrado.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimas Inseminações -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-syringe text-warning me-2"></i>
                        Últimas Inseminações
                    </h6>
                    <a href="inseminacoes.php" class="btn btn-sm btn-outline-warning">Ver Todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($ultimasInseminacoes && $ultimasInseminacoes->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($ins = $ultimasInseminacoes->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="row">
                                <div class="col-6">
                                    <strong><?php echo $ins['brinco']; ?></strong>
                                    <?php if ($ins['nome']): ?>
                                        <br><small><?php echo $ins['nome']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="badge bg-warning"><?php echo date('d/m/Y', strtotime($ins['data_evento'])); ?></span>
                                    <br>
                                    <small>
                                        Touro: <?php echo $ins['touro_brinco'] ?: $ins['semen_touro'] ?: 'Não informado'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma inseminação registrada.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendário Reprodutivo -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-heart text-success me-2"></i>
                        Ciclo Reprodutivo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="bg-light p-3 rounded">
                                <i class="bi bi-heart text-danger fs-1"></i>
                                <h6>Cio</h6>
                                <small>Dia 0</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-light p-3 rounded">
                                <i class="bi bi-syringe text-warning fs-1"></i>
                                <h6>Inseminação</h6>
                                <small>Dia 0</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-light p-3 rounded">
                                <i class="bi bi-check-circle text-info fs-1"></i>
                                <h6>Diagnóstico</h6>
                                <small>30-45 dias</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-light p-3 rounded">
                                <i class="bi bi-heart-fill text-success fs-1"></i>
                                <h6>Prenhez</h6>
                                <small>283 dias</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-light p-3 rounded">
                                <i class="bi bi-egg text-primary fs-1"></i>
                                <h6>Parto</h6>
                                <small>Dia 283</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="bg-light p-3 rounded">
                                <i class="bi bi-cup text-success fs-1"></i>
                                <h6>Lactação</h6>
                                <small>60-90 dias</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>