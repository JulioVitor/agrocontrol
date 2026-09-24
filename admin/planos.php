<?php
// admin/planos.php
// Gerenciamento de planos - Versão admin

// CORREÇÃO DOS CAMINHOS!
require_once '../config/database.php';        // ← Apenas um nível
require_once '../config/constants.php';      // ← Apenas um nível
require_once '../includes/functions.php';    // ← Apenas um nível
require_once 'auth.php';                      // ← Mesma pasta

$superAdmin->requireLogin();
$admin = $superAdmin->getAdmin();

$pageTitle = 'Gerenciar Planos';
$error = '';
$success = '';

// Processar ações
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM planos WHERE id = $id";
    if (executeQuery($sql)) {
        setAlert('Plano excluído com sucesso!', 'success');
    } else {
        setAlert('Erro ao excluir plano.', 'danger');
    }
    redirect('planos.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $nome_plano = escapeString($_POST['nome_plano']);
        $descricao = escapeString($_POST['descricao']);
        $preco_mensal = floatval($_POST['preco_mensal']);
        $max_animais = !empty($_POST['max_animais']) ? intval($_POST['max_animais']) : 'NULL';
        $max_usuarios = !empty($_POST['max_usuarios']) ? intval($_POST['max_usuarios']) : 'NULL';
        $max_propriedades = !empty($_POST['max_propriedades']) ? intval($_POST['max_propriedades']) : 1;
        $destaque = isset($_POST['destaque']) ? 1 : 0;
        $ordem = intval($_POST['ordem']);
        
        $sql = "INSERT INTO planos (nome_plano, descricao, preco_mensal, max_animais, max_usuarios, max_propriedades, destaque, ordem) 
                VALUES ('$nome_plano', '$descricao', $preco_mensal, $max_animais, $max_usuarios, $max_propriedades, $destaque, $ordem)";
        
        if (executeQuery($sql)) {
            $plano_id = getLastInsertId();
            
            // Vincular módulos selecionados
            if (isset($_POST['modulos'])) {
                foreach ($_POST['modulos'] as $modulo_id) {
                    $modulo_id = intval($modulo_id);
                    $sqlMod = "INSERT INTO plano_modulos (id_plano, id_modulo) VALUES ($plano_id, $modulo_id)";
                    executeQuery($sqlMod);
                }
            }
            
            setAlert('Plano criado com sucesso!', 'success');
        } else {
            setAlert('Erro ao criar plano.', 'danger');
        }
        redirect('planos.php');
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $nome_plano = escapeString($_POST['nome_plano']);
        $descricao = escapeString($_POST['descricao']);
        $preco_mensal = floatval($_POST['preco_mensal']);
        $max_animais = !empty($_POST['max_animais']) ? intval($_POST['max_animais']) : 'NULL';
        $max_usuarios = !empty($_POST['max_usuarios']) ? intval($_POST['max_usuarios']) : 'NULL';
        $max_propriedades = !empty($_POST['max_propriedades']) ? intval($_POST['max_propriedades']) : 1;
        $destaque = isset($_POST['destaque']) ? 1 : 0;
        $ordem = intval($_POST['ordem']);
        
        $sql = "UPDATE planos SET 
                nome_plano = '$nome_plano',
                descricao = '$descricao',
                preco_mensal = $preco_mensal,
                max_animais = $max_animais,
                max_usuarios = $max_usuarios,
                max_propriedades = $max_propriedades,
                destaque = $destaque,
                ordem = $ordem
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            // Remover módulos antigos
            $sqlDel = "DELETE FROM plano_modulos WHERE id_plano = $id";
            executeQuery($sqlDel);
            
            // Adicionar novos módulos
            if (isset($_POST['modulos'])) {
                foreach ($_POST['modulos'] as $modulo_id) {
                    $modulo_id = intval($modulo_id);
                    $sqlMod = "INSERT INTO plano_modulos (id_plano, id_modulo) VALUES ($id, $modulo_id)";
                    executeQuery($sqlMod);
                }
            }
            
            setAlert('Plano atualizado com sucesso!', 'success');
        } else {
            setAlert('Erro ao atualizar plano.', 'danger');
        }
        redirect('planos.php');
    }
}

// Buscar planos
$sql = "SELECT * FROM planos ORDER BY ordem";
$planos = executeQuery($sql);

// Buscar módulos
$sqlModulos = "SELECT * FROM modulos ORDER BY ordem";
$modulos = executeQuery($sqlModulos);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AgroControl Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(180deg, #0f3460 0%, #1a1a2e 100%);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header i {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .sidebar-header h4 {
            margin: 0;
            font-size: 18px;
        }
        .sidebar-header small {
            opacity: 0.7;
            font-size: 12px;
        }
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #00d4ff;
        }
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #00d4ff;
        }
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .navbar-top {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Sidebar Admin -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-shield-lock-fill"></i>
            <h4>Super Admin</h4>
            <small><?php echo $admin['nome']; ?></small>
        </div>
        
        <div class="sidebar-menu mt-3">
            <a href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="tenants.php">
                <i class="bi bi-building"></i> Fazendas
            </a>
            <a href="users.php">
                <i class="bi bi-people"></i> Usuários
            </a>
            <a href="planos.php" class="active">
                <i class="bi bi-tags"></i> Planos
            </a>
            <a href="system.php">
                <i class="bi bi-gear"></i> Sistema
            </a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
            <a href="logout.php">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="content">
        <div class="navbar-top">
            <h5><?php echo $pageTitle; ?></h5>
            <div class="d-flex align-items-center">
                <span class="me-3">Olá, <strong><?php echo $admin['nome']; ?></strong></span>
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($admin['nome'], 0, 1)); ?>
                </div>
            </div>
        </div>
        
        <!-- Botão Novo Plano -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novoPlanoModal">
                <i class="bi bi-plus-circle"></i> Novo Plano
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Lista de Planos -->
        <div class="row">
            <?php if ($planos && $planos->num_rows > 0): ?>
                <?php while ($plano = $planos->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm <?php echo $plano['destaque'] ? 'border-warning border-2' : ''; ?>">
                        <?php if ($plano['destaque']): ?>
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-warning">Mais vendido</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-header bg-<?php echo $plano['destaque'] ? 'warning' : 'success'; ?> text-white">
                            <h5 class="card-title mb-0"><?php echo $plano['nome_plano']; ?></h5>
                        </div>
                        
                        <div class="card-body">
                            <h3 class="text-center mb-3">
                                R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?>
                                <small class="text-muted">/mês</small>
                            </h3>
                            
                            <p class="text-muted"><?php echo $plano['descricao']; ?></p>
                            
                            <hr>
                            
                            <h6>Limites:</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i> 
                                    Animais: <?php echo $plano['max_animais'] ? 'Até ' . $plano['max_animais'] : 'Ilimitado'; ?>
                                </li>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i> 
                                    Usuários: <?php echo $plano['max_usuarios'] ? 'Até ' . $plano['max_usuarios'] : 'Ilimitado'; ?>
                                </li>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i> 
                                    Propriedades: <?php echo $plano['max_propriedades'] ? 'Até ' . $plano['max_propriedades'] : 'Ilimitado'; ?>
                                </li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Módulos inclusos:</h6>
                            <?php
                            $sqlModPlano = "SELECT m.* FROM modulos m
                                            JOIN plano_modulos pm ON m.id = pm.id_modulo
                                            WHERE pm.id_plano = " . $plano['id'];
                            $modPlano = executeQuery($sqlModPlano);
                            ?>
                            <ul class="list-unstyled">
                                <?php if ($modPlano && $modPlano->num_rows > 0): ?>
                                    <?php while ($mod = $modPlano->fetch_assoc()): ?>
                                    <li><i class="bi bi-check text-success me-2"></i> <?php echo $mod['nome_modulo']; ?></li>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="card-footer bg-white">
                            <button class="btn btn-sm btn-warning" onclick="editarPlano(<?php echo htmlspecialchars(json_encode($plano)); ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <a href="?delete=<?php echo $plano['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Excluir plano?')">
                                <i class="bi bi-trash"></i> Excluir
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Novo Plano -->
    <div class="modal fade" id="novoPlanoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Novo Plano</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Plano</label>
                                <input type="text" class="form-control" name="nome_plano" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preço Mensal (R$)</label>
                                <input type="number" step="0.01" class="form-control" name="preco_mensal" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição</label>
                                <input type="text" class="form-control" name="descricao" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. Animais</label>
                                <input type="number" class="form-control" name="max_animais" placeholder="Deixe em branco para ilimitado">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. Usuários</label>
                                <input type="number" class="form-control" name="max_usuarios" placeholder="Deixe em branco para ilimitado">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. Propriedades</label>
                                <input type="number" class="form-control" name="max_propriedades" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ordem</label>
                                <input type="number" class="form-control" name="ordem" value="1">
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="destaque" id="destaque">
                                    <label class="form-check-label" for="destaque">
                                        Plano em destaque
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <h6>Módulos Disponíveis</h6>
                                <div class="row">
                                    <?php if ($modulos && $modulos->num_rows > 0): ?>
                                        <?php $modulos->data_seek(0); ?>
                                        <?php while ($mod = $modulos->fetch_assoc()): ?>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="modulos[]" 
                                                       value="<?php echo $mod['id']; ?>" id="mod_<?php echo $mod['id']; ?>">
                                                <label class="form-check-label" for="mod_<?php echo $mod['id']; ?>">
                                                    <i class="bi <?php echo $mod['icone']; ?>"></i>
                                                    <?php echo $mod['nome_modulo']; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Plano</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Plano -->
    <div class="modal fade" id="editarPlanoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Editar Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Plano</label>
                                <input type="text" class="form-control" name="nome_plano" id="edit_nome" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preço Mensal (R$)</label>
                                <input type="number" step="0.01" class="form-control" name="preco_mensal" id="edit_preco" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição</label>
                                <input type="text" class="form-control" name="descricao" id="edit_descricao" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. Animais</label>
                                <input type="number" class="form-control" name="max_animais" id="edit_max_animais" placeholder="Deixe em branco para ilimitado">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. Usuários</label>
                                <input type="number" class="form-control" name="max_usuarios" id="edit_max_usuarios" placeholder="Deixe em branco para ilimitado">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. Propriedades</label>
                                <input type="number" class="form-control" name="max_propriedades" id="edit_max_propriedades" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ordem</label>
                                <input type="number" class="form-control" name="ordem" id="edit_ordem" value="1">
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="destaque" id="edit_destaque">
                                    <label class="form-check-label" for="edit_destaque">
                                        Plano em destaque
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <h6>Módulos Disponíveis</h6>
                                <div class="row" id="modulos_container">
                                    <?php if ($modulos && $modulos->num_rows > 0): ?>
                                        <?php $modulos->data_seek(0); ?>
                                        <?php while ($mod = $modulos->fetch_assoc()): ?>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input modulo-check" type="checkbox" name="modulos[]" 
                                                       value="<?php echo $mod['id']; ?>" id="edit_mod_<?php echo $mod['id']; ?>">
                                                <label class="form-check-label" for="edit_mod_<?php echo $mod['id']; ?>">
                                                    <i class="bi <?php echo $mod['icone']; ?>"></i>
                                                    <?php echo $mod['nome_modulo']; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarPlano(plano) {
    document.getElementById('edit_id').value = plano.id;
    document.getElementById('edit_nome').value = plano.nome_plano;
    document.getElementById('edit_descricao').value = plano.descricao;
    document.getElementById('edit_preco').value = plano.preco_mensal;
    document.getElementById('edit_max_animais').value = plano.max_animais || '';
    document.getElementById('edit_max_usuarios').value = plano.max_usuarios || '';
    document.getElementById('edit_max_propriedades').value = plano.max_propriedades || 1;
    document.getElementById('edit_ordem').value = plano.ordem;
    document.getElementById('edit_destaque').checked = plano.destaque == 1;
    
    // Buscar módulos do plano
    fetch('get_plan_modulos.php?plano_id=' + plano.id)
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.modulo-check').forEach(cb => {
                cb.checked = data.includes(parseInt(cb.value));
            });
        });
    
    new bootstrap.Modal(document.getElementById('editarPlanoModal')).show();
}
</script>