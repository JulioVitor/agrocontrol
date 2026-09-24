<?php
// modules/estoque/entrada.php
// Registrar entrada de produto

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

$pageTitle = 'Registrar Entrada';
$error = '';
$success = '';

// Se veio com produto_id pré-selecionado
$produto_id = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : 0;

// Buscar produtos ativos
$sqlProdutos = "SELECT p.*, c.nome_categoria 
                FROM estoque_produtos p
                JOIN estoque_categorias c ON p.id_categoria = c.id
                WHERE p.id_fazenda = $farmId AND p.ativo = 1
                ORDER BY p.nome_produto";
$produtos = executeQuery($sqlProdutos);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_produto = intval($_POST['id_produto']);
    $quantidade = floatval($_POST['quantidade']);
    $motivo = !empty($_POST['motivo']) ? "'" . escapeString($_POST['motivo']) . "'" : "'Entrada de estoque'";
    $documento = !empty($_POST['documento']) ? "'" . escapeString($_POST['documento']) . "'" : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if ($id_produto <= 0 || $quantidade <= 0) {
        $error = 'Selecione um produto e informe a quantidade.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Buscar quantidade atual do produto
            $sqlQtd = "SELECT quantidade_atual FROM estoque_produtos WHERE id = $id_produto AND id_fazenda = $farmId";
            $resultQtd = $conn->query($sqlQtd);
            
            if (!$resultQtd || $resultQtd->num_rows == 0) {
                throw new Exception('Produto não encontrado.');
            }
            
            $quantidade_anterior = $resultQtd->fetch_assoc()['quantidade_atual'];
            $quantidade_posterior = $quantidade_anterior + $quantidade;
            
            // Atualizar produto
            $sqlUpdate = "UPDATE estoque_produtos SET 
                         quantidade_atual = $quantidade_posterior
                         WHERE id = $id_produto";
            
            if (!$conn->query($sqlUpdate)) {
                throw new Exception('Erro ao atualizar quantidade.');
            }
            
            // Registrar movimentação
            $sqlMov = "INSERT INTO estoque_movimentacoes (
                        id_fazenda, id_produto, tipo_movimento, quantidade,
                        quantidade_anterior, quantidade_posterior, motivo, documento, 
                        observacoes, id_usuario
                      ) VALUES (
                        $farmId, $id_produto, 'entrada', $quantidade,
                        $quantidade_anterior, $quantidade_posterior, $motivo, $documento,
                        $observacoes, {$_SESSION['usuario_id']}
                      )";
            
            if (!$conn->query($sqlMov)) {
                throw new Exception('Erro ao registrar movimentação.');
            }
            
            $conn->commit();
            setAlert('Entrada registrada com sucesso!', 'success');
            redirect('historico.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-arrow-down-circle me-2 text-success"></i>
                Registrar Entrada
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item active">Entrada</li>
                </ol>
            </nav>
        </div>
        <a href="produtos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Produto *</label>
                        <select class="form-select" name="id_produto" required>
                            <option value="">Selecione</option>
                            <?php if ($produtos && $produtos->num_rows > 0): ?>
                                <?php while ($p = $produtos->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-qtd="<?php echo $p['quantidade_atual']; ?>"
                                        <?php echo $produto_id == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo $p['nome_produto']; ?> (<?php echo $p['codigo'] ?: '---'; ?>) - 
                                    Atual: <?php echo $p['quantidade_atual']; ?> <?php echo $p['unidade']; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" step="0.01" class="form-control" name="quantidade" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Motivo</label>
                        <input type="text" class="form-control" name="motivo" value="Compra">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Documento (NF)</label>
                        <input type="text" class="form-control" name="documento" placeholder="Número da nota fiscal">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="produtos.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Registrar Entrada
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

<?php include '../../includes/footer.php'; ?>