<?php
// modules/reproducao/matrizes.php
// Lista de matrizes (fêmeas)

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

$pageTitle = 'Matrizes';

// Filtro de status
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';

// Buscar matrizes
$where = TenantManager::addTenantFilter('b') . " AND b.sexo = 'F' AND b.ativo = 1";

if ($status_filtro) {
    $where .= " AND m.status_reprodutivo = '$status_filtro'";
}

$sql = "SELECT b.*, 
               m.status_reprodutivo,
               m.numero_partos,
               m.total_crias,
               m.data_ultimo_parto,
               m.data_ultima_inseminacao,
               m.data_ultimo_cio,
               m.data_secagem,
               r.data_prevista_parto
        FROM bovinos b
        LEFT JOIN matrizes m ON b.id = m.id_bovino
        LEFT JOIN (
            SELECT id_bovino_femea, data_prevista_parto 
            FROM reproducao 
            WHERE tipo_evento = 'prenhez' AND confirmada = 1
            GROUP BY id_bovino_femea
        ) r ON b.id = r.id_bovino_femea
        WHERE $where
        ORDER BY b.brinco";
$matrizes = executeQuery($sql);

// Estatísticas por status
$stats = [];
$sqlStats = "SELECT status_reprodutivo, COUNT(*) as total 
             FROM matrizes m
             JOIN bovinos b ON m.id_bovino = b.id
             WHERE " . TenantManager::addTenantFilter('b') . "
             GROUP BY status_reprodutivo";
$resultStats = executeQuery($sqlStats);

while ($s = $resultStats->fetch_assoc()) {
    $stats[$s['status_reprodutivo']] = $s['total'];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-person-arms-up me-2 text-success"></i>
                Matrizes
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Matrizes</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Cards de status -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Vazias</h6>
                    <h3><?php echo $stats['vazia'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Inseminadas</h6>
                    <h3><?php echo $stats['inseminada'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Prenhes</h6>
                    <h3><?php echo $stats['prenhe'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>Lactação</h6>
                    <h3><?php echo $stats['lactacao'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h6>Secas</h6>
                    <h3><?php echo $stats['seca'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtro -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filtrar por Status</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="vazia" <?php echo $status_filtro == 'vazia' ? 'selected' : ''; ?>>Vazias</option>
                        <option value="inseminada" <?php echo $status_filtro == 'inseminada' ? 'selected' : ''; ?>>Inseminadas</option>
                        <option value="prenhe" <?php echo $status_filtro == 'prenhe' ? 'selected' : ''; ?>>Prenhes</option>
                        <option value="lactacao" <?php echo $status_filtro == 'lactacao' ? 'selected' : ''; ?>>Em Lactação</option>
                        <option value="seca" <?php echo $status_filtro == 'seca' ? 'selected' : ''; ?>>Secas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-4">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de matrizes -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($matrizes && $matrizes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Brinco</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Partos</th>
                            <th>Crias</th>
                            <th>Último Parto</th>
                            <th>Última IA</th>
                            <th>Previsto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($matriz = $matrizes->fetch_assoc()): 
                            $statusClass = [
                                'vazia' => 'info',
                                'inseminada' => 'primary',
                                'prenhe' => 'success',
                                'lactacao' => 'warning',
                                'seca' => 'secondary'
                            ][$matriz['status_reprodutivo']] ?? 'secondary';
                        ?>
                        <tr>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $matriz['id']; ?>">
                                    <strong><?php echo $matriz['brinco']; ?></strong>
                                </a>
                            </td>
                            <td><?php echo $matriz['nome'] ?: '-'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo ucfirst($matriz['status_reprodutivo'] ?? 'vazia'); ?>
                                </span>
                            </td>
                            <td><?php echo $matriz['numero_partos'] ?: 0; ?></td>
                            <td><?php echo $matriz['total_crias'] ?: 0; ?></td>
                            <td><?php echo $matriz['data_ultimo_parto'] ? date('d/m/Y', strtotime($matriz['data_ultimo_parto'])) : '-'; ?></td>
                            <td><?php echo $matriz['data_ultima_inseminacao'] ? date('d/m/Y', strtotime($matriz['data_ultima_inseminacao'])) : '-'; ?></td>
                            <td>
                                <?php if ($matriz['data_prevista_parto']): ?>
                                    <?php echo date('d/m/Y', strtotime($matriz['data_prevista_parto'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($matriz['status_reprodutivo'] == 'vazia'): ?>
                                    <a href="nova_inseminacao.php?femea_id=<?php echo $matriz['id']; ?>" 
                                       class="btn btn-warning" title="Inseminar">
                                        <i class="bi bi-syringe"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($matriz['status_reprodutivo'] == 'prenhe'): ?>
                                    <a href="novo_parto.php?femea_id=<?php echo $matriz['id']; ?>" 
                                       class="btn btn-success" title="Registrar Parto">
                                        <i class="bi bi-egg"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="../bovinos/visualizar.php?id=<?php echo $matriz['id']; ?>" 
                                       class="btn btn-info" title="Visualizar">
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
                <i class="bi bi-person-arms-up display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma matriz encontrada</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>