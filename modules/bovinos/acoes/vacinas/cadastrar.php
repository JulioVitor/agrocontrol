<?php
// modules/bovinos/acoes/vacinas/cadastrar.php
// Registrar aplicação de vacina - Versão com seletor de animais

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

// Buscar lista de vacinas disponíveis na fazenda
$vacinasSql = "SELECT id, nome_vacina, fabricante, intervalo_dias, dose_ml, via_aplicacao 
               FROM vacinas 
               WHERE " . TenantManager::addTenantFilter() . " 
               ORDER BY nome_vacina";
$vacinas = executeQuery($vacinasSql);

// Buscar todos os bovinos para o seletor
$sqlBovinos = "SELECT id, brinco, nome FROM bovinos 
               WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
               ORDER BY brinco";
$bovinos = executeQuery($sqlBovinos);

// Verificar se recebeu ID do bovino (via GET da página do animal)
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;
$step = ($bovino_id > 0) ? 2 : 1; // Se tem ID, vai direto para o formulário

$pageTitle = 'Registrar Vacina';
$error = '';
$success = '';

// Se tem bovino_id, buscar dados do animal
if ($bovino_id > 0) {
    $sqlBovino = "SELECT id, brinco, nome FROM bovinos WHERE id = $bovino_id AND " . TenantManager::addTenantFilter();
    $resultBovino = executeQuery($sqlBovino);

    if (!$resultBovino || $resultBovino->num_rows == 0) {
        setAlert('Bovino não encontrado.', 'danger');
        redirect(BASE_URL . 'modules/bovinos/index.php');
    }

    $bovino = $resultBovino->fetch_assoc();
    $pageTitle = 'Nova Vacina - ' . ($bovino['nome'] ?: $bovino['brinco']);
}

// ============================================
// STEP 1: Processar seleção do animal (quando vem do menu)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 1) {
    $bovino_id = intval($_POST['bovino_id']);
    if ($bovino_id > 0) {
        header('Location: cadastrar.php?bovino_id=' . $bovino_id);
        exit;
    } else {
        $error = 'Selecione um animal.';
    }
}

// ============================================
// STEP 2: Processar registro da vacina
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_vacina'])) {
    
    $id_vacina = intval($_POST['id_vacina']);
    $data_aplicacao = $_POST['data_aplicacao'];
    $dose_ml = !empty($_POST['dose_ml']) ? floatval($_POST['dose_ml']) : 'NULL';
    $lote = !empty($_POST['lote']) ? "'" . escapeString($_POST['lote']) . "'" : "NULL";
    $via_aplicacao = !empty($_POST['via_aplicacao']) ? "'" . escapeString($_POST['via_aplicacao']) . "'" : "NULL";
    $responsavel = !empty($_POST['responsavel']) ? "'" . escapeString($_POST['responsavel']) . "'" : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    // Calcular próxima dose baseada no intervalo da vacina
    $proxima_dose = 'NULL';
    if (isset($_POST['proxima_dose']) && !empty($_POST['proxima_dose'])) {
        $proxima_dose = "'" . $_POST['proxima_dose'] . "'";
    } else {
        // Buscar intervalo da vacina
        $sqlIntervalo = "SELECT intervalo_dias FROM vacinas WHERE id = $id_vacina";
        $resultIntervalo = executeQuery($sqlIntervalo);
        if ($resultIntervalo && $resultIntervalo->num_rows > 0) {
            $intervalo = $resultIntervalo->fetch_assoc()['intervalo_dias'];
            if ($intervalo) {
                $data = new DateTime($data_aplicacao);
                $data->modify("+$intervalo days");
                $proxima_dose = "'" . $data->format('Y-m-d') . "'";
            }
        }
    }
    
    if (empty($id_vacina) || empty($data_aplicacao)) {
        $error = 'Vacina e data são obrigatórios.';
    } else {
        
        $sql = "INSERT INTO aplicacoes_vacinas 
                (id_bovino, id_vacina, data_aplicacao, dose_ml, lote, via_aplicacao, responsavel, proxima_dose, observacoes) 
                VALUES 
                ($bovino_id, $id_vacina, '$data_aplicacao', $dose_ml, $lote, $via_aplicacao, $responsavel, $proxima_dose, $observacoes)";
        
        if (executeQuery($sql)) {
            setAlert('Vacina registrada com sucesso!', 'success');
            redirect("index.php?bovino_id=$bovino_id");
        } else {
            $error = 'Erro ao registrar vacina.';
        }
    }
}

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <?php if ($step == 1): ?>
    <!-- STEP 1: Selecionar o animal (quando vem do menu) -->
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-search"></i> Passo 1: Selecione o Animal
            </h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="step" value="1">
                
                <div class="mb-3">
                    <label class="form-label">Animal *</label>
                    <select class="form-select" name="bovino_id" required>
                        <option value="">Selecione um animal</option>
                        <?php if ($bovinos && $bovinos->num_rows > 0): ?>
                            <?php while ($a = $bovinos->fetch_assoc()): ?>
                            <option value="<?php echo $a['id']; ?>">
                                <?php echo $a['brinco']; ?> - <?php echo $a['nome'] ?: 'Sem nome'; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-arrow-right"></i> Continuar
                    </button>
                    <a href="../index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- STEP 2: Registrar a vacina (já tem o animal selecionado) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-plus-circle me-2 text-success"></i>
                Registrar Vacina - <?php echo $bovino['brinco']; ?>
                <?php if ($bovino['nome']): ?>
                    <small class="text-muted">(<?php echo $bovino['nome']; ?>)</small>
                <?php endif; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $bovino_id; ?>"><?php echo $bovino['brinco']; ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?bovino_id=<?php echo $bovino_id; ?>">Vacinas</a></li>
                    <li class="breadcrumb-item active">Registrar</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="index.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Formulário de Registro de Vacina -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="registrar_vacina" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Vacina *</label>
                        <select class="form-select" name="id_vacina" required>
                            <option value="">Selecione</option>
                            <?php if ($vacinas && $vacinas->num_rows > 0): ?>
                                <?php while ($vacina = $vacinas->fetch_assoc()): ?>
                                <option value="<?php echo $vacina['id']; ?>" 
                                        data-dose="<?php echo $vacina['dose_ml']; ?>"
                                        data-via="<?php echo $vacina['via_aplicacao']; ?>"
                                        data-intervalo="<?php echo $vacina['intervalo_dias']; ?>">
                                    <?php echo $vacina['nome_vacina']; ?> 
                                    <?php echo $vacina['fabricante'] ? '- ' . $vacina['fabricante'] : ''; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data da Aplicação *</label>
                        <input type="date" class="form-control" name="data_aplicacao" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Dose (ml)</label>
                        <input type="number" step="0.1" class="form-control" name="dose_ml" id="dose_ml">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Lote</label>
                        <input type="text" class="form-control" name="lote">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Via de Aplicação</label>
                        <select class="form-select" name="via_aplicacao" id="via_aplicacao">
                            <option value="">Selecione</option>
                            <option value="Intramuscular">Intramuscular (IM)</option>
                            <option value="Subcutânea">Subcutânea (SC)</option>
                            <option value="Intravenosa">Intravenosa (IV)</option>
                            <option value="Oral">Oral</option>
                            <option value="Tópica">Tópica</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="responsavel" 
                               value="<?php echo $_SESSION['usuario_nome']; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Próxima Dose</label>
                        <input type="date" class="form-control" name="proxima_dose" id="proxima_dose">
                        <small class="text-muted">Deixe em branco para calcular automaticamente</small>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Registrar Vacina
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
// Preencher dados da vacina selecionada
document.querySelector('select[name="id_vacina"]')?.addEventListener('change', function() {
    var option = this.options[this.selectedIndex];
    
    if (option.value) {
        var dose = option.getAttribute('data-dose');
        var via = option.getAttribute('data-via');
        var intervalo = option.getAttribute('data-intervalo');
        
        if (dose) document.getElementById('dose_ml').value = dose;
        if (via) document.getElementById('via_aplicacao').value = via;
        
        // Calcular próxima dose se houver intervalo
        if (intervalo) {
            var dataAplicacao = document.querySelector('input[name="data_aplicacao"]').value;
            if (dataAplicacao) {
                var data = new Date(dataAplicacao);
                data.setDate(data.getDate() + parseInt(intervalo));
                var ano = data.getFullYear();
                var mes = String(data.getMonth() + 1).padStart(2, '0');
                var dia = String(data.getDate()).padStart(2, '0');
                document.getElementById('proxima_dose').value = ano + '-' + mes + '-' + dia;
            }
        }
    }
});

// Recalcular próxima dose quando mudar a data
document.querySelector('input[name="data_aplicacao"]')?.addEventListener('change', function() {
    var select = document.querySelector('select[name="id_vacina"]');
    var option = select?.options[select.selectedIndex];
    var intervalo = option?.getAttribute('data-intervalo');
    
    if (intervalo && this.value) {
        var data = new Date(this.value);
        data.setDate(data.getDate() + parseInt(intervalo));
        var ano = data.getFullYear();
        var mes = String(data.getMonth() + 1).padStart(2, '0');
        var dia = String(data.getDate()).padStart(2, '0');
        document.getElementById('proxima_dose').value = ano + '-' + mes + '-' + dia;
    }
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