<?php
// modules/pastejos/acoes/alocar.php
// Alocar animais em um piquete

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

// Verificar se piquete está disponível
if (!$piquete['disponivel']) {
    setAlert('Este piquete já está ocupado.', 'warning');
    redirect('../visualizar.php?id=' . $piquete_id);
}

// Buscar animais disponíveis (sem piquete atual)
$sqlAnimais = "SELECT b.*, r.nome_raca 
               FROM bovinos b
               LEFT JOIN racas r ON b.id_raca = r.id
               WHERE " . TenantManager::addTenantFilter('b') . " 
               AND (b.id_piquete_atual IS NULL OR b.id_piquete_atual = 0)
               AND b.ativo = 1
               ORDER BY b.brinco";
$animais = executeQuery($sqlAnimais);

$pageTitle = 'Alocar Animais - ' . $piquete['nome_piquete'];
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $animais_selecionados = isset($_POST['animais']) ? $_POST['animais'] : [];
    $data_entrada = $_POST['data_entrada'];
    $observacoes = !empty($_POST['observacoes']) ? escapeString($_POST['observacoes']) : '';
    
    if (empty($animais_selecionados)) {
        $error = 'Selecione pelo menos um animal.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            $qtd_animais = count($animais_selecionados);
            
            // Verificar capacidade
            if ($piquete['capacidade_suporte'] > 0 && ($piquete['lotacao_atual'] + $qtd_animais) > $piquete['capacidade_suporte']) {
                throw new Exception('Capacidade do piquete excedida. Capacidade: ' . $piquete['capacidade_suporte'] . ', Disponível: ' . ($piquete['capacidade_suporte'] - $piquete['lotacao_atual']));
            }
            
            // Atualizar piquete
            $novaLotacao = $piquete['lotacao_atual'] + $qtd_animais;
            $disponivel = $novaLotacao < $piquete['capacidade_suporte'] ? 1 : 0;
            
            $sqlUpdate = "UPDATE piquetes SET 
                          lotacao_atual = $novaLotacao,
                          disponivel = $disponivel,
                          data_ultima_ocupacao = '$data_entrada'
                          WHERE id = $piquete_id";
            if (!$conn->query($sqlUpdate)) {
                throw new Exception('Erro ao atualizar piquete.');
            }
            
            // Alocar cada animal e registrar histórico
            foreach ($animais_selecionados as $animal_id) {
                $animal_id = intval($animal_id);
                
                // Atualizar animal
                $sqlAnimal = "UPDATE bovinos SET id_piquete_atual = $piquete_id WHERE id = $animal_id";
                if (!$conn->query($sqlAnimal)) {
                    throw new Exception('Erro ao alocar animal.');
                }
                
                // Registrar histórico
                $sqlHistorico = "INSERT INTO historico_piquete (id_piquete, id_bovino, data_entrada, observacoes) 
                                VALUES ($piquete_id, $animal_id, '$data_entrada', " . ($observacoes ? "'$observacoes'" : "NULL") . ")";
                if (!$conn->query($sqlHistorico)) {
                    throw new Exception('Erro ao registrar histórico.');
                }
            }
            
            $conn->commit();
            setAlert($qtd_animais . ' animais alocados com sucesso!', 'success');
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
                <i class="bi bi-arrow-right-circle me-2 text-success"></i>
                Alocar Animais - <?php echo $piquete['nome_piquete']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="../visualizar.php?id=<?php echo $piquete_id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item active">Alocar</li>
                </ol>
            </nav>
        </div>
        <a href="../visualizar.php?id=<?php echo $piquete_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Informações do piquete -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-3">
                <strong>Capacidade:</strong> <?php echo $piquete['capacidade_suporte'] ?: 'Ilimitada'; ?> animais
            </div>
            <div class="col-md-3">
                <strong>Lotação atual:</strong> <?php echo $piquete['lotacao_atual']; ?> animais
            </div>
            <div class="col-md-3">
                <strong>Disponível:</strong> 
                <?php 
                $vagas = $piquete['capacidade_suporte'] ? $piquete['capacidade_suporte'] - $piquete['lotacao_atual'] : '∞';
                echo $vagas; 
                ?> vagas
            </div>
            <div class="col-md-3">
                <strong>Área:</strong> <?php echo number_format($piquete['area_hectares'], 2, ',', '.'); ?> ha
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
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Selecione os animais para alocar</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="formAlocar">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Data de Entrada</label>
                        <input type="date" class="form-control" name="data_entrada" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Animais Disponíveis</label>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodos()">
                                    Selecionar Todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limparSelecao()">
                                    Limpar
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($animais && $animais->num_rows > 0): ?>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($animal = $animais->fetch_assoc()): ?>
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
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-tree display-4 text-muted"></i>
                                <p class="text-muted mt-2">Nenhum animal disponível para alocar.</p>
                                <p class="small">Todos os animais já estão alocados em outros piquetes.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="../visualizar.php?id=<?php echo $piquete_id; ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success" <?php echo (!$animais || $animais->num_rows == 0) ? 'disabled' : ''; ?>>
                        <i class="bi bi-check-circle"></i> Confirmar Alocação
                    </button>
                </div>
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
}

function selecionarTodos() {
    var checkboxes = document.querySelectorAll('.animal-check');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    document.querySelector('input[onchange="toggleTodos(this)"]').checked = true;
}

function limparSelecao() {
    var checkboxes = document.querySelectorAll('.animal-check');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    document.querySelector('input[onchange="toggleTodos(this)"]').checked = false;
}
</script>

<?php include '../../../includes/footer.php'; ?>