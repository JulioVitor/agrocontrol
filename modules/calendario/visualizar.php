<?php
// modules/calendario/visualizar.php
// Visualizar detalhes do evento

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
$sql = "SELECT e.*, 
               b.brinco as bovino_brinco,
               b.nome as bovino_nome,
               u.nome as usuario_nome,
               u.email as usuario_email
        FROM eventos e
        LEFT JOIN bovinos b ON e.id_bovino = b.id
        LEFT JOIN usuarios u ON e.id_usuario = u.id
        WHERE e.id = $id AND " . TenantManager::addTenantFilter('e');
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Evento não encontrado.', 'danger');
    redirect('index.php');
}

$evento = $result->fetch_assoc();

$data_inicio = new DateTime($evento['data_inicio']);
$data_fim = $evento['data_fim'] ? new DateTime($evento['data_fim']) : null;

$pageTitle = 'Evento: ' . $evento['titulo'];

// Tipos de eventos
$tipos = [
    'vacina' => 'Vacina',
    'parto' => 'Parto',
    'inseminacao' => 'Inseminação',
    'desmama' => 'Desmama',
    'venda' => 'Venda',
    'compra' => 'Compra',
    'manutencao' => 'Manutenção',
    'consulta' => 'Consulta Veterinária',
    'outro' => 'Outro'
];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-calendar-event me-2 text-success"></i>
                Detalhes do Evento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Calendário</a></li>
                    <li class="breadcrumb-item"><a href="eventos.php">Eventos</a></li>
                    <li class="breadcrumb-item active"><?php echo $evento['titulo']; ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if (!$evento['concluido']): ?>
            <a href="acoes/concluir.php?id=<?php echo $id; ?>" class="btn btn-success me-2">
                <i class="bi bi-check-lg"></i> Marcar Concluído
            </a>
            <?php endif; ?>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning me-2">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="eventos.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Card do evento -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: <?php echo $evento['cor']; ?>; color: white;">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        <?php echo $evento['titulo']; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Tipo</h6>
                            <p class="h5"><?php echo $tipos[$evento['tipo']] ?? $evento['tipo']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p>
                                <?php if ($evento['concluido']): ?>
                                    <span class="badge bg-success">Concluído</span>
                                    <small class="text-muted d-block">
                                        em <?php echo date('d/m/Y H:i', strtotime($evento['data_conclusao'])); ?>
                                    </small>
                                <?php elseif ($data_inicio < new DateTime()): ?>
                                    <span class="badge bg-danger">Atrasado</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Pendente</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Data e Hora</h6>
                            <p>
                                <i class="bi bi-calendar me-2"></i>
                                <strong>Início:</strong> <?php echo $data_inicio->format('d/m/Y'); ?>
                                <?php if (!$evento['dia_inteiro']): ?>
                                    às <?php echo $data_inicio->format('H:i'); ?>
                                <?php endif; ?>
                                <br>
                                <?php if ($data_fim): ?>
                                    <i class="bi bi-calendar me-2"></i>
                                    <strong>Término:</strong> <?php echo $data_fim->format('d/m/Y'); ?>
                                    <?php if (!$evento['dia_inteiro']): ?>
                                        às <?php echo $data_fim->format('H:i'); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-muted">Local</h6>
                            <p><?php echo $evento['local'] ?: 'Não informado'; ?></p>
                        </div>
                    </div>
                    
                    <?php if ($evento['id_bovino']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Bovino Vinculado</h6>
                            <p>
                                <a href="../bovinos/visualizar.php?id=<?php echo $evento['id_bovino']; ?>" class="text-decoration-none">
                                    <i class="bi bi-tree"></i>
                                    <strong><?php echo $evento['bovino_brinco']; ?></strong>
                                    <?php if ($evento['bovino_nome']): ?>
                                        - <?php echo $evento['bovino_nome']; ?>
                                    <?php endif; ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($evento['descricao']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Descrição</h6>
                            <p class="bg-light p-3 rounded"><?php echo nl2br($evento['descricao']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($evento['notificar']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Notificação</h6>
                            <p>
                                <i class="bi bi-bell"></i>
                                Notificar <?php echo $evento['notificar_antecedencia']; ?> minutos antes
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <small class="text-muted">
                                <i class="bi bi-person"></i> Criado por: <?php echo $evento['usuario_nome']; ?>
                                <br>
                                <i class="bi bi-clock"></i> Cadastrado em: <?php echo date('d/m/Y H:i', strtotime($evento['data_cadastro'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Mini calendário -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar2-week"></i>
                        Mês Atual
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div id="miniCalendario"></div>
                </div>
            </div>
            
            <!-- Ações rápidas -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning"></i>
                        Ações Rápidas
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="../bovinos/novo.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle text-success"></i> Novo Bovino
                    </a>
                    <a href="../vacinas/cadastrar.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-shield text-info"></i> Registrar Vacina
                    </a>
                    <a href="../leite/cadastrar.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-cup text-warning"></i> Registrar Produção
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Mini calendário -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('miniCalendario');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: 250,
        events: '../api.php',
        eventDisplay: 'background',
        eventDidMount: function(info) {
            // Destacar o evento atual
            if (info.event.id == <?php echo $id; ?>) {
                info.el.style.backgroundColor = '<?php echo $evento['cor']; ?>';
                info.el.style.opacity = '0.5';
            }
        }
    });
    calendar.render();
});
</script>

<?php include '../../includes/footer.php'; ?>