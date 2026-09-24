<?php
// modules/bovinos/acoes/pesagem/editar.php
// Editar uma pesagem existente

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

// Verificar se recebeu ID da pesagem
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;

if ($id <= 0 || $bovino_id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect(BASE_URL . 'modules/bovinos/index.php');
}

// Buscar dados da pesagem
$sql = "SELECT * FROM pesagens WHERE id = $id AND id_bovino = $bovino_id";
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Pesagem não encontrada.', 'danger');
    redirect("index.php?bovino_id=$bovino_id");
}

$pesagem = $result->fetch_assoc();

// Buscar dados do bovino
$sqlBovino = "SELECT id, brinco, nome FROM bovinos WHERE id = $bovino_id AND " . TenantManager::addTenantFilter();
$resultBovino = executeQuery($sqlBovino);
$bovino = $resultBovino->fetch_assoc();

$pageTitle = 'Editar Pesagem';
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data_pesagem = $_POST['data_pesagem'];
    $peso = floatval($_POST['peso']);
    $observacoes = escapeString($_POST['observacoes']);
    
    if (empty($data_pesagem) || $peso <= 0) {
        $error = 'Data e peso são obrigatórios.';
    } else {
        
        $sql = "UPDATE pesagens SET 
                data_pesagem = '$data_pesagem',
                peso = $peso,
                observacoes = " . ($observacoes ? "'$observacoes'" : "NULL") . "
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            // Atualizar peso atual do bovino (pegar a pesagem mais recente)
            $sqlUltima = "SELECT peso FROM pesagens 
                          WHERE id_bovino = $bovino_id 
                          ORDER BY data_pesagem DESC LIMIT 1";
            $resultUltima = executeQuery($sqlUltima);
            $ultima = $resultUltima->fetch_assoc();
            
            $updateSql = "UPDATE bovinos SET peso_atual = {$ultima['peso']} WHERE id = $bovino_id";
            executeQuery($updateSql);
            
            setAlert('Pesagem atualizada com sucesso!', 'success');
            redirect("index.php?bovino_id=$bovino_id");
        } else {
            $error = 'Erro ao atualizar pesagem.';
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
                Editar Pesagem
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $bovino_id; ?>"><?php echo $bovino['brinco']; ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?bovino_id=<?php echo $bovino_id; ?>">Pesagens</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <a href="index.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Voltar
        </a>
    </div>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Data da Pesagem *</label>
                        <input type="date" class="form-control" name="data_pesagem" 
                               value="<?php echo $pesagem['data_pesagem']; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Peso (kg) *</label>
                        <input type="number" step="0.01" class="form-control" name="peso" 
                               value="<?php echo $pesagem['peso']; ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"><?php echo $pesagem['observacoes']; ?></textarea>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-2"></i>
                        Salvar Alterações
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