<?php
// modules/configuracao/situacoes.php
// Gerenciamento de situações dos animais

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

$pageTitle = 'Situações dos Animais';
$error = '';
$success = '';

// Processar ações
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verificar se há bovinos usando esta situação
    $checkSql = "SELECT COUNT(*) as total FROM bovinos WHERE id_situacao = $id";
    $checkResult = executeQuery($checkSql);
    $total = $checkResult->fetch_assoc()['total'];
    
    if ($total > 0) {
        setAlert("Não é possível excluir esta situação pois existem $total bovinos com esta classificação.", 'danger');
    } else {
        $sql = "DELETE FROM situacoes WHERE id = $id AND id_fazenda = $farmId";
        if (executeQuery($sql)) {
            setAlert('Situação excluída com sucesso!', 'success');
        } else {
            setAlert('Erro ao excluir situação.', 'danger');
        }
    }
    redirect('situacoes.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $nome = escapeString(trim($_POST['nome']));
        $cor = $_POST['cor'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        $padrao = isset($_POST['padrao']) ? 1 : 0;
        
        if (empty($nome) || empty($cor)) {
            $error = 'Nome e cor são obrigatórios.';
        } else {
            
            // Se for padrão, remover padrão de outras
            if ($padrao) {
                $sqlReset = "UPDATE situacoes SET padrao = 0 WHERE id_fazenda = $farmId";
                executeQuery($sqlReset);
            }
            
            $sql = "INSERT INTO situacoes (id_fazenda, nome, cor, descricao, padrao) 
                    VALUES ($farmId, '$nome', '$cor', " . ($descricao ? "'$descricao'" : "NULL") . ", $padrao)";
            
            if (executeQuery($sql)) {
                $success = 'Situação cadastrada com sucesso!';
            } else {
                $error = 'Erro ao cadastrar situação.';
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $nome = escapeString(trim($_POST['nome']));
        $cor = $_POST['cor'];
        $descricao = !empty($_POST['descricao']) ? escapeString($_POST['descricao']) : '';
        $padrao = isset($_POST['padrao']) ? 1 : 0;
        
        if (empty($nome) || empty($cor)) {
            $error = 'Nome e cor são obrigatórios.';
        } else {
            
            // Se for padrão, remover padrão de outras
            if ($padrao) {
                $sqlReset = "UPDATE situacoes SET padrao = 0 WHERE id_fazenda = $farmId AND id != $id";
                executeQuery($sqlReset);
            }
            
            $sql = "UPDATE situacoes SET 
                    nome = '$nome',
                    cor = '$cor',
                    descricao = " . ($descricao ? "'$descricao'" : "NULL") . ",
                    padrao = $padrao
                    WHERE id = $id AND id_fazenda = $farmId";
            
            if (executeQuery($sql)) {
                $success = 'Situação atualizada com sucesso!';
            } else {
                $error = 'Erro ao atualizar situação.';
            }
        }
    }
}

// Buscar situações
$sql = "SELECT * FROM situacoes WHERE id_fazenda = $farmId ORDER BY ordem, nome";
$situacoes = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-tags me-2 text-success"></i>
                Situações dos Animais
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Situações</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaSituacaoModal">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Situação
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Lista de Situações -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($situacoes && $situacoes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cor</th>
                            <th>Situação</th>
                            <th>Descrição</th>
                            <th>Padrão</th>
                            <th>Animais</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sit = $situacoes->fetch_assoc()): 
                            $sqlCount = "SELECT COUNT(*) as total FROM bovinos WHERE id_situacao = " . $sit['id'];
                            $resultCount = executeQuery($sqlCount);
                            $totalAnimais = $resultCount->fetch_assoc()['total'];
                        ?>
                        <tr>
                            <td>
                                <span class="badge" style="background-color: <?php echo $sit['cor']; ?>; width: 30px; height: 20px;">&nbsp;</span>
                            </td>
                            <td><strong><?php echo $sit['nome']; ?></strong></td>
                            <td><?php echo $sit['descricao'] ?: '-'; ?></td>
                            <td>
                                <?php if ($sit['padrao']): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-info"><?php echo $totalAnimais; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarSituacao(<?php echo htmlspecialchars(json_encode($sit)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($totalAnimais == 0 && !$sit['padrao']): ?>
                                <a href="?delete=<?php echo $sit['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Excluir situação <?php echo $sit['nome']; ?>?')">
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
                <h4 class="mt-3">Nenhuma situação cadastrada</h4>
                <p class="text-muted">Cadastre as situações para classificar os animais.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Nova Situação -->
<div class="modal fade" id="novaSituacaoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nova Situação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Situação *</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cor *</label>
                        <input type="color" class="form-control form-control-color" name="cor" value="#28a745">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="padrao" id="padrao">
                            <label class="form-check-label" for="padrao">
                                Definir como situação padrão para novos animais
                            </label>
                        </div>
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

<!-- Modal Editar Situação -->
<div class="modal fade" id="editarSituacaoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Situação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Situação *</label>
                        <input type="text" class="form-control" name="nome" id="edit_nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cor *</label>
                        <input type="color" class="form-control form-control-color" name="cor" id="edit_cor" value="#28a745">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" id="edit_descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="padrao" id="edit_padrao">
                            <label class="form-check-label" for="edit_padrao">
                                Definir como situação padrão
                            </label>
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
function editarSituacao(sit) {
    document.getElementById('edit_id').value = sit.id;
    document.getElementById('edit_nome').value = sit.nome;
    document.getElementById('edit_cor').value = sit.cor;
    document.getElementById('edit_descricao').value = sit.descricao || '';
    document.getElementById('edit_padrao').checked = sit.padrao == 1;
    
    new bootstrap.Modal(document.getElementById('editarSituacaoModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>