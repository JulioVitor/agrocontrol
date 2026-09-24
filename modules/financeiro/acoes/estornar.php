<?php
// modules/financeiro/acoes/estornar.php
// Estornar um lançamento (cancelar pagamento)

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
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';

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

// Verificar se está pago
if ($lancamento['status'] != 'pago') {
    setAlert('Este lançamento não está pago.', 'warning');
    redirect('../lancamentos.php');
}

// Se confirmou, processar estorno
if ($confirm == 'sim') {
    
    $conn->begin_transaction();
    
    try {
        // Voltar status para pendente
        $sql = "UPDATE lancamentos_financeiros SET 
                status = 'pendente',
                valor_pago = 0,
                data_pagamento = NULL,
                observacoes = CONCAT(IFNULL(observacoes, ''), ' | Estornado em " . date('d/m/Y H:i') . "')
                WHERE id = $id";
        
        if (!$conn->query($sql)) {
            throw new Exception('Erro ao estornar lançamento.');
        }
        
        // Registrar no log
        $logSql = "INSERT INTO logs (usuario_id, acao, descricao, data) 
                  VALUES ({$_SESSION['usuario_id']}, 'estorno', 'Estorno de lançamento #$id', NOW())";
        $conn->query($logSql);
        
        $conn->commit();
        
        setAlert('Estorno realizado com sucesso!', 'success');
        redirect('../lancamentos.php');
        
    } catch (Exception $e) {
        $conn->rollback();
        setAlert('Erro ao estornar: ' . $e->getMessage(), 'danger');
        redirect('../lancamentos.php');
    }
}

$pageTitle = 'Confirmar Estorno';

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>
                Confirmar Estorno
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Financeiro</a></li>
                    <li class="breadcrumb-item"><a href="../lancamentos.php">Lançamentos</a></li>
                    <li class="breadcrumb-item active">Estorno</li>
                </ol>
            </nav>
        </div>
        <a href="../lancamentos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Card de confirmação -->
    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h6 class="card-title mb-0 text-white">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Atenção!
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="bi bi-info-circle me-2"></i>
                Você está prestes a estornar o seguinte lançamento:
            </div>
            
            <table class="table table-bordered">
                <tr>
                    <th width="200">Descrição:</th>
                    <td><?php echo $lancamento['descricao']; ?></td>
                </tr>
                <tr>
                    <th>Categoria:</th>
                    <td><?php echo $lancamento['nome_categoria']; ?></td>
                </tr>
                <tr>
                    <th>Valor:</th>
                    <td class="text-<?php echo $lancamento['tipo_categoria'] == 'receita' ? 'success' : 'danger'; ?>">
                        <strong>R$ <?php echo number_format($lancamento['valor'], 2, ',', '.'); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th>Data do Pagamento:</th>
                    <td><?php echo date('d/m/Y', strtotime($lancamento['data_pagamento'])); ?></td>
                </tr>
                <tr>
                    <th>Forma de Pagamento:</th>
                    <td><?php echo $lancamento['forma_pagamento'] ?: '-'; ?></td>
                </tr>
            </table>
            
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Esta ação não poderá ser desfeita!</strong><br>
                O lançamento voltará ao status "pendente" e o valor será estornado.
            </div>
            
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="../lancamentos.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <a href="estornar.php?id=<?php echo $id; ?>&confirm=sim" class="btn btn-warning btn-lg">
                    <i class="bi bi-check-circle"></i> Sim, Estornar
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../../../includes/footer.php'; ?>