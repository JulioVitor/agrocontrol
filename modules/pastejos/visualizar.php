<?php
// modules/pastejos/visualizar.php
// Visualizar detalhes completos de um piquete

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

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Buscar dados do piquete
$sql = "SELECT * FROM piquetes WHERE id = $id AND " . TenantManager::addTenantFilter();
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Piquete não encontrado.', 'danger');
    redirect('index.php');
}

$piquete = $result->fetch_assoc();

// Buscar animais atualmente no piquete
$sqlAnimais = "SELECT b.*, r.nome_raca 
               FROM bovinos b
               LEFT JOIN racas r ON b.id_raca = r.id
               WHERE b.id_piquete_atual = $id AND b.ativo = 1";
$animais = executeQuery($sqlAnimais);

// Buscar histórico de ocupação
$sqlHistorico = "SELECT h.*, b.brinco, b.nome as nome_bovino
                 FROM historico_piquete h
                 JOIN bovinos b ON h.id_bovino = b.id
                 WHERE h.id_piquete = $id
                 ORDER BY h.data_entrada DESC
                 LIMIT 20";
$historico = executeQuery($sqlHistorico);

// Buscar manutenções
$sqlManutencao = "SELECT * FROM manutencao_piquete 
                  WHERE id_piquete = $id 
                  ORDER BY data_manutencao DESC";
$manutencoes = executeQuery($sqlManutencao);

$pageTitle = 'Piquete: ' . $piquete['nome_piquete'];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-map me-2 text-success"></i>
                <?php echo $piquete['nome_piquete']; ?>
                <?php if ($piquete['codigo']): ?>
                    <small class="text-muted">(<?php echo $piquete['codigo']; ?>)</small>
                <?php endif; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Pastagens</a></li>
                    <li class="breadcrumb-item active"><?php echo $piquete['nome_piquete']; ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($piquete['disponivel']): ?>
                <a href="acoes/alocar.php?piquete_id=<?php echo $id; ?>" class="btn btn-success">
                    <i class="bi bi-arrow-right-circle"></i> Alocar Animais
                </a>
            <?php else: ?>
                <a href="acoes/desocupar.php?piquete_id=<?php echo $id; ?>" class="btn btn-warning">
                    <i class="bi bi-arrow-left-circle"></i> Desocupar Piquete
                </a>
            <?php endif; ?>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Status e informações principais -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informações Gerais</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Status:</th>
                                    <td>
                                        <?php if ($piquete['disponivel']): ?>
                                            <span class="badge bg-success">Disponível</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Ocupado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tipo de Pasto:</th>
                                    <td><?php echo $piquete['tipo_pasto'] ?: 'Não informado'; ?></td>
                                </tr>
                                <tr>
                                    <th>Área:</th>
                                    <td><?php echo number_format($piquete['area_hectares'], 2, ',', '.'); ?> hectares</td>
                                </tr>
                                <tr>
                                    <th>Dimensões:</th>
                                    <td>
                                        <?php 
                                        if ($piquete['comprimento_m'] && $piquete['largura_m']) {
                                            echo $piquete['comprimento_m'] . ' x ' . $piquete['largura_m'] . ' m';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Capacidade:</th>
                                    <td><?php echo $piquete['capacidade_suporte'] ?: 'Não definida'; ?> animais</td>
                                </tr>
                                <tr>
                                    <th>Lotação atual:</th>
                                    <td>
                                        <strong><?php echo $piquete['lotacao_atual'] ?: 0; ?> animais</strong>
                                        <?php if ($piquete['capacidade_suporte'] > 0): ?>
                                            (<?php echo round(($piquete['lotacao_atual'] / $piquete['capacidade_suporte']) * 100); ?>%)
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Dias de descanso:</th>
                                    <td><?php echo $piquete['dias_descanso'] ?: 'Não definido'; ?></td>
                                </tr>
                                <tr>
                                    <th>Dias de ocupação:</th>
                                    <td><?php echo $piquete['dias_ocupacao'] ?: 'Não definido'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Recursos -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Recursos Disponíveis</h6>
                            <div class="d-flex gap-3">
                                <?php if ($piquete['cerca_eletrica']): ?>
                                    <span class="badge bg-info"><i class="bi bi-lightning"></i> Cerca Elétrica</span>
                                <?php endif; ?>
                                <?php if ($piquete['agua_disponivel']): ?>
                                    <span class="badge bg-info"><i class="bi bi-droplet"></i> Água</span>
                                <?php endif; ?>
                                <?php if ($piquete['sombra_disponivel']): ?>
                                    <span class="badge bg-info"><i class="bi bi-cloud-sun"></i> Sombra</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Datas importantes -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Última ocupação:</small>
                            <strong><?php echo $piquete['data_ultima_ocupacao'] ? date('d/m/Y', strtotime($piquete['data_ultima_ocupacao'])) : 'Nunca'; ?></strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Última manutenção:</small>
                            <strong><?php echo $piquete['data_ultima_manutencao'] ? date('d/m/Y', strtotime($piquete['data_ultima_manutencao'])) : 'Nunca'; ?></strong>
                        </div>
                    </div>

                    <?php if ($piquete['observacoes']): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Observações</h6>
                                <p class="bg-light p-3 rounded"><?php echo nl2br($piquete['observacoes']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card de ocupação atual -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Animais no Piquete</h5>
                    <span class="badge bg-primary"><?php echo $animais ? $animais->num_rows : 0; ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if ($animais && $animais->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($animal = $animais->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo $animal['brinco']; ?></strong>
                                        <?php if ($animal['nome']): ?>
                                            <br><small><?php echo $animal['nome']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <a href="../bovinos/visualizar.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-tree display-4 text-muted"></i>
                            <p class="text-muted mt-2">Nenhum animal neste piquete</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#historico">
                        <i class="bi bi-clock-history"></i> Histórico de Ocupação
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#manutencao">
                        <i class="bi bi-tools"></i> Manutenções
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#recomendacoes">
                        <i class="bi bi-graph-up"></i> Recomendações
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Histórico de Ocupação -->
                <div class="tab-pane fade show active" id="historico">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Data Entrada</th>
                                    <th>Data Saída</th>
                                    <th>Dias</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($historico && $historico->num_rows > 0): ?>
                                    <?php while ($h = $historico->fetch_assoc()): 
                                        $dias = $h['data_saida'] 
                                            ? (strtotime($h['data_saida']) - strtotime($h['data_entrada'])) / 86400
                                            : (time() - strtotime($h['data_entrada'])) / 86400;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $h['brinco']; ?></strong>
                                            <?php if ($h['nome_bovino']): ?>
                                                <br><small><?php echo $h['nome_bovino']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($h['data_entrada'])); ?></td>
                                        <td><?php echo $h['data_saida'] ? date('d/m/Y', strtotime($h['data_saida'])) : 'Atual'; ?></td>
                                        <td><?php echo round($dias); ?> dias</td>
                                        <td><?php echo $h['observacoes'] ?: '-'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            Nenhum histórico de ocupação encontrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Manutenções -->
                <div class="tab-pane fade" id="manutencao">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>Histórico de Manutenções</h6>
                        <a href="acoes/manutencoes/cadastrar.php?piquete_id=<?php echo $id; ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle"></i> Nova Manutenção
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Custo</th>
                                    <th>Responsável</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($manutencoes && $manutencoes->num_rows > 0): ?>
                                    <?php while ($m = $manutencoes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($m['data_manutencao'])); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php 
                                                $tipos = [
                                                    'adubacao' => 'Adubação',
                                                    'calagem' => 'Calagem',
                                                    'roçada' => 'Roçada',
                                                    'plantio' => 'Plantio',
                                                    'limpeza' => 'Limpeza'
                                                ];
                                                echo $tipos[$m['tipo_manutencao']] ?? $m['tipo_manutencao'];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo $m['descricao'] ?: '-'; ?></td>
                                        <td><?php echo $m['custo'] ? 'R$ ' . number_format($m['custo'], 2, ',', '.') : '-'; ?></td>
                                        <td><?php echo $m['responsavel'] ?: '-'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            Nenhuma manutenção registrada.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recomendações -->
                <div class="tab-pane fade" id="recomendacoes">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-calendar-check text-success"></i> Próxima Manutenção</h6>
                                    <?php
                                    $proxManutencao = null;
                                    if ($piquete['data_ultima_manutencao']) {
                                        $ultima = new DateTime($piquete['data_ultima_manutencao']);
                                        $proxManutencao = $ultima->modify('+90 days'); // Recomendação a cada 3 meses
                                    }
                                    ?>
                                    <p class="mt-2">
                                        <?php if ($proxManutencao): ?>
                                            <strong><?php echo $proxManutencao->format('d/m/Y'); ?></strong>
                                            <?php if ($proxManutencao < new DateTime()): ?>
                                                <span class="badge bg-danger ms-2">Atrasada</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Nenhuma manutenção registrada
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-arrow-repeat text-success"></i> Rotação de Pasto</h6>
                                    <?php
                                    if ($piquete['dias_ocupacao'] && $piquete['dias_descanso']) {
                                        $ocupadoHa = $piquete['data_ultima_ocupacao'] 
                                            ? (new DateTime())->diff(new DateTime($piquete['data_ultima_ocupacao']))->days 
                                            : 0;
                                        
                                        if (!$piquete['disponivel']) {
                                            $diasRestantes = $piquete['dias_ocupacao'] - $ocupadoHa;
                                            echo "<p>Ocupado há <strong>$ocupadoHa</strong> dias<br>";
                                            if ($diasRestantes > 0) {
                                                echo "Deve ser desocupado em <strong>$diasRestantes</strong> dias</p>";
                                            } else {
                                                echo "<span class='badge bg-warning'>Excedeu tempo de ocupação</span></p>";
                                            }
                                        } else {
                                            echo "<p>Em descanso há <strong>$ocupadoHa</strong> dias<br>";
                                            if ($ocupadoHa < $piquete['dias_descanso']) {
                                                $diasRestantes = $piquete['dias_descanso'] - $ocupadoHa;
                                                echo "Pode ser ocupado em <strong>$diasRestantes</strong> dias</p>";
                                            } else {
                                                echo "<span class='badge bg-success'>Pronto para ocupação</span></p>";
                                            }
                                        }
                                    } else {
                                        echo "<p class='text-muted'>Configure dias de ocupação e descanso</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>