<?php
// modules/configuracao/vacinas.php
// Cadastro de vacinas disponíveis

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

$pageTitle = 'Cadastro de Vacinas';
$error = '';
$success = '';

// Processar ações
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verificar se há aplicações usando esta vacina
    $checkSql = "SELECT COUNT(*) as total FROM aplicacoes_vacinas WHERE id_vacina = $id";
    $checkResult = executeQuery($checkSql);
    $total = $checkResult->fetch_assoc()['total'];
    
    if ($total > 0) {
        setAlert("Não é possível excluir esta vacina pois existem $total aplicações registradas.", 'danger');
    } else {
        $sql = "DELETE FROM vacinas WHERE id = $id AND id_fazenda = $farmId";
        if (executeQuery($sql)) {
            setAlert('Vacina excluída com sucesso!', 'success');
        } else {
            setAlert('Erro ao excluir vacina.', 'danger');
        }
    }
    redirect('vacinas.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $nome_vacina = escapeString(trim($_POST['nome_vacina']));
        $fabricante = !empty($_POST['fabricante']) ? escapeString($_POST['fabricante']) : '';
        $doenca_prevenida = !empty($_POST['doenca_prevenida']) ? escapeString($_POST['doenca_prevenida']) : '';
        $intervalo_dias = !empty($_POST['intervalo_dias']) ? intval($_POST['intervalo_dias']) : 'NULL';
        $dose_ml = !empty($_POST['dose_ml']) ? floatval($_POST['dose_ml']) : 'NULL';
        $via_aplicacao = !empty($_POST['via_aplicacao']) ? escapeString($_POST['via_aplicacao']) : '';
        $observacoes = !empty($_POST['observacoes']) ? escapeString($_POST['observacoes']) : '';
        
        if (empty($nome_vacina)) {
            $error = 'Nome da vacina é obrigatório.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM vacinas WHERE id_fazenda = $farmId AND nome_vacina = '$nome_vacina'";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe uma vacina com este nome.';
            } else {
                
                $sql = "INSERT INTO vacinas (id_fazenda, nome_vacina, fabricante, doenca_prevenida, 
                        intervalo_dias, dose_ml, via_aplicacao, observacoes) 
                        VALUES ($farmId, '$nome_vacina', " . ($fabricante ? "'$fabricante'" : "NULL") . ",
                        " . ($doenca_prevenida ? "'$doenca_prevenida'" : "NULL") . ",
                        $intervalo_dias, $dose_ml, " . ($via_aplicacao ? "'$via_aplicacao'" : "NULL") . ",
                        " . ($observacoes ? "'$observacoes'" : "NULL") . ")";
                
                if (executeQuery($sql)) {
                    $success = 'Vacina cadastrada com sucesso!';
                } else {
                    $error = 'Erro ao cadastrar vacina.';
                }
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $nome_vacina = escapeString(trim($_POST['nome_vacina']));
        $fabricante = !empty($_POST['fabricante']) ? escapeString($_POST['fabricante']) : '';
        $doenca_prevenida = !empty($_POST['doenca_prevenida']) ? escapeString($_POST['doenca_prevenida']) : '';
        $intervalo_dias = !empty($_POST['intervalo_dias']) ? intval($_POST['intervalo_dias']) : 'NULL';
        $dose_ml = !empty($_POST['dose_ml']) ? floatval($_POST['dose_ml']) : 'NULL';
        $via_aplicacao = !empty($_POST['via_aplicacao']) ? escapeString($_POST['via_aplicacao']) : '';
        $observacoes = !empty($_POST['observacoes']) ? escapeString($_POST['observacoes']) : '';
        
        if (empty($nome_vacina)) {
            $error = 'Nome da vacina é obrigatório.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM vacinas WHERE id_fazenda = $farmId AND nome_vacina = '$nome_vacina' AND id != $id";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe outra vacina com este nome.';
            } else {
                
                $sql = "UPDATE vacinas SET 
                        nome_vacina = '$nome_vacina',
                        fabricante = " . ($fabricante ? "'$fabricante'" : "NULL") . ",
                        doenca_prevenida = " . ($doenca_prevenida ? "'$doenca_prevenida'" : "NULL") . ",
                        intervalo_dias = $intervalo_dias,
                        dose_ml = $dose_ml,
                        via_aplicacao = " . ($via_aplicacao ? "'$via_aplicacao'" : "NULL") . ",
                        observacoes = " . ($observacoes ? "'$observacoes'" : "NULL") . "
                        WHERE id = $id AND id_fazenda = $farmId";
                
                if (executeQuery($sql)) {
                    $success = 'Vacina atualizada com sucesso!';
                } else {
                    $error = 'Erro ao atualizar vacina.';
                }
            }
        }
    }
}

// Buscar vacinas
$sql = "SELECT * FROM vacinas WHERE id_fazenda = $farmId ORDER BY nome_vacina";
$vacinas = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-shield-check me-2 text-success"></i>
                Cadastro de Vacinas
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Vacinas</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaVacinaModal">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Vacina
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Lista de Vacinas -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($vacinas && $vacinas->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Vacina</th>
                            <th>Fabricante</th>
                            <th>Doença</th>
                            <th>Intervalo</th>
                            <th>Dose</th>
                            <th>Via</th>
                            <th>Aplicações</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($vac = $vacinas->fetch_assoc()): 
                            $sqlCount = "SELECT COUNT(*) as total FROM aplicacoes_vacinas WHERE id_vacina = " . $vac['id'];
                            $resultCount = executeQuery($sqlCount);
                            $totalAplicacoes = $resultCount->fetch_assoc()['total'];
                        ?>
                        <tr>
                            <td><strong><?php echo $vac['nome_vacina']; ?></strong></td>
                            <td><?php echo $vac['fabricante'] ?: '-'; ?></td>
                            <td><?php echo $vac['doenca_prevenida'] ?: '-'; ?></td>
                            <td><?php echo $vac['intervalo_dias'] ? $vac['intervalo_dias'] . ' dias' : '-'; ?></td>
                            <td><?php echo $vac['dose_ml'] ? $vac['dose_ml'] . ' ml' : '-'; ?></td>
                            <td><?php echo $vac['via_aplicacao'] ?: '-'; ?></td>
                            <td><span class="badge bg-info"><?php echo $totalAplicacoes; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarVacina(<?php echo htmlspecialchars(json_encode($vac)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($totalAplicacoes == 0): ?>
                                <a href="?delete=<?php echo $vac['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Excluir vacina <?php echo $vac['nome_vacina']; ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-shield display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma vacina cadastrada</h4>
                <p class="text-muted">Cadastre as vacinas utilizadas na sua fazenda.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Nova Vacina -->
<div class="modal fade" id="novaVacinaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nova Vacina</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome da Vacina *</label>
                            <input type="text" class="form-control" name="nome_vacina" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fabricante</label>
                            <input type="text" class="form-control" name="fabricante">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Doença Prevenida</label>
                            <input type="text" class="form-control" name="doenca_prevenida">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Intervalo (dias)</label>
                            <input type="number" class="form-control" name="intervalo_dias" min="1">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Dose (ml)</label>
                            <input type="number" step="0.1" class="form-control" name="dose_ml" min="0.1">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Via de Aplicação</label>
                            <select class="form-select" name="via_aplicacao">
                                <option value="">Selecione</option>
                                <option value="Intramuscular">Intramuscular</option>
                                <option value="Subcutânea">Subcutânea</option>
                                <option value="Intravenosa">Intravenosa</option>
                                <option value="Oral">Oral</option>
                                <option value="Tópica">Tópica</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar Vacina</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Vacina -->
<div class="modal fade" id="editarVacinaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Vacina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome da Vacina *</label>
                            <input type="text" class="form-control" name="nome_vacina" id="edit_nome" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fabricante</label>
                            <input type="text" class="form-control" name="fabricante" id="edit_fabricante">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Doença Prevenida</label>
                            <input type="text" class="form-control" name="doenca_prevenida" id="edit_doenca">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Intervalo (dias)</label>
                            <input type="number" class="form-control" name="intervalo_dias" id="edit_intervalo" min="1">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Dose (ml)</label>
                            <input type="number" step="0.1" class="form-control" name="dose_ml" id="edit_dose" min="0.1">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Via de Aplicação</label>
                            <select class="form-select" name="via_aplicacao" id="edit_via">
                                <option value="">Selecione</option>
                                <option value="Intramuscular">Intramuscular</option>
                                <option value="Subcutânea">Subcutânea</option>
                                <option value="Intravenosa">Intravenosa</option>
                                <option value="Oral">Oral</option>
                                <option value="Tópica">Tópica</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" id="edit_observacoes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarVacina(vac) {
    document.getElementById('edit_id').value = vac.id;
    document.getElementById('edit_nome').value = vac.nome_vacina;
    document.getElementById('edit_fabricante').value = vac.fabricante || '';
    document.getElementById('edit_doenca').value = vac.doenca_prevenida || '';
    document.getElementById('edit_intervalo').value = vac.intervalo_dias || '';
    document.getElementById('edit_dose').value = vac.dose_ml || '';
    document.getElementById('edit_via').value = vac.via_aplicacao || '';
    document.getElementById('edit_observacoes').value = vac.observacoes || '';
    
    new bootstrap.Modal(document.getElementById('editarVacinaModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>