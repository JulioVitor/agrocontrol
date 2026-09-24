<?php
// modules/financeiro/despesa.php
// Nova despesa

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

$pageTitle = 'Nova Despesa';
$error = '';

// Buscar categorias de despesa
$sqlCats = "SELECT id, nome_categoria, cor FROM categorias_financeiras 
            WHERE id_fazenda = $farmId AND tipo = 'despesa' AND ativo = 1 
            ORDER BY nome_categoria";
$categorias = executeQuery($sqlCats);

// Buscar bovinos (opcional, para vincular)
$sqlBovinos = "SELECT id, brinco, nome FROM bovinos 
               WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
               ORDER BY brinco";
$bovinos = executeQuery($sqlBovinos);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_categoria = intval($_POST['id_categoria']);
    $descricao = escapeString(trim($_POST['descricao']));
    $valor = floatval($_POST['valor']);
    $data_emissao = $_POST['data_emissao'];
    $data_vencimento = !empty($_POST['data_vencimento']) ? "'" . $_POST['data_vencimento'] . "'" : "NULL";
    $forma_pagamento = !empty($_POST['forma_pagamento']) ? "'" . escapeString($_POST['forma_pagamento']) . "'" : "NULL";
    $id_bovino = !empty($_POST['id_bovino']) ? intval($_POST['id_bovino']) : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    $status = $_POST['status'];
    $pago_parcial = ($status == 'pago_parcial') ? floatval($_POST['valor_pago']) : 0;
    
    // Definir data de pagamento e valor pago
    if ($status == 'pago') {
        $data_pagamento = "'$data_emissao'";
        $valor_pago = $valor;
    } elseif ($status == 'pago_parcial') {
        $data_pagamento = "'$data_emissao'";
        $valor_pago = $pago_parcial;
    } else {
        $data_pagamento = "NULL";
        $valor_pago = "NULL";
    }
    
    if ($id_categoria <= 0 || empty($descricao) || $valor <= 0 || empty($data_emissao)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        
        $sql = "INSERT INTO lancamentos_financeiros (
                id_fazenda, id_categoria, id_bovino, descricao, valor, valor_pago,
                data_emissao, data_vencimento, data_pagamento, forma_pagamento, 
                status, observacoes
            ) VALUES (
                $farmId, $id_categoria, $id_bovino, '$descricao', $valor, $valor_pago,
                '$data_emissao', $data_vencimento, $data_pagamento, $forma_pagamento,
                '$status', $observacoes
            )";
        
        if (executeQuery($sql)) {
            setAlert('Despesa registrada com sucesso!', 'success');
            redirect('index.php');
        } else {
            $error = 'Erro ao registrar despesa.';
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
                <i class="bi bi-arrow-down-circle me-2 text-danger"></i>
                Nova Despesa
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Nova Despesa</li>
                </ol>
            </nav>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
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
                                <?php while ($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" style="color: <?php echo $cat['cor']; ?>">
                                    <?php echo $cat['nome_categoria']; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>Cadastre categorias primeiro</option>
                            <?php endif; ?>
                        </select>
                        <small>
                            <a href="categorias.php" target="_blank">+ Nova Categoria</a>
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Valor (R$) *</label>
                        <input type="number" step="0.01" class="form-control" name="valor" id="valor" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Descrição *</label>
                        <input type="text" class="form-control" name="descricao" 
                               placeholder="Ex: Compra de ração, pagamento de funcionário..." required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Data de Emissão *</label>
                        <input type="date" class="form-control" name="data_emissao" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="data_vencimento">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Forma de Pagamento</label>
                        <select class="form-select" name="forma_pagamento">
                            <option value="">Selecione</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="PIX">PIX</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="Boleto">Boleto</option>
                            <option value="Transferência">Transferência</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Vincular a Bovino (opcional)</label>
                        <select class="form-select" name="id_bovino">
                            <option value="">Nenhum</option>
                            <?php if ($bovinos && $bovinos->num_rows > 0): ?>
                                <?php while ($b = $bovinos->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    <?php echo $b['brinco']; ?> - <?php echo $b['nome'] ?: 'Sem nome'; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Para despesas com animais específicos</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="pendente">Pendente (a pagar)</option>
                            <option value="pago">Pago</option>
                            <option value="pago_parcial">Pago Parcialmente</option>
                        </select>
                    </div>
                    
                    <div class="col-12" id="campoValorPago" style="display: none;">
                        <label class="form-label">Valor Pago (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="valor_pago" id="valor_pago">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-check-circle"></i> Registrar Despesa
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Mostrar campo de valor pago quando selecionar "pago_parcial"
document.getElementById('status').addEventListener('change', function() {
    var campo = document.getElementById('campoValorPago');
    if (this.value === 'pago_parcial') {
        campo.style.display = 'block';
        document.getElementById('valor_pago').required = true;
    } else {
        campo.style.display = 'none';
        document.getElementById('valor_pago').required = false;
    }
});

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