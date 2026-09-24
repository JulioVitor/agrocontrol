<?php
// modules/reproducao/cios.php
// Lista de cios registrados

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

$pageTitle = 'Registro de Cios';

// Filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$femea_id = isset($_GET['femea_id']) ? intval($_GET['femea_id']) : 0;

// Construir WHERE
$where = TenantManager::addTenantFilter('r') . " AND r.tipo_evento = 'cio'";

if ($data_inicio && $data_fim) {
    $where .= " AND r.data_evento BETWEEN '$data_inicio' AND '$data_fim'";
}

if ($femea_id > 0) {
    $where .= " AND r.id_bovino_femea = $femea_id";
}

// Buscar cios
$sql = "SELECT r.*, b.brinco, b.nome 
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        WHERE $where
        ORDER BY r.data_evento DESC";
$cios = executeQuery($sql);

// Buscar fêmeas para filtro
$sqlFemeas = "SELECT id, brinco, nome FROM bovinos 
              WHERE " . TenantManager::addTenantFilter() . " 
              AND sexo = 'F' AND ativo = 1 
              ORDER BY brinco";
$femeas = executeQuery($sqlFemeas);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-heart me-2 text-danger"></i>
                Registro de Cios
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Cios</li>
                </ol>
            </nav>
        </div>
        <a href="novo_cio.php" class="btn btn-danger">
            <i class="bi bi-plus-circle me-2"></i>
            Novo Cio
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
                    <label class="form-label">Matriz</label>
                    <select class="form-select" name="femea_id">
                        <option value="0">Todas</option>
                        <?php if ($femeas && $femeas->num_rows > 0): ?>
                            <?php while ($f = $femeas->fetch_assoc()): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $femea_id == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo $f['brinco']; ?> - <?php echo $f['nome'] ?: 'Sem nome'; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="cios.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de cios -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Cios Registrados</h6>
            <span class="badge bg-danger"><?php echo $cios ? $cios->num_rows : 0; ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($cios && $cios->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Matriz</th>
                            <th>Intervalo</th>
                            <th>Observações</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dataAnterior = null;
                        while ($cio = $cios->fetch_assoc()): 
                            $dataAtual = new DateTime($cio['data_evento']);
                            $intervalo = $dataAnterior ? $dataAtual->diff($dataAnterior)->days : null;
                            $dataAnterior = $dataAtual;
                        ?>
                        <tr>
                            <td><strong><?php echo date('d/m/Y', strtotime($cio['data_evento'])); ?></strong></td>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $cio['id_bovino_femea']; ?>">
                                    <strong><?php echo $cio['brinco']; ?></strong>
                                    <?php if ($cio['nome']): ?>
                                        <br><small><?php echo $cio['nome']; ?></small>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($intervalo): ?>
                                    <span class="badge bg-info"><?php echo $intervalo; ?> dias</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $cio['observacoes'] ?: '-'; ?></td>
                            <td>
                                <a href="nova_inseminacao.php?femea_id=<?php echo $cio['id_bovino_femea']; ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="bi bi-syringe"></i> Inseminar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-heart display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum cio registrado</h4>
                <p class="text-muted">Registre o primeiro cio clicando no botão acima.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>