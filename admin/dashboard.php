<?php
// admin/dashboard.php
require_once 'auth.php';
$superAdmin->requireLogin();

$admin = $superAdmin->getAdmin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white vh-100 p-0">
                <div class="p-3 text-center border-bottom">
                    <i class="bi bi-shield-lock-fill fs-1"></i>
                    <h5>Super Admin</h5>
                    <small><?php echo $admin['nome']; ?></small>
                </div>
                <ul class="nav flex-column mt-3">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link text-white active">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="tenants.php" class="nav-link text-white">
                            <i class="bi bi-building me-2"></i> Fazendas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link text-white">
                            <i class="bi bi-people me-2"></i> Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="planos.php" class="nav-link text-white">
                            <i class="bi bi-tags me-2"></i> Planos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="system.php" class="nav-link text-white">
                            <i class="bi bi-gear me-2"></i> Sistema
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a href="logout.php" class="nav-link text-white">
                            <i class="bi bi-box-arrow-right me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Conteúdo -->
            <div class="col-md-10 p-4">
                <h2>Dashboard</h2>
                <p>Bem-vindo ao painel administrativo, <strong><?php echo $admin['nome']; ?></strong>!</p>
                
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Total de Fazendas</h6>
                                <?php
                                require_once '../config/database.php';
                                $sql = "SELECT COUNT(*) as total FROM fazendas";
                                $result = executeQuery($sql);
                                $total = $result->fetch_assoc()['total'];
                                ?>
                                <h2><?php echo $total; ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Total de Usuários</h6>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM usuarios";
                                $result = executeQuery($sql);
                                $total = $result->fetch_assoc()['total'];
                                ?>
                                <h2><?php echo $total; ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Total de Animais</h6>
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM bovinos";
                                $result = executeQuery($sql);
                                $total = $result->fetch_assoc()['total'];
                                ?>
                                <h2><?php echo number_format($total, 0, ',', '.'); ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6>Receita Mensal</h6>
                                <?php
                                $sql = "SELECT COALESCE(SUM(preco_mensal), 0) as total 
                                        FROM fazendas f 
                                        JOIN planos p ON f.id_plano = p.id 
                                        WHERE f.status IN ('ativo', 'trial')";
                                $result = executeQuery($sql);
                                $total = $result->fetch_assoc()['total'];
                                ?>
                                <h2>R$ <?php echo number_format($total, 2, ',', '.'); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>