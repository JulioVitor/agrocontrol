<?php
// includes/farm_selector.php
// Seletor de fazenda para aparecer no topo do sistema

$userFarms = TenantManager::getUserFarms();
$activeFarmId = TenantManager::getActiveFarmId();
$activeFarmName = TenantManager::getActiveFarmName();
?>

<div class="dropdown d-inline-block">
    <button class="btn btn-light dropdown-toggle" type="button" id="farmSelector" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 250px;">
        <i class="bi bi-house-door-fill me-1 text-success"></i>
        <span class="fw-bold"><?php echo $activeFarmName; ?></span>
    </button>
    <ul class="dropdown-menu" aria-labelledby="farmSelector" style="min-width: 300px;">
        <?php if (count($userFarms) > 0): ?>
            <li>
                <h6 class="dropdown-header">
                    <i class="bi bi-building me-2"></i>
                    Suas Fazendas
                </h6>
            </li>
            <?php foreach ($userFarms as $farm): ?>
            <li>
                <a class="dropdown-item <?php echo ($activeFarmId == $farm['id']) ? 'active' : ''; ?>" 
                   href="#" 
                   onclick="changeFarm(<?php echo $farm['id']; ?>)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-tree-fill me-2 <?php echo ($activeFarmId == $farm['id']) ? '' : 'text-success'; ?>"></i>
                            <strong><?php echo $farm['nome_fazenda']; ?></strong>
                        </div>
                        <span class="badge bg-<?php 
                            echo $farm['papel'] == 'proprietario' ? 'danger' : 
                                ($farm['papel'] == 'gerente' ? 'warning' : 'info'); 
                        ?>">
                            <?php echo $farm['papel']; ?>
                        </span>
                    </div>
                    <small class="text-muted d-block ps-4">
                        <?php echo $farm['cidade'] ? $farm['cidade'] . ' - ' : ''; ?>
                        <?php echo $farm['estado'] ?? ''; ?>
                    </small>
                </a>
            </li>
            <?php endforeach; ?>
            
            <li><hr class="dropdown-divider"></li>
        <?php endif; ?>
        
        <li>
            <a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/fazendas/cadastrar.php">
                <i class="bi bi-plus-circle me-2 text-success"></i>
                Cadastrar Nova Fazenda
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/fazendas/index.php">
                <i class="bi bi-gear me-2 text-secondary"></i>
                Gerenciar Fazendas
            </a>
        </li>
    </ul>
</div>

<script>
function changeFarm(farmId) {
    // Mostrar loading
    $('#farmSelector').html('<span class="spinner-border spinner-border-sm me-2"></span> Carregando...');
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>modules/fazendas/ajax_change_farm.php',
        type: 'POST',
        data: { farm_id: farmId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao mudar de fazenda: ' + response.message);
                location.reload(); // Recarrega mesmo assim
            }
        },
        error: function() {
            alert('Erro na comunicação com o servidor');
            location.reload();
        }
    });
}
</script>