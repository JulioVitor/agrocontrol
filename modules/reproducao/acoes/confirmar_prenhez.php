<?php
// modules/reproducao/acoes/confirmar_prenhez.php
// Confirmar prenhez

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
$femea_id = isset($_GET['femea_id']) ? intval($_GET['femea_id']) : 0;
$confirmar = isset($_GET['confirmar']) ? $_GET['confirmar'] : '';

if ($femea_id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('../inseminacoes.php');
}

// Se confirmou, processar
if ($confirmar == 'sim') {
    
    $metodo = $_GET['metodo'] ?? 'ultrassom';
    $data_confirmacao = date('Y-m-d');
    
    // Calcular data prevista do parto (283 dias após última inseminação)
    $sqlIns = "SELECT data_evento FROM reproducao 
               WHERE id_bovino_femea = $femea_id 
               AND tipo_evento = 'inseminacao' 
               ORDER BY data_evento DESC LIMIT 1";
    $resultIns = executeQuery($sqlIns);
    $inseminacao = $resultIns->fetch_assoc();
    
    $data_prevista = date('Y-m-d', strtotime($inseminacao['data_evento'] . ' +283 days'));
    
    $conn->begin_transaction();
    
    try {
        // Registrar prenhez
        $sql = "INSERT INTO reproducao (id_fazenda, id_bovino_femea, data_evento, tipo_evento,
                data_prevista_parto, confirmada, metodo_confirmacao, data_confirmacao)
                VALUES ($farmId, $femea_id, '$data_confirmacao', 'prenhez',
                '$data_prevista', 1, '$metodo', '$data_confirmacao')";
        
        if (!$conn->query($sql)) {
            throw new Exception('Erro ao registrar prenhez.');
        }
        
        // Atualizar matriz
        $sqlMatriz = "UPDATE matrizes SET 
                     status_reprodutivo = 'prenhe'
                     WHERE id_bovino = $femea_id";
        $conn->query($sqlMatriz);
        
        $conn->commit();
        
        setAlert('Prenhez confirmada com sucesso!', 'success');
        
    } catch (Exception $e) {
        $conn->rollback();
        setAlert('Erro ao confirmar prenhez: ' . $e->getMessage(), 'danger');
    }
    
    redirect('../inseminacoes.php');
}

// Página de confirmação
$pageTitle = 'Confirmar Prenhez';

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-check-circle me-2"></i>
                Confirmar Prenhez
            </h5>
        </div>
        <div class="card-body">
            <p>Confirme o diagnóstico de prenhez para a matriz selecionada.</p>
            
            <form action="" method="GET" class="row g-3">
                <input type="hidden" name="femea_id" value="<?php echo $femea_id; ?>">
                <input type="hidden" name="confirmar" value="sim">
                
                <div class="col-md-6">
                    <label class="form-label">Método de Diagnóstico</label>
                    <select class="form-select" name="metodo" required>
                        <option value="ultrassom">Ultrassom</option>
                        <option value="palpacao">Palpação</option>
                        <option value="sangue">Exame de Sangue</option>
                        <option value="observacao">Observação</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Confirmar Prenhez
                    </button>
                    <a href="../inseminacoes.php" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../../includes/footer.php'; ?>