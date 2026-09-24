<?php
// modules/relatorios/index.php
// Painel principal de relatórios

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

$pageTitle = 'Relatórios';

// Estatísticas gerais para os cards
$stats = [];

// Total de bovinos
$sql = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1";
$result = executeQuery($sql);
$stats['total_bovinos'] = $result->fetch_assoc()['total'];

// Total de fêmeas em lactação
$sql = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter() . " 
        AND sexo = 'F' AND ativo = 1 AND id IN (SELECT DISTINCT id_bovino FROM producao_leite)";
$result = executeQuery($sql);
$stats['lactacao'] = $result->fetch_assoc()['total'];

// Produção de leite (mês atual)
$sql = "SELECT SUM(quantidade_litros) as total FROM producao_leite pl
        JOIN bovinos b ON pl.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND MONTH(pl.data_producao) = MONTH(CURDATE())
        AND YEAR(pl.data_producao) = YEAR(CURDATE())";
$result = executeQuery($sql);
$stats['producao_mes'] = $result->fetch_assoc()['total'] ?? 0;

// Vacinas a vencer
$sql = "SELECT COUNT(*) as total FROM aplicacoes_vacinas av
        JOIN bovinos b ON av.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$result = executeQuery($sql);
$stats['vacinas_proximas'] = $result->fetch_assoc()['total'];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-file-text me-2 text-success"></i>
                Relatórios
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Relatórios</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Exportar Dados
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportar('csv')">CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportar('pdf')">PDF</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportar('excel')">Excel</a></li>
            </ul>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Rebanho Total</h6>
                            <h2 class="mb-0"><?php echo $stats['total_bovinos']; ?></h2>
                        </div>
                        <i class="bi bi-tree-fill fs-1 opacity-50"></i>
                    </div>
                    <a href="bovinos.php" class="text-white stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Em Lactação</h6>
                            <h2 class="mb-0"><?php echo $stats['lactacao']; ?></h2>
                        </div>
                        <i class="bi bi-cup-fill fs-1 opacity-50"></i>
                    </div>
                    <a href="producao.php" class="text-white stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Produção Mês</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['producao_mes'], 0, ',', '.'); ?> L</h2>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                    <a href="producao.php" class="text-white stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Vacinas Próximas</h6>
                            <h2 class="mb-0"><?php echo $stats['vacinas_proximas']; ?></h2>
                        </div>
                        <i class="bi bi-shield-check fs-1 opacity-50"></i>
                    </div>
                    <a href="vacinas.php" class="text-white stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de Relatórios -->
    <div class="row g-4">
        <!-- Relatório do Rebanho -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-tree-fill display-1 text-success"></i>
                    <h4 class="mt-3">Relatório do Rebanho</h4>
                    <p class="text-muted">Composição do rebanho, distribuição por raça, sexo e situação.</p>
                    <div class="d-grid">
                        <a href="bovinos.php" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>Visualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Relatório de Produção -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cup-fill display-1 text-success"></i>
                    <h4 class="mt-3">Produção de Leite</h4>
                    <p class="text-muted">Produção diária, mensal e anual, média por animal.</p>
                    <div class="d-grid">
                        <a href="producao.php" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>Visualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Relatório de Pesagens -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart display-1 text-success"></i>
                    <h4 class="mt-3">Ganho de Peso</h4>
                    <p class="text-muted">Evolução de peso, ganho diário, desempenho por animal.</p>
                    <div class="d-grid">
                        <a href="pesagens.php" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>Visualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Relatório de Vacinas -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-shield-check display-1 text-success"></i>
                    <h4 class="mt-3">Controle de Vacinas</h4>
                    <p class="text-muted">Cobertura vacinal, vacinas a vencer, histórico por animal.</p>
                    <div class="d-grid">
                        <a href="vacinas.php" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>Visualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Relatório Financeiro -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack display-1 text-success"></i>
                    <h4 class="mt-3">Relatório Financeiro</h4>
                    <p class="text-muted">Receitas, despesas, lucro, investimentos por animal.</p>
                    <div class="d-grid">
                        <a href="financeiro.php" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>Visualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos Gerais -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-pie-chart-fill display-1 text-success"></i>
                    <h4 class="mt-3">Gráficos Gerais</h4>
                    <p class="text-muted">Visualização gráfica de todos os indicadores da fazenda.</p>
                    <div class="d-grid">
                        <a href="graficos.php" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>Visualizar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function exportar(tipo) {
    // Redirecionar para página de exportação
    window.location.href = 'exportar.php?tipo=' + tipo;
}
</script>

<?php include '../../includes/footer.php'; ?>