<?php
// modules/bovinos/acoes/leite/index.php
// Listagem de produção de leite de um bovino específico

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
$pageTitle = 'Produção de Leite - ' . ($bovino['nome'] ?: $bovino['brinco']);

// Processar exclusão
if (isset($_GET['delete'])) {
    $producao_id = intval($_GET['delete']);
    $sql = "DELETE FROM producao_leite WHERE id = $producao_id AND id_bovino = $bovino_id";
    if (executeQuery($sql)) {
        setAlert('Registro excluído com sucesso!', 'success');
    } else {
        setAlert('Erro ao excluir registro.', 'danger');
    }
    redirect("index.php?bovino_id=$bovino_id");
}

// Buscar produção do bovino
$sql = "SELECT * FROM producao_leite 
        WHERE id_bovino = $bovino_id 
        ORDER BY data_producao DESC, 
                 FIELD(turno, 'manha', 'tarde', 'noite', 'unico')";
$producoes = executeQuery($sql);

// Calcular totais
$totalLitros = 0;
$totalDias = 0;
$mediaDiaria = 0;
$ultimaProducao = null;

if ($producoes && $producoes->num_rows > 0) {
    $dias = [];
    while ($p = $producoes->fetch_assoc()) {
        $totalLitros += $p['quantidade_litros'];
        $dias[$p['data_producao']] = true;
    }
    $totalDias = count($dias);
    $mediaDiaria = $totalDias > 0 ? $totalLitros / $totalDias : 0;
    
    // Reset pointer
    $producoes->data_seek(0);
    
    // Última produção
    $ultimaProducao = $producoes->fetch_assoc();
    $producoes->data_seek(0);
}

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-cup me-2 text-success"></i>
                Produção de Leite - <?php echo $bovino['brinco']; ?>
                <?php if ($bovino['nome']): ?>
                    <small class="text-muted">(<?php echo $bovino['nome']; ?>)</small>
                <?php endif; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $bovino_id; ?>">Detalhes</a></li>
                    <li class="breadcrumb-item active">Produção de Leite</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Nova Produção
            </a>
            <a href="../../visualizar.php?id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <small>Total Produzido</small>
                    <h3><?php echo number_format($totalLitros, 2, ',', '.'); ?> L</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <small>Dias em Lactação</small>
                    <h3><?php echo $totalDias; ?> dias</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <small>Média Diária</small>
                    <h3><?php echo number_format($mediaDiaria, 2, ',', '.'); ?> L/dia</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <small>Última Produção</small>
                    <h3>
                        <?php 
                        if ($ultimaProducao) {
                            echo number_format($ultimaProducao['quantidade_litros'], 2, ',', '.') . ' L';
                        } else {
                            echo '-';
                        }
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de produção -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Histórico de Produção</h5>
            <a href="graficos.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-sm btn-info">
                <i class="bi bi-graph-up"></i> Ver Gráficos
            </a>
        </div>
        <div class="card-body p-0">
            <?php if ($producoes && $producoes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Turno</th>
                            <th>Litros</th>
                            <th>Total do Dia</th>
                            <th>Observações</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDia = [];
                        $producoes->data_seek(0);
                        while ($p = $producoes->fetch_assoc()) {
                            if (!isset($totalDia[$p['data_producao']])) {
                                $totalDia[$p['data_producao']] = 0;
                            }
                            $totalDia[$p['data_producao']] += $p['quantidade_litros'];
                        }
                        
                        $producoes->data_seek(0);
                        while ($producao = $producoes->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><strong><?php echo date('d/m/Y', strtotime($producao['data_producao'])); ?></strong></td>
                            <td>
                                <?php 
                                switch($producao['turno']) {
                                    case 'manha': echo '<span class="badge bg-warning">Manhã</span>'; break;
                                    case 'tarde': echo '<span class="badge bg-info">Tarde</span>'; break;
                                    case 'noite': echo '<span class="badge bg-secondary">Noite</span>'; break;
                                    default: echo '<span class="badge bg-success">Único</span>';
                                }
                                ?>
                            </td>
                            <td><strong><?php echo number_format($producao['quantidade_litros'], 2, ',', '.'); ?> L</strong></td>
                            <td><?php echo number_format($totalDia[$producao['data_producao']], 2, ',', '.'); ?> L</td>
                            <td><?php echo $producao['observacoes'] ?: '-'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="editar.php?id=<?php echo $producao['id']; ?>&bovino_id=<?php echo $bovino_id; ?>" 
                                       class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?bovino_id=<?php echo $bovino_id; ?>&delete=<?php echo $producao['id']; ?>" 
                                       class="btn btn-outline-danger" title="Excluir"
                                       onclick="return confirm('Excluir este registro?')">
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
                <i class="bi bi-cup display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma produção registrada</h4>
                <p class="text-muted">Registre a primeira produção de leite deste animal.</p>
                <a href="cadastrar.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>
                    Registrar Produção
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../../../includes/footer.php'; ?>