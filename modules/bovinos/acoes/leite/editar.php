<?php
// modules/bovinos/acoes/leite/editar.php
// Editar registro de produção de leite

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
$bovino_id = isset($_GET['bovino_id']) ? intval($_GET['bovino_id']) : 0;

if ($id <= 0 || $bovino_id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect(BASE_URL . 'modules/bovinos/index.php');
}

// Buscar dados da produção
$sql = "SELECT pl.*, b.brinco, b.nome as nome_bovino
        FROM producao_leite pl
        JOIN bovinos b ON pl.id_bovino = b.id
        WHERE pl.id = $id AND pl.id_bovino = $bovino_id";
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Registro não encontrado.', 'danger');
    redirect("index.php?bovino_id=$bovino_id");
}

$producao = $result->fetch_assoc();

$pageTitle = 'Editar Produção de Leite';
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data_producao = $_POST['data_producao'];
    $turno = $_POST['turno'];
    $quantidade = floatval($_POST['quantidade']);
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if (empty($data_producao) || empty($turno) || $quantidade <= 0) {
        $error = 'Todos os campos são obrigatórios.';
    } else {
        
        // Verificar se já existe outro registro para este animal/data/turno
        $checkSql = "SELECT id FROM producao_leite 
                     WHERE id_bovino = $bovino_id 
                     AND data_producao = '$data_producao' 
                     AND turno = '$turno'
                     AND id != $id";
        $checkResult = executeQuery($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $error = 'Já existe outro registro para este animal, data e turno.';
        } else {
            
            $sql = "UPDATE producao_leite SET 
                    data_producao = '$data_producao',
                    turno = '$turno',
                    quantidade_litros = $quantidade,
                    observacoes = $observacoes
                    WHERE id = $id";
            
            if (executeQuery($sql)) {
                setAlert('Registro atualizado com sucesso!', 'success');
                redirect("index.php?bovino_id=$bovino_id");
            } else {
                $error = 'Erro ao atualizar registro.';
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
                <i class="bi bi-pencil me-2 text-warning"></i>
                Editar Produção - <?php echo $producao['brinco']; ?>
            </h1>
        </div>
        <a href="index.php?bovino_id=<?php echo $bovino_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Data *</label>
                        <input type="date" class="form-control" name="data_producao" 
                               value="<?php echo $producao['data_producao']; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Turno *</label>
                        <select class="form-select" name="turno" required>
                            <option value="">Selecione</option>
                            <option value="manha" <?php echo ($producao['turno'] == 'manha') ? 'selected' : ''; ?>>Manhã</option>
                            <option value="tarde" <?php echo ($producao['turno'] == 'tarde') ? 'selected' : ''; ?>>Tarde</option>
                            <option value="noite" <?php echo ($producao['turno'] == 'noite') ? 'selected' : ''; ?>>Noite</option>
                            <option value="unico" <?php echo ($producao['turno'] == 'unico') ? 'selected' : ''; ?>>Único (dia todo)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Quantidade (litros) *</label>
                        <input type="number" step="0.1" class="form-control" name="quantidade" 
                               value="<?php echo $producao['quantidade_litros']; ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"><?php echo $producao['observacoes']; ?></textarea>
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

<?php include '../../../../includes/footer.php'; ?>