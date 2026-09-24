<?php
// modules/estoque/categorias.php
// Gerenciar categorias de produtos

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

$pageTitle = 'Categorias de Produtos';
$error = '';
$success = '';

// Processar exclusão
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verificar se existem produtos nesta categoria
    $checkSql = "SELECT COUNT(*) as total FROM estoque_produtos WHERE id_categoria = $id";
    $checkResult = executeQuery($checkSql);
    $total = $checkResult->fetch_assoc()['total'];
    
    if ($total > 0) {
        setAlert("Não é possível excluir esta categoria pois existem $total produtos vinculados.", 'danger');
    } else {
        $sql = "DELETE FROM estoque_categorias WHERE id = $id AND id_fazenda = $farmId";
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
        $cor = $_POST['cor'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        
        if (empty($nome_categoria) || empty($cor)) {
            $error = 'Nome e cor são obrigatórios.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM estoque_categorias WHERE id_fazenda = $farmId AND nome_categoria = '$nome_categoria'";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe uma categoria com este nome.';
            } else {
                
                $sql = "INSERT INTO estoque_categorias (id_fazenda, nome_categoria, cor, descricao) 
                        VALUES ($farmId, '$nome_categoria', '$cor', " . ($descricao ? "'$descricao'" : "NULL") . ")";
                
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
        $cor = $_POST['cor'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        
        if (empty($nome_categoria) || empty($cor)) {
            $error = 'Nome e cor são obrigatórios.';
        } else {
            
            // Verificar duplicidade
            $checkSql = "SELECT id FROM estoque_categorias WHERE id_fazenda = $farmId AND nome_categoria = '$nome_categoria' AND id != $id";
            $checkResult = executeQuery($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $error = 'Já existe outra categoria com este nome.';
            } else {
                
                $sql = "UPDATE estoque_categorias SET 
                        nome_categoria = '$nome_categoria',
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
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM estoque_produtos WHERE id_categoria = c.id) as total_produtos,
               (SELECT COALESCE(SUM(quantidade_atual * preco_custo), 0) FROM estoque_produtos WHERE id_categoria = c.id) as valor_total
        FROM estoque_categorias c
        WHERE c.id_fazenda = $farmId
        ORDER BY c.nome_categoria";
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
                Categorias de Produtos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
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
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($categorias && $categorias->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cor</th>
                            <th>Categoria</th>
                            <th>Descrição</th>
                            <th class="text-center">Produtos</th>
                            <th class="text-end">Valor Total</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge" style="background-color: <?php echo $cat['cor']; ?>; width: 30px; height: 20px;">&nbsp;</span>
                            </td>
                            <td><strong><?php echo $cat['nome_categoria']; ?></strong></td>
                            <td><?php echo $cat['descricao'] ?: '-'; ?></td>
                            <td class="text-center"><span class="badge bg-info"><?php echo $cat['total_produtos']; ?></span></td>
                            <td class="text-end">R$ <?php echo number_format($cat['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($cat['total_produtos'] == 0): ?>
                                <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Excluir categoria <?php echo $cat['nome_categoria']; ?>?')">
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
                <h4 class="mt-3">Nenhuma categoria cadastrada</h4>
                <p class="text-muted">Cadastre as categorias de produtos para organizar seu estoque.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Nova Categoria -->
<div class="modal fade" id="novaCategoriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nova Categoria</h5>
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
    document.getElementById('edit_cor').value = cat.cor;
    document.getElementById('edit_descricao').value = cat.descricao || '';
    
    new bootstrap.Modal(document.getElementById('editarCategoriaModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>