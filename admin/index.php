<?php
// modules/admin/index.php
// Dashboard administrativo do SaaS

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once 'auth.php'; 

AdminAuth::requireAdmin();

// Verificar se é admin (você pode criar uma tabela de admins ou usar um email específico)
$admin_emails = ['admin@agrocontrol.com', 'seu@email.com']; // Adicione seus emails aqui
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_email'], $admin_emails)) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$pageTitle = 'Admin Dashboard';

// Estatísticas globais
$stats = [];

// Total de fazendas
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'trial' THEN 1 ELSE 0 END) as trial,
            SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativo,
            SUM(CASE WHEN status = 'inadimplente' THEN 1 ELSE 0 END) as inadimplente,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelado
        FROM fazendas";
$result = executeQuery($sql);
$stats['fazendas'] = $result->fetch_assoc();

// Total de usuários
$sql = "SELECT COUNT(*) as total FROM usuarios";
$result = executeQuery($sql);
$stats['usuarios'] = $result->fetch_assoc()['total'];

// Total de animais (todos os tenants)
$sql = "SELECT COUNT(*) as total FROM bovinos";
$result = executeQuery($sql);
$stats['animais'] = $result->fetch_assoc()['total'];

// Receita mensal estimada
$sql = "SELECT COALESCE(SUM(preco_mensal), 0) as receita 
        FROM fazendas f 
        JOIN planos p ON f.id_plano = p.id 
        WHERE f.status IN ('trial', 'ativo')";
$result = executeQuery($sql);
$stats['receita_mensal'] = $result->fetch_assoc()['receita'] ?? 0;

// Planos mais usados
$sqlPlanos = "SELECT p.nome_plano, COUNT(f.id) as total_fazendas
              FROM planos p
              LEFT JOIN fazendas f ON p.id = f.id_plano
              GROUP BY p.id
              ORDER BY total_fazendas DESC";
$planos = executeQuery($sqlPlanos);

// Últimas fazendas cadastradas
$sql = "SELECT f.*, u.nome as proprietario_nome, p.nome_plano 
        FROM fazendas f
        JOIN usuarios u ON f.id_proprietario = u.id
        JOIN planos p ON f.id_plano = p.id
        ORDER BY f.data_cadastro DESC
        LIMIT 10";
$recentFarms = executeQuery($sql);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Admin -->
        <nav class="col-md-2 d-md-block bg-light sidebar" style="min-height: 100vh;">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tenants.php">
                            <i class="bi bi-building me-2"></i>
                            Fazendas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="planos.php">
                            <i class="bi bi-tags me-2"></i>
                            Planos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people me-2"></i>
                            Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system.php">
                            <i class="bi bi-gear me-2"></i>
                            Sistema
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard Administrativo</h1>
            </div>
            
            <!-- Cards -->
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h6 class="card-title">Fazendas</h6>
                            <h2><?php echo $stats['fazendas']['total']; ?></h2>
                            <small>
                                Trial: <?php echo $stats['fazendas']['trial']; ?> | 
                                Ativas: <?php echo $stats['fazendas']['ativo']; ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h6 class="card-title">Usuários</h6>
                            <h2><?php echo $stats['usuarios']; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h6 class="card-title">Animais</h6>
                            <h2><?php echo number_format($stats['animais'], 0, ',', '.'); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h6 class="card-title">Receita Mensal</h6>
                            <h2>R$ <?php echo number_format($stats['receita_mensal'], 2, ',', '.'); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Planos -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Distribuição de Planos
                        </div>
                        <div class="card-body">
                            <canvas id="graficoPlanos" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Status das Fazendas
                        </div>
                        <div class="card-body">
                            <canvas id="graficoStatus" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Últimas fazendas -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>
                    Últimas Fazendas Cadastradas
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fazenda</th>
                                    <th>Proprietário</th>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentFarms && $recentFarms->num_rows > 0): ?>
                                    <?php while ($farm = $recentFarms->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $farm['nome_fazenda']; ?></td>
                                        <td><?php echo $farm['proprietario_nome']; ?></td>
                                        <td><?php echo $farm['nome_plano']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $farm['status'] == 'ativo' ? 'success' : 
                                                    ($farm['status'] == 'trial' ? 'info' : 
                                                    ($farm['status'] == 'inadimplente' ? 'warning' : 'secondary')); 
                                            ?>">
                                                <?php echo $farm['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($farm['data_cadastro'])); ?></td>
                                        <td>
                                            <a href="tenants.php?view=<?php echo $farm['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
<?php
// Preparar dados para o gráfico de planos
$planosLabels = [];
$planosData = [];
if ($planos && $planos->num_rows > 0) {
    while ($p = $planos->fetch_assoc()) {
        $planosLabels[] = $p['nome_plano'];
        $planosData[] = $p['total_fazendas'];
    }
}
?>

// Gráfico de Planos
const ctxPlanos = document.getElementById('graficoPlanos').getContext('2d');
new Chart(ctxPlanos, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($planosLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($planosData); ?>,
            backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6c757d']
        }]
    }
});

// Gráfico de Status
const ctxStatus = document.getElementById('graficoStatus').getContext('2d');
new Chart(ctxStatus, {
    type: 'bar',
    data: {
        labels: ['Trial', 'Ativo', 'Inadimplente', 'Cancelado'],
        datasets: [{
            data: [
                <?php echo $stats['fazendas']['trial']; ?>,
                <?php echo $stats['fazendas']['ativo']; ?>,
                <?php echo $stats['fazendas']['inadimplente']; ?>,
                <?php echo $stats['fazendas']['cancelado']; ?>
            ],
            backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#6c757d']
        }]
    },
    options: {
        plugins: {
            legend: { display: false }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>