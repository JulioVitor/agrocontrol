<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
// modules/dashboard/index.php
// Dashboard principal do usuário

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

$pageTitle = 'Dashboard';
$activeFarm = TenantManager::getActiveFarm();

// Estatísticas da fazenda atual
$stats = [];

// 1. Total de bovinos
$sql = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter();
$result = executeQuery($sql);
$stats['total_bovinos'] = $result->fetch_assoc()['total'];

// 2. Total por sexo
$sql = "SELECT sexo, COUNT(*) as total 
        FROM bovinos 
        WHERE " . TenantManager::addTenantFilter() . " 
        GROUP BY sexo";
$result = executeQuery($sql);
$stats['sexo'] = ['M' => 0, 'F' => 0];
while ($row = $result->fetch_assoc()) {
    $stats['sexo'][$row['sexo']] = $row['total'];
}

// 3. Total por situação
$sql = "SELECT s.nome, s.cor, COUNT(b.id) as total 
        FROM situacoes s
        LEFT JOIN bovinos b ON s.id = b.id_situacao AND " . TenantManager::addTenantFilter('b')
    . " WHERE s.id_fazenda = $farmId
        GROUP BY s.id
        ORDER BY s.ordem";
$result = executeQuery($sql);
$stats['situacoes'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['situacoes'][] = $row;
}

// 4. Total de piquetes
$sql = "SELECT COUNT(*) as total FROM piquetes WHERE " . TenantManager::addTenantFilter();
$result = executeQuery($sql);
$stats['total_piquetes'] = $result->fetch_assoc()['total'];

// 5. Piquetes ocupados vs disponíveis
$sql = "SELECT 
            SUM(CASE WHEN disponivel = 1 THEN 1 ELSE 0 END) as disponiveis,
            SUM(CASE WHEN disponivel = 0 THEN 1 ELSE 0 END) as ocupados
        FROM piquetes 
        WHERE " . TenantManager::addTenantFilter();
$result = executeQuery($sql);
$piquetes = $result->fetch_assoc();
$stats['piquetes_disponiveis'] = $piquetes['disponiveis'] ?? 0;
$stats['piquetes_ocupados'] = $piquetes['ocupados'] ?? 0;

// 6. Produção de leite (últimos 7 dias)
$sql = "SELECT DATE(data_producao) as data, SUM(quantidade_litros) as total 
        FROM producao_leite pl
        JOIN bovinos b ON pl.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND data_producao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(data_producao)
        ORDER BY data_producao DESC";
$result = executeQuery($sql);
$stats['producao_leite'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['producao_leite'][$row['data']] = $row['total'];
}

// 7. Eventos próximos (próximos 7 dias)
$sql = "SELECT * FROM eventos 
        WHERE " . TenantManager::addTenantFilter() . "
        AND data_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        AND concluido = 0
        ORDER BY data_inicio ASC
        LIMIT 5";
$proximosEventos = executeQuery($sql);

// 8. Últimos animais cadastrados
$sql = "SELECT b.*, r.nome_raca, s.nome as situacao_nome 
        FROM bovinos b
        LEFT JOIN racas r ON b.id_raca = r.id
        LEFT JOIN situacoes s ON b.id_situacao = s.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        ORDER BY b.data_cadastro DESC
        LIMIT 5";
$recentBovinos = executeQuery($sql);

// 9. Alertas (animais para vacinar, etc)
$sql = "SELECT av.*, b.brinco, b.nome as nome_bovino, v.nome_vacina 
        FROM aplicacoes_vacinas av
        JOIN bovinos b ON av.id_bovino = b.id
        JOIN vacinas v ON av.id_vacina = v.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
        ORDER BY av.proxima_dose ASC";
$vacinasProximas = executeQuery($sql);

// Incluir header
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Conteúdo principal -->
<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho da página -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-speedometer2 me-2 text-success"></i>
            Dashboard
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Atualizar
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Exportar
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">PDF</a></li>
                    <li><a class="dropdown-item" href="#">Excel</a></li>
                    <li><a class="dropdown-item" href="#">Imprimir</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Informação da fazenda ativa (card resumido) -->
    <?php if ($activeFarm): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Fazenda ativa:</strong> <?php echo $activeFarm['nome_fazenda']; ?>
            <?php if ($activeFarm['cidade']): ?>
                - <?php echo $activeFarm['cidade']; ?>/<?php echo $activeFarm['estado']; ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Cards de estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase">Total de Animais</span>
                            <h2 class="mb-0 mt-2"><?php echo $stats['total_bovinos']; ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-tree-fill fs-1 text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small">
                        <span class="text-info">
                            <i class="bi bi-gender-male"></i> Machos: <?php echo $stats['sexo']['M']; ?>
                        </span>
                        <span class="ms-3 text-warning">
                            <i class="bi bi-gender-female"></i> Fêmeas: <?php echo $stats['sexo']['F']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🤖 Agente de IA -->
        <link rel="stylesheet" href="<?= BASE_URL ?>agente/css/agente.css">

        <div id="agente-widget">
            <div id="agente-chat"></div>
            <form id="agente-form">
                <input id="agente-input"
                    placeholder="Pergunte algo… ex: produção de leite da semana"
                    autocomplete="off">
                <button type="submit">Enviar</button>
            </form>
        </div>

        <script>
            window.AGENTE_BASE_URL = "<?= BASE_URL ?>";
            window.AGENTE_FAZENDA_ID = <?= (int)($_SESSION['fazenda_id'] ?? 0) ?>;
        </script>
        <script src="<?= BASE_URL ?>agente/js/agente.js?v=<?= time() ?>"></script>

        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase">Piquetes</span>
                            <h2 class="mb-0 mt-2"><?php echo $stats['total_piquetes']; ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-map-fill fs-1 text-info"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small">
                        <span class="text-success">
                            <i class="bi bi-check-circle"></i> Disponíveis: <?php echo $stats['piquetes_disponiveis']; ?>
                        </span>
                        <span class="ms-3 text-warning">
                            <i class="bi bi-exclamation-circle"></i> Ocupados: <?php echo $stats['piquetes_ocupados']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase">Produção de Leite</span>
                            <h2 class="mb-0 mt-2"><?php echo array_sum($stats['producao_leite']); ?> L</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-cup-fill fs-1 text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small">
                        Últimos 7 dias
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase">Eventos</span>
                            <h2 class="mb-0 mt-2"><?php echo $proximosEventos ? $proximosEventos->num_rows : 0; ?></h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-calendar-event-fill fs-1 text-danger"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small">
                        Próximos 7 dias
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e informações detalhadas -->
    <div class="row g-3 mb-4">
        <!-- Gráfico de Situação dos Animais -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <i class="bi bi-pie-chart me-2 text-success"></i>
                    Situação do Rebanho
                </div>
                <div class="card-body">
                    <canvas id="graficoSituacao" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Últimos eventos -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-calendar-check me-2 text-success"></i>
                        Próximos Eventos
                    </div>
                    <a href="<?php echo BASE_URL; ?>modules/calendario/index.php" class="btn btn-sm btn-outline-success">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($proximosEventos && $proximosEventos->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($evento = $proximosEventos->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo $evento['titulo']; ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($evento['data_inicio'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $evento['cor'] ?? 'secondary'; ?>">
                                            <?php echo $evento['tipo']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x display-6"></i>
                            <p class="mt-2">Nenhum evento programado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Últimos animais cadastrados -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-clock-history me-2 text-success"></i>
                        Últimos Animais Cadastrados
                    </div>
                    <a href="<?php echo BASE_URL; ?>modules/bovinos/index.php" class="btn btn-sm btn-outline-success">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recentBovinos && $recentBovinos->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Brinco</th>
                                        <th>Nome</th>
                                        <th>Raça</th>
                                        <th>Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($bovino = $recentBovinos->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>modules/bovinos/visualizar.php?id=<?php echo $bovino['id']; ?>">
                                                    <?php echo $bovino['brinco']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $bovino['nome'] ?: '-'; ?></td>
                                            <td><?php echo $bovino['nome_raca'] ?: '-'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $bovino['cor'] ?? 'secondary'; ?>">
                                                    <?php echo $bovino['situacao_nome']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-tree display-6"></i>
                            <p class="mt-2">Nenhum animal cadastrado</p>
                            <a href="<?php echo BASE_URL; ?>modules/bovinos/cadastrar.php" class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle"></i> Cadastrar primeiro animal
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Vacinas a vencer -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-shield-check me-2 text-success"></i>
                        Vacinas a Vencer (próximos 15 dias)
                    </div>
                    <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/vacinas/calendario.php" class="btn btn-sm btn-outline-success">
                        Gerenciar
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($vacinasProximas && $vacinasProximas->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Animal</th>
                                        <th>Vacina</th>
                                        <th>Próxima dose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($vacina = $vacinasProximas->fetch_assoc()):
                                        $diasRestantes = (strtotime($vacina['proxima_dose']) - time()) / 86400;
                                        $statusClass = $diasRestantes <= 3 ? 'danger' : ($diasRestantes <= 7 ? 'warning' : 'info');
                                    ?>
                                        <tr>
                                            <td>
                                                <?php echo $vacina['brinco']; ?>
                                                <?php if ($vacina['nome_bovino']): ?>
                                                    <br><small><?php echo $vacina['nome_bovino']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $vacina['nome_vacina']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($vacina['proxima_dose'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo round($diasRestantes); ?> dias
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-shield-check display-6"></i>
                            <p class="mt-2">Nenhuma vacina a vencer</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Gráfico de Situação
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('graficoSituacao').getContext('2d');

        // Dados do PHP
        const situacoes = <?php echo json_encode($stats['situacoes']); ?>;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: situacoes.map(s => s.nome),
                datasets: [{
                    data: situacoes.map(s => s.total),
                    backgroundColor: [
                        '#28a745', // success
                        '#ffc107', // warning
                        '#dc3545', // danger
                        '#6c757d', // secondary
                        '#17a2b8', // info
                        '#007bff', // primary
                        '#6610f2' // indigo
                    ],
                    borderWidth: 0
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
    });
</script>

<?php
include '../../includes/footer.php';
?>