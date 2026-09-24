<?php
// modules/reproducao/partos.php
// Lista de partos registrados

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

$pageTitle = 'Partos';

// Buscar partos
$sql = "SELECT r.*, 
               b.brinco, 
               b.nome,
               (SELECT COUNT(*) FROM bovinos WHERE id_mae = b.id) as total_crias
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND r.tipo_evento = 'parto'
        ORDER BY r.data_evento DESC";
$partos = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-egg me-2 text-success"></i>
                Registro de Partos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Partos</li>
                </ol>
            </nav>
        </div>
        <a href="novo_parto.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>
            Novo Parto
        </a>
    </div>

    <!-- Lista de partos -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Partos Registrados</h6>
        </div>
        <div class="card-body p-0">
            <?php if ($partos && $partos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Matriz</th>
                            <th>Crias</th>
                            <th>Vivas/Mortas</th>
                            <th>Dificuldade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($parto = $partos->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo date('d/m/Y', strtotime($parto['data_evento'])); ?></strong></td>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $parto['id_bovino_femea']; ?>">
                                    <strong><?php echo $parto['brinco']; ?></strong>
                                    <?php if ($parto['nome']): ?>
                                        <br><small><?php echo $parto['nome']; ?></small>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td><?php echo $parto['crias_nascidas']; ?></td>
                            <td>
                                <span class="text-success"><?php echo $parto['crias_vivas']; ?> vivas</span> / 
                                <span class="text-danger"><?php echo $parto['crias_mortas']; ?> mortas</span>
                            </td>
                            <td>
                                <?php 
                                $dificuldade = [
                                    'normal' => '<span class="badge bg-success">Normal</span>',
                                    'dificil' => '<span class="badge bg-warning">Difícil</span>',
                                    'cesariana' => '<span class="badge bg-danger">Cesariana</span>'
                                ];
                                echo $dificuldade[$parto['dificuldade_parto']] ?? '-';
                                ?>
                            </td>
                            <td>
                                <a href="../bovinos/index.php?mae=<?php echo $parto['id_bovino_femea']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Ver Crias
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-egg display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum parto registrado</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>