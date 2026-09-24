<?php
// modules/pastejos/cadastrar.php
// Cadastrar novo piquete

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

$pageTitle = 'Novo Piquete';
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome_piquete = escapeString(trim($_POST['nome_piquete']));
    $codigo = !empty($_POST['codigo']) ? "'" . escapeString($_POST['codigo']) . "'" : "NULL";
    $area_hectares = !empty($_POST['area_hectares']) ? floatval($_POST['area_hectares']) : "NULL";
    $comprimento_m = !empty($_POST['comprimento_m']) ? floatval($_POST['comprimento_m']) : "NULL";
    $largura_m = !empty($_POST['largura_m']) ? floatval($_POST['largura_m']) : "NULL";
    $tipo_pasto = !empty($_POST['tipo_pasto']) ? "'" . escapeString($_POST['tipo_pasto']) . "'" : "NULL";
    $capacidade_suporte = !empty($_POST['capacidade_suporte']) ? intval($_POST['capacidade_suporte']) : "NULL";
    $dias_descanso = !empty($_POST['dias_descanso']) ? intval($_POST['dias_descanso']) : "NULL";
    $dias_ocupacao = !empty($_POST['dias_ocupacao']) ? intval($_POST['dias_ocupacao']) : "NULL";
    $cerca_eletrica = isset($_POST['cerca_eletrica']) ? 1 : 0;
    $agua_disponivel = isset($_POST['agua_disponivel']) ? 1 : 0;
    $sombra_disponivel = isset($_POST['sombra_disponivel']) ? 1 : 0;
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if (empty($nome_piquete)) {
        $error = 'Nome do piquete é obrigatório.';
    } else {
        
        // Verificar se já existe piquete com este nome
        $checkSql = "SELECT id FROM piquetes WHERE " . TenantManager::addTenantFilter() . " AND nome_piquete = '$nome_piquete'";
        $checkResult = executeQuery($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $error = 'Já existe um piquete com este nome.';
        } else {
            
            $sql = "INSERT INTO piquetes (
                id_fazenda, nome_piquete, codigo, area_hectares, comprimento_m, largura_m,
                tipo_pasto, capacidade_suporte, dias_descanso, dias_ocupacao,
                cerca_eletrica, agua_disponivel, sombra_disponivel, observacoes,
                disponivel, lotacao_atual
            ) VALUES (
                $farmId, '$nome_piquete', $codigo, $area_hectares, $comprimento_m, $largura_m,
                $tipo_pasto, $capacidade_suporte, $dias_descanso, $dias_ocupacao,
                $cerca_eletrica, $agua_disponivel, $sombra_disponivel, $observacoes,
                1, 0
            )";
            
            if (executeQuery($sql)) {
                setAlert('Piquete cadastrado com sucesso!', 'success');
                redirect('index.php');
            } else {
                $error = 'Erro ao cadastrar piquete.';
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-plus-circle me-2 text-success"></i>
                Novo Piquete
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Pastagens</a></li>
                    <li class="breadcrumb-item active">Novo Piquete</li>
                </ol>
            </nav>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Voltar
        </a>
    </div>

    <!-- Mensagens -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <!-- Identificação -->
                    <div class="col-md-4">
                        <label class="form-label">Nome do Piquete *</label>
                        <input type="text" class="form-control" name="nome_piquete" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" placeholder="Ex: P01, A-12">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Pasto</label>
                        <input type="text" class="form-control" name="tipo_pasto" placeholder="Ex: Braquiária, Tifton">
                    </div>

                    <!-- Dimensões -->
                    <div class="col-md-4">
                        <label class="form-label">Área (hectares)</label>
                        <input type="number" step="0.01" class="form-control" name="area_hectares">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Comprimento (metros)</label>
                        <input type="number" step="0.1" class="form-control" name="comprimento_m">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Largura (metros)</label>
                        <input type="number" step="0.1" class="form-control" name="largura_m">
                    </div>

                    <!-- Capacidade e Manejo -->
                    <div class="col-md-4">
                        <label class="form-label">Capacidade de Suporte</label>
                        <input type="number" class="form-control" name="capacidade_suporte" placeholder="Nº de animais">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Dias de Descanso</label>
                        <input type="number" class="form-control" name="dias_descanso" placeholder="Ex: 30">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Dias de Ocupação</label>
                        <input type="number" class="form-control" name="dias_ocupacao" placeholder="Ex: 7">
                    </div>

                    <!-- Recursos -->
                    <div class="col-12">
                        <label class="form-label mb-3">Recursos Disponíveis</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cerca_eletrica" id="cerca_eletrica">
                                    <label class="form-check-label" for="cerca_eletrica">
                                        <i class="bi bi-lightning"></i> Cerca Elétrica
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="agua_disponivel" id="agua_disponivel" checked>
                                    <label class="form-check-label" for="agua_disponivel">
                                        <i class="bi bi-droplet"></i> Água Disponível
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sombra_disponivel" id="sombra_disponivel" checked>
                                    <label class="form-check-label" for="sombra_disponivel">
                                        <i class="bi bi-cloud-sun"></i> Sombra Disponível
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>
                        Salvar Piquete
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
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

<?php include '../../includes/footer.php'; ?>