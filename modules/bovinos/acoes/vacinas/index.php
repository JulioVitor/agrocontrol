<?php
// modules/bovinos/acoes/vacinas/index.php
// Listagem de vacinas aplicadas em um bovino

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
$pageTitle = 'Vacinas - ' . ($bovino['nome'] ?: $bovino['brinco']);

// Processar exclusão
if (isset($_GET['delete'])) {
    $aplicacao_id = intval($_GET['delete']);
    $sql = "DELETE FROM aplicacoes_vacinas WHERE id = $aplicacao_id AND id_bovino = $bovino_id";
    if (executeQuery($sql)) {
        setAlert('Registro de vacina excluído com sucesso!', 'success');
    } else {
        setAlert('Erro ao excluir registro.', 'danger');
    }
    redirect("index.php?bovino_id=$bovino_id");
}

// Buscar todas as vacinas aplicadas no bovino
$sql = "SELECT av.*, v.nome_vacina, v.fabricante, v.intervalo_dias
        FROM aplicacoes_vacinas av
        JOIN vacinas v ON av.id_vacina = v.id
        WHERE av.id_bovino = $bovino_id
        ORDER BY av.data_aplicacao DESC";
$vacinas = executeQuery($sql);

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-shield-check me-2 text-success"></i>
                Vacinas - <?php echo $bovino['brinco']; ?>
                <?php if ($bovino['nome']): ?>
                    <small class="text-muted">(<?php echo $bovino['nome']; ?>)</small>
                <?php endif; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $bovino_id; ?>">Detalhes</a></li>
                    <li class="breadcrumb-item active">Vacinas</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Nova Vacina
            </a>
            <a href="calendario.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-info">
                <i class="bi bi-calendar me-2"></i>
                Calendário
            </a>
            <a href="../../visualizar.php?id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>

    <!-- Próximas vacinas -->
    <?php
    $hoje = new DateTime();
    $proximas = [];
    if ($vacinas && $vacinas->num_rows > 0) {
        $vacinas->data_seek(0);
        while ($v = $vacinas->fetch_assoc()) {
            if ($v['proxima_dose']) {
                $proxima = new DateTime($v['proxima_dose']);
                if ($proxima >= $hoje) {
                    $proximas[] = $v;
                }
            }
        }
        $vacinas->data_seek(0);
    }
    
    if (count($proximas) > 0):
    ?>
    <div class="alert alert-info mb-4">
        <h5 class="alert-heading">
            <i class="bi bi-calendar-check me-2"></i>
            Próximas Vacinas
        </h5>
        <div class="row">
            <?php foreach ($proximas as $proxima): 
                $dataProxima = new DateTime($proxima['proxima_dose']);
                $diasRestantes = $hoje->diff($dataProxima)->days;
                $cor = $diasRestantes <= 7 ? 'warning' : 'info';
            ?>
            <div class="col-md-4 mb-2">
                <div class="border-start border-3 border-<?php echo $cor; ?> ps-2">
                    <strong><?php echo $proxima['nome_vacina']; ?></strong><br>
                    <small>Data: <?php echo date('d/m/Y', strtotime($proxima['proxima_dose'])); ?></small><br>
                    <span class="badge bg-<?php echo $cor; ?>"><?php echo $diasRestantes; ?> dias</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabela de vacinas aplicadas -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Histórico de Vacinas</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($vacinas && $vacinas->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Vacina</th>
                            <th>Fabricante</th>
                            <th>Dose (ml)</th>
                            <th>Lote</th>
                            <th>Via</th>
                            <th>Próxima Dose</th>
                            <th>Status</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($vacina = $vacinas->fetch_assoc()): 
                            $dataAplicacao = new DateTime($vacina['data_aplicacao']);
                            $statusClass = 'success';
                            $statusText = 'Em dia';
                            
                            if ($vacina['proxima_dose']) {
                                $proxima = new DateTime($vacina['proxima_dose']);
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
                            <td><?php echo date('d/m/Y', strtotime($vacina['data_aplicacao'])); ?></td>
                            <td><strong><?php echo $vacina['nome_vacina']; ?></strong></td>
                            <td><?php echo $vacina['fabricante'] ?: '-'; ?></td>
                            <td><?php echo $vacina['dose_ml'] ? number_format($vacina['dose_ml'], 2, ',', '.') : '-'; ?></td>
                            <td><?php echo $vacina['lote'] ?: '-'; ?></td>
                            <td><?php echo $vacina['via_aplicacao'] ?: '-'; ?></td>
                            <td>
                                <?php if ($vacina['proxima_dose']): ?>
                                    <?php echo date('d/m/Y', strtotime($vacina['proxima_dose'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="editar.php?id=<?php echo $vacina['id']; ?>&bovino_id=<?php echo $bovino_id; ?>" 
                                       class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?bovino_id=<?php echo $bovino_id; ?>&delete=<?php echo $vacina['id']; ?>" 
                                       class="btn btn-outline-danger" title="Excluir"
                                       onclick="return confirm('Excluir este registro de vacina?')">
                                        <i class="bi bi-trash"></i>
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
                <i class="bi bi-shield display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma vacina registrada</h4>
                <p class="text-muted">Registre a primeira vacina deste animal.</p>
                <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>
                    Registrar Vacina
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../../../includes/footer.php'; ?>