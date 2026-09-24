<?php
// modules/reproducao/gestacoes.php
// Acompanhamento de gestações

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

$pageTitle = 'Gestações';

// Buscar gestações ativas
$sql = "SELECT r.*, 
               b.brinco, 
               b.nome,
               DATEDIFF(r.data_prevista_parto, CURDATE()) as dias_restantes,
               TIMESTAMPDIFF(DAY, r.data_evento, CURDATE()) as dias_gestacao
        FROM reproducao r
        JOIN bovinos b ON r.id_bovino_femea = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND r.tipo_evento = 'prenhez'
        AND r.confirmada = 1
        AND (r.data_prevista_parto >= CURDATE() OR r.data_prevista_parto IS NULL)
        ORDER BY r.data_prevista_parto ASC";
$gestacoes = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-heart-fill me-2 text-success"></i>
                Acompanhamento de Gestações
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Gestações</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Cards de resumo -->
    <?php
    $totalGestacoes = $gestacoes ? $gestacoes->num_rows : 0;
    $proximosPartos = 0;
    $atrasados = 0;
    
    if ($gestacoes && $gestacoes->num_rows > 0) {
        $gestacoes->data_seek(0);
        while ($g = $gestacoes->fetch_assoc()) {
            if ($g['dias_restantes'] <= 15 && $g['dias_restantes'] > 0) {
                $proximosPartos++;
            } elseif ($g['dias_restantes'] < 0) {
                $atrasados++;
            }
        }
        $gestacoes->data_seek(0);
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Gestações</h6>
                    <h2><?php echo $totalGestacoes; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Partos Próximos (15 dias)</h6>
                    <h2><?php echo $proximosPartos; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Atrasados</h6>
                    <h2><?php echo $atrasados; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de gestações -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">Gestações Ativas</h6>
        </div>
        <div class="card-body p-0">
            <?php if ($gestacoes && $gestacoes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Matriz</th>
                            <th>Data da IA</th>
                            <th>Dias de Gestação</th>
                            <th>Data Prevista</th>
                            <th>Dias Restantes</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($gest = $gestacoes->fetch_assoc()): 
                            $classeStatus = 'success';
                            $statusTexto = 'Normal';
                            
                            if ($gest['dias_restantes'] < 0) {
                                $classeStatus = 'danger';
                                $statusTexto = 'Atrasado';
                            } elseif ($gest['dias_restantes'] <= 7) {
                                $classeStatus = 'warning';
                                $statusTexto = 'Próximo';
                            }
                        ?>
                        <tr>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $gest['id_bovino_femea']; ?>">
                                    <strong><?php echo $gest['brinco']; ?></strong>
                                    <?php if ($gest['nome']): ?>
                                        <br><small><?php echo $gest['nome']; ?></small>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($gest['data_evento'])); ?></td>
                            <td><?php echo $gest['dias_gestacao']; ?> dias</td>
                            <td><?php echo date('d/m/Y', strtotime($gest['data_prevista_parto'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $classeStatus; ?>">
                                    <?php echo abs($gest['dias_restantes']); ?> dias
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $classeStatus; ?>">
                                    <?php echo $statusTexto; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="novo_parto.php?femea_id=<?php echo $gest['id_bovino_femea']; ?>" 
                                       class="btn btn-success" title="Registrar Parto">
                                        <i class="bi bi-egg"></i>
                                    </a>
                                    <a href="acoes/finalizar_gestacao.php?id=<?php echo $gest['id']; ?>" 
                                       class="btn btn-danger" title="Finalizar Gestação"
                                       onclick="return confirm('Finalizar esta gestação?')">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-heart-fill display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma gestação ativa</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>