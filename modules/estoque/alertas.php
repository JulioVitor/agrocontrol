<?php
// modules/estoque/alertas.php
// Alertas de estoque baixo e vencimentos

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

$pageTitle = 'Alertas de Estoque';

// ============================================
// 1. PRODUTOS COM ESTOQUE BAIXO
// ============================================
$sqlBaixo = "SELECT p.*, c.nome_categoria, c.cor as categoria_cor,
                    (p.quantidade_minima - p.quantidade_atual) as falta,
                    (p.quantidade_atual / p.quantidade_minima) * 100 as percentual
             FROM estoque_produtos p
             JOIN estoque_categorias c ON p.id_categoria = c.id
             WHERE p.id_fazenda = $farmId AND p.ativo = 1
               AND p.quantidade_atual <= p.quantidade_minima
               AND p.quantidade_minima > 0
             ORDER BY percentual ASC";
$estoqueBaixo = executeQuery($sqlBaixo);

// ============================================
// 2. PRODUTOS PRÓXIMOS AO VENCIMENTO (30 DIAS)
// ============================================
$sqlProximos = "SELECT p.*, c.nome_categoria, c.cor as categoria_cor,
                       DATEDIFF(p.data_validade, CURDATE()) as dias_restantes
                FROM estoque_produtos p
                JOIN estoque_categorias c ON p.id_categoria = c.id
                WHERE p.id_fazenda = $farmId AND p.ativo = 1
                  AND p.data_validade IS NOT NULL
                  AND p.data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY p.data_validade ASC";
$proximosVencer = executeQuery($sqlProximos);

// ============================================
// 3. PRODUTOS VENCIDOS
// ============================================
$sqlVencidos = "SELECT p.*, c.nome_categoria, c.cor as categoria_cor,
                       DATEDIFF(CURDATE(), p.data_validade) as dias_vencido
                FROM estoque_produtos p
                JOIN estoque_categorias c ON p.id_categoria = c.id
                WHERE p.id_fazenda = $farmId AND p.ativo = 1
                  AND p.data_validade IS NOT NULL
                  AND p.data_validade < CURDATE()
                ORDER BY p.data_validade ASC";
$vencidos = executeQuery($sqlVencidos);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                Alertas de Estoque
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item active">Alertas</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Cards de Resumo de Alertas -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Estoque Baixo</h6>
                    <h2><?php echo $estoqueBaixo ? $estoqueBaixo->num_rows : 0; ?></h2>
                    <small>Produtos abaixo do mínimo</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Próximos a Vencer</h6>
                    <h2><?php echo $proximosVencer ? $proximosVencer->num_rows : 0; ?></h2>
                    <small>Vencem em até 30 dias</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Vencidos</h6>
                    <h2><?php echo $vencidos ? $vencidos->num_rows : 0; ?></h2>
                    <small>Produtos com validade expirada</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-white">
            <h6 class="card-title mb-0">
                <i class="bi bi-exclamation-triangle"></i> Produtos com Estoque Baixo
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if ($estoqueBaixo && $estoqueBaixo->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th class="text-end">Atual</th>
                            <th class="text-end">Mínimo</th>
                            <th class="text-end">Falta</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $estoqueBaixo->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo $p['nome_produto']; ?></strong>
                                <?php if ($p['codigo']): ?>
                                    <br><small>Cód: <?php echo $p['codigo']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $p['categoria_cor']; ?>">
                                    <?php echo $p['nome_categoria']; ?>
                                </span>
                            </td>
                            <td class="text-end text-danger">
                                <strong><?php echo number_format($p['quantidade_atual'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></strong>
                            </td>
                            <td class="text-end"><?php echo number_format($p['quantidade_minima'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></td>
                            <td class="text-end text-danger"><?php echo number_format($p['falta'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></td>
                            <td>
                                <div class="progress" style="height: 5px; width: 100px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo min($p['percentual'], 100); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="entrada.php?produto_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-arrow-down-circle"></i> Comprar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                <p class="text-muted mt-2">Nenhum produto com estoque baixo.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Produtos Próximos ao Vencimento -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="card-title mb-0">
                <i class="bi bi-clock-history"></i> Produtos Próximos ao Vencimento
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if ($proximosVencer && $proximosVencer->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Lote</th>
                            <th>Data Validade</th>
                            <th>Dias Restantes</th>
                            <th>Quantidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $proximosVencer->fetch_assoc()): 
                            $classeDias = $p['dias_restantes'] <= 7 ? 'danger' : 'warning';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $p['nome_produto']; ?></strong>
                                <?php if ($p['codigo']): ?>
                                    <br><small>Cód: <?php echo $p['codigo']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $p['categoria_cor']; ?>">
                                    <?php echo $p['nome_categoria']; ?>
                                </span>
                            </td>
                            <td><?php echo $p['lote'] ?: '-'; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($p['data_validade'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $classeDias; ?>">
                                    <?php echo $p['dias_restantes']; ?> dias
                                </span>
                            </td>
                            <td><?php echo number_format($p['quantidade_atual'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></td>
                            <td>
                                <a href="saida.php?produto_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-arrow-up-circle"></i> Usar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-calendar-check text-success fs-1"></i>
                <p class="text-muted mt-2">Nenhum produto próximo ao vencimento.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Produtos Vencidos -->
    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
            <h6 class="card-title mb-0">
                <i class="bi bi-exclamation-octagon"></i> Produtos Vencidos
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if ($vencidos && $vencidos->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Lote</th>
                            <th>Data Validade</th>
                            <th>Dias Vencido</th>
                            <th>Quantidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $vencidos->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo $p['nome_produto']; ?></strong>
                                <?php if ($p['codigo']): ?>
                                    <br><small>Cód: <?php echo $p['codigo']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $p['categoria_cor']; ?>">
                                    <?php echo $p['nome_categoria']; ?>
                                </span>
                            </td>
                            <td><?php echo $p['lote'] ?: '-'; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($p['data_validade'])); ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo $p['dias_vencido']; ?> dias
                                </span>
                            </td>
                            <td><?php echo number_format($p['quantidade_atual'], 2, ',', '.'); ?> <?php echo $p['unidade']; ?></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-danger" onclick="return confirm('Descartar este produto?')">
                                    <i class="bi bi-trash"></i> Descartar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle-fill text-success fs-1"></i>
                <p class="text-muted mt-2">Nenhum produto vencido.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>