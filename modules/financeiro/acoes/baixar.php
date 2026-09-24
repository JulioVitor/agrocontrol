<?php
// modules/financeiro/acoes/baixar.php
// Baixar/confirmar pagamento de uma conta

require_once '../../../config/database.php';
require_once '../../../config/constants.php';
require_once '../../../includes/functions.php';
require_once '../../../config/tenant.php';

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
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'pagar'; // pagar ou receber

if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('../lancamentos.php');
}

// Buscar dados do lançamento
$sql = "SELECT l.*, c.nome_categoria, c.tipo as tipo_categoria
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE l.id = $id AND " . TenantManager::addTenantFilter('l');
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Lançamento não encontrado.', 'danger');
    redirect('../lancamentos.php');
}

$lancamento = $result->fetch_assoc();

// Verificar se já está pago
if ($lancamento['status'] == 'pago') {
    setAlert('Este lançamento já foi quitado.', 'warning');
    redirect('../lancamentos.php');
}

$pageTitle = 'Baixar ' . ($tipo == 'pagar' ? 'Pagamento' : 'Recebimento');
$error = '';
$success = '';

$valor_original = $lancamento['valor'];
$valor_pago = $lancamento['valor_pago'] ?: 0;
$saldo = $valor_original - $valor_pago;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data_pagamento = $_POST['data_pagamento'];
    $valor_pagar = floatval($_POST['valor_pagar']);
    $forma_pagamento = !empty($_POST['forma_pagamento']) ? "'" . escapeString($_POST['forma_pagamento']) . "'" : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? escapeString($_POST['observacoes']) : '';
    
    if ($valor_pagar <= 0) {
        $error = 'Valor inválido.';
    } elseif ($valor_pagar > $saldo) {
        $error = 'Valor não pode ser maior que o saldo devedor.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            $novo_valor_pago = $valor_pago + $valor_pagar;
            $novo_status = ($novo_valor_pago >= $valor_original) ? 'pago' : 'pendente';
            
            $sql = "UPDATE lancamentos_financeiros SET 
                    data_pagamento = '$data_pagamento',
                    valor_pago = $novo_valor_pago,
                    status = '$novo_status',
                    forma_pagamento = $forma_pagamento,
                    observacoes = CONCAT(IFNULL(observacoes, ''), ' | Baixa: $observacoes')
                    WHERE id = $id";
            
            if (!$conn->query($sql)) {
                throw new Exception('Erro ao atualizar lançamento.');
            }
            
            // Registrar no histórico (opcional)
            $historicoSql = "INSERT INTO logs (usuario_id, acao, descricao, data) 
                            VALUES ({$_SESSION['usuario_id']}, 'baixa', 'Baixa de {$tipo} - R$ " . number_format($valor_pagar, 2, ',', '.') . "', NOW())";
            $conn->query($historicoSql);
            
            $conn->commit();
            
            setAlert('Baixa realizada com sucesso!', 'success');
            
            if ($tipo == 'pagar') {
                redirect('../contas/pagar.php');
            } else {
                redirect('../contas/receber.php');
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-check-circle me-2 text-success"></i>
                Baixar <?php echo $tipo == 'pagar' ? 'Pagamento' : 'Recebimento'; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Financeiro</a></li>
                    <li class="breadcrumb-item"><a href="../lancamentos.php">Lançamentos</a></li>
                    <li class="breadcrumb-item active">Baixar</li>
                </ol>
            </nav>
        </div>
        <a href="../lancamentos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Informações do lançamento -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-<?php echo $tipo == 'pagar' ? 'danger' : 'success'; ?> text-white">
            <h6 class="card-title mb-0">Detalhes do Lançamento</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Descrição:</th>
                            <td><strong><?php echo $lancamento['descricao']; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Categoria:</th>
                            <td><?php echo $lancamento['nome_categoria']; ?></td>
                        </tr>
                        <tr>
                            <th>Data Emissão:</th>
                            <td><?php echo date('d/m/Y', strtotime($lancamento['data_emissao'])); ?></td>
                        </tr>
                        <?php if ($lancamento['data_vencimento']): ?>
                        <tr>
                            <th>Data Vencimento:</th>
                            <td><?php echo date('d/m/Y', strtotime($lancamento['data_vencimento'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Valor Original:</th>
                            <td>R$ <?php echo number_format($valor_original, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <th>Valor Já Pago:</th>
                            <td>R$ <?php echo number_format($valor_pago, 2, ',', '.'); ?></td>
                        </tr>
                        <tr class="table-<?php echo $tipo == 'pagar' ? 'danger' : 'success'; ?>">
                            <th><strong>Saldo Devedor:</strong></th>
                            <td><strong>R$ <?php echo number_format($saldo, 2, ',', '.'); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulário de baixa -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Registrar Baixa</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Data do <?php echo $tipo == 'pagar' ? 'Pagamento' : 'Recebimento'; ?> *</label>
                        <input type="date" class="form-control" name="data_pagamento" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Valor a <?php echo $tipo == 'pagar' ? 'Pagar' : 'Receber'; ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" name="valor_pagar" 
                                   value="<?php echo $saldo; ?>" max="<?php echo $saldo; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
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
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="../lancamentos.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-<?php echo $tipo == 'pagar' ? 'danger' : 'success'; ?>">
                        <i class="bi bi-check-circle"></i> Confirmar Baixa
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

<?php include '../../../includes/footer.php'; ?>