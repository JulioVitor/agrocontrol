<?php
// modules/calendario/eventos.php
// Lista de eventos

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

$pageTitle = 'Lista de Eventos';

// Filtros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

// Construir WHERE
$where = TenantManager::addTenantFilter('e');

if ($tipo) {
    $where .= " AND e.tipo = '$tipo'";
}

if ($status === 'concluido') {
    $where .= " AND e.concluido = 1";
} elseif ($status === 'pendente') {
    $where .= " AND e.concluido = 0 AND e.data_inicio >= CURDATE()";
} elseif ($status === 'atrasado') {
    $where .= " AND e.concluido = 0 AND e.data_inicio < CURDATE()";
}

if ($mes && $ano) {
    $data_inicio = "$ano-$mes-01";
    $data_fim = date('Y-m-t', strtotime($data_inicio));
    $where .= " AND DATE(e.data_inicio) BETWEEN '$data_inicio' AND '$data_fim'";
}

// Buscar eventos
$sql = "SELECT e.*, 
               b.brinco as bovino_brinco,
               b.nome as bovino_nome,
               u.nome as usuario_nome
        FROM eventos e
        LEFT JOIN bovinos b ON e.id_bovino = b.id
        LEFT JOIN usuarios u ON e.id_usuario = u.id
        WHERE $where
        ORDER BY e.data_inicio ASC";
$eventos = executeQuery($sql);

// Estatísticas
$stats = [];

// Total de eventos
$sqlTotal = "SELECT COUNT(*) as total FROM eventos e WHERE " . TenantManager::addTenantFilter('e');
$resultTotal = executeQuery($sqlTotal);
$stats['total'] = $resultTotal->fetch_assoc()['total'];

// Pendentes
$sqlPendentes = "SELECT COUNT(*) as total FROM eventos e 
                 WHERE " . TenantManager::addTenantFilter('e') . " 
                 AND e.concluido = 0 AND e.data_inicio >= CURDATE()";
$resultPendentes = executeQuery($sqlPendentes);
$stats['pendentes'] = $resultPendentes->fetch_assoc()['total'];

// Atrasados
$sqlAtrasados = "SELECT COUNT(*) as total FROM eventos e 
                 WHERE " . TenantManager::addTenantFilter('e') . " 
                 AND e.concluido = 0 AND e.data_inicio < CURDATE()";
$resultAtrasados = executeQuery($sqlAtrasados);
$stats['atrasados'] = $resultAtrasados->fetch_assoc()['total'];

// Concluídos
$sqlConcluidos = "SELECT COUNT(*) as total FROM eventos e 
                  WHERE " . TenantManager::addTenantFilter('e') . " 
                  AND e.concluido = 1";
$resultConcluidos = executeQuery($sqlConcluidos);
$stats['concluidos'] = $resultConcluidos->fetch_assoc()['total'];

// Tipos de eventos para filtro
$tipos = [
    'vacina' => 'Vacinas',
    'parto' => 'Partos',
    'inseminacao' => 'Inseminações',
    'desmama' => 'Desmamas',
    'venda' => 'Vendas',
    'compra' => 'Compras',
    'manutencao' => 'Manutenções',
    'consulta' => 'Consultas',
    'outro' => 'Outros'
];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-list-task me-2 text-success"></i>
                Lista de Eventos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Calendário</a></li>
                    <li class="breadcrumb-item active">Eventos</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="novo.php" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Novo Evento
            </a>
            <a href="index.php" class="btn btn-outline-info">
                <i class="bi bi-calendar3 me-2"></i>
                Calendário
            </a>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total</h6>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Pendentes</h6>
                    <h3><?php echo $stats['pendentes']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Atrasados</h6>
                    <h3><?php echo $stats['atrasados']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Concluídos</h6>
                    <h3><?php echo $stats['concluidos']; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $key => $nome): ?>
                        <option value="<?php echo $key; ?>" <?php echo $tipo == $key ? 'selected' : ''; ?>>
                            <?php echo $nome; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="atrasado" <?php echo $status == 'atrasado' ? 'selected' : ''; ?>>Atrasados</option>
                        <option value="concluido" <?php echo $status == 'concluido' ? 'selected' : ''; ?>>Concluídos</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Mês</label>
                    <select class="form-select" name="mes">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $mes == $m ? 'selected' : ''; ?>>
                            <?php echo strftime('%B', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Ano</label>
                    <select class="form-select" name="ano">
                        <?php for ($a = date('Y')-1; $a <= date('Y')+1; $a++): ?>
                        <option value="<?php echo $a; ?>" <?php echo $ano == $a ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success mt-4">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de eventos -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($eventos && $eventos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Data/Hora</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Bovino</th>
                            <th>Local</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($evento = $eventos->fetch_assoc()): 
                            $data = new DateTime($evento['data_inicio']);
                            $hoje = new DateTime();
                            $classeStatus = '';
                            
                            if ($evento['concluido']) {
                                $classeStatus = 'success';
                                $statusTexto = 'Concluído';
                            } elseif ($data < $hoje) {
                                $classeStatus = 'danger';
                                $statusTexto = 'Atrasado';
                            } else {
                                $dias = $hoje->diff($data)->days;
                                if ($dias <= 2) {
                                    $classeStatus = 'warning';
                                    $statusTexto = 'Próximo';
                                } else {
                                    $classeStatus = 'info';
                                    $statusTexto = 'Pendente';
                                }
                            }
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?php echo $classeStatus; ?>">
                                    <?php echo $statusTexto; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo $data->format('d/m/Y'); ?></strong>
                                <?php if (!$evento['dia_inteiro']): ?>
                                    <br><small><?php echo $data->format('H:i'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="visualizar.php?id=<?php echo $evento['id']; ?>">
                                    <strong><?php echo $evento['titulo']; ?></strong>
                                </a>
                                <?php if ($evento['descricao']): ?>
                                    <br><small class="text-muted"><?php echo substr($evento['descricao'], 0, 50); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $evento['cor']; ?>">
                                    <?php echo $tipos[$evento['tipo']] ?? $evento['tipo']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($evento['bovino_brinco']): ?>
                                    <a href="../bovinos/visualizar.php?id=<?php echo $evento['id_bovino']; ?>">
                                        <?php echo $evento['bovino_brinco']; ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $evento['local'] ?: '-'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$evento['concluido']): ?>
                                    <a href="acoes/concluir.php?id=<?php echo $evento['id']; ?>" 
                                       class="btn btn-outline-success" title="Concluir">
                                        <i class="bi bi-check-lg"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="editar.php?id=<?php echo $evento['id']; ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="acoes/excluir.php?id=<?php echo $evento['id']; ?>" 
                                       class="btn btn-outline-danger" title="Excluir"
                                       onclick="return confirm('Excluir este evento?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum evento encontrado</h4>
                <p class="text-muted">Tente ajustar os filtros ou crie um novo evento.</p>
                <a href="novo.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>
                    Novo Evento
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>