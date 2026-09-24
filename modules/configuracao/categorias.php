<?php
// modules/configuracao/categorias.php
// Gerenciamento de categorias financeiras

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

$pageTitle = 'Categorias Financeiras';
$error = '';
$success = '';

// Processar ações
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verificar se há lançamentos usando esta categoria
    $checkSql = "SELECT COUNT(*) as total FROM lancamentos_financeiros WHERE id_categoria = $id";
    $checkResult = executeQuery($checkSql);
    $total = $checkResult->fetch_assoc()['total'];
    
    if ($total > 0) {
        setAlert("Não é possível excluir esta categoria pois existem $total lançamentos vinculados.", 'danger');
    } else {
        $sql = "DELETE FROM categorias_financeiras WHERE id = $id AND id_fazenda = $farmId";
        if (executeQuery($sql)) {
            setAlert('Categoria excluída com sucesso!', 'success');
        } else {
            setAlert('Erro ao excluir categoria.', 'danger');
        }
    }
    redirect('categorias.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $nome_categoria = escapeString(trim($_POST['nome_categoria']));
        $tipo = $_POST['tipo'];
        $cor = $_POST['cor'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        
        if (empty($nome_categoria) || empty($tipo) || empty($cor)) {
            $error = 'Nome, tipo e cor são obrigatórios.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM categorias_financeiras 
                        WHERE id_fazenda = $farmId AND nome_categoria = '$nome_categoria' AND tipo = '$tipo'";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe uma categoria com este nome para este tipo.';
            } else {
                
                $sql = "INSERT INTO categorias_financeiras (id_fazenda, nome_categoria, tipo, cor, descricao) 
                        VALUES ($farmId, '$nome_categoria', '$tipo', '$cor', " . ($descricao ? "'$descricao'" : "NULL") . ")";
                
                if (executeQuery($sql)) {
                    $success = 'Categoria cadastrada com sucesso!';
                } else {
                    $error = 'Erro ao cadastrar categoria.';
                }
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $nome_categoria = escapeString(trim($_POST['nome_categoria']));
        $tipo = $_POST['tipo'];
        $cor = $_POST['cor'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        
        if (empty($nome_categoria) || empty($tipo) || empty($cor)) {
            $error = 'Nome, tipo e cor são obrigatórios.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM categorias_financeiras 
                        WHERE id_fazenda = $farmId AND nome_categoria = '$nome_categoria' AND tipo = '$tipo' AND id != $id";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe outra categoria com este nome para este tipo.';
            } else {
                
                $sql = "UPDATE categorias_financeiras SET 
                        nome_categoria = '$nome_categoria',
                        tipo = '$tipo',
                        cor = '$cor',
                        descricao = " . ($descricao ? "'$descricao'" : "NULL") . "
                        WHERE id = $id AND id_fazenda = $farmId";
                
                if (executeQuery($sql)) {
                    $success = 'Categoria atualizada com sucesso!';
                } else {
                    $error = 'Erro ao atualizar categoria.';
                }
            }
        }
    }
}

// Buscar categorias
$sql = "SELECT * FROM categorias_financeiras 
        WHERE id_fazenda = $farmId 
        ORDER BY tipo, nome_categoria";
$categorias = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-tags me-2 text-success"></i>
                Categorias Financeiras
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Categorias</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaCategoriaModal">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Categoria
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Lista de Categorias -->
    <div class="row">
        <!-- Receitas -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-arrow-up-circle"></i> Receitas
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php 
                    $categorias->data_seek(0);
                    $temReceita = false;
                    while ($cat = $categorias->fetch_assoc()): 
                        if ($cat['tipo'] == 'receita'):
                        $temReceita = true;
                        
                        $sqlCount = "SELECT COUNT(*) as total FROM lancamentos_financeiros WHERE id_categoria = " . $cat['id'];
                        $resultCount = executeQuery($sqlCount);
                        $totalLancamentos = $resultCount->fetch_assoc()['total'];
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge me-2" style="background-color: <?php echo $cat['cor']; ?>">
                                <i class="bi bi-tag-fill"></i>
                            </span>
                            <strong><?php echo $cat['nome_categoria']; ?></strong>
                            <?php if ($cat['descricao']): ?>
                                <br><small class="text-muted"><?php echo $cat['descricao']; ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-secondary me-2"><?php echo $totalLancamentos; ?></span>
                            <button class="btn btn-sm btn-warning" onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($totalLancamentos == 0): ?>
                            <a href="?delete=<?php echo $cat['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Excluir categoria <?php echo $cat['nome_categoria']; ?>?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; endwhile; ?>
                    <?php if (!$temReceita): ?>
                    <div class="list-group-item text-center text-muted py-3">
                        Nenhuma categoria de receita cadastrada.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Despesas -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-arrow-down-circle"></i> Despesas
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php 
                    $categorias->data_seek(0);
                    $temDespesa = false;
                    while ($cat = $categorias->fetch_assoc()): 
                        if ($cat['tipo'] == 'despesa'):
                        $temDespesa = true;
                        
                        $sqlCount = "SELECT COUNT(*) as total FROM lancamentos_financeiros WHERE id_categoria = " . $cat['id'];
                        $resultCount = executeQuery($sqlCount);
                        $totalLancamentos = $resultCount->fetch_assoc()['total'];
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge me-2" style="background-color: <?php echo $cat['cor']; ?>">
                                <i class="bi bi-tag-fill"></i>
                            </span>
                            <strong><?php echo $cat['nome_categoria']; ?></strong>
                            <?php if ($cat['descricao']): ?>
                                <br><small class="text-muted"><?php echo $cat['descricao']; ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-secondary me-2"><?php echo $totalLancamentos; ?></span>
                            <button class="btn btn-sm btn-warning" onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($totalLancamentos == 0): ?>
                            <a href="?delete=<?php echo $cat['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Excluir categoria <?php echo $cat['nome_categoria']; ?>?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; endwhile; ?>
                    <?php if (!$temDespesa): ?>
                    <div class="list-group-item text-center text-muted py-3">
                        Nenhuma categoria de despesa cadastrada.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nova Categoria -->
<div class="modal fade" id="novaCategoriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nova Categoria Financeira</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Categoria *</label>
                        <input type="text" class="form-control" name="nome_categoria" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cor *</label>
                        <input type="color" class="form-control form-control-color" name="cor" value="#28a745">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoria -->
<div class="modal fade" id="editarCategoriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Categoria *</label>
                        <input type="text" class="form-control" name="nome_categoria" id="edit_nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" id="edit_tipo" required>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cor *</label>
                        <input type="color" class="form-control form-control-color" name="cor" id="edit_cor" value="#28a745">
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
function editarCategoria(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_nome').value = cat.nome_categoria;
    document.getElementById('edit_tipo').value = cat.tipo;
    document.getElementById('edit_cor').value = cat.cor;
    document.getElementById('edit_descricao').value = cat.descricao || '';
    
    new bootstrap.Modal(document.getElementById('editarCategoriaModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>