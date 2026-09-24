<?php
// modules/reproducao/nova_inseminacao.php
// Registrar inseminação artificial

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

$pageTitle = 'Nova Inseminação';
$error = '';
$success = '';

// Buscar fêmeas disponíveis
$sqlFemeas = "SELECT b.*, m.status_reprodutivo 
              FROM bovinos b
              LEFT JOIN matrizes m ON b.id = m.id_bovino
              WHERE " . TenantManager::addTenantFilter('b') . " 
              AND b.sexo = 'F' AND b.ativo = 1
              ORDER BY b.brinco";
$femeas = executeQuery($sqlFemeas);

// Buscar touros
$sqlTouros = "SELECT id, brinco, nome FROM bovinos 
              WHERE " . TenantManager::addTenantFilter() . " 
              AND sexo = 'M' AND ativo = 1 
              ORDER BY brinco";
$touros = executeQuery($sqlTouros);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_bovino_femea = intval($_POST['id_bovino_femea']);
    $id_bovino_macho = !empty($_POST['id_bovino_macho']) ? intval($_POST['id_bovino_macho']) : "NULL";
    $data_evento = $_POST['data_evento'];
    $tecnico = !empty($_POST['tecnico']) ? "'" . escapeString($_POST['tecnico']) . "'" : "NULL";
    $semen_raca = !empty($_POST['semen_raca']) ? "'" . escapeString($_POST['semen_raca']) . "'" : "NULL";
    $semen_touro = !empty($_POST['semen_touro']) ? "'" . escapeString($_POST['semen_touro']) . "'" : "NULL";
    $lote_semen = !empty($_POST['lote_semen']) ? "'" . escapeString($_POST['lote_semen']) . "'" : "NULL";
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if ($id_bovino_femea <= 0 || empty($data_evento)) {
        $error = 'Selecione a matriz e a data da inseminação.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Registrar inseminação
            $sql = "INSERT INTO reproducao (
                    id_fazenda, id_bovino_femea, id_bovino_macho, data_evento, 
                    tipo_evento, tecnico, semen_raca, semen_touro, lote_semen, observacoes
                ) VALUES (
                    $farmId, $id_bovino_femea, $id_bovino_macho, '$data_evento',
                    'inseminacao', $tecnico, $semen_raca, $semen_touro, $lote_semen, $observacoes
                )";
            
            if (!$conn->query($sql)) {
                throw new Exception('Erro ao registrar inseminação.');
            }
            
            // Calcular data prevista para diagnóstico (30 dias)
            $data_diagnostico = date('Y-m-d', strtotime($data_evento . ' +30 days'));
            
            // Atualizar matriz
            $sqlMatriz = "UPDATE matrizes SET 
                         data_ultima_inseminacao = '$data_evento',
                         status_reprodutivo = 'inseminada'
                         WHERE id_bovino = $id_bovino_femea";
            
            if (!$conn->query($sqlMatriz)) {
                // Se não existir, inserir
                $sqlInsert = "INSERT INTO matrizes (id_bovino, data_ultima_inseminacao, status_reprodutivo) 
                              VALUES ($id_bovino_femea, '$data_evento', 'inseminada')";
                $conn->query($sqlInsert);
            }
            
            $conn->commit();
            
            setAlert('Inseminação registrada com sucesso!', 'success');
            redirect('inseminacoes.php');
            
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
                <i class="bi bi-syringe me-2 text-warning"></i>
                Nova Inseminação
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Nova Inseminação</li>
                </ol>
            </nav>
        </div>
        <a href="inseminacoes.php" class="btn btn-outline-secondary">
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
                        <label class="form-label">Matriz *</label>
                        <select class="form-select" name="id_bovino_femea" required>
                            <option value="">Selecione</option>
                            <?php if ($femeas && $femeas->num_rows > 0): ?>
                                <?php while ($f = $femeas->fetch_assoc()): ?>
                                <option value="<?php echo $f['id']; ?>">
                                    <?php echo $f['brinco']; ?> - <?php echo $f['nome'] ?: 'Sem nome'; ?>
                                    (<?php echo $f['status_reprodutivo'] ?? 'vazia'; ?>)
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data da Inseminação *</label>
                        <input type="date" class="form-control" name="data_evento" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Touro (cadastrado)</label>
                        <select class="form-select" name="id_bovino_macho">
                            <option value="">Sêmen (informar abaixo)</option>
                            <?php if ($touros && $touros->num_rows > 0): ?>
                                <?php while ($t = $touros->fetch_assoc()): ?>
                                <option value="<?php echo $t['id']; ?>">
                                    <?php echo $t['brinco']; ?> - <?php echo $t['nome'] ?: 'Sem nome'; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Técnico</label>
                        <input type="text" class="form-control" name="tecnico" 
                               placeholder="Nome do técnico">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Raça do Sêmen</label>
                        <input type="text" class="form-control" name="semen_raca" 
                               placeholder="Ex: Nelore, Angus">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Touro do Sêmen</label>
                        <input type="text" class="form-control" name="semen_touro" 
                               placeholder="Nome/Código do touro">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Lote do Sêmen</label>
                        <input type="text" class="form-control" name="lote_semen" 
                               placeholder="Número do lote">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="inseminacoes.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Registrar Inseminação
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