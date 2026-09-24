<?php
// modules/estoque/cadastrar.php
// Cadastrar novo produto

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

$pageTitle = 'Novo Produto';
$error = '';
$success = '';

// Buscar categorias
$sqlCats = "SELECT id, nome_categoria FROM estoque_categorias WHERE id_fazenda = $farmId ORDER BY nome_categoria";
$categorias = executeQuery($sqlCats);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_categoria = intval($_POST['id_categoria']);
    $codigo = !empty($_POST['codigo']) ? "'" . escapeString($_POST['codigo']) . "'" : "NULL";
    $nome_produto = escapeString(trim($_POST['nome_produto']));
    $descricao = !empty($_POST['descricao']) ? "'" . escapeString($_POST['descricao']) . "'" : "NULL";
    $unidade = $_POST['unidade'];
    $quantidade_inicial = !empty($_POST['quantidade_inicial']) ? floatval($_POST['quantidade_inicial']) : 0;
    $quantidade_minima = !empty($_POST['quantidade_minima']) ? floatval($_POST['quantidade_minima']) : 0;
    $quantidade_maxima = !empty($_POST['quantidade_maxima']) ? floatval($_POST['quantidade_maxima']) : 0;
    $localizacao = !empty($_POST['localizacao']) ? "'" . escapeString($_POST['localizacao']) . "'" : "NULL";
    $fabricante = !empty($_POST['fabricante']) ? "'" . escapeString($_POST['fabricante']) . "'" : "NULL";
    $lote = !empty($_POST['lote']) ? "'" . escapeString($_POST['lote']) . "'" : "NULL";
    $data_fabricacao = !empty($_POST['data_fabricacao']) ? "'" . $_POST['data_fabricacao'] . "'" : "NULL";
    $data_validade = !empty($_POST['data_validade']) ? "'" . $_POST['data_validade'] . "'" : "NULL";
    $preco_custo = !empty($_POST['preco_custo']) ? floatval($_POST['preco_custo']) : "NULL";
    $preco_venda = !empty($_POST['preco_venda']) ? floatval($_POST['preco_venda']) : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if ($id_categoria <= 0 || empty($nome_produto)) {
        $error = 'Categoria e nome do produto são obrigatórios.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Inserir produto
            $sql = "INSERT INTO estoque_produtos (
                    id_fazenda, id_categoria, codigo, nome_produto, descricao, unidade,
                    quantidade_atual, quantidade_minima, quantidade_maxima, localizacao,
                    fabricante, lote, data_fabricacao, data_validade, preco_custo, preco_venda, observacoes
                ) VALUES (
                    $farmId, $id_categoria, $codigo, '$nome_produto', $descricao, '$unidade',
                    $quantidade_inicial, $quantidade_minima, $quantidade_maxima, $localizacao,
                    $fabricante, $lote, $data_fabricacao, $data_validade, $preco_custo, $preco_venda, $observacoes
                )";
            
            if (!$conn->query($sql)) {
                throw new Exception('Erro ao cadastrar produto.');
            }
            
            $produto_id = $conn->insert_id;
            
            // Se quantidade inicial > 0, registrar movimentação
            if ($quantidade_inicial > 0) {
                $sqlMov = "INSERT INTO estoque_movimentacoes (
                            id_fazenda, id_produto, tipo_movimento, quantidade,
                            quantidade_anterior, quantidade_posterior, motivo, id_usuario
                          ) VALUES (
                            $farmId, $produto_id, 'entrada', $quantidade_inicial,
                            0, $quantidade_inicial, 'Estoque inicial', {$_SESSION['usuario_id']}
                          )";
                
                if (!$conn->query($sqlMov)) {
                    throw new Exception('Erro ao registrar movimentação inicial.');
                }
            }
            
            $conn->commit();
            setAlert('Produto cadastrado com sucesso!', 'success');
            redirect('produtos.php');
            
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
                <i class="bi bi-plus-circle me-2 text-success"></i>
                Novo Produto
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item"><a href="produtos.php">Produtos</a></li>
                    <li class="breadcrumb-item active">Novo</li>
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
                        <label class="form-label">Categoria *</label>
                        <select class="form-select" name="id_categoria" required>
                            <option value="">Selecione</option>
                            <?php if ($categorias && $categorias->num_rows > 0): ?>
                                <?php while ($c = $categorias->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nome_categoria']; ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" placeholder="Ex: R001, VAC-001">
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">Nome do Produto *</label>
                        <input type="text" class="form-control" name="nome_produto" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Unidade</label>
                        <select class="form-select" name="unidade">
                            <option value="un">Unidade (un)</option>
                            <option value="kg">Quilograma (kg)</option>
                            <option value="g">Grama (g)</option>
                            <option value="L">Litro (L)</option>
                            <option value="ml">Mililitro (ml)</option>
                            <option value="cx">Caixa (cx)</option>
                            <option value="sc">Saco (sc)</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Quantidade Inicial</label>
                        <input type="number" step="0.01" class="form-control" name="quantidade_inicial" value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Quantidade Mínima</label>
                        <input type="number" step="0.01" class="form-control" name="quantidade_minima" value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Quantidade Máxima</label>
                        <input type="number" step="0.01" class="form-control" name="quantidade_maxima" value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Localização</label>
                        <input type="text" class="form-control" name="localizacao" placeholder="Ex: Prateleira A1">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Fabricante</label>
                        <input type="text" class="form-control" name="fabricante">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Lote</label>
                        <input type="text" class="form-control" name="lote">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Data de Fabricação</label>
                        <input type="date" class="form-control" name="data_fabricacao">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Data de Validade</label>
                        <input type="date" class="form-control" name="data_validade">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Preço de Custo (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="preco_custo">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="preco_venda">
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
                        <i class="bi bi-save"></i> Salvar Produto
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