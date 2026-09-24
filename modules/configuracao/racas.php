<?php
// modules/configuracao/racas.php
// Gerenciamento de raças

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

$pageTitle = 'Gerenciar Raças';
$error = '';
$success = '';

// Processar ações
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verificar se há bovinos usando esta raça
    $checkSql = "SELECT COUNT(*) as total FROM bovinos WHERE id_raca = $id";
    $checkResult = executeQuery($checkSql);
    $total = $checkResult->fetch_assoc()['total'];
    
    if ($total > 0) {
        setAlert("Não é possível excluir esta raça pois existem $total bovinos vinculados a ela.", 'danger');
    } else {
        $sql = "DELETE FROM racas WHERE id = $id AND id_fazenda = $farmId";
        if (executeQuery($sql)) {
            setAlert('Raça excluída com sucesso!', 'success');
        } else {
            setAlert('Erro ao excluir raça.', 'danger');
        }
    }
    redirect('racas.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $nome_raca = escapeString(trim($_POST['nome_raca']));
        $tipo = $_POST['tipo'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        
        if (empty($nome_raca) || empty($tipo)) {
            $error = 'Nome e tipo são obrigatórios.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM racas WHERE id_fazenda = $farmId AND nome_raca = '$nome_raca'";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe uma raça com este nome.';
            } else {
                
                $sql = "INSERT INTO racas (id_fazenda, nome_raca, tipo, descricao) 
                        VALUES ($farmId, '$nome_raca', '$tipo', " . ($descricao ? "'$descricao'" : "NULL") . ")";
                
                if (executeQuery($sql)) {
                    $success = 'Raça cadastrada com sucesso!';
                } else {
                    $error = 'Erro ao cadastrar raça.';
                }
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $nome_raca = escapeString(trim($_POST['nome_raca']));
        $tipo = $_POST['tipo'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        
        if (empty($nome_raca) || empty($tipo)) {
            $error = 'Nome e tipo são obrigatórios.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM racas WHERE id_fazenda = $farmId AND nome_raca = '$nome_raca' AND id != $id";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe outra raça com este nome.';
            } else {
                
                $sql = "UPDATE racas SET 
                        nome_raca = '$nome_raca',
                        tipo = '$tipo',
                        descricao = " . ($descricao ? "'$descricao'" : "NULL") . "
                        WHERE id = $id AND id_fazenda = $farmId";
                
                if (executeQuery($sql)) {
                    $success = 'Raça atualizada com sucesso!';
                } else {
                    $error = 'Erro ao atualizar raça.';
                }
            }
        }
    }
}

// Buscar todas as raças
$sql = "SELECT * FROM racas WHERE id_fazenda = $farmId ORDER BY tipo, nome_raca";
$racas = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-tags me-2 text-success"></i>
                Gerenciar Raças
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Raças</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaRacaModal">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Raça
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Lista de Raças -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($racas && $racas->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Raça</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Animais</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($raca = $racas->fetch_assoc()): 
                            $sqlCount = "SELECT COUNT(*) as total FROM bovinos WHERE id_raca = " . $raca['id'];
                            $resultCount = executeQuery($sqlCount);
                            $totalAnimais = $resultCount->fetch_assoc()['total'];
                        ?>
                        <tr>
                            <td><strong><?php echo $raca['nome_raca']; ?></strong></td>
                            <td>
                                <?php if ($raca['tipo'] == 'corte'): ?>
                                    <span class="badge bg-primary">Corte</span>
                                <?php elseif ($raca['tipo'] == 'leite'): ?>
                                    <span class="badge bg-success">Leite</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Misto</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $raca['descricao'] ?: '-'; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $totalAnimais; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarRaca(<?php echo htmlspecialchars(json_encode($raca)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($totalAnimais == 0): ?>
                                <a href="?delete=<?php echo $raca['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Excluir raça <?php echo $raca['nome_raca']; ?>?')">
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
                <i class="bi bi-tags display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma raça cadastrada</h4>
                <p class="text-muted">Cadastre as raças utilizadas na sua fazenda.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Nova Raça -->
<div class="modal fade" id="novaRacaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nova Raça</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Raça *</label>
                        <input type="text" class="form-control" name="nome_raca" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" required>
                            <option value="corte">Corte</option>
                            <option value="leite">Leite</option>
                            <option value="misto">Misto</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar Raça</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Raça -->
<div class="modal fade" id="editarRacaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Raça</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Raça *</label>
                        <input type="text" class="form-control" name="nome_raca" id="edit_nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" id="edit_tipo" required>
                            <option value="corte">Corte</option>
                            <option value="leite">Leite</option>
                            <option value="misto">Misto</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" id="edit_descricao" rows="3"></textarea>
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
function editarRaca(raca) {
    document.getElementById('edit_id').value = raca.id;
    document.getElementById('edit_nome').value = raca.nome_raca;
    document.getElementById('edit_tipo').value = raca.tipo;
    document.getElementById('edit_descricao').value = raca.descricao || '';
    
    new bootstrap.Modal(document.getElementById('editarRacaModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>