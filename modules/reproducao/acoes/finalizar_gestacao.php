<?php
// modules/reproducao/acoes/finalizar_gestacao.php
// Finalizar gestação (aborto ou parto sem registro)

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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';

if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('../gestacoes.php');
}

// Buscar dados da gestação
$sql = "SELECT r.*, b.brinco, b.nome, b.id as bovino_id
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        WHERE r.id = $id AND " . TenantManager::addTenantFilter('b');
$result = executeQuery($sql);
$gestacao = $result->fetch_assoc();

if (!$gestacao) {
    setAlert('Gestação não encontrada.', 'danger');
    redirect('../gestacoes.php');
}

// Se confirmou, processar
if ($confirm == 'sim') {
    
    $motivo = $_GET['motivo'] ?? 'aborto';
    
    $conn->begin_transaction();
    
    try {
        // Registrar aborto
        $sql = "INSERT INTO reproducao (id_fazenda, id_bovino_femea, data_evento, tipo_evento, observacoes)
                VALUES ($farmId, {$gestacao['id_bovino_femea']}, CURDATE(), 'aborto', 'Gestação finalizada - $motivo')";
        
        if (!$conn->query($sql)) {
            throw new Exception('Erro ao registrar finalização.');
        }
        
        // Atualizar matriz
        $sqlMatriz = "UPDATE matrizes SET 
                     status_reprodutivo = 'vazia'
                     WHERE id_bovino = {$gestacao['id_bovino_femea']}";
        $conn->query($sqlMatriz);
        
        // Opcional: excluir a gestação ou marcar como inativa
        // $sqlDelete = "DELETE FROM reproducao WHERE id = $id";
        // $conn->query($sqlDelete);
        
        $conn->commit();
        
        setAlert('Gestação finalizada com sucesso.', 'success');
        
    } catch (Exception $e) {
        $conn->rollback();
        setAlert('Erro ao finalizar gestação: ' . $e->getMessage(), 'danger');
    }
    
    redirect('../gestacoes.php');
}

$pageTitle = 'Finalizar Gestação';

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Finalizar Gestação
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Atenção!</strong> Esta ação irá finalizar a gestação da matriz:
                <br><br>
                <strong>Matriz:</strong> <?php echo $gestacao['brinco']; ?> - <?php echo $gestacao['nome'] ?: 'Sem nome'; ?><br>
                <strong>Data da IA:</strong> <?php echo date('d/m/Y', strtotime($gestacao['data_evento'])); ?><br>
                <strong>Data Prevista:</strong> <?php echo date('d/m/Y', strtotime($gestacao['data_prevista_parto'])); ?>
            </div>
            
            <p>Selecione o motivo da finalização:</p>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="?id=<?php echo $id; ?>&confirm=sim&motivo=aborto" 
                   class="btn btn-warning btn-lg"
                   onclick="return confirm('Confirmar aborto?')">
                    <i class="bi bi-exclamation-circle"></i> Aborto
                </a>
                <a href="?id=<?php echo $id; ?>&confirm=sim&motivo=parto_nao_registrado" 
                   class="btn btn-info btn-lg"
                   onclick="return confirm('Confirmar parto não registrado?')">
                    <i class="bi bi-egg"></i> Parto não registrado
                </a>
                <a href="../gestacoes.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../../../includes/footer.php'; ?>