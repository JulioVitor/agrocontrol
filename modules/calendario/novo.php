<?php
// modules/calendario/novo.php
// Criar novo evento


ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$pageTitle = 'Novo Evento';
$error = '';
$success = '';

// Buscar bovinos para seleção
$sqlBovinos = "SELECT id, brinco, nome FROM bovinos 
               WHERE " . TenantManager::addTenantFilter() . " AND ativo = 1 
               ORDER BY brinco";
$bovinos = executeQuery($sqlBovinos);

// Data padrão (se veio do calendário)
$data_padrao = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = escapeString(trim($_POST['titulo']));
    $tipo = $_POST['tipo'];
    $descricao = !empty($_POST['descricao']) ? "'" . escapeString($_POST['descricao']) . "'" : "NULL";
    $data_inicio = $_POST['data_inicio'];
    $hora_inicio = !empty($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '00:00';
    $data_fim = !empty($_POST['data_fim']) ? "'" . $_POST['data_fim'] . "'" : "NULL";
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
    if ($data_fim != "NULL" && !empty($_POST['data_fim'])) {
        $datetime_fim = $_POST['data_fim'] . ' ' . $hora_fim . ':00';
    }

    if (empty($titulo) || empty($tipo) || empty($data_inicio)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {

        // CORREÇÃO: Ajustar a sintaxe do INSERT
        $sql = "INSERT INTO eventos (
                id_fazenda, 
                id_usuario, 
                id_bovino, 
                titulo, 
                descricao, 
                tipo,
                data_inicio, 
                data_fim, 
                dia_inteiro, 
                local, 
                cor,
                notificar, 
                notificar_antecedencia
            ) VALUES (
                $farmId, 
                {$_SESSION['usuario_id']}, 
                " . ($id_bovino != "NULL" ? $id_bovino : "NULL") . ", 
                '$titulo', 
                " . ($descricao != "NULL" ? $descricao : "NULL") . ", 
                '$tipo',
                '$datetime_inicio', 
                " . ($datetime_fim ? "'$datetime_fim'" : "NULL") . ", 
                $dia_inteiro, 
                " . ($local != "NULL" ? $local : "NULL") . ", 
                '$cor',
                $notificar, 
                $notificar_antecedencia
            )";

        if (executeQuery($sql)) {
            setAlert('Evento criado com sucesso!', 'success');
            redirect('index.php');
        } else {
            $error = 'Erro ao criar evento.';
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
                Novo Evento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Calendário</a></li>
                    <li class="breadcrumb-item active">Novo Evento</li>
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
                    <div class="col-md-8">
                        <label class="form-label">Título do Evento *</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" required>
                            <option value="">Selecione</option>
                            <option value="vacina">Vacina</option>
                            <option value="parto">Parto</option>
                            <option value="inseminacao">Inseminação</option>
                            <option value="desmama">Desmama</option>
                            <option value="venda">Venda</option>
                            <option value="compra">Compra</option>
                            <option value="manutencao">Manutenção</option>
                            <option value="consulta">Consulta Veterinária</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Data de Início *</label>
                        <input type="date" class="form-control" name="data_inicio"
                            value="<?php echo $data_padrao; ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Hora de Início</label>
                        <input type="time" class="form-control" name="hora_inicio" value="08:00">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Data de Término</label>
                        <input type="date" class="form-control" name="data_fim">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Hora de Término</label>
                        <input type="time" class="form-control" name="hora_fim" value="17:00">
                    </div>

                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="dia_inteiro" id="dia_inteiro" checked>
                            <label class="form-check-label" for="dia_inteiro">
                                Dia inteiro
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cor do Evento</label>
                        <input type="color" class="form-control form-control-color" name="cor" value="#28a745">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Local</label>
                        <input type="text" class="form-control" name="local"
                            placeholder="Ex: Piquete 1, Sala de ordenha...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Vincular a Bovino</label>
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
                    </div>

                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notificar" id="notificar">
                            <label class="form-check-label" for="notificar">
                                Notificar antes do evento
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Antecedência (minutos)</label>
                        <input type="number" class="form-control" name="notificar_antecedencia"
                            value="30" min="0">
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Salvar Evento
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
            document.querySelector('[name="' + id + '"]').disabled = this.checked;
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