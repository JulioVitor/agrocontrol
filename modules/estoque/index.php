<?php
// modules/estoque/index.php
// Dashboard do estoque

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

$pageTitle = 'Dashboard de Estoque';

// Estatísticas gerais
$stats = [];

// Total de produtos
$sql = "SELECT COUNT(*) as total FROM estoque_produtos WHERE id_fazenda = $farmId AND ativo = 1";
$result = executeQuery($sql);
$stats['total_produtos'] = $result->fetch_assoc()['total'];

// Valor total em estoque
$sql = "SELECT COALESCE(SUM(quantidade_atual * preco_custo), 0) as valor_total 
        FROM estoque_produtos WHERE id_fazenda = $farmId AND ativo = 1";
$result = executeQuery($sql);
$stats['valor_total'] = $result->fetch_assoc()['valor_total'];

// Produtos com estoque baixo
$sql = "SELECT COUNT(*) as total FROM estoque_produtos 
        WHERE id_fazenda = $farmId AND ativo = 1 
        AND quantidade_atual <= quantidade_minima AND quantidade_minima > 0";
$result = executeQuery($sql);
$stats['estoque_baixo'] = $result->fetch_assoc()['total'];

// Produtos próximos ao vencimento (30 dias)
$sql = "SELECT COUNT(*) as total FROM estoque_produtos 
        WHERE id_fazenda = $farmId AND ativo = 1 
        AND data_validade IS NOT NULL 
        AND data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$result = executeQuery($sql);
$stats['proximos_vencer'] = $result->fetch_assoc()['total'];

// Produtos vencidos
$sql = "SELECT COUNT(*) as total FROM estoque_produtos 
        WHERE id_fazenda = $farmId AND ativo = 1 
        AND data_validade IS NOT NULL AND data_validade < CURDATE()";
$result = executeQuery($sql);
$stats['vencidos'] = $result->fetch_assoc()['total'];

// Últimas movimentações
$sql = "SELECT m.*, p.nome_produto, p.codigo, u.nome as usuario_nome
        FROM estoque_movimentacoes m
        JOIN estoque_produtos p ON m.id_produto = p.id
        LEFT JOIN usuarios u ON m.id_usuario = u.id
        WHERE m.id_fazenda = $farmId
        ORDER BY m.data_movimento DESC
        LIMIT 10";
$movimentacoes = executeQuery($sql);

// Produtos com estoque baixo (lista)
$sqlBaixo = "SELECT * FROM estoque_produtos 
             WHERE id_fazenda = $farmId AND ativo = 1 
             AND quantidade_atual <= quantidade_minima AND quantidade_minima > 0
             ORDER BY (quantidade_atual / quantidade_minima) ASC
             LIMIT 10";
$produtosBaixo = executeQuery($sqlBaixo);

// Produtos por categoria
$sqlCategorias = "SELECT c.nome_categoria, c.cor, COUNT(p.id) as total_produtos,
                  COALESCE(SUM(p.quantidade_atual * p.preco_custo), 0) as valor_categoria
                  FROM estoque_categorias c
                  LEFT JOIN estoque_produtos p ON c.id = p.id_categoria AND p.ativo = 1
                  WHERE c.id_fazenda = $farmId
                  GROUP BY c.id
                  ORDER BY total_produtos DESC";
$categorias = executeQuery($sqlCategorias);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-box-seam me-2 text-success"></i>
                Dashboard de Estoque
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Estoque</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="cadastrar.php" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>
                Novo Produto
            </a>
            <a href="entrada.php" class="btn btn-info">
                <i class="bi bi-arrow-down-circle"></i> Entrada
            </a>
            <a href="saida.php" class="btn btn-warning">
                <i class="bi bi-arrow-up-circle"></i> Saída
            </a>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total de Produtos</h6>
                    <h2><?php echo $stats['total_produtos']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Valor em Estoque</h6>
                    <h2>R$ <?php echo number_format($stats['valor_total'], 2, ',', '.'); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Estoque Baixo</h6>
                    <h2><?php echo $stats['estoque_baixo']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Próximos a Vencer</h6>
                    <h2><?php echo $stats['proximos_vencer']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <?php if ($stats['vencidos'] > 0): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Atenção!</strong> Existem <?php echo $stats['vencidos']; ?> produtos vencidos no estoque.
        <a href="produtos.php?status=vencido" class="alert-link">Ver produtos</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($stats['proximos_vencer'] > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="bi bi-clock-history me-2"></i>
        <strong>Atenção!</strong> <?php echo $stats['proximos_vencer']; ?> produtos vencem nos próximos 30 dias.
        <a href="produtos.php?status=proximo_vencer" class="alert-link">Ver produtos</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Produtos com Estoque Baixo -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Produtos com Estoque Baixo
                    </h6>
                    <a href="alertas.php" class="text-white">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($produtosBaixo && $produtosBaixo->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($p = $produtosBaixo->fetch_assoc()): 
                            $percentual = ($p['quantidade_atual'] / $p['quantidade_minima']) * 100;
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $p['nome_produto']; ?></strong>
                                    <br>
                                    <small class="text-muted">Cód: <?php echo $p['codigo'] ?: '---'; ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?php echo $p['quantidade_atual']; ?> <?php echo $p['unidade']; ?></span>
                                    <br>
                                    <small>Mín: <?php echo $p['quantidade_minima']; ?> <?php echo $p['unidade']; ?></small>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                        <p class="text-muted mt-2">Nenhum produto com estoque baixo.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimas Movimentações -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clock-history"></i> Últimas Movimentações
                    </h6>
                    <a href="historico.php" class="text-white">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($movimentacoes && $movimentacoes->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($m = $movimentacoes->fetch_assoc()): 
                            $classeTipo = [
                                'entrada' => 'success',
                                'saida' => 'danger',
                                'ajuste' => 'warning',
                                'perda' => 'dark'
                            ][$m['tipo_movimento']] ?? 'secondary';
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $m['nome_produto']; ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?> - 
                                        <?php echo $m['usuario_nome'] ?: 'Sistema'; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $classeTipo; ?>">
                                        <?php echo ucfirst($m['tipo_movimento']); ?>
                                    </span>
                                    <br>
                                    <strong><?php echo $m['quantidade']; ?> <?php echo $m['unidade']; ?></strong>
                                </div>
                            </div>
                            <?php if ($m['motivo']): ?>
                            <small class="text-muted"><?php echo $m['motivo']; ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history fs-1 text-muted"></i>
                        <p class="text-muted mt-2">Nenhuma movimentação registrada.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorias e Valores -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Resumo por Categoria</h6>
                </div>
                <div class="card-body p-0">
                    <?php if ($categorias && $categorias->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-center">Produtos</th>
                                    <th class="text-end">Valor em Estoque</th>
                                    <th class="text-end">% do Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cat = $categorias->fetch_assoc()): 
                                    $percentual = $stats['valor_total'] > 0 ? ($cat['valor_categoria'] / $stats['valor_total']) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $cat['cor']; ?>">
                                            <?php echo $cat['nome_categoria']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo $cat['total_produtos']; ?></td>
                                    <td class="text-end">R$ <?php echo number_format($cat['valor_categoria'], 2, ',', '.'); ?></td>
                                    <td class="text-end">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: <?php echo $percentual; ?>%; background-color: <?php echo $cat['cor']; ?>">
                                                <?php echo number_format($percentual, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Nenhuma categoria cadastrada.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>