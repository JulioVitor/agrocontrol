<?php
// modules/fazendas/selector.php
// Página para selecionar uma fazenda quando o usuário tem múltiplas

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Selecionar Fazenda';

// Buscar todas as fazendas do usuário
$sql = "SELECT f.*, 
               (SELECT COUNT(*) FROM bovinos WHERE id_fazenda = f.id AND ativo = 1) as total_animais,
               ufp.papel
        FROM fazendas f
        INNER JOIN fazenda_usuarios ufp ON f.id = ufp.id_fazenda
        WHERE ufp.id_usuario = $userId AND ufp.ativo = 1
        ORDER BY f.nome_fazenda";

$fazendas = executeQuery($sql);

// Se não tem fazendas, redireciona para cadastro
if (!$fazendas || $fazendas->num_rows == 0) {
    redirect('cadastrar.php');
}

// Se tem apenas uma, seleciona automaticamente
if ($fazendas->num_rows == 1) {
    $fazenda = $fazendas->fetch_assoc();
    TenantManager::setActiveFarm($fazenda['id']);
    redirect(BASE_URL . 'modules/dashboard/index.php');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Fazenda - AgroControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .selector-container {
            max-width: 800px;
            width: 100%;
        }
        
        .farm-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .farm-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-color: #28a745;
        }
        
        .farm-card.selected {
            border-color: #28a745;
            background: #f0fff0;
        }
        
        .btn-new-farm {
            background: white;
            color: #28a745;
            border: 2px dashed #28a745;
            padding: 15px;
            border-radius: 15px;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-new-farm:hover {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <div class="text-center text-white mb-4">
            <i class="bi bi-tree-fill display-1"></i>
            <h1 class="h2">AgroControl</h1>
            <p>Selecione uma fazenda para continuar</p>
        </div>
        
        <div class="bg-white rounded-4 p-4 shadow">
            <h5 class="mb-3">Suas Fazendas</h5>
            
            <?php 
            $fazendas->data_seek(0);
            while ($fazenda = $fazendas->fetch_assoc()): 
            ?>
            <div class="farm-card" onclick="selectFarm(<?php echo $fazenda['id']; ?>)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1">
                            <i class="bi bi-tree-fill text-success me-2"></i>
                            <?php echo $fazenda['nome_fazenda']; ?>
                        </h5>
                        <p class="text-muted mb-2">
                            <i class="bi bi-geo-alt me-1"></i>
                            <?php echo $fazenda['cidade'] ? $fazenda['cidade'] . ' - ' . $fazenda['estado'] : 'Local não informado'; ?>
                        </p>
                    </div>
                    <span class="badge bg-<?php 
                        echo $fazenda['papel'] == 'proprietario' ? 'danger' : 
                            ($fazenda['papel'] == 'gerente' ? 'warning' : 'info'); 
                    ?>">
                        <?php echo $fazenda['papel']; ?>
                    </span>
                </div>
                
                <div class="row mt-3 text-center">
                    <div class="col-4">
                        <small class="text-muted">Animais</small>
                        <br>
                        <strong><?php echo $fazenda['total_animais']; ?></strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Área</small>
                        <br>
                        <strong><?php echo $fazenda['area_total_hectares'] ?: '0'; ?> ha</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Status</small>
                        <br>
                        <span class="badge bg-<?php echo $fazenda['status'] == 'ativo' ? 'success' : 'warning'; ?>">
                            <?php echo $fazenda['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
            <a href="cadastrar.php" class="btn-new-farm mt-3">
                <i class="bi bi-plus-circle me-2"></i>
                Cadastrar Nova Fazenda
            </a>
        </div>
    </div>
    
    <script>
    function selectFarm(farmId) {
        // Mostrar loading
        document.body.style.cursor = 'wait';
        
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
                alert('Erro: ' + data.message);
                document.body.style.cursor = 'default';
            }
        })
        .catch(error => {
            alert('Erro na comunicação com o servidor');
            document.body.style.cursor = 'default';
        });
    }
    </script>
</body>
</html>