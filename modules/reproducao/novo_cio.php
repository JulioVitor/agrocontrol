<?php
// modules/reproducao/novo_cio.php
// Registrar cio

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

$pageTitle = 'Registrar Cio';
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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_bovino = intval($_POST['id_bovino']);
    $data_evento = $_POST['data_evento'];
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    if ($id_bovino <= 0 || empty($data_evento)) {
        $error = 'Selecione o animal e a data do cio.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Registrar cio
            $sql = "INSERT INTO reproducao (id_fazenda, id_bovino_femea, data_evento, tipo_evento, observacoes) 
                    VALUES ($farmId, $id_bovino, '$data_evento', 'cio', $observacoes)";
            
            if (!$conn->query($sql)) {
                throw new Exception('Erro ao registrar cio.');
            }
            
            // Atualizar matriz
            $sqlMatriz = "UPDATE matrizes SET 
                         data_ultimo_cio = '$data_evento',
                         status_reprodutivo = 'vazia'
                         WHERE id_bovino = $id_bovino";
            
            if (!$conn->query($sqlMatriz)) {
                // Se não existir, inserir
                $sqlInsert = "INSERT INTO matrizes (id_bovino, data_ultimo_cio, status_reprodutivo) 
                              VALUES ($id_bovino, '$data_evento', 'vazia')";
                $conn->query($sqlInsert);
            }
            
            $conn->commit();
            
            setAlert('Cio registrado com sucesso!', 'success');
            redirect('cios.php');
            
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
                <i class="bi bi-heart me-2 text-danger"></i>
                Registrar Cio
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Novo Cio</li>
                </ol>
            </nav>
        </div>
        <a href="cios.php" class="btn btn-outline-secondary">
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
                        <select class="form-select" name="id_bovino" required>
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
                        <label class="form-label">Data do Cio *</label>
                        <input type="date" class="form-control" name="data_evento" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3" 
                                  placeholder="Intensidade, comportamento, etc..."></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="cios.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-save"></i> Registrar Cio
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