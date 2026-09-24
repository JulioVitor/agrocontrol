<?php
// modules/pastejos/editar.php
// Editar piquete existente

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

$pageTitle = 'Editar Piquete: ' . $piquete['nome_piquete'];
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
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    // Campos de data (opcionais)
    $data_ultima_ocupacao = !empty($_POST['data_ultima_ocupacao']) ? "'" . $_POST['data_ultima_ocupacao'] . "'" : "NULL";
    $data_ultima_manutencao = !empty($_POST['data_ultima_manutencao']) ? "'" . $_POST['data_ultima_manutencao'] . "'" : "NULL";
    
    if (empty($nome_piquete)) {
        $error = 'Nome do piquete é obrigatório.';
    } else {
        
        // Verificar se já existe outro piquete com este nome (exceto o atual)
        $checkSql = "SELECT id FROM piquetes WHERE " . TenantManager::addTenantFilter() . " 
                     AND nome_piquete = '$nome_piquete' AND id != $id";
        $checkResult = executeQuery($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $error = 'Já existe outro piquete com este nome.';
        } else {
            
            // Se o piquete for marcado como disponível mas tem animais, ajustar
            if ($disponivel && $piquete['lotacao_atual'] > 0) {
                // Manter como não disponível
                $disponivel = 0;
                $error_msg = 'O piquete não pode ser marcado como disponível pois possui animais.';
            }
            
            $sql = "UPDATE piquetes SET 
                    nome_piquete = '$nome_piquete',
                    codigo = $codigo,
                    area_hectares = $area_hectares,
                    comprimento_m = $comprimento_m,
                    largura_m = $largura_m,
                    tipo_pasto = $tipo_pasto,
                    capacidade_suporte = $capacidade_suporte,
                    dias_descanso = $dias_descanso,
                    dias_ocupacao = $dias_ocupacao,
                    cerca_eletrica = $cerca_eletrica,
                    agua_disponivel = $agua_disponivel,
                    sombra_disponivel = $sombra_disponivel,
                    disponivel = $disponivel,
                    data_ultima_ocupacao = $data_ultima_ocupacao,
                    data_ultima_manutencao = $data_ultima_manutencao,
                    observacoes = $observacoes
                    WHERE id = $id";
            
            if (executeQuery($sql)) {
                if (isset($error_msg)) {
                    setAlert('Piquete atualizado, mas ' . $error_msg, 'warning');
                } else {
                    setAlert('Piquete atualizado com sucesso!', 'success');
                }
                redirect('visualizar.php?id=' . $id);
            } else {
                $error = 'Erro ao atualizar piquete.';
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
                <i class="bi bi-pencil me-2 text-warning"></i>
                Editar Piquete: <?php echo $piquete['nome_piquete']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="visualizar.php?id=<?php echo $id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                <i class="bi bi-eye"></i> Visualizar
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
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
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="formTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="basicos-tab" data-bs-toggle="tab" data-bs-target="#basicos" type="button">
                        <i class="bi bi-info-circle"></i> Dados Básicos
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="dimensoes-tab" data-bs-toggle="tab" data-bs-target="#dimensoes" type="button">
                        <i class="bi bi-rulers"></i> Dimensões
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="manejo-tab" data-bs-toggle="tab" data-bs-target="#manejo" type="button">
                        <i class="bi bi-gear"></i> Manejo
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="recursos-tab" data-bs-toggle="tab" data-bs-target="#recursos" type="button">
                        <i class="bi bi-grid"></i> Recursos
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button">
                        <i class="bi bi-activity"></i> Status
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="tab-content">
                    <!-- Aba: Dados Básicos -->
                    <div class="tab-pane fade show active" id="basicos" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Piquete *</label>
                                <input type="text" class="form-control" name="nome_piquete" 
                                       value="<?php echo $piquete['nome_piquete']; ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Código</label>
                                <input type="text" class="form-control" name="codigo" 
                                       value="<?php echo $piquete['codigo'] ?: ''; ?>" 
                                       placeholder="Ex: P01, A-12">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Pasto</label>
                                <input type="text" class="form-control" name="tipo_pasto" 
                                       value="<?php echo $piquete['tipo_pasto'] ?: ''; ?>" 
                                       placeholder="Ex: Braquiária, Tifton, Mombaça">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes" rows="3"><?php echo $piquete['observacoes'] ?: ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Dimensões -->
                    <div class="tab-pane fade" id="dimensoes" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Área (hectares)</label>
                                <input type="number" step="0.01" class="form-control" name="area_hectares" 
                                       value="<?php echo $piquete['area_hectares'] ?: ''; ?>">
                                <small class="text-muted">1 hectare = 10.000 m²</small>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Comprimento (metros)</label>
                                <input type="number" step="0.1" class="form-control" name="comprimento_m" 
                                       value="<?php echo $piquete['comprimento_m'] ?: ''; ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Largura (metros)</label>
                                <input type="number" step="0.1" class="form-control" name="largura_m" 
                                       value="<?php echo $piquete['largura_m'] ?: ''; ?>">
                            </div>
                            
                            <?php if ($piquete['comprimento_m'] && $piquete['largura_m']): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-calculator"></i>
                                    Área calculada: <strong><?php echo number_format($piquete['comprimento_m'] * $piquete['largura_m'] / 10000, 2, ',', '.'); ?> hectares</strong>
                                    <?php if ($piquete['area_hectares']): ?>
                                        (diferença: <?php echo number_format(abs(($piquete['comprimento_m'] * $piquete['largura_m'] / 10000) - $piquete['area_hectares']), 2, ',', '.'); ?> ha)
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aba: Manejo -->
                    <div class="tab-pane fade" id="manejo" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Capacidade de Suporte</label>
                                <input type="number" class="form-control" name="capacidade_suporte" 
                                       value="<?php echo $piquete['capacidade_suporte'] ?: ''; ?>" 
                                       placeholder="Nº máximo de animais">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Dias de Descanso</label>
                                <input type="number" class="form-control" name="dias_descanso" 
                                       value="<?php echo $piquete['dias_descanso'] ?: ''; ?>" 
                                       placeholder="Ex: 30">
                                <small class="text-muted">Tempo sem animais</small>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Dias de Ocupação</label>
                                <input type="number" class="form-control" name="dias_ocupacao" 
                                       value="<?php echo $piquete['dias_ocupacao'] ?: ''; ?>" 
                                       placeholder="Ex: 7">
                                <small class="text-muted">Tempo com animais</small>
                            </div>
                            
                            <div class="col-12">
                                <div class="alert <?php echo ($piquete['dias_ocupacao'] && $piquete['dias_descanso']) ? 'alert-success' : 'alert-secondary'; ?>">
                                    <h6>Ciclo de Rotação:</h6>
                                    <?php if ($piquete['dias_ocupacao'] && $piquete['dias_descanso']): ?>
                                        <p class="mb-0">
                                            <strong><?php echo $piquete['dias_ocupacao']; ?></strong> dias ocupado + 
                                            <strong><?php echo $piquete['dias_descanso']; ?></strong> dias descanso = 
                                            <strong><?php echo $piquete['dias_ocupacao'] + $piquete['dias_descanso']; ?></strong> dias ciclo total
                                        </p>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted">Configure os dias de ocupação e descanso para calcular o ciclo</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Recursos -->
                    <div class="tab-pane fade" id="recursos" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label mb-3">Recursos Disponíveis</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="cerca_eletrica" 
                                                   id="cerca_eletrica" <?php echo $piquete['cerca_eletrica'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="cerca_eletrica">
                                                <i class="bi bi-lightning text-warning"></i> Cerca Elétrica
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="agua_disponivel" 
                                                   id="agua_disponivel" <?php echo $piquete['agua_disponivel'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="agua_disponivel">
                                                <i class="bi bi-droplet text-primary"></i> Água Disponível
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="sombra_disponivel" 
                                                   id="sombra_disponivel" <?php echo $piquete['sombra_disponivel'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="sombra_disponivel">
                                                <i class="bi bi-cloud-sun text-success"></i> Sombra Disponível
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Status -->
                    <div class="tab-pane fade" id="status" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="disponivel" 
                                           id="disponivel" <?php echo $piquete['disponivel'] ? 'checked' : ''; ?>
                                           <?php echo ($piquete['lotacao_atual'] > 0) ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="disponivel">
                                        <strong>Piquete Disponível</strong>
                                    </label>
                                    <?php if ($piquete['lotacao_atual'] > 0): ?>
                                        <br><small class="text-warning">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            Não pode ser marcado como disponível pois possui <?php echo $piquete['lotacao_atual']; ?> animais
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert bg-light">
                                    <strong>Lotação atual:</strong> <?php echo $piquete['lotacao_atual']; ?> animais
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Data da Última Ocupação</label>
                                <input type="date" class="form-control" name="data_ultima_ocupacao" 
                                       value="<?php echo $piquete['data_ultima_ocupacao'] ?: ''; ?>">
                                <small class="text-muted">Quando foi ocupado pela última vez</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Data da Última Manutenção</label>
                                <input type="date" class="form-control" name="data_ultima_manutencao" 
                                       value="<?php echo $piquete['data_ultima_manutencao'] ?: ''; ?>">
                                <small class="text-muted">Quando foi feita a última manutenção</small>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <h6>Informações do Sistema</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Data de Cadastro:</small>
                                        <strong><?php echo date('d/m/Y H:i', strtotime($piquete['data_cadastro'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Salvar Alterações
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

// Calcular área automaticamente (opcional)
document.addEventListener('DOMContentLoaded', function() {
    var comprimento = document.querySelector('input[name="comprimento_m"]');
    var largura = document.querySelector('input[name="largura_m"]');
    var area = document.querySelector('input[name="area_hectares"]');
    
    function calcularArea() {
        if (comprimento.value && largura.value) {
            var areaCalculada = (parseFloat(comprimento.value) * parseFloat(largura.value)) / 10000;
            if (!area.value) {
                area.value = areaCalculada.toFixed(2);
            }
        }
    }
    
    comprimento.addEventListener('blur', calcularArea);
    largura.addEventListener('blur', calcularArea);
});
</script>

<?php include '../../includes/footer.php'; ?>