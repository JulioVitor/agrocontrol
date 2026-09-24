<?php
// modules/admin/system.php
// Configurações gerais do sistema

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once 'auth.php'; 

// Verificar se é admin
$admin_emails = ['admin@agrocontrol.com', 'seu@email.com'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_email'], $admin_emails)) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$pageTitle = 'Configurações do Sistema';
$error = '';
$success = '';

// Informações do sistema
$php_version = phpversion();
$mysql_version = $conn->server_info;
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido';
$document_root = $_SERVER['DOCUMENT_ROOT'];

// Estatísticas de uso
$stats = [];

// Espaço em disco (se disponível)
$upload_dir = ROOT_PATH . 'uploads/';
if (is_dir($upload_dir)) {
    $stats['disk_free'] = disk_free_space($upload_dir);
    $stats['disk_total'] = disk_total_space($upload_dir);
    $stats['disk_used'] = $stats['disk_total'] - $stats['disk_free'];
    $stats['disk_percent'] = ($stats['disk_used'] / $stats['disk_total']) * 100;
}

// Tamanho do banco de dados
$sql = "SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as tamanho_mb
        FROM information_schema.tables
        WHERE table_schema = '" . DB_NAME . "'";
$result = executeQuery($sql);
$stats['db_size'] = $result->fetch_assoc()['tamanho_mb'] ?? 0;

// Total de registros
$tabelas = ['bovinos', 'usuarios', 'fazendas', 'producao_leite', 'pesagens', 'aplicacoes_vacinas'];
foreach ($tabelas as $tabela) {
    $sql = "SELECT COUNT(*) as total FROM $tabela";
    $result = executeQuery($sql);
    $stats['registros_' . $tabela] = $result->fetch_assoc()['total'];
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_cache'])) {
        // Limpar cache de sessão (exemplo)
        $sql = "DELETE FROM logs WHERE data < DATE_SUB(NOW(), INTERVAL 90 DAY)";
        executeQuery($sql);
        $success = 'Cache limpo com sucesso!';
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Admin -->
        <nav class="col-md-2 d-md-block bg-light sidebar" style="min-height: 100vh;">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
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
                        <a class="nav-link active" href="system.php">
                            <i class="bi bi-gear me-2"></i>
                            Sistema
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Configurações do Sistema</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Informações do Sistema -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-info-circle"></i> Informações do Servidor
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th>PHP Version:</th>
                                    <td><?php echo $php_version; ?></td>
                                </tr>
                                <tr>
                                    <th>MySQL Version:</th>
                                    <td><?php echo $mysql_version; ?></td>
                                </tr>
                                <tr>
                                    <th>Servidor:</th>
                                    <td><?php echo $server_software; ?></td>
                                </tr>
                                <tr>
                                    <th>Document Root:</th>
                                    <td><small><?php echo $document_root; ?></small></td>
                                </tr>
                                <tr>
                                    <th>Banco de Dados:</th>
                                    <td><?php echo DB_NAME; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-hdd"></i> Espaço em Disco
                        </div>
                        <div class="card-body">
                            <?php if (isset($stats['disk_total'])): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Total:</span>
                                        <strong><?php echo round($stats['disk_total'] / 1024 / 1024 / 1024, 2); ?> GB</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Usado:</span>
                                        <strong><?php echo round($stats['disk_used'] / 1024 / 1024 / 1024, 2); ?> GB</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Livre:</span>
                                        <strong><?php echo round($stats['disk_free'] / 1024 / 1024 / 1024, 2); ?> GB</strong>
                                    </div>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-<?php echo $stats['disk_percent'] > 80 ? 'danger' : 'success'; ?>" 
                                         style="width: <?php echo $stats['disk_percent']; ?>%">
                                        <?php echo round($stats['disk_percent'], 1); ?>%
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Não foi possível verificar espaço em disco.</p>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <span>Tamanho do Banco:</span>
                                <strong><?php echo $stats['db_size']; ?> MB</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas de Registros -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-database"></i> Estatísticas de Registros
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>Bovinos</h5>
                                    <h3><?php echo number_format($stats['registros_bovinos'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>Usuários</h5>
                                    <h3><?php echo number_format($stats['registros_usuarios'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>Fazendas</h5>
                                    <h3><?php echo number_format($stats['registros_fazendas'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>Produção Leite</h5>
                                    <h3><?php echo number_format($stats['registros_producao_leite'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ações do Sistema -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <i class="bi bi-tools"></i> Manutenção
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <button type="submit" name="clear_cache" class="btn btn-warning mb-2 w-100">
                                    <i class="bi bi-eraser"></i> Limpar Logs Antigos (90+ dias)
                                </button>
                            </form>
                            <a href="../configuracao/backup.php" class="btn btn-success w-100">
                                <i class="bi bi-database"></i> Gerenciar Backups
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <i class="bi bi-exclamation-triangle"></i> Zona de Risco
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Cuidado! Essas ações são irreversíveis.</p>
                            <button class="btn btn-outline-danger w-100 mb-2" onclick="return confirm('Isso é apenas um aviso. Não há ação real aqui.')">
                                <i class="bi bi-database"></i> Otimizar Banco de Dados
                            </button>
                            <button class="btn btn-outline-danger w-100" onclick="return confirm('Isso é apenas um aviso. Não há ação real aqui.')">
                                <i class="bi bi-arrow-repeat"></i> Reindexar Tabelas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>