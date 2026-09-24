<?php
// modules/bovinos/visualizar.php
// Visualizar detalhes completos de um bovino

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

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Buscar dados do bovino com todas as relações
$sql = "SELECT b.*, 
               r.nome_raca, 
               r.tipo as tipo_raca,
               s.nome as situacao_nome,
               s.cor as situacao_cor,
               p.nome_piquete,
               pai.brinco as brinco_pai,
               pai.nome as nome_pai,
               mae.brinco as brinco_mae,
               mae.nome as nome_mae,
               u.nome as usuario_cadastro_nome
        FROM bovinos b
        LEFT JOIN racas r ON b.id_raca = r.id
        LEFT JOIN situacoes s ON b.id_situacao = s.id
        LEFT JOIN piquetes p ON b.id_piquete_atual = p.id
        LEFT JOIN bovinos pai ON b.id_pai = pai.id
        LEFT JOIN bovinos mae ON b.id_mae = mae.id
        LEFT JOIN usuarios u ON b.id_usuario_cadastro = u.id
        WHERE b.id = $id AND " . TenantManager::addTenantFilter('b');

$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Bovino não encontrado.', 'danger');
    redirect('index.php');
}

$bovino = $result->fetch_assoc();

// Buscar últimas pesagens
$pesagensSql = "SELECT * FROM pesagens 
                WHERE id_bovino = $id 
                ORDER BY data_pesagem DESC 
                LIMIT 5";
$pesagens = executeQuery($pesagensSql);

// Buscar últimas vacinas
$vacinasSql = "SELECT av.*, v.nome_vacina, v.intervalo_dias
               FROM aplicacoes_vacinas av
               JOIN vacinas v ON av.id_vacina = v.id
               WHERE av.id_bovino = $id
               ORDER BY av.data_aplicacao DESC
               LIMIT 5";
$vacinas = executeQuery($vacinasSql);

// Buscar produção de leite (se for fêmea)
if ($bovino['sexo'] == 'F') {
    $leiteSql = "SELECT * FROM producao_leite 
                 WHERE id_bovino = $id 
                 ORDER BY data_producao DESC 
                 LIMIT 10";
    $producaoLeite = executeQuery($leiteSql);
}

$pageTitle = 'Detalhes do Bovino - ' . ($bovino['nome'] ?: $bovino['brinco']);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-tree-fill me-2 text-success"></i>
                <?php echo $bovino['nome'] ?: $bovino['brinco']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Bovinos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-2"></i>
                Editar
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <small>Identificação</small>
                    <h5 class="mb-0"><?php echo $bovino['brinco']; ?></h5>
                    <?php if ($bovino['brinco_eletronico']): ?>
                        <small>Eletrônico: <?php echo $bovino['brinco_eletronico']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <small>Raça</small>
                    <h5 class="mb-0"><?php echo $bovino['nome_raca'] ?: 'Não definida'; ?></h5>
                    <?php if ($bovino['tipo_raca']): ?>
                        <small>Tipo: <?php echo $bovino['tipo_raca']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <small>Sexo</small>
                    <h5 class="mb-0">
                        <?php if ($bovino['sexo'] == 'M'): ?>
                            <i class="bi bi-gender-male"></i> Macho
                        <?php else: ?>
                            <i class="bi bi-gender-female"></i> Fêmea
                        <?php endif; ?>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <small>Situação</small>
                    <h5 class="mb-0">
                        <span class="badge bg-<?php echo $bovino['situacao_cor'] ?: 'secondary'; ?> p-2">
                            <?php echo $bovino['situacao_nome']; ?>
                        </span>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações detalhadas em abas -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="bovinoTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                        <i class="bi bi-info-circle me-2"></i>
                        Dados Gerais
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="genealogia-tab" data-bs-toggle="tab" data-bs-target="#genealogia" type="button" role="tab">
                        <i class="bi bi-diagram-3 me-2"></i>
                        Genealogia
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="pesagens-tab" data-bs-toggle="tab" data-bs-target="#pesagens" type="button" role="tab">
                        <i class="bi bi-bar-chart me-2"></i>
                        Pesagens
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="vacinas-tab" data-bs-toggle="tab" data-bs-target="#vacinas" type="button" role="tab">
                        <i class="bi bi-shield me-2"></i>
                        Vacinas
                    </button>
                </li>
                <?php if ($bovino['sexo'] == 'F'): ?>
                    <li class="nav-item">
                        <button class="nav-link" id="leite-tab" data-bs-toggle="tab" data-bs-target="#leite" type="button" role="tab">
                            <i class="bi bi-cup me-2"></i>
                            Produção de Leite
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="bovinoTabsContent">

                <!-- Aba: Dados Gerais -->
                <div class="tab-pane fade show active" id="dados" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Brinco:</th>
                                    <td><?php echo $bovino['brinco']; ?></td>
                                </tr>
                                <tr>
                                    <th>Brinco Eletrônico:</th>
                                    <td><?php echo $bovino['brinco_eletronico'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Nome:</th>
                                    <td><?php echo $bovino['nome'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Registro ABCRH:</th>
                                    <td><?php echo $bovino['registro_abcrh'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Registro ANC:</th>
                                    <td><?php echo $bovino['registro_anc'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Raça:</th>
                                    <td><?php echo $bovino['nome_raca'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Situação:</th>
                                    <td>
                                        <span class="badge bg-<?php echo $bovino['situacao_cor'] ?: 'secondary'; ?>">
                                            <?php echo $bovino['situacao_nome']; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Sexo:</th>
                                    <td><?php echo $bovino['sexo'] == 'M' ? 'Macho' : 'Fêmea'; ?></td>
                                </tr>
                                <tr>
                                    <th>Data Nascimento:</th>
                                    <td><?php echo $bovino['data_nascimento'] ? date('d/m/Y', strtotime($bovino['data_nascimento'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Idade:</th>
                                    <td>
                                        <?php
                                        if ($bovino['data_nascimento']) {
                                            $nasc = new DateTime($bovino['data_nascimento']);
                                            $hoje = new DateTime();
                                            $idade = $hoje->diff($nasc);
                                            echo $idade->y . ' anos, ' . $idade->m . ' meses, ' . $idade->d . ' dias';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Cor da Pelagem:</th>
                                    <td><?php echo $bovino['cor_pelagem'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Peso Atual:</th>
                                    <td><?php echo $bovino['peso_atual'] ? number_format($bovino['peso_atual'], 2, ',', '.') . ' kg' : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Piquete Atual:</th>
                                    <td><?php echo $bovino['nome_piquete'] ?: '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Origem</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Origem:</th>
                                    <td>
                                        <?php
                                        switch ($bovino['origem']) {
                                            case 'nascido':
                                                echo 'Nascido na Fazenda';
                                                break;
                                            case 'comprado':
                                                echo 'Comprado';
                                                break;
                                            case 'doacao':
                                                echo 'Doação';
                                                break;
                                            default:
                                                echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Data de Entrada:</th>
                                    <td><?php echo date('d/m/Y', strtotime($bovino['data_entrada'])); ?></td>
                                </tr>
                                <?php if ($bovino['valor_compra']): ?>
                                    <tr>
                                        <th>Valor de Compra:</th>
                                        <td>R$ <?php echo number_format($bovino['valor_compra'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Cadastro</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Cadastrado por:</th>
                                    <td><?php echo $bovino['usuario_cadastro_nome'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Data de Cadastro:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($bovino['data_cadastro'])); ?></td>
                                </tr>
                                <?php if ($bovino['data_atualizacao']): ?>
                                    <tr>
                                        <th>Última Atualização:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($bovino['data_atualizacao'])); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <?php if ($bovino['observacoes']): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Observações</h6>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br($bovino['observacoes']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Aba: Genealogia -->
                <div class="tab-pane fade" id="genealogia" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Pai</h5>
                            <?php if ($bovino['id_pai']): ?>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="100">Brinco:</th>
                                        <td>
                                            <a href="visualizar.php?id=<?php echo $bovino['id_pai']; ?>">
                                                <?php echo $bovino['brinco_pai']; ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php if ($bovino['nome_pai']): ?>
                                        <tr>
                                            <th>Nome:</th>
                                            <td><?php echo $bovino['nome_pai']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            <?php elseif ($bovino['nome_pai']): ?>
                                <p><strong>Nome:</strong> <?php echo $bovino['nome_pai']; ?> (não cadastrado)</p>
                            <?php else: ?>
                                <p class="text-muted">Não informado</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>Mãe</h5>
                            <?php if ($bovino['id_mae']): ?>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="100">Brinco:</th>
                                        <td>
                                            <a href="visualizar.php?id=<?php echo $bovino['id_mae']; ?>">
                                                <?php echo $bovino['brinco_mae']; ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php if ($bovino['nome_mae']): ?>
                                        <tr>
                                            <th>Nome:</th>
                                            <td><?php echo $bovino['nome_mae']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            <?php elseif ($bovino['nome_mae']): ?>
                                <p><strong>Nome:</strong> <?php echo $bovino['nome_mae']; ?> (não cadastrada)</p>
                            <?php else: ?>
                                <p class="text-muted">Não informado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Aba: Pesagens -->
                <div class="tab-pane fade" id="pesagens" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Últimas Pesagens</h5>
                        <div>
                            <a href="acoes/pesagem/index.php?bovino_id=<?php echo $id; ?>" class="btn btn-sm btn-info me-2">
                                <i class="bi bi-list"></i> Histórico Completo
                            </a>
                            <a href="acoes/pesagem/cadastrar.php?bovino_id=<?php echo $id; ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-plus-circle"></i> Nova Pesagem
                            </a>
                        </div>
                    </div>

                    <?php if ($pesagens && $pesagens->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Peso (kg)</th>
                                        <th>Ganho</th>
                                        <th>Observações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pesoAnterior = null;
                                    while ($pesagem = $pesagens->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($pesagem['data_pesagem'])); ?></td>
                                            <td><strong><?php echo number_format($pesagem['peso'], 2, ',', '.'); ?> kg</strong></td>
                                            <td>
                                                <?php
                                                if ($pesoAnterior) {
                                                    $ganho = $pesagem['peso'] - $pesoAnterior;
                                                    $cor = $ganho >= 0 ? 'success' : 'danger';
                                                    echo "<span class='badge bg-$cor'>" . ($ganho >= 0 ? '+' : '') . number_format($ganho, 2, ',', '.') . " kg</span>";
                                                } else {
                                                    echo '-';
                                                }
                                                $pesoAnterior = $pesagem['peso'];
                                                ?>
                                            </td>
                                            <td><?php echo $pesagem['observacoes'] ?: '-'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-2">
                            <a href="acoes/pesagem/index.php?bovino_id=<?php echo $id; ?>" class="btn btn-outline-info btn-sm">
                                Ver todas as <?php echo $pesagens->num_rows; ?> pesagens
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">Nenhuma pesagem registrada.</p>
                    <?php endif; ?>
                </div>

                <!-- Aba: Vacinas -->
                <div class="tab-pane fade" id="vacinas" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Histórico de Vacinas</h5>
                        <a href="acoes/vacinas/cadastrar.php?bovino_id=<?php echo $id; ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle"></i> Nova Aplicação
                        </a>
                    </div>

                    <?php if ($vacinas && $vacinas->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Vacina</th>
                                        <th>Dose</th>
                                        <th>Próxima Dose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($vacina = $vacinas->fetch_assoc()):
                                        $hoje = new DateTime();
                                        $proxima = $vacina['proxima_dose'] ? new DateTime($vacina['proxima_dose']) : null;
                                        $diasRestantes = $proxima ? $hoje->diff($proxima)->days : null;

                                        if ($proxima && $proxima < $hoje) {
                                            $statusClass = 'danger';
                                            $statusText = 'Atrasada';
                                        } elseif ($diasRestantes && $diasRestantes <= 7) {
                                            $statusClass = 'warning';
                                            $statusText = 'Próxima';
                                        } else {
                                            $statusClass = 'success';
                                            $statusText = 'Em dia';
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($vacina['data_aplicacao'])); ?></td>
                                            <td><?php echo $vacina['nome_vacina']; ?></td>
                                            <td><?php echo $vacina['dose_ml'] ?: '-'; ?></td>
                                            <td><?php echo $vacina['proxima_dose'] ? date('d/m/Y', strtotime($vacina['proxima_dose'])) : '-'; ?></td>
                                            <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">Nenhuma vacina registrada.</p>
                    <?php endif; ?>
                </div>

                <?php if ($bovino['sexo'] == 'F'): ?>
                    <!-- Aba: Produção de Leite -->
                    <div class="tab-pane fade" id="leite" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Produção de Leite</h5>
                            <a href="acoes/leite/cadastrar.php?php echo $id; ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-plus-circle"></i> Nova Produção
                            </a>
                        </div>

                        <?php if (isset($producaoLeite) && $producaoLeite && $producaoLeite->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Turno</th>
                                            <th>Litros</th>
                                            <th>Total do dia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalDia = [];
                                        while ($leite = $producaoLeite->fetch_assoc()):
                                            $data = $leite['data_producao'];
                                            if (!isset($totalDia[$data])) {
                                                $totalDia[$data] = 0;
                                            }
                                            $totalDia[$data] += $leite['quantidade_litros'];
                                        ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($leite['data_producao'])); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($leite['turno']) {
                                                        case 'manha':
                                                            echo 'Manhã';
                                                            break;
                                                        case 'tarde':
                                                            echo 'Tarde';
                                                            break;
                                                        case 'noite':
                                                            echo 'Noite';
                                                            break;
                                                        default:
                                                            echo $leite['turno'];
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($leite['quantidade_litros'], 2, ',', '.'); ?> L</td>
                                                <td><?php echo number_format($totalDia[$data], 2, ',', '.'); ?> L</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Nenhuma produção de leite registrada.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>