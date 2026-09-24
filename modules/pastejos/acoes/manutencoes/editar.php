<?php
// modules/pastejos/acoes/manutencao/editar.php
// Editar manutenção de piquete

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

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$piquete_id = isset($_GET['piquete_id']) ? intval($_GET['piquete_id']) : 0;

if ($id <= 0 || $piquete_id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('../../index.php');
}

// Buscar dados da manutenção
$sql = "SELECT * FROM manutencao_piquete WHERE id = $id AND id_piquete = $piquete_id";
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Manutenção não encontrada.', 'danger');
    redirect("index.php?piquete_id=$piquete_id");
}

$manutencao = $result->fetch_assoc();

// Buscar dados do piquete
$sqlPiquete = "SELECT * FROM piquetes WHERE id = $piquete_id AND " . TenantManager::addTenantFilter();
$resultPiquete = executeQuery($sqlPiquete);
$piquete = $resultPiquete->fetch_assoc();

$pageTitle = 'Editar Manutenção';
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
        
        $sql = "UPDATE manutencao_piquete SET 
                tipo_manutencao = '$tipo_manutencao',
                data_manutencao = '$data_manutencao',
                descricao = " . ($descricao ? "'$descricao'" : "NULL") . ",
                custo = $custo,
                responsavel = $responsavel
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            
            // Verificar se esta é a manutenção mais recente
            $checkSql = "SELECT MAX(data_manutencao) as ultima FROM manutencao_piquete WHERE id_piquete = $piquete_id";
            $checkResult = executeQuery($checkSql);
            $ultima = $checkResult->fetch_assoc()['ultima'];
            
            if ($ultima == $data_manutencao) {
                $updateSql = "UPDATE piquetes SET data_ultima_manutencao = '$data_manutencao' WHERE id = $piquete_id";
                executeQuery($updateSql);
            }
            
            setAlert('Manutenção atualizada com sucesso!', 'success');
            redirect("index.php?piquete_id=$piquete_id");
        } else {
            $error = 'Erro ao atualizar manutenção.';
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
                <i class="bi bi-pencil me-2 text-warning"></i>
                Editar Manutenção - <?php echo $piquete['nome_piquete']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Pastagens</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $piquete_id; ?>"><?php echo $piquete['nome_piquete']; ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?piquete_id=<?php echo $piquete_id; ?>">Manutenções</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <a href="index.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-outline-secondary">
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
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Manutenção *</label>
                        <select class="form-select" name="tipo_manutencao" required>
                            <option value="">Selecione</option>
                            <option value="adubacao" <?php echo $manutencao['tipo_manutencao'] == 'adubacao' ? 'selected' : ''; ?>>Adubação</option>
                            <option value="calagem" <?php echo $manutencao['tipo_manutencao'] == 'calagem' ? 'selected' : ''; ?>>Calagem</option>
                            <option value="roçada" <?php echo $manutencao['tipo_manutencao'] == 'roçada' ? 'selected' : ''; ?>>Roçada</option>
                            <option value="plantio" <?php echo $manutencao['tipo_manutencao'] == 'plantio' ? 'selected' : ''; ?>>Plantio</option>
                            <option value="limpeza" <?php echo $manutencao['tipo_manutencao'] == 'limpeza' ? 'selected' : ''; ?>>Limpeza</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data da Manutenção *</label>
                        <input type="date" class="form-control" name="data_manutencao" 
                               value="<?php echo $manutencao['data_manutencao']; ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"><?php echo $manutencao['descricao']; ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Custo (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" name="custo" 
                                   value="<?php echo $manutencao['custo']; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="responsavel" 
                               value="<?php echo $manutencao['responsavel'] ?: $_SESSION['usuario_nome']; ?>">
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?piquete_id=<?php echo $piquete_id; ?>" class="btn btn-secondary">
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
</script>

<?php include '../../../../includes/footer.php'; ?>