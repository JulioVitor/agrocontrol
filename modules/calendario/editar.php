<?php
// modules/calendario/editar.php
// Editar evento existente

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

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Buscar dados do evento
$sql = "SELECT e.*, b.brinco as bovino_brinco
        FROM eventos e
        LEFT JOIN bovinos b ON e.id_bovino = b.id
        WHERE e.id = $id AND e.id_fazenda = $farmId";
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Evento não encontrado.', 'danger');
    redirect('index.php');
}

$evento = $result->fetch_assoc();

// Separar data e hora
$data_inicio = date('Y-m-d', strtotime($evento['data_inicio']));
$hora_inicio = date('H:i', strtotime($evento['data_inicio']));

$data_fim = $evento['data_fim'] ? date('Y-m-d', strtotime($evento['data_fim'])) : '';
$hora_fim = $evento['data_fim'] ? date('H:i', strtotime($evento['data_fim'])) : '';

$pageTitle = 'Editar Evento';
$error = '';
$success = '';

// Buscar bovinos para seleção
$sqlBovinos = "SELECT id, brinco, nome FROM bovinos 
               WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
               ORDER BY brinco";
$bovinos = executeQuery($sqlBovinos);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $titulo = escapeString(trim($_POST['titulo']));
    $tipo = $_POST['tipo'];
    $descricao = !empty($_POST['descricao']) ? "'" . escapeString($_POST['descricao']) . "'" : "NULL";
    $data_inicio = $_POST['data_inicio'];
    $hora_inicio = !empty($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '00:00';
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
    $hora_fim = !empty($_POST['hora_fim']) ? $_POST['hora_fim'] : '00:00';
    $dia_inteiro = isset($_POST['dia_inteiro']) ? 1 : 0;
    $local = !empty($_POST['local']) ? "'" . escapeString($_POST['local']) . "'" : "NULL";
    $id_bovino = !empty($_POST['id_bovino']) ? intval($_POST['id_bovino']) : "NULL";
    $cor = $_POST['cor'];
    $notificar = isset($_POST['notificar']) ? 1 : 0;
    $notificar_antecedencia = !empty($_POST['notificar_antecedencia']) ? intval($_POST['notificar_antecedencia']) : 0;
    
    // Montar datetime completo
    $datetime_inicio = $data_inicio . ' ' . $hora_inicio . ':00';
    $datetime_fim = null;
    if ($data_fim) {
        $datetime_fim = $data_fim . ' ' . $hora_fim . ':00';
    }
    
    if (empty($titulo) || empty($tipo) || empty($data_inicio)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        
        $sql = "UPDATE eventos SET 
                titulo = '$titulo',
                tipo = '$tipo',
                descricao = $descricao,
                data_inicio = '$datetime_inicio',
                data_fim = " . ($datetime_fim ? "'$datetime_fim'" : "NULL") . ",
                dia_inteiro = $dia_inteiro,
                local = $local,
                id_bovino = $id_bovino,
                cor = '$cor',
                notificar = $notificar,
                notificar_antecedencia = $notificar_antecedencia
                WHERE id = $id AND id_fazenda = $farmId";
        
        if (executeQuery($sql)) {
            setAlert('Evento atualizado com sucesso!', 'success');
            redirect('visualizar.php?id=' . $id);
        } else {
            $error = 'Erro ao atualizar evento.';
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
                <i class="bi bi-pencil me-2 text-warning"></i>
                Editar Evento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Calendário</a></li>
                    <li class="breadcrumb-item"><a href="visualizar.php?id=<?php echo $id; ?>"><?php echo $evento['titulo']; ?></a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                <i class="bi bi-eye"></i> Visualizar
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
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
                    <div class="col-md-8">
                        <label class="form-label">Título do Evento *</label>
                        <input type="text" class="form-control" name="titulo" 
                               value="<?php echo $evento['titulo']; ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" required>
                            <option value="">Selecione</option>
                            <option value="vacina" <?php echo $evento['tipo'] == 'vacina' ? 'selected' : ''; ?>>Vacina</option>
                            <option value="parto" <?php echo $evento['tipo'] == 'parto' ? 'selected' : ''; ?>>Parto</option>
                            <option value="inseminacao" <?php echo $evento['tipo'] == 'inseminacao' ? 'selected' : ''; ?>>Inseminação</option>
                            <option value="desmama" <?php echo $evento['tipo'] == 'desmama' ? 'selected' : ''; ?>>Desmama</option>
                            <option value="venda" <?php echo $evento['tipo'] == 'venda' ? 'selected' : ''; ?>>Venda</option>
                            <option value="compra" <?php echo $evento['tipo'] == 'compra' ? 'selected' : ''; ?>>Compra</option>
                            <option value="manutencao" <?php echo $evento['tipo'] == 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
                            <option value="consulta" <?php echo $evento['tipo'] == 'consulta' ? 'selected' : ''; ?>>Consulta Veterinária</option>
                            <option value="outro" <?php echo $evento['tipo'] == 'outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"><?php echo $evento['descricao']; ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data de Início *</label>
                        <input type="date" class="form-control" name="data_inicio" 
                               value="<?php echo $data_inicio; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Hora de Início</label>
                        <input type="time" class="form-control" name="hora_inicio" 
                               value="<?php echo $hora_inicio; ?>" <?php echo $evento['dia_inteiro'] ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data de Término</label>
                        <input type="date" class="form-control" name="data_fim" 
                               value="<?php echo $data_fim; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Hora de Término</label>
                        <input type="time" class="form-control" name="hora_fim" 
                               value="<?php echo $hora_fim; ?>" <?php echo $evento['dia_inteiro'] ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="dia_inteiro" id="dia_inteiro" 
                                   <?php echo $evento['dia_inteiro'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="dia_inteiro">
                                Dia inteiro
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Cor do Evento</label>
                        <input type="color" class="form-control form-control-color" name="cor" 
                               value="<?php echo $evento['cor'] ?: '#28a745'; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Local</label>
                        <input type="text" class="form-control" name="local" 
                               value="<?php echo $evento['local'] ?: ''; ?>"
                               placeholder="Ex: Piquete 1, Sala de ordenha...">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Vincular a Bovino</label>
                        <select class="form-select" name="id_bovino">
                            <option value="">Nenhum</option>
                            <?php if ($bovinos && $bovinos->num_rows > 0): ?>
                                <?php while ($b = $bovinos->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>" 
                                    <?php echo ($evento['id_bovino'] == $b['id']) ? 'selected' : ''; ?>>
                                    <?php echo $b['brinco']; ?> - <?php echo $b['nome'] ?: 'Sem nome'; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notificar" id="notificar"
                                   <?php echo $evento['notificar'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notificar">
                                Notificar antes do evento
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Antecedência (minutos)</label>
                        <input type="number" class="form-control" name="notificar_antecedencia" 
                               value="<?php echo $evento['notificar_antecedencia'] ?: 30; ?>" min="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <div>
                            <?php if ($evento['concluido']): ?>
                                <span class="badge bg-success">Concluído</span>
                                <input type="hidden" name="concluido" value="1">
                            <?php else: ?>
                                <span class="badge bg-warning">Pendente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Mostrar/ocultar hora baseado no checkbox "dia inteiro"
document.getElementById('dia_inteiro').addEventListener('change', function() {
    var inputs = ['hora_inicio', 'hora_fim'];
    inputs.forEach(function(id) {
        var input = document.querySelector('[name="' + id + '"]');
        if (input) {
            input.disabled = this.checked;
        }
    }.bind(this));
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