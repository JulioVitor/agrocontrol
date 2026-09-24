<?php
// modules/reproducao/inseminacoes.php
// Lista de inseminações


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

$pageTitle = 'Inseminações';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-60 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Construir WHERE - CORRIGIDO!
$where = TenantManager::addTenantFilter('r') . " AND r.tipo_evento = 'inseminacao'";

if ($data_inicio && $data_fim) {
    $where .= " AND r.data_evento BETWEEN '$data_inicio' AND '$data_fim'";
}

// Buscar inseminações
$sql = "SELECT r.*, 
               b.brinco, 
               b.nome,
               m.brinco as touro_brinco,
               m.nome as touro_nome,
               (SELECT COUNT(*) FROM reproducao WHERE id_bovino_femea = r.id_bovino_femea AND tipo_evento = 'prenhez' AND confirmada = 1) as tem_prenhez,
               (SELECT data_prevista_parto FROM reproducao WHERE id_bovino_femea = r.id_bovino_femea AND tipo_evento = 'prenhez' ORDER BY data_evento DESC LIMIT 1) as data_prevista
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        LEFT JOIN bovinos m ON r.id_bovino_macho = m.id
        WHERE $where
        ORDER BY r.data_evento DESC";
$inseminacoes = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-syringe me-2 text-warning"></i>
                Inseminações
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Inseminações</li>
                </ol>
            </nav>
        </div>
        <a href="nova_inseminacao.php" class="btn btn-warning">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Inseminação
        </a>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-select" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-select" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="todas">Todas</option>
                        <option value="prenhe">Confirmadas</option>
                        <option value="pendente">Aguardando</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="inseminacoes.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de inseminações -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Inseminações Realizadas</h6>
            <span class="badge bg-warning"><?php echo $inseminacoes ? $inseminacoes->num_rows : 0; ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($inseminacoes && $inseminacoes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Matriz</th>
                            <th>Touro/Sêmen</th>
                            <th>Técnico</th>
                            <th>Dias</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hoje = new DateTime();
                        while ($ins = $inseminacoes->fetch_assoc()): 
                            $dataIns = new DateTime($ins['data_evento']);
                            $dias = $hoje->diff($dataIns)->days;
                        ?>
                        <tr>
                            <td><strong><?php echo date('d/m/Y', strtotime($ins['data_evento'])); ?></strong></td>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $ins['id_bovino_femea']; ?>">
                                    <strong><?php echo $ins['brinco']; ?></strong>
                                    <?php if ($ins['nome']): ?>
                                        <br><small><?php echo $ins['nome']; ?></small>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($ins['id_bovino_macho']): ?>
                                    <a href="../bovinos/visualizar.php?id=<?php echo $ins['id_bovino_macho']; ?>">
                                        <?php echo $ins['touro_brinco']; ?>
                                    </a>
                                <?php elseif ($ins['semen_touro']): ?>
                                    <?php echo $ins['semen_touro']; ?> (<?php echo $ins['semen_raca']; ?>)
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $ins['tecnico'] ?: '-'; ?></td>
                            <td><?php echo $dias; ?> dias</td>
                            <td>
                                <?php if ($ins['tem_prenhez'] > 0): ?>
                                    <span class="badge bg-success">Prenhe</span>
                                    <?php if ($ins['data_prevista']): ?>
                                        <br><small>Parto: <?php echo date('d/m/Y', strtotime($ins['data_prevista'])); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($dias > 45): ?>
                                    <span class="badge bg-danger">Negativo</span>
                                    <br><small><a href="acoes/diagnostico.php?femea_id=<?php echo $ins['id_bovino_femea']; ?>" class="text-white">Diagnosticar</a></small>
                                <?php elseif ($dias > 30): ?>
                                    <span class="badge bg-warning">Aguardando diagnóstico</span>
                                    <br><small><a href="acoes/diagnostico.php?femea_id=<?php echo $ins['id_bovino_femea']; ?>" class="text-white">Diagnosticar</a></small>
                                <?php else: ?>
                                    <span class="badge bg-info">Aguardando</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($dias >= 30 && $ins['tem_prenhez'] == 0): ?>
                                    <a href="acoes/diagnostico.php?femea_id=<?php echo $ins['id_bovino_femea']; ?>" 
                                       class="btn btn-success" title="Diagnosticar">
                                        <i class="bi bi-stethoscope"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="#" class="btn btn-outline-info" title="Detalhes">
                                        <i class="bi bi-eye"></i>
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
                <i class="bi bi-syringe display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma inseminação registrada</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>