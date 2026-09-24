<?php
// modules/relatorios/vacinas.php
// Relatório de controle de vacinas

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

$pageTitle = 'Relatório de Vacinas';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-6 months'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : 'todas';
$vacina_id = isset($_GET['vacina_id']) ? intval($_GET['vacina_id']) : 0;
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;

// Buscar vacinas cadastradas
$sqlVacinas = "SELECT id, nome_vacina FROM vacinas 
               WHERE " . TenantManager::addTenantFilter() . " 
               ORDER BY nome_vacina";
$vacinas = executeQuery($sqlVacinas);

// Buscar bovinos para filtro
$sqlBovinos = "SELECT id, brinco, nome FROM bovinos 
               WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
               ORDER BY brinco";
$bovinos = executeQuery($sqlBovinos);

// ============================================
// 1. ESTATÍSTICAS GERAIS DE VACINAS
// ============================================
$whereStats = TenantManager::addTenantFilter('b') . " AND av.data_aplicacao BETWEEN '$data_inicio' AND '$data_fim'";

if ($vacina_id > 0) {
    $whereStats .= " AND av.id_vacina = $vacina_id";
}

$sqlStats = "SELECT 
                COUNT(DISTINCT av.id_bovino) as total_animais_vacinados,
                COUNT(av.id) as total_aplicacoes,
                COUNT(DISTINCT av.id_vacina) as vacinas_diferentes,
                SUM(CASE WHEN av.proxima_dose < CURDATE() AND av.proxima_dose IS NOT NULL THEN 1 ELSE 0 END) as doses_atrasadas,
                SUM(CASE WHEN av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as doses_proximas
             FROM aplicacoes_vacinas av
             JOIN bovinos b ON av.id_bovino = b.id
             WHERE $whereStats";
$resultStats = executeQuery($sqlStats);
$stats = $resultStats->fetch_assoc();

// ============================================
// 2. APLICAÇÕES POR VACINA
// ============================================
$sqlPorVacina = "SELECT 
                    v.id,
                    v.nome_vacina,
                    COUNT(av.id) as total_aplicacoes,
                    COUNT(DISTINCT av.id_bovino) as animais_atendidos,
                    MIN(av.data_aplicacao) as primeira_dose,
                    MAX(av.data_aplicacao) as ultima_dose,
                    SUM(CASE WHEN av.proxima_dose IS NOT NULL AND av.proxima_dose < CURDATE() THEN 1 ELSE 0 END) as atrasadas
                 FROM vacinas v
                 LEFT JOIN aplicacoes_vacinas av ON v.id = av.id_vacina
                 WHERE v.id_fazenda = $farmId
                 GROUP BY v.id
                 ORDER BY total_aplicacoes DESC";
$porVacina = executeQuery($sqlPorVacina);

// ============================================
// 3. PRÓXIMAS DOSES (próximos 30 dias)
// ============================================
$sqlProximas = "SELECT 
                    av.*,
                    v.nome_vacina,
                    v.intervalo_dias,
                    b.brinco,
                    b.nome as nome_bovino,
                    DATEDIFF(av.proxima_dose, CURDATE()) as dias_restantes
                FROM aplicacoes_vacinas av
                JOIN vacinas v ON av.id_vacina = v.id
                JOIN bovinos b ON av.id_bovino = b.id
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY av.proxima_dose ASC";
$proximasDoses = executeQuery($sqlProximas);

// ============================================
// 4. DOSES ATRASADAS
// ============================================
$sqlAtrasadas = "SELECT 
                    av.*,
                    v.nome_vacina,
                    v.intervalo_dias,
                    b.brinco,
                    b.nome as nome_bovino,
                    DATEDIFF(CURDATE(), av.proxima_dose) as dias_atraso
                FROM aplicacoes_vacinas av
                JOIN vacinas v ON av.id_vacina = v.id
                JOIN bovinos b ON av.id_bovino = b.id
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND av.proxima_dose < CURDATE()
                ORDER BY av.proxima_dose ASC";
$dosesAtrasadas = executeQuery($sqlAtrasadas);

// ============================================
// 5. HISTÓRICO DE APLICAÇÕES (com filtros)
// ============================================
$whereHistorico = TenantManager::addTenantFilter('b');

if ($data_inicio && $data_fim) {
    $whereHistorico .= " AND av.data_aplicacao BETWEEN '$data_inicio' AND '$data_fim'";
}

if ($vacina_id > 0) {
    $whereHistorico .= " AND av.id_vacina = $vacina_id";
}

if ($bovino_id > 0) {
    $whereHistorico .= " AND av.id_bovino = $bovino_id";
}

if ($status == 'atrasadas') {
    $whereHistorico .= " AND av.proxima_dose < CURDATE()";
} elseif ($status == 'proximas') {
    $whereHistorico .= " AND av.proxima_dose BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$sqlHistorico = "SELECT 
                    av.*,
                    v.nome_vacina,
                    v.intervalo_dias,
                    b.brinco,
                    b.nome as nome_bovino,
                    u.nome as responsavel_nome
                 FROM aplicacoes_vacinas av
                 JOIN vacinas v ON av.id_vacina = v.id
                 JOIN bovinos b ON av.id_bovino = b.id
                 LEFT JOIN usuarios u ON av.responsavel = u.id
                 WHERE $whereHistorico
                 ORDER BY av.data_aplicacao DESC";
$historico = executeQuery($sqlHistorico);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-shield-check me-2 text-success"></i>
                Relatório de Vacinas
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Vacinas</li>
                </ol>
            </nav>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="exportar.php?tipo=csv&relatorio=vacinas" class="btn btn-success">
                <i class="bi bi-download"></i> Exportar
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="todas">Todas</option>
                        <option value="proximas" <?php echo $status == 'proximas' ? 'selected' : ''; ?>>Próximas doses</option>
                        <option value="atrasadas" <?php echo $status == 'atrasadas' ? 'selected' : ''; ?>>Atrasadas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vacina</label>
                    <select class="form-select" name="vacina_id">
                        <option value="0">Todas</option>
                        <?php if ($vacinas && $vacinas->num_rows > 0): ?>
                            <?php while ($v = $vacinas->fetch_assoc()): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $vacina_id == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo $v['nome_vacina']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Animal</label>
                    <select class="form-select" name="bovino_id">
                        <option value="0">Todos</option>
                        <?php if ($bovinos && $bovinos->num_rows > 0): ?>
                            <?php while ($b = $bovinos->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $bovino_id == $b['id'] ? 'selected' : ''; ?>>
                                <?php echo $b['brinco']; ?> - <?php echo $b['nome'] ?: 'Sem nome'; ?>
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

    <!-- Cards de Alerta -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Próximas Doses (30 dias)</h6>
                    <h3><?php echo $stats['doses_proximas'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Doses Atrasadas</h6>
                    <h3><?php echo $stats['doses_atrasadas'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Animais Vacinados</h6>
                    <h3><?php echo $stats['total_animais_vacinados'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Aplicações</h6>
                    <h3><?php echo $stats['total_aplicacoes'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Próximas Doses e Atrasadas -->
    <div class="row g-3 mb-4">
        <!-- Próximas Doses -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-check"></i> 
                        Próximas Doses (30 dias)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($proximasDoses && $proximasDoses->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Vacina</th>
                                    <th>Data</th>
                                    <th>Dias</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pd = $proximasDoses->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="../bovinos/visualizar.php?id=<?php echo $pd['id_bovino']; ?>">
                                            <?php echo $pd['brinco']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $pd['nome_vacina']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($pd['proxima_dose'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $pd['dias_restantes'] <= 7 ? 'danger' : 'warning'; ?>">
                                            <?php echo $pd['dias_restantes']; ?> dias
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma dose próxima</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Doses Atrasadas -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Doses Atrasadas
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($dosesAtrasadas && $dosesAtrasadas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Vacina</th>
                                    <th>Data Prevista</th>
                                    <th>Atraso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($da = $dosesAtrasadas->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="../bovinos/visualizar.php?id=<?php echo $da['id_bovino']; ?>">
                                            <?php echo $da['brinco']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $da['nome_vacina']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($da['proxima_dose'])); ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo $da['dias_atraso']; ?> dias
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma dose atrasada</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Aplicações por Vacina -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Aplicações por Vacina</h6>
        </div>
        <div class="card-body p-0">
            <?php if ($porVacina && $porVacina->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Vacina</th>
                            <th class="text-center">Total Aplicações</th>
                            <th class="text-center">Animais Atendidos</th>
                            <th class="text-center">Primeira Dose</th>
                            <th class="text-center">Última Dose</th>
                            <th class="text-center">Atrasadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pv = $porVacina->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $pv['nome_vacina']; ?></strong></td>
                            <td class="text-center"><?php echo $pv['total_aplicacoes']; ?></td>
                            <td class="text-center"><?php echo $pv['animais_atendidos']; ?></td>
                            <td class="text-center"><?php echo $pv['primeira_dose'] ? date('d/m/Y', strtotime($pv['primeira_dose'])) : '-'; ?></td>
                            <td class="text-center"><?php echo $pv['ultima_dose'] ? date('d/m/Y', strtotime($pv['ultima_dose'])) : '-'; ?></td>
                            <td class="text-center">
                                <?php if ($pv['atrasadas'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $pv['atrasadas']; ?></span>
                                <?php else: ?>
                                <span class="badge bg-success">0</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">Nenhuma vacina cadastrada</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Histórico de Aplicações -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Histórico de Aplicações</h6>
            <span class="badge bg-primary"><?php echo $historico ? $historico->num_rows : 0; ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($historico && $historico->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Animal</th>
                            <th>Vacina</th>
                            <th>Dose</th>
                            <th>Lote</th>
                            <th>Responsável</th>
                            <th>Próxima Dose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hoje = new DateTime();
                        while ($h = $historico->fetch_assoc()): 
                            $statusClass = 'success';
                            $statusText = 'Em dia';
                            
                            if ($h['proxima_dose']) {
                                $proxima = new DateTime($h['proxima_dose']);
                                if ($proxima < $hoje) {
                                    $statusClass = 'danger';
                                    $statusText = 'Atrasada';
                                } elseif ($hoje->diff($proxima)->days <= 15) {
                                    $statusClass = 'warning';
                                    $statusText = 'Próxima';
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($h['data_aplicacao'])); ?></td>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $h['id_bovino']; ?>">
                                    <strong><?php echo $h['brinco']; ?></strong>
                                </a>
                                <?php if ($h['nome_bovino']): ?>
                                    <br><small><?php echo $h['nome_bovino']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $h['nome_vacina']; ?></td>
                            <td><?php echo $h['dose_ml'] ? number_format($h['dose_ml'], 2, ',', '.') . ' ml' : '-'; ?></td>
                            <td><?php echo $h['lote'] ?: '-'; ?></td>
                            <td><?php echo $h['responsavel_nome'] ?: $h['responsavel'] ?: '-'; ?></td>
                            <td>
                                <?php if ($h['proxima_dose']): ?>
                                    <?php echo date('d/m/Y', strtotime($h['proxima_dose'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-shield display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma aplicação encontrada</h4>
                <p class="text-muted">Não há registros de vacinas no período selecionado.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>