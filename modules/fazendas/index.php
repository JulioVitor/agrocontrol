<?php
// modules/fazendas/index.php
// Listagem de fazendas do usuário

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Minhas Fazendas';

// Buscar todas as fazendas do usuário
$sql = "SELECT f.*, 
               (SELECT COUNT(*) FROM bovinos WHERE id_fazenda = f.id AND ativo = 1) as total_animais,
               (SELECT COUNT(*) FROM piquetes WHERE id_fazenda = f.id) as total_piquetes,
               ufp.papel
        FROM fazendas f
        INNER JOIN fazenda_usuarios ufp ON f.id = ufp.id_fazenda
        WHERE ufp.id_usuario = $userId AND ufp.ativo = 1
        ORDER BY f.nome_fazenda";

$fazendas = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="bi bi-building me-2 text-success"></i>
            Minhas Fazendas
        </h1>
        <a href="cadastrar.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Fazenda
        </a>
    </div>

    <?php if ($fazendas && $fazendas->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($fazenda = $fazendas->fetch_assoc()): 
                $isActive = ($fazenda['id'] == getActiveFarmId());
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm <?php echo $isActive ? 'border-success border-2' : ''; ?>">
                    <?php if ($isActive): ?>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle"></i> Ativa
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-tree-fill fs-3 text-success"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-1"><?php echo $fazenda['nome_fazenda']; ?></h5>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?php echo $fazenda['cidade'] ?: 'Cidade não informada'; ?>
                                    <?php echo $fazenda['estado'] ? '- ' . $fazenda['estado'] : ''; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="small text-muted">Animais</div>
                                <strong><?php echo $fazenda['total_animais']; ?></strong>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Piquetes</div>
                                <strong><?php echo $fazenda['total_piquetes']; ?></strong>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Área</div>
                                <strong><?php echo $fazenda['area_total_hectares'] ?: '0'; ?> ha</strong>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <?php if (!$isActive): ?>
                            <button onclick="setActiveFarm(<?php echo $fazenda['id']; ?>)" class="btn btn-success btn-sm flex-grow-1">
                                <i class="bi bi-check-circle me-1"></i>
                                Selecionar
                            </button>
                            <?php endif; ?>
                            
                            <a href="visualizar.php?id=<?php echo $fazenda['id']; ?>" class="btn btn-outline-info btn-sm" title="Detalhes">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="editar.php?id=<?php echo $fazenda['id']; ?>" class="btn btn-outline-warning btn-sm" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                        
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-person-badge me-1"></i>
                            Seu papel: <span class="badge bg-<?php 
                                echo $fazenda['papel'] == 'proprietario' ? 'danger' : 
                                    ($fazenda['papel'] == 'gerente' ? 'warning' : 'info'); 
                            ?>"><?php echo $fazenda['papel']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-building display-1 text-muted"></i>
            <h4 class="mt-3">Você ainda não tem nenhuma fazenda cadastrada</h4>
            <p class="text-muted">Comece cadastrando sua primeira propriedade rural.</p>
            <a href="cadastrar.php" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle me-2"></i>
                Cadastrar Primeira Fazenda
            </a>
        </div>
    <?php endif; ?>
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
            location.reload();
        } else {
            alert('Erro ao selecionar fazenda');
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>