<?php
// modules/calendario/index.php
// Calendário principal da fazenda

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

$pageTitle = 'Calendário da Fazenda';

// Buscar eventos para o calendário (via API AJAX)
// A página vai carregar o calendário vazio e buscar os eventos via JavaScript

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-calendar3 me-2 text-success"></i>
                Calendário da Fazenda
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Calendário</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="novo.php" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Novo Evento
            </a>
            <a href="eventos.php" class="btn btn-outline-info">
                <i class="bi bi-list-ul me-2"></i>
                Lista
            </a>
        </div>
    </div>

    <!-- Filtros rápidos -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="filtrarTipo('todos')">Todos</button>
                        <button type="button" class="btn btn-outline-success" onclick="filtrarTipo('vacina')">Vacinas</button>
                        <button type="button" class="btn btn-outline-danger" onclick="filtrarTipo('parto')">Partos</button>
                        <button type="button" class="btn btn-outline-warning" onclick="filtrarTipo('inseminacao')">Inseminações</button>
                        <button type="button" class="btn btn-outline-info" onclick="filtrarTipo('manutencao')">Manutenções</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div id="calendario"></div>
        </div>
    </div>

    <!-- Legenda -->
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <span class="badge bg-success">Vacinas</span>
                </div>
                <div class="col-md-2">
                    <span class="badge bg-danger">Partos</span>
                </div>
                <div class="col-md-2">
                    <span class="badge bg-warning">Inseminações</span>
                </div>
                <div class="col-md-2">
                    <span class="badge bg-info">Desmamas</span>
                </div>
                <div class="col-md-2">
                    <span class="badge bg-primary">Manutenções</span>
                </div>
                <div class="col-md-2">
                    <span class="badge bg-secondary">Outros</span>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- FullCalendar CSS e JS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendario');
    
    window.calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia'
        },
        events: 'api.php',
        eventClick: function(info) {
            // Abrir detalhes do evento
            window.location.href = 'visualizar.php?id=' + info.event.id;
        },
        eventDidMount: function(info) {
            // Tooltip personalizado
            var tooltip = new bootstrap.Tooltip(info.el, {
                title: info.event.title + ' - ' + (info.event.extendedProps.descricao || ''),
                placement: 'top',
                trigger: 'hover',
                container: 'body'
            });
        },
        dateClick: function(info) {
            // Criar evento na data clicada
            if (confirm('Criar novo evento para ' + info.dateStr + '?')) {
                window.location.href = 'novo.php?data=' + info.dateStr;
            }
        },
        eventDrop: function(info) {
            // Atualizar data do evento via AJAX
            if (!confirm('Mover este evento?')) {
                info.revert();
                return;
            }
            
            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'updateDate',
                    id: info.event.id,
                    data_inicio: info.event.start.toISOString(),
                    data_fim: info.event.end ? info.event.end.toISOString() : null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Erro ao mover evento: ' + data.message);
                    info.revert();
                }
            });
        }
    });
    
    calendar.render();
});

function filtrarTipo(tipo) {
    if (tipo === 'todos') {
        window.calendar.getEventSources()[0].setFilter(null);
    } else {
        window.calendar.getEventSources()[0].setFilter(function(event) {
            return event.extendedProps.tipo === tipo;
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>