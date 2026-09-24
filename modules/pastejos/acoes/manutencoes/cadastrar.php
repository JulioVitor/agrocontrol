<?php
// modules/pastejos/acoes/manutencao/cadastrar.php
// Registrar nova manutenção de piquete

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

// Verificar se recebeu ID do piquete
$piquete_id = isset($_GET['piquete_id']) ? intval($_GET['piquete_id']) : 0;
if ($piquete_id <= 0) {
    setAlert('ID do piquete inválido.', 'danger');
    redirect('../../index.php');
}

// Buscar dados do piquete
$sqlPiquete = "SELECT * FROM piquetes WHERE id = $piquete_id AND " . TenantManager::addTenantFilter();
$resultPiquete = executeQuery($sqlPiquete);

if (!$resultPiquete || $resultPiquete->num_rows == 0) {
    setAlert('Piquete não encontrado.', 'danger');
    redirect('../../index.php');
}

$piquete = $resultPiquete->fetch_assoc();

$pageTitle = 'Nova Manutenção - ' . $piquete['nome_piquete'];
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $tipo_manutencao = $_POST['tipo_manutencao'];
    $data_manutencao = $_POST['data_manutencao'];
    $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
    $custo = !empty($_POST['custo']) ? floatval($_POST['custo']) : 'NULL';
    $responsavel = !empty($_POST['responsavel']) ? "'" . escapeString($_POST['responsavel']) . "'" : "NULL";
    
    if (empty($tipo_manutencao) || empty($data_manutencao)) {
        $error = 'Tipo de manutenção e data são obrigatórios.';
    } else {
        
        $sql = "INSERT INTO manutencao_piquete (id_piquete, tipo_manutencao, data_manutencao, descricao, custo, responsavel) 
                VALUES ($piquete_id, '$tipo_manutencao', '$data_manutencao', " . ($descricao ? "'$descricao'" : "NULL") . ", $custo, $responsavel)";
        
        if (executeQuery($sql)) {
            
            // Atualizar data da última manutenção no piquete
            $updateSql = "UPDATE piquetes SET data_ultima_manutencao = '$data_manutencao' WHERE id = $piquete_id";
            executeQuery($updateSql);
            
            setAlert('Manutenção registrada com sucesso!', 'success');
            redirect("index.php?piquete_id=$piquete_id");
        } else {
            $error = 'Erro ao registrar manutenção.';
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
                Nova Manutenção - <?php echo $piquete['nome_piquete']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $piquete_id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?piquete_id=<?php echo $piquete_id; ?>">Manutenções</a></li>
                    <li class="breadcrumb-item active">Nova</li>
                </ol>
            </nav>
        </div>
        <a href="index.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Informações do piquete -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-4">
                <strong>Piquete:</strong> <?php echo $piquete['nome_piquete']; ?>
            </div>
            <div class="col-md-4">
                <strong>Área:</strong> <?php echo number_format($piquete['area_hectares'], 2, ',', '.'); ?> ha
            </div>
            <div class="col-md-4">
                <strong>Tipo de Pasto:</strong> <?php echo $piquete['tipo_pasto'] ?: 'Não informado'; ?>
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
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Manutenção *</label>
                        <select class="form-select" name="tipo_manutencao" required>
                            <option value="">Selecione</option>
                            <option value="adubacao">Adubação</option>
                            <option value="calagem">Calagem</option>
                            <option value="roçada">Roçada</option>
                            <option value="plantio">Plantio</option>
                            <option value="limpeza">Limpeza</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data da Manutenção *</label>
                        <input type="date" class="form-control" name="data_manutencao" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3" 
                                  placeholder="Descreva detalhes da manutenção realizada..."></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Custo (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" name="custo" 
                                   placeholder="0,00">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="responsavel" 
                               value="<?php echo $_SESSION['usuario_nome']; ?>">
                    </div>
                </div>

                <!-- Recomendações baseadas no tipo -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="bi bi-lightbulb text-warning"></i> Recomendações:</h6>
                                <div id="recomendacoes">
                                    <p class="text-muted mb-0">Selecione um tipo de manutenção para ver recomendações.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Registrar Manutenção
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Recomendações por tipo de manutenção
const recomendacoes = {
    'adubacao': 'Realizar análise de solo antes da adubação. Aplicar conforme necessidade da cultura. Evitar dias de chuva intensa.',
    'calagem': 'Aplicar calcário com antecedência (3 meses antes do plantio). Incorporar ao solo. Necessário análise de solo.',
    'roçada': 'Manter altura adequada para o tipo de pasto. Não roçar muito baixo para não prejudicar a rebrota.',
    'plantio': 'Preparar o solo adequadamente. Escolher sementes de qualidade. Observar época certa para a região.',
    'limpeza': 'Remover plantas invasoras. Limpar cercas e bebedouros. Verificar cerca elétrica.'
};

document.querySelector('select[name="tipo_manutencao"]').addEventListener('change', function() {
    var tipo = this.value;
    var divRecomendacoes = document.getElementById('recomendacoes');
    
    if (tipo && recomendacoes[tipo]) {
        divRecomendacoes.innerHTML = '<p class="mb-0"><i class="bi bi-check-circle-fill text-success"></i> ' + recomendacoes[tipo] + '</p>';
    } else {
        divRecomendacoes.innerHTML = '<p class="text-muted mb-0">Selecione um tipo de manutenção para ver recomendações.</p>';
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