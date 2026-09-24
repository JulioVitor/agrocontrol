<?php
// modules/fazendas/visualizar.php
// Visualizar detalhes de uma fazenda específica

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Detalhes da Fazenda';

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Verificar se o usuário tem acesso a esta fazenda
$checkSql = "SELECT f.*, ufp.papel 
             FROM fazendas f
             INNER JOIN fazenda_usuarios ufp ON f.id = ufp.id_fazenda
             WHERE f.id = $id AND ufp.id_usuario = $userId AND ufp.ativo = 1";
$result = executeQuery($checkSql);

if (!$result || $result->num_rows == 0) {
    setAlert('Fazenda não encontrada ou você não tem permissão.', 'danger');
    redirect('index.php');
}

$fazenda = $result->fetch_assoc();

// Estatísticas da fazenda
$stats = [];

// Total de bovinos
$sqlBovinos = "SELECT COUNT(*) as total FROM bovinos WHERE id_fazenda = $id AND ativo = 1";
$resBovinos = executeQuery($sqlBovinos);
$stats['total_bovinos'] = $resBovinos->fetch_assoc()['total'];

// Total por sexo
$sqlSexo = "SELECT sexo, COUNT(*) as total FROM bovinos WHERE id_fazenda = $id AND ativo = 1 GROUP BY sexo";
$resSexo = executeQuery($sqlSexo);
$stats['machos'] = 0;
$stats['femeas'] = 0;
while ($row = $resSexo->fetch_assoc()) {
    if ($row['sexo'] == 'M') $stats['machos'] = $row['total'];
    else $stats['femeas'] = $row['total'];
}

// Total de piquetes
$sqlPiquetes = "SELECT COUNT(*) as total FROM piquetes WHERE id_fazenda = $id";
$resPiquetes = executeQuery($sqlPiquetes);
$stats['total_piquetes'] = $resPiquetes->fetch_assoc()['total'];

// Total de usuários com acesso
$sqlUsuarios = "SELECT COUNT(*) as total FROM fazenda_usuarios WHERE id_fazenda = $id AND ativo = 1";
$resUsuarios = executeQuery($sqlUsuarios);
$stats['total_usuarios'] = $resUsuarios->fetch_assoc()['total'];

// Últimos animais cadastrados
$sqlUltimos = "SELECT id, brinco, nome, data_cadastro 
               FROM bovinos 
               WHERE id_fazenda = $id AND ativo = 1 
               ORDER BY data_cadastro DESC 
               LIMIT 5";
$ultimosBovinos = executeQuery($sqlUltimos);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-building me-2 text-success"></i>
                <?php echo $fazenda['nome_fazenda']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Fazendas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($fazenda['id'] != getActiveFarmId()): ?>
            <button onclick="setActiveFarm(<?php echo $fazenda['id']; ?>)" class="btn btn-success me-2">
                <i class="bi bi-check-circle me-2"></i>
                Selecionar esta Fazenda
            </button>
            <?php endif; ?>
            <a href="editar.php?id=<?php echo $fazenda['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-2"></i>
                Editar
            </a>
        </div>
    </div>

    <!-- Informações da Fazenda -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2 text-success"></i>
                        Informações Gerais
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Nome:</th>
                                    <td><?php echo $fazenda['nome_fazenda']; ?></td>
                                </tr>
                                <tr>
                                    <th>Proprietário:</th>
                                    <td><?php echo $_SESSION['usuario_nome']; ?></td>
                                </tr>
                                <tr>
                                    <th>Localização:</th>
                                    <td>
                                        <?php 
                                        echo $fazenda['cidade'] ?: 'Não informado';
                                        echo $fazenda['estado'] ? ' - ' . $fazenda['estado'] : '';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Área total:</th>
                                    <td>
                                        <?php echo $fazenda['area_total_hectares'] ? number_format($fazenda['area_total_hectares'], 2, ',', '.') . ' hectares' : 'Não informado'; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo $fazenda['status'] == 'ativo' ? 'success' : 'warning'; ?>">
                                            <?php echo $fazenda['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Seu papel:</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $fazenda['papel'] == 'proprietario' ? 'danger' : 
                                                ($fazenda['papel'] == 'gerente' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo $fazenda['papel']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Data cadastro:</th>
                                    <td><?php echo date('d/m/Y', strtotime($fazenda['data_cadastro'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Data ativação:</th>
                                    <td><?php echo $fazenda['data_ativacao'] ? date('d/m/Y', strtotime($fazenda['data_ativacao'])) : 'Não informado'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-speedometer2 me-2 text-success"></i>
                        Resumo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="display-4 text-success"><?php echo $stats['total_bovinos']; ?></div>
                        <div class="text-muted">Total de Animais</div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h4 text-primary"><?php echo $stats['machos']; ?></div>
                            <div class="small text-muted">Machos</div>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-warning"><?php echo $stats['femeas']; ?></div>
                            <div class="small text-muted">Fêmeas</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <span>
                            <i class="bi bi-map me-1 text-info"></i>
                            Piquetes: <strong><?php echo $stats['total_piquetes']; ?></strong>
                        </span>
                        <span>
                            <i class="bi bi-people me-1 text-info"></i>
                            Usuários: <strong><?php echo $stats['total_usuarios']; ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos animais cadastrados -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-clock-history me-2 text-success"></i>
                Últimos Animais Cadastrados
            </h5>
            <a href="../bovinos/index.php?fazenda=<?php echo $fazenda['id']; ?>" class="btn btn-sm btn-outline-success">
                Ver todos
            </a>
        </div>
        <div class="card-body">
            <?php if ($ultimosBovinos && $ultimosBovinos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Brinco</th>
                            <th>Nome</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bovino = $ultimosBovinos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $bovino['brinco']; ?></td>
                            <td><?php echo $bovino['nome'] ?: '-'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($bovino['data_cadastro'])); ?></td>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $bovino['id']; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center py-3">
                <i class="bi bi-tree"></i>
                Nenhum animal cadastrado nesta fazenda.
            </p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function setActiveFarm(farmId) {
    fetch('ajax_change_farm.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'farm_id=' + farmId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '../dashboard/index.php';
        } else {
            alert('Erro ao selecionar fazenda');
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>