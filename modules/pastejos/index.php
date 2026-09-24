<?php
// modules/pastejos/index.php
// Listagem de piquetes da fazenda

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

$pageTitle = 'Pastagens / Piquetes';

// Buscar todos os piquetes da fazenda
$sql = "SELECT * FROM piquetes 
        WHERE " . TenantManager::addTenantFilter() . " 
        ORDER BY nome_piquete";
$piquetes = executeQuery($sql);

// Estatísticas
$totalPiquetes = $piquetes ? $piquetes->num_rows : 0;
$areaTotal = 0;
$piquetesDisponiveis = 0;
$piquetesOcupados = 0;
$ocupacaoTotal = 0;

if ($piquetes && $piquetes->num_rows > 0) {
    while ($p = $piquetes->fetch_assoc()) {
        $areaTotal += $p['area_hectares'] ?: 0;
        if ($p['disponivel']) {
            $piquetesDisponiveis++;
        } else {
            $piquetesOcupados++;
            $ocupacaoTotal += $p['lotacao_atual'] ?: 0;
        }
    }
    $piquetes->data_seek(0); // Reset pointer
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-map me-2 text-success"></i>
                Pastagens / Piquetes
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Pastagens</li>
                </ol>
            </nav>
        </div>
        <a href="cadastrar.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>
            Novo Piquete
        </a>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Piquetes</h6>
                    <h3><?php echo $totalPiquetes; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Área Total</h6>
                    <h3><?php echo number_format($areaTotal, 2, ',', '.'); ?> ha</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Disponíveis</h6>
                    <h3><?php echo $piquetesDisponiveis; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Ocupados</h6>
                    <h3><?php echo $piquetesOcupados; ?></h3>
                    <small><?php echo $ocupacaoTotal; ?> animais</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de piquetes -->
    <div class="row g-4">
        <?php if ($piquetes && $piquetes->num_rows > 0): ?>
            <?php while ($piquete = $piquetes->fetch_assoc()): 
                $ocupacaoPercentual = $piquete['capacidade_suporte'] > 0 
                    ? ($piquete['lotacao_atual'] / $piquete['capacidade_suporte']) * 100 
                    : 0;
                $corStatus = $piquete['disponivel'] ? 'success' : ($ocupacaoPercentual > 90 ? 'danger' : 'warning');
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm <?php echo !$piquete['disponivel'] ? 'border-warning' : ''; ?>">
                    <div class="card-header bg-<?php echo $corStatus; ?> text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-tree-fill me-2"></i>
                            <?php echo $piquete['nome_piquete']; ?>
                        </h5>
                        <span class="badge bg-light text-dark">
                            Cód: <?php echo $piquete['codigo'] ?: '---'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Área</small>
                                <strong><?php echo number_format($piquete['area_hectares'], 2, ',', '.'); ?> ha</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Dimensões</small>
                                <strong>
                                    <?php 
                                    if ($piquete['comprimento_m'] && $piquete['largura_m']) {
                                        echo $piquete['comprimento_m'] . ' x ' . $piquete['largura_m'] . ' m';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Tipo de Pasto</small>
                                <strong><?php echo $piquete['tipo_pasto'] ?: 'Não informado'; ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Capacidade</small>
                                <strong><?php echo $piquete['capacidade_suporte'] ?: '0'; ?> animais</strong>
                            </div>
                        </div>

                        <!-- Barra de ocupação -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Ocupação</small>
                                <small>
                                    <strong><?php echo $piquete['lotacao_atual'] ?: 0; ?>/<?php echo $piquete['capacidade_suporte'] ?: '∞'; ?></strong>
                                </small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo $corStatus; ?>" 
                                     style="width: <?php echo min($ocupacaoPercentual, 100); ?>%"></div>
                            </div>
                        </div>

                        <!-- Recursos -->
                        <div class="mb-3">
                            <?php if ($piquete['cerca_eletrica']): ?>
                                <span class="badge bg-info me-1"><i class="bi bi-lightning"></i> Cerca Elétrica</span>
                            <?php endif; ?>
                            <?php if ($piquete['agua_disponivel']): ?>
                                <span class="badge bg-info me-1"><i class="bi bi-droplet"></i> Água</span>
                            <?php endif; ?>
                            <?php if ($piquete['sombra_disponivel']): ?>
                                <span class="badge bg-info me-1"><i class="bi bi-cloud-sun"></i> Sombra</span>
                            <?php endif; ?>
                        </div>

                        <!-- Última ocupação/manutenção -->
                        <div class="small text-muted mb-3">
                            <?php if ($piquete['data_ultima_ocupacao']): ?>
                                <div><i class="bi bi-clock-history me-1"></i> Última ocupação: <?php echo date('d/m/Y', strtotime($piquete['data_ultima_ocupacao'])); ?></div>
                            <?php endif; ?>
                            <?php if ($piquete['data_ultima_manutencao']): ?>
                                <div><i class="bi bi-tools me-1"></i> Última manutenção: <?php echo date('d/m/Y', strtotime($piquete['data_ultima_manutencao'])); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Botões de ação -->
                        <div class="d-flex gap-2">
                            <a href="visualizar.php?id=<?php echo $piquete['id']; ?>" class="btn btn-sm btn-outline-info flex-grow-1">
                                <i class="bi bi-eye"></i> Detalhes
                            </a>
                            <?php if ($piquete['disponivel']): ?>
                                <a href="acoes/alocar.php?piquete_id=<?php echo $piquete['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-arrow-right-circle"></i> Alocar
                                </a>
                            <?php else: ?>
                                <a href="acoes/desocupar.php?piquete_id=<?php echo $piquete['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-arrow-left-circle"></i> Desocupar
                                </a>
                            <?php endif; ?>
                            <a href="editar.php?id=<?php echo $piquete['id']; ?>" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-map display-1 text-muted"></i>
                    <h4 class="mt-3">Nenhum piquete cadastrado</h4>
                    <p class="text-muted">Comece cadastrando o primeiro piquete da sua fazenda.</p>
                    <a href="cadastrar.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>
                        Cadastrar Primeiro Piquete
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>