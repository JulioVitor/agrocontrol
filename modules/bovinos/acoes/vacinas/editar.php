<?php
// modules/bovinos/acoes/vacinas/editar.php
// Editar aplicação de vacina

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

// Buscar dados da aplicação
$sql = "SELECT av.*, b.brinco, b.nome as nome_bovino
        FROM aplicacoes_vacinas av
        JOIN bovinos b ON av.id_bovino = b.id
        WHERE av.id = $id AND av.id_bovino = $bovino_id";
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Registro não encontrado.', 'danger');
    redirect("index.php?bovino_id=$bovino_id");
}

$aplicacao = $result->fetch_assoc();

// Buscar lista de vacinas
$vacinasSql = "SELECT id, nome_vacina, fabricante FROM vacinas WHERE " . TenantManager::addTenantFilter() . " ORDER BY nome_vacina";
$vacinas = executeQuery($vacinasSql);

$pageTitle = 'Editar Vacina';
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_vacina = intval($_POST['id_vacina']);
    $data_aplicacao = $_POST['data_aplicacao'];
    $dose_ml = !empty($_POST['dose_ml']) ? floatval($_POST['dose_ml']) : 'NULL';
    $lote = !empty($_POST['lote']) ? "'" . escapeString($_POST['lote']) . "'" : "NULL";
    $via_aplicacao = !empty($_POST['via_aplicacao']) ? "'" . escapeString($_POST['via_aplicacao']) . "'" : "NULL";
    $responsavel = !empty($_POST['responsavel']) ? "'" . escapeString($_POST['responsavel']) . "'" : "NULL";
    $proxima_dose = !empty($_POST['proxima_dose']) ? "'" . $_POST['proxima_dose'] . "'" : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    $sql = "UPDATE aplicacoes_vacinas SET 
            id_vacina = $id_vacina,
            data_aplicacao = '$data_aplicacao',
            dose_ml = $dose_ml,
            lote = $lote,
            via_aplicacao = $via_aplicacao,
            responsavel = $responsavel,
            proxima_dose = $proxima_dose,
            observacoes = $observacoes
            WHERE id = $id";
    
    if (executeQuery($sql)) {
        setAlert('Registro atualizado com sucesso!', 'success');
        redirect("index.php?bovino_id=$bovino_id");
    } else {
        $error = 'Erro ao atualizar registro.';
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
                Editar Vacina - <?php echo $aplicacao['brinco']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Bovinos</a></li>
                    <li class="breadcrumb-item"><a href="../../visualizar.php?id=<?php echo $bovino_id; ?>"><?php echo $aplicacao['brinco']; ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?bovino_id=<?php echo $bovino_id; ?>">Vacinas</a></li>
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
                        <label class="form-label">Vacina *</label>
                        <select class="form-select" name="id_vacina" required>
                            <option value="">Selecione</option>
                            <?php if ($vacinas && $vacinas->num_rows > 0): ?>
                                <?php while ($vacina = $vacinas->fetch_assoc()): ?>
                                <option value="<?php echo $vacina['id']; ?>" 
                                    <?php echo ($aplicacao['id_vacina'] == $vacina['id']) ? 'selected' : ''; ?>>
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
                               value="<?php echo $aplicacao['data_aplicacao']; ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Dose (ml)</label>
                        <input type="number" step="0.1" class="form-control" name="dose_ml" 
                               value="<?php echo $aplicacao['dose_ml']; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Lote</label>
                        <input type="text" class="form-control" name="lote" 
                               value="<?php echo $aplicacao['lote']; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Via de Aplicação</label>
                        <select class="form-select" name="via_aplicacao">
                            <option value="">Selecione</option>
                            <option value="Intramuscular" <?php echo ($aplicacao['via_aplicacao'] == 'Intramuscular') ? 'selected' : ''; ?>>Intramuscular (IM)</option>
                            <option value="Subcutânea" <?php echo ($aplicacao['via_aplicacao'] == 'Subcutânea') ? 'selected' : ''; ?>>Subcutânea (SC)</option>
                            <option value="Intravenosa" <?php echo ($aplicacao['via_aplicacao'] == 'Intravenosa') ? 'selected' : ''; ?>>Intravenosa (IV)</option>
                            <option value="Oral" <?php echo ($aplicacao['via_aplicacao'] == 'Oral') ? 'selected' : ''; ?>>Oral</option>
                            <option value="Tópica" <?php echo ($aplicacao['via_aplicacao'] == 'Tópica') ? 'selected' : ''; ?>>Tópica</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="responsavel" 
                               value="<?php echo $aplicacao['responsavel'] ?: $_SESSION['usuario_nome']; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Próxima Dose</label>
                        <input type="date" class="form-control" name="proxima_dose" 
                               value="<?php echo $aplicacao['proxima_dose']; ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"><?php echo $aplicacao['observacoes']; ?></textarea>
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