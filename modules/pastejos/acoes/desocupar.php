<?php
// modules/pastejos/acoes/desocupar.php
// Desocupar piquete (remover animais)

require_once '../../../config/database.php';
require_once '../../../config/constants.php';
require_once '../../../includes/functions.php';
require_once '../../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    redirect(BASE_URL . 'modules/fazendas/selector.php');
}

// Verificar se recebeu ID do piquete
$piquete_id = isset($_GET['piquete_id']) ? intval($_GET['piquete_id']) : 0;
if ($piquete_id <= 0) {
    setAlert('ID do piquete inválido.', 'danger');
    redirect('../index.php');
}

// Buscar dados do piquete
$sqlPiquete = "SELECT * FROM piquetes WHERE id = $piquete_id AND " . TenantManager::addTenantFilter();
$resultPiquete = executeQuery($sqlPiquete);

if (!$resultPiquete || $resultPiquete->num_rows == 0) {
    setAlert('Piquete não encontrado.', 'danger');
    redirect('../index.php');
}

$piquete = $resultPiquete->fetch_assoc();

// Verificar se o piquete está ocupado
if ($piquete['disponivel']) {
    setAlert('Este piquete já está disponível.', 'warning');
    redirect('../visualizar.php?id=' . $piquete_id);
}

// Buscar animais atualmente no piquete
$sqlAnimais = "SELECT b.*, r.nome_raca 
               FROM bovinos b
               LEFT JOIN racas r ON b.id_raca = r.id
               WHERE b.id_piquete_atual = $piquete_id AND b.ativo = 1";
$animais = executeQuery($sqlAnimais);

$pageTitle = 'Desocupar Piquete - ' . $piquete['nome_piquete'];
$error = '';
$success = '';

// Processar formulário de desocupação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $animais_selecionados = isset($_POST['animais']) ? $_POST['animais'] : [];
    $data_saida = $_POST['data_saida'];
    $observacoes = !empty($_POST['observacoes']) ? escapeString($_POST['observacoes']) : '';
    $acao = $_POST['acao'] ?? 'desocupar'; // desocupar ou remover_parcial
    
    if (empty($animais_selecionados)) {
        $error = 'Selecione pelo menos um animal para remover.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            $qtd_animais = count($animais_selecionados);
            
            // Atualizar o histórico para cada animal selecionado
            foreach ($animais_selecionados as $animal_id) {
                $animal_id = intval($animal_id);
                
                // Buscar o registro de histórico ativo (sem data_saida)
                $sqlHistorico = "SELECT id FROM historico_piquete 
                                WHERE id_piquete = $piquete_id 
                                AND id_bovino = $animal_id 
                                AND data_saida IS NULL 
                                ORDER BY data_entrada DESC LIMIT 1";
                $resultHistorico = $conn->query($sqlHistorico);
                
                if ($resultHistorico && $resultHistorico->num_rows > 0) {
                    $historico = $resultHistorico->fetch_assoc();
                    
                    // Atualizar data_saida no histórico
                    $sqlUpdateHistorico = "UPDATE historico_piquete SET 
                                          data_saida = '$data_saida',
                                          observacoes = CONCAT(IFNULL(observacoes, ''), ' | Saída: $observacoes')
                                          WHERE id = " . $historico['id'];
                    if (!$conn->query($sqlUpdateHistorico)) {
                        throw new Exception('Erro ao atualizar histórico.');
                    }
                }
                
                // Remover o animal do piquete
                $sqlAnimal = "UPDATE bovinos SET id_piquete_atual = NULL WHERE id = $animal_id";
                if (!$conn->query($sqlAnimal)) {
                    throw new Exception('Erro ao remover animal do piquete.');
                }
            }
            
            // Calcular nova lotação
            $novaLotacao = $piquete['lotacao_atual'] - $qtd_animais;
            
            // Verificar se ainda há animais no piquete
            $sqlVerifica = "SELECT COUNT(*) as total FROM bovinos WHERE id_piquete_atual = $piquete_id";
            $resultVerifica = $conn->query($sqlVerifica);
            $animaisRestantes = $resultVerifica->fetch_assoc()['total'];
            
            // Atualizar status do piquete
            $disponivel = ($animaisRestantes == 0) ? 1 : 0;
            
            $sqlUpdatePiquete = "UPDATE piquetes SET 
                                lotacao_atual = $animaisRestantes,
                                disponivel = $disponivel
                                WHERE id = $piquete_id";
            
            if (!$conn->query($sqlUpdatePiquete)) {
                throw new Exception('Erro ao atualizar piquete.');
            }
            
            $conn->commit();
            
            if ($animaisRestantes == 0) {
                setAlert('Piquete desocupado completamente com sucesso!', 'success');
            } else {
                setAlert($qtd_animais . ' animais removidos. ' . $animaisRestantes . ' animais permanecem no piquete.', 'success');
            }
            
            redirect('../visualizar.php?id=' . $piquete_id);
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-arrow-left-circle me-2 text-warning"></i>
                Desocupar Piquete - <?php echo $piquete['nome_piquete']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="../visualizar.php?id=<?php echo $piquete_id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item active">Desocupar</li>
                </ol>
            </nav>
        </div>
        <a href="../visualizar.php?id=<?php echo $piquete_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Informações do piquete -->
    <div class="alert alert-warning mb-4">
        <div class="row">
            <div class="col-md-4">
                <strong>Lotação atual:</strong> <?php echo $piquete['lotacao_atual']; ?> animais
            </div>
            <div class="col-md-4">
                <strong>Ocupado desde:</strong> 
                <?php 
                if ($piquete['data_ultima_ocupacao']) {
                    echo date('d/m/Y', strtotime($piquete['data_ultima_ocupacao']));
                    $dias = (new DateTime())->diff(new DateTime($piquete['data_ultima_ocupacao']))->days;
                    echo " ($dias dias)";
                }
                ?>
            </div>
            <div class="col-md-4">
                <strong>Capacidade:</strong> <?php echo $piquete['capacidade_suporte'] ?: 'Ilimitada'; ?> animais
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Selecione os animais para remover</h5>
            <div>
                <span class="badge bg-warning">Total: <?php echo $animais ? $animais->num_rows : 0; ?> animais</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="formDesocupar">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Data de Saída</label>
                        <input type="date" class="form-control" name="data_saida" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <?php if ($animais && $animais->num_rows > 0): ?>
                    
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="selecionarTodos()">
                                <i class="bi bi-check-all"></i> Selecionar Todos
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="selecionarMetade()">
                                <i class="bi bi-symmetry-horizontal"></i> Metade
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="limparSelecao()">
                                <i class="bi bi-x"></i> Limpar
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" onchange="toggleTodos(this)">
                                    </th>
                                    <th>Brinco</th>
                                    <th>Nome</th>
                                    <th>Raça</th>
                                    <th>Sexo</th>
                                    <th>Peso</th>
                                    <th>Dias no piquete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $animal_count = 0;
                                while ($animal = $animais->fetch_assoc()): 
                                    $animal_count++;
                                    
                                    // Calcular dias no piquete
                                    $dias_no_piquete = 0;
                                    if ($piquete['data_ultima_ocupacao']) {
                                        $entrada = new DateTime($piquete['data_ultima_ocupacao']);
                                        $hoje = new DateTime();
                                        $dias_no_piquete = $entrada->diff($hoje)->days;
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="animais[]" value="<?php echo $animal['id']; ?>" class="animal-check">
                                    </td>
                                    <td><strong><?php echo $animal['brinco']; ?></strong></td>
                                    <td><?php echo $animal['nome'] ?: '-'; ?></td>
                                    <td><?php echo $animal['nome_raca'] ?: '-'; ?></td>
                                    <td>
                                        <?php if ($animal['sexo'] == 'M'): ?>
                                            <span class="badge bg-primary">Macho</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Fêmea</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $animal['peso_atual'] ? number_format($animal['peso_atual'], 2, ',', '.') . ' kg' : '-'; ?></td>
                                    <td><?php echo $dias_no_piquete; ?> dias</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Observações sobre a saída</label>
                            <textarea class="form-control" name="observacoes" rows="3" 
                                      placeholder="Ex: Animais transferidos para outro piquete, motivo da saída..."></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Resumo:</strong> 
                                <span id="resumoSelecao">Nenhum animal selecionado</span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="../visualizar.php?id=<?php echo $piquete_id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" name="acao" value="remover_parcial" class="btn btn-warning">
                            <i class="bi bi-arrow-left-circle"></i> Remover Selecionados
                        </button>
                        <button type="submit" name="acao" value="desocupar" class="btn btn-danger" 
                                onclick="return confirm('Tem certeza que deseja remover TODOS os animais do piquete?')">
                            <i class="bi bi-trash"></i> Desocupar Completo
                        </button>
                    </div>

                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-emoji-frown display-4 text-muted"></i>
                        <p class="text-muted mt-2">Não há animais neste piquete no momento.</p>
                        <a href="../visualizar.php?id=<?php echo $piquete_id; ?>" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</main>

<script>
function toggleTodos(checkbox) {
    var checkboxes = document.querySelectorAll('.animal-check');
    checkboxes.forEach(function(cb) {
        cb.checked = checkbox.checked;
    });
    atualizarResumo();
}

function selecionarTodos() {
    var checkboxes = document.querySelectorAll('.animal-check');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    document.querySelector('input[onchange="toggleTodos(this)"]').checked = true;
    atualizarResumo();
}

function selecionarMetade() {
    var checkboxes = document.querySelectorAll('.animal-check');
    var metade = Math.ceil(checkboxes.length / 2);
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = i < metade;
    }
    document.querySelector('input[onchange="toggleTodos(this)"]').checked = false;
    atualizarResumo();
}

function limparSelecao() {
    var checkboxes = document.querySelectorAll('.animal-check');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    document.querySelector('input[onchange="toggleTodos(this)"]').checked = false;
    atualizarResumo();
}

// Adicionar evento de change para todos os checkboxes
document.addEventListener('DOMContentLoaded', function() {
    var checkboxes = document.querySelectorAll('.animal-check');
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', atualizarResumo);
    });
});

function atualizarResumo() {
    var checkboxes = document.querySelectorAll('.animal-check:checked');
    var total = checkboxes.length;
    
    if (total > 0) {
        document.getElementById('resumoSelecao').innerHTML = 
            '<strong>' + total + '</strong> animal(is) selecionado(s) para remoção';
    } else {
        document.getElementById('resumoSelecao').innerHTML = 'Nenhum animal selecionado';
    }
}

// Confirmar antes de enviar
document.getElementById('formDesocupar').addEventListener('submit', function(e) {
    var checkboxes = document.querySelectorAll('.animal-check:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Selecione pelo menos um animal para remover.');
    }
});
</script>

<?php include '../../../includes/footer.php'; ?>