<?php
// modules/reproducao/acoes/diagnostico.php
// Registrar diagnóstico de gestação

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

$femea_id = isset($_GET['femea_id']) ? intval($_GET['femea_id']) : 0;

if ($femea_id <= 0) {
    setAlert('ID da matriz inválido.', 'danger');
    redirect('../inseminacoes.php');
}

// Buscar dados da matriz
$sql = "SELECT b.*, m.status_reprodutivo
        FROM bovinos b
        LEFT JOIN matrizes m ON b.id = m.id_bovino
        WHERE b.id = $femea_id AND " . TenantManager::addTenantFilter('b');
$result = executeQuery($sql);
$matriz = $result->fetch_assoc();

if (!$matriz) {
    setAlert('Matriz não encontrada.', 'danger');
    redirect('../inseminacoes.php');
}

$pageTitle = 'Diagnóstico de Gestação';
$error = '';
$success = '';

// Buscar última inseminação
$sqlIns = "SELECT * FROM reproducao 
           WHERE id_bovino_femea = $femea_id 
           AND tipo_evento = 'inseminacao' 
           ORDER BY data_evento DESC LIMIT 1";
$resultIns = executeQuery($sqlIns);
$ultimaIA = $resultIns->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $resultado = $_POST['resultado'];
    $metodo = $_POST['metodo'];
    $data_diagnostico = $_POST['data_diagnostico'];
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if ($resultado == 'positivo') {
        // Calcular data prevista do parto
        $data_prevista = date('Y-m-d', strtotime($ultimaIA['data_evento'] . ' +283 days'));
        
        $conn->begin_transaction();
        
        try {
            // Registrar prenhez
            $sql = "INSERT INTO reproducao (id_fazenda, id_bovino_femea, data_evento, tipo_evento,
                    data_prevista_parto, confirmada, metodo_confirmacao, observacoes)
                    VALUES ($farmId, $femea_id, '$data_diagnostico', 'prenhez',
                    '$data_prevista', 1, '$metodo', $observacoes)";
            
            if (!$conn->query($sql)) {
                throw new Exception('Erro ao registrar prenhez.');
            }
            
            // Atualizar matriz
            $sqlMatriz = "UPDATE matrizes SET 
                         status_reprodutivo = 'prenhe'
                         WHERE id_bovino = $femea_id";
            $conn->query($sqlMatriz);
            
            $conn->commit();
            
            setAlert('Gestação confirmada! Data prevista do parto: ' . date('d/m/Y', strtotime($data_prevista)), 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
        
    } else {
        // Negativo - registrar apenas observação
        $sqlObs = "UPDATE matrizes SET 
                  status_reprodutivo = 'vazia'
                  WHERE id_bovino = $femea_id";
        executeQuery($sqlObs);
        
        setAlert('Diagnóstico negativo registrado.', 'info');
    }
    
    redirect('../inseminacoes.php');
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-stethoscope me-2"></i>
                Diagnóstico de Gestação
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Matriz:</strong> <?php echo $matriz['brinco']; ?> - <?php echo $matriz['nome'] ?: 'Sem nome'; ?><br>
                <strong>Última IA:</strong> <?php echo date('d/m/Y', strtotime($ultimaIA['data_evento'])); ?><br>
                <strong>Dias após IA:</strong> <?php 
                    $dias = (new DateTime())->diff(new DateTime($ultimaIA['data_evento']))->days;
                    echo $dias; 
                ?> dias
            </div>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Resultado *</label>
                        <select class="form-select" name="resultado" required>
                            <option value="">Selecione</option>
                            <option value="positivo">Positivo (Prenhe)</option>
                            <option value="negativo">Negativo (Vazia)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Método de Diagnóstico *</label>
                        <select class="form-select" name="metodo" required>
                            <option value="">Selecione</option>
                            <option value="ultrassom">Ultrassom</option>
                            <option value="palpacao">Palpação</option>
                            <option value="sangue">Exame de Sangue</option>
                            <option value="observacao">Observação</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data do Diagnóstico *</label>
                        <input type="date" class="form-control" name="data_diagnostico" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="../inseminacoes.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check-circle"></i> Registrar Diagnóstico
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../../includes/footer.php'; ?>