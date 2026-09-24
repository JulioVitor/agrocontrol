<?php
// modules/bovinos/acoes/leite/cadastrar.php
// Registrar produção de leite - VERSÃO CORRIGIDA

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

$pageTitle = 'Registrar Produção de Leite';
$error = '';
$success = '';

// Buscar todas as fêmeas ativas
$sqlFemeas = "SELECT id, brinco, nome FROM bovinos 
              WHERE " . TenantManager::addTenantFilter() . " 
              AND sexo = 'F' AND ativo = 1 
              ORDER BY brinco";
$femeas = executeQuery($sqlFemeas);

// Total de fêmeas para exibir
$sqlCount = "SELECT COUNT(*) as total FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND sexo = 'F' AND ativo = 1";
$resultCount = executeQuery($sqlCount);
$totalFemeas = $resultCount->fetch_assoc()['total'];

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $tipo_registro = isset($_POST['tipo_registro']) ? $_POST['tipo_registro'] : 'total';
    $data_producao = $_POST['data_producao'];
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if ($tipo_registro == 'total') {
        // ============================================
        // REGISTRO POR PRODUÇÃO TOTAL DA FAZENDA
        // ============================================
        $total_litros = floatval($_POST['total_litros']);
        
        if ($total_litros <= 0) {
            $error = 'Informe a quantidade total de litros.';
        } else {
            
            // Verificar se já existe registro para esta data
            $checkSql = "SELECT COUNT(*) as total FROM producao_leite 
                        WHERE data_producao = '$data_producao'";
            $checkResult = executeQuery($checkSql);
            $existe = $checkResult->fetch_assoc()['total'];
            
            if ($existe > 0) {
                $error = 'Já existe produção registrada para esta data. Se deseja adicionar mais animais, use a opção "Registro Individual".';
            } else {
                
                $conn->begin_transaction();
                $sucesso = true;
                $qtdeFemeas = 0;
                
                // Buscar todas as fêmeas ativas
                $sqlF = "SELECT id FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND sexo = 'F' AND ativo = 1";
                $resultF = $conn->query($sqlF);
                
                if ($resultF && $resultF->num_rows > 0) {
                    $qtdeFemeas = $resultF->num_rows;
                    $litrosPorAnimal = $total_litros / $qtdeFemeas;
                    
                    while ($f = $resultF->fetch_assoc()) {
                        $sql = "INSERT INTO producao_leite (id_bovino, data_producao, turno, quantidade_litros, observacoes) 
                                VALUES ({$f['id']}, '$data_producao', 'unico', $litrosPorAnimal, $observacoes)";
                        
                        if (!$conn->query($sql)) {
                            $sucesso = false;
                            break;
                        }
                    }
                } else {
                    $error = 'Não há fêmeas cadastradas na fazenda.';
                    $sucesso = false;
                }
                
                if ($sucesso && $qtdeFemeas > 0) {
                    $conn->commit();
                    setAlert('Produção total registrada com sucesso! Distribuído entre ' . $qtdeFemeas . ' animais.', 'success');
                    redirect('dashboard.php');
                } else {
                    $conn->rollback();
                    if (empty($error)) {
                        $error = 'Erro ao registrar produção total.';
                    }
                }
            }
        }
        
    } elseif ($tipo_registro == 'individual') {
        // ============================================
        // REGISTRO INDIVIDUAL POR ANIMAL
        // ============================================
        $bovino_id = intval($_POST['bovino_id']);
        $turno = $_POST['turno'];
        $quantidade = floatval($_POST['quantidade']);
        
        if ($bovino_id <= 0 || empty($turno) || $quantidade <= 0) {
            $error = 'Selecione o animal, turno e quantidade.';
        } else {
            
            // Verificar se já existe registro para este animal/data/turno
            $checkSql = "SELECT id FROM producao_leite 
                         WHERE id_bovino = $bovino_id 
                         AND data_producao = '$data_producao' 
                         AND turno = '$turno'";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe um registro para este animal, data e turno.';
            } else {
                
                $sql = "INSERT INTO producao_leite (id_bovino, data_producao, turno, quantidade_litros, observacoes) 
                        VALUES ($bovino_id, '$data_producao', '$turno', $quantidade, $observacoes)";
                
                if (executeQuery($sql)) {
                    setAlert('Produção registrada com sucesso!', 'success');
                    redirect('dashboard.php');
                } else {
                    $error = 'Erro ao registrar produção.';
                }
            }
        }
    }
}

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-plus-circle me-2 text-success"></i>
                Registrar Produção de Leite
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Novo Registro</li>
                </ol>
            </nav>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
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
            <ul class="nav nav-tabs card-header-tabs" id="tipoRegistroTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="total-tab" data-bs-toggle="tab" data-bs-target="#total" type="button" role="tab">
                        <i class="bi bi-factory"></i> Produção Total da Fazenda
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button" role="tab">
                        <i class="bi bi-person"></i> Registro Individual
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <!-- Campo oculto para tipo de registro -->
                <input type="hidden" name="tipo_registro" id="tipo_registro" value="total">
                
                <!-- Data (comum aos dois tipos) -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Data da Produção *</label>
                        <input type="date" class="form-control" name="data_producao" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <!-- Abas de conteúdo -->
                <div class="tab-content" id="tipoRegistroContent">
                    
                    <!-- Aba: Produção Total -->
                    <div class="tab-pane fade show active" id="total" role="tabpanel">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Produção Total:</strong> Use esta opção quando quiser registrar a produção total do dia, distribuindo igualmente entre todas as fêmeas.
                            <br>
                            <strong>Total de fêmeas ativas:</strong> <?php echo $totalFemeas; ?> animais
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Total de Litros *</label>
                                <div class="input-group">
                                    <span class="input-group-text">L</span>
                                    <input type="number" step="0.1" class="form-control" name="total_litros" 
                                           id="total_litros" placeholder="0.0" required>
                                </div>
                                <?php if ($totalFemeas > 0): ?>
                                <small class="text-muted">
                                    Média por animal: <strong id="mediaPorAnimal">0</strong> L
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Registro Individual -->
                    <div class="tab-pane fade" id="individual" role="tabpanel">
                        <div class="alert alert-secondary">
                            <i class="bi bi-info-circle"></i>
                            <strong>Registro Individual:</strong> Use esta opção para registrar a produção de animais específicos, por turno.
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Animal *</label>
                                <select class="form-select" name="bovino_id" id="bovino_id">
                                    <option value="">Selecione</option>
                                    <?php if ($femeas && $femeas->num_rows > 0): ?>
                                        <?php while ($f = $femeas->fetch_assoc()): ?>
                                        <option value="<?php echo $f['id']; ?>">
                                            <?php echo $f['brinco']; ?> - <?php echo $f['nome'] ?: 'Sem nome'; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Turno *</label>
                                <select class="form-select" name="turno" id="turno">
                                    <option value="">Selecione</option>
                                    <option value="manha">Manhã</option>
                                    <option value="tarde">Tarde</option>
                                    <option value="noite">Noite</option>
                                    <option value="unico">Único (dia todo)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Quantidade (litros) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">L</span>
                                    <input type="number" step="0.1" class="form-control" name="quantidade" 
                                           id="quantidade" placeholder="0.0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observações (comum aos dois tipos) -->
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Atualizar o campo hidden quando mudar de aba
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(button => {
    button.addEventListener('shown.bs.tab', function (event) {
        var tabId = event.target.id.replace('-tab', '');
        document.getElementById('tipo_registro').value = tabId;
        
        // Atualizar required fields conforme a aba ativa
        if (tabId === 'total') {
            document.getElementById('total_litros').required = true;
            document.getElementById('bovino_id').required = false;
            document.getElementById('turno').required = false;
            document.getElementById('quantidade').required = false;
        } else {
            document.getElementById('total_litros').required = false;
            document.getElementById('bovino_id').required = true;
            document.getElementById('turno').required = true;
            document.getElementById('quantidade').required = true;
        }
    });
});

// Calcular média por animal quando digitar o total
document.querySelector('input[name="total_litros"]').addEventListener('input', function() {
    let total = parseFloat(this.value) || 0;
    let totalFemeas = <?php echo $totalFemeas; ?>;
    let media = totalFemeas > 0 ? total / totalFemeas : 0;
    document.getElementById('mediaPorAnimal').textContent = media.toFixed(2);
});

// Validação do formulário
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../../../../includes/footer.php'; ?>