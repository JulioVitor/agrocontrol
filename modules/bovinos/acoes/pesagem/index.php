<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Verificar se recebeu ID do bovino
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;
if ($bovino_id <= 0) {
    setAlert('ID do bovino inválido.', 'danger');
    redirect(BASE_URL . 'modules/bovinos/index.php');
}

// Buscar dados do bovino
$sqlBovino = "SELECT id, brinco, nome FROM bovinos WHERE id = $bovino_id AND " . TenantManager::addTenantFilter();
$resultBovino = executeQuery($sqlBovino);

if (!$resultBovino || $resultBovino->num_rows == 0) {
    setAlert('Bovino não encontrado.', 'danger');
    redirect(BASE_URL . 'modules/bovinos/index.php');
}

$bovino = $resultBovino->fetch_assoc();
$pageTitle = 'Pesagens - ' . ($bovino['nome'] ?: $bovino['brinco']);

// Buscar todas as pesagens do bovino
$sql = "SELECT * FROM pesagens 
        WHERE id_bovino = $bovino_id 
        ORDER BY data_pesagem DESC";
$pesagens = executeQuery($sql);

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-bar-chart me-2 text-success"></i>
                Pesagens - <?php echo $bovino['brinco']; ?>
                <?php if ($bovino['nome']): ?>
                    <small class="text-muted">(<?php echo $bovino['nome']; ?>)</small>
                <?php endif; ?>
            </h1>
        </div>
        <div>
            <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Nova Pesagem
            </a>
            <a href="../../visualizar.php?id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>

    <?php if ($pesagens && $pesagens->num_rows > 0): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Histórico de Pesagens</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Peso (kg)</th>
                                <th>Ganho</th>
                                <th>Ganho Diário</th>
                                <th>Observações</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pesoAnterior = null;
                            $dataAnterior = null;
                            while ($pesagem = $pesagens->fetch_assoc()):
                                if ($pesoAnterior) {
                                    $ganho = $pesagem['peso'] - $pesoAnterior;
                                    $diasDiff = $dataAnterior ? $dataAnterior->diff(new DateTime($pesagem['data_pesagem']))->days : 1;
                                    $ganhoDiario = $diasDiff > 0 ? $ganho / $diasDiff : 0;
                                    $cor = $ganho >= 0 ? 'success' : 'danger';
                                } else {
                                    $ganho = null;
                                    $ganhoDiario = null;
                                    $cor = 'secondary';
                                }
                                $dataAtual = new DateTime($pesagem['data_pesagem']);
                            ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y', strtotime($pesagem['data_pesagem'])); ?></strong></td>
                                    <td><strong><?php echo number_format($pesagem['peso'], 2, ',', '.'); ?> kg</strong></td>
                                    <td>
                                        <?php if ($ganho !== null): ?>
                                            <span class="badge bg-<?php echo $cor; ?>">
                                                <?php echo $ganho >= 0 ? '+' : ''; ?><?php echo number_format($ganho, 2, ',', '.'); ?> kg
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Primeira</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ganhoDiario !== null): ?>
                                            <?php echo number_format($ganhoDiario, 3, ',', '.'); ?> kg/dia
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $pesagem['observacoes'] ?: '-'; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="editar.php?id=<?php echo $pesagem['id']; ?>&bovino_id=<?php echo $bovino_id; ?>"
                                                class="btn btn-outline-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="index.php?bovino_id=<?php echo $bovino_id; ?>&delete=<?php echo $pesagem['id']; ?>"
                                                class="btn btn-outline-danger" title="Excluir"
                                                onclick="return confirm('Excluir esta pesagem?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                                $pesoAnterior = $pesagem['peso'];
                                $dataAnterior = $dataAtual;
                            endwhile;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-bar-chart display-1 text-muted"></i>
            <h4 class="mt-3">Nenhuma pesagem registrada</h4>
            <p class="text-muted">Registre a primeira pesagem deste animal.</p>
            <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Primeira Pesagem
            </a>
        </div>
    <?php endif; ?>
</main>

<?php include '../../../../includes/footer.php'; ?>