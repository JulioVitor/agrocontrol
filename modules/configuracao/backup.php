<?php
// modules/configuracao/backup.php
// Backup e restauração do banco de dados

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se é admin
$userId = $_SESSION['usuario_id'];
$sqlCheck = "SELECT nivel FROM usuarios WHERE id = $userId";
$resultCheck = executeQuery($sqlCheck);
$user = $resultCheck->fetch_assoc();

if ($user['nivel'] != 'admin') {
    setAlert('Apenas administradores podem acessar esta página.', 'danger');
    redirect('index.php');
}

$pageTitle = 'Backup do Sistema';
$message = '';
$error = '';

// Criar diretório de backup se não existir
$backupDir = ROOT_PATH . 'backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Listar backups existentes
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql' || pathinfo($file, PATHINFO_EXTENSION) == 'gz') {
            $filePath = $backupDir . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'date' => filemtime($filePath),
                'type' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        }
    }
    // Ordenar por data (mais recente primeiro)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Processar ações
if (isset($_GET['action'])) {
    
    if ($_GET['action'] == 'backup') {
        // Gerar nome do arquivo
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . $filename;
        
        // Comando mysqldump
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_NAME),
            escapeshellarg($filepath)
        );
        
        system($command, $output);
        
        if ($output === 0) {
            // Comprimir arquivo
            $gzipCommand = "gzip $filepath";
            system($gzipCommand);
            
            $message = 'Backup realizado com sucesso!';
        } else {
            $error = 'Erro ao realizar backup.';
        }
        
        redirect('backup.php');
    }
    
    if ($_GET['action'] == 'download' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $filepath = $backupDir . $file;
        
        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    }
    
    if ($_GET['action'] == 'delete' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $filepath = $backupDir . $file;
        
        if (file_exists($filepath) && unlink($filepath)) {
            $message = 'Arquivo excluído com sucesso!';
        } else {
            $error = 'Erro ao excluir arquivo.';
        }
        
        redirect('backup.php');
    }
    
    if ($_GET['action'] == 'restore' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $filepath = $backupDir . $file;
        
        if (file_exists($filepath)) {
            
            // Descomprimir se for .gz
            if (pathinfo($file, PATHINFO_EXTENSION) == 'gz') {
                $gzFile = $filepath;
                $sqlFile = str_replace('.gz', '', $filepath);
                
                $gunzipCommand = "gunzip -c $gzFile > $sqlFile";
                system($gunzipCommand);
                $filepath = $sqlFile;
            }
            
            // Restaurar backup
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s %s < %s',
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );
            
            system($command, $output);
            
            // Remover arquivo SQL temporário se foi descomprimido
            if (pathinfo($file, PATHINFO_EXTENSION) == 'gz' && file_exists($filepath)) {
                unlink($filepath);
            }
            
            if ($output === 0) {
                $message = 'Backup restaurado com sucesso!';
            } else {
                $error = 'Erro ao restaurar backup.';
            }
        } else {
            $error = 'Arquivo não encontrado.';
        }
        
        redirect('backup.php');
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
                <i class="bi bi-database me-2 text-success"></i>
                Backup do Sistema
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Configurações</a></li>
                    <li class="breadcrumb-item active">Backup</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Card de Backup -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">Realizar Backup</h6>
                </div>
                <div class="card-body text-center">
                    <i class="bi bi-cloud-arrow-down display-1 text-success"></i>
                    <h5 class="mt-3">Backup do Banco de Dados</h5>
                    <p class="text-muted">Crie uma cópia de segurança completa do sistema.</p>
                    <a href="?action=backup" class="btn btn-success btn-lg">
                        <i class="bi bi-download"></i> Gerar Backup Agora
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">Informações</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong>Banco de Dados:</strong> <?php echo DB_NAME; ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong>Último Backup:</strong> 
                            <?php 
                            if (!empty($backups)) {
                                echo date('d/m/Y H:i:s', $backups[0]['date']);
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong>Total de Backups:</strong> <?php echo count($backups); ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong>Pasta de Backup:</strong> <?php echo $backupDir; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Backups -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Backups Disponíveis</h6>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($backups)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Arquivo</th>
                            <th>Tamanho</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th width="200">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><strong><?php echo $backup['name']; ?></strong></td>
                            <td><?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo date('d/m/Y H:i:s', $backup['date']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $backup['type'] == 'sql' ? 'primary' : 'info'; ?>">
                                    <?php echo strtoupper($backup['type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?action=download&file=<?php echo urlencode($backup['name']); ?>" 
                                   class="btn btn-sm btn-success" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                <a href="?action=restore&file=<?php echo urlencode($backup['name']); ?>" 
                                   class="btn btn-sm btn-warning" title="Restaurar"
                                   onclick="return confirm('Restaurar este backup? Todos os dados atuais serão substituídos.')">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                                <a href="?action=delete&file=<?php echo urlencode($backup['name']); ?>" 
                                   class="btn btn-sm btn-danger" title="Excluir"
                                   onclick="return confirm('Excluir este arquivo?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-database display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum backup encontrado</h4>
                <p class="text-muted">Clique em "Gerar Backup" para criar o primeiro.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>