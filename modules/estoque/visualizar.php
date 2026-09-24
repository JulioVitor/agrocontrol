<?php
// modules/estoque/visualizar.php
// Visualizar detalhes do produto

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

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('produtos.php');
}

// Buscar dados do produto
$sql = "SELECT p.*, c.nome_categoria, c.cor as categoria_cor
        FROM estoque_produtos p
        JOIN estoque_categorias c ON p.id_categoria = c.id
        WHERE p.id = $id AND p.id_fazenda = $farmId";
$result = executeQuery($sql);

if (!$result || $result->num_rows == 0) {
    setAlert('Produto não encontrado.', 'danger');
    redirect('produtos.php');
}

$produto = $result->fetch_assoc();

// Buscar últimas movimentações
$sqlMov = "SELECT m.*, u.nome as usuario_nome, b.brinco as bovino_brinco
           FROM estoque_movimentacoes m
           LEFT JOIN usuarios u ON m.id_usuario = u.id
           LEFT JOIN bovinos b ON m.id_bovino = b.id
           WHERE m.id_produto = $id
           ORDER BY m.data_movimento DESC
           LIMIT 20";
$movimentacoes = executeQuery($sqlMov);

$pageTitle = 'Detalhes do Produto - ' . $produto['nome_produto'];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-box-seam me-2 text-success"></i>
                <?php echo $produto['nome_produto']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Estoque</a></li>
                    <li class="breadcrumb-item"><a href="produtos.php">Produtos</a></li>
                    <li class="breadcrumb-item active"><?php echo $produto['codigo'] ?: 'Detalhes'; ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="entrada.php?produto_id=<?php echo $id; ?>" class="btn btn-success">
                <i class="bi bi-arrow-down-circle"></i> Entrada
            </a>
            <a href="saida.php?produto_id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="bi bi-arrow-up-circle"></i> Saída
            </a>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="produtos.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Informações do Produto -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Informações Gerais</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="120">Código:</th>
                                    <td><strong><?php echo $produto['codigo'] ?: '---'; ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Categoria:</th>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $produto['categoria_cor']; ?>">
                                            <?php echo $produto['nome_categoria']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Unidade:</th>
                                    <td><?php echo $produto['unidade']; ?></td>
                                </tr>
                                <tr>
                                    <th>Localização:</th>
                                    <td><?php echo $produto['localizacao'] ?: 'Não definido'; ?></td>
                                </tr>
                                <tr>
                                    <th>Fabricante:</th>
                                    <td><?php echo $produto['fabricante'] ?: '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="120">Lote:</th>
                                    <td><?php echo $produto['lote'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Fabricação:</th>
                                    <td><?php echo $produto['data_fabricacao'] ? date('d/m/Y', strtotime($produto['data_fabricacao'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Validade:</th>
                                    <td>
                                        <?php if ($produto['data_validade']): ?>
                                            <?php echo date('d/m/Y', strtotime($produto['data_validade'])); ?>
                                            <?php 
                                            $hoje = new DateTime();
                                            $validade = new DateTime($produto['data_validade']);
                                            if ($validade < $hoje) {
                                                echo '<span class="badge bg-danger ms-2">Vencido</span>';
                                            } elseif ($hoje->diff($validade)->days <= 30) {
                                                echo '<span class="badge bg-warning ms-2">Próx. Vencer</span>';
                                            }
                                            ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Preço Custo:</th>
                                    <td>R$ <?php echo number_format($produto['preco_custo'] ?? 0, 2, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <th>Preço Venda:</th>
                                    <td>R$ <?php echo number_format($produto['preco_venda'] ?? 0, 2, ',', '.'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($produto['descricao']): ?>
                    <hr>
                    <h6>Descrição</h6>
                    <p class="bg-light p-3 rounded"><?php echo nl2br($produto['descricao']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($produto['observacoes']): ?>
                    <hr>
                    <h6>Observações</h6>
                    <p class="bg-light p-3 rounded"><?php echo nl2br($produto['observacoes']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card de Estoque -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">Situação do Estoque</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-4 text-primary">
                            <?php echo number_format($produto['quantidade_atual'], 2, ',', '.'); ?>
                        </div>
                        <div class="text-muted"><?php echo $produto['unidade']; ?> disponíveis</div>
                    </div>
                    
                    <?php if ($produto['quantidade_minima'] > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Estoque mínimo</small>
                            <strong><?php echo number_format($produto['quantidade_minima'], 2, ',', '.'); ?> <?php echo $produto['unidade']; ?></strong>
                        </div>
                        <?php 
                        $percentual = ($produto['quantidade_atual'] / $produto['quantidade_minima']) * 100;
                        $barClass = $percentual < 50 ? 'bg-danger' : ($percentual < 80 ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo min($percentual, 100); ?>%"></div>
                        </div>
                        <?php if ($produto['quantidade_atual'] < $produto['quantidade_minima']): ?>
                        <div class="text-danger small mt-1">
                            <i class="bi bi-exclamation-triangle"></i> Abaixo do mínimo
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($produto['quantidade_maxima'] > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <small>Estoque máximo</small>
                            <strong><?php echo number_format($produto['quantidade_maxima'], 2, ',', '.'); ?> <?php echo $produto['unidade']; ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="entrada.php?produto_id=<?php echo $id; ?>" class="btn btn-success">
                            <i class="bi bi-arrow-down-circle"></i> Registrar Entrada
                        </a>
                        <a href="saida.php?produto_id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="bi bi-arrow-up-circle"></i> Registrar Saída
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Movimentações -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Últimas Movimentações</h6>
            <a href="historico.php?produto_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-info">Ver todas</a>
        </div>
        <div class="card-body p-0">
            <?php if ($movimentacoes && $movimentacoes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th class="text-end">Quantidade</th>
                            <th>Saldo</th>
                            <th>Motivo</th>
                            <th>Bovino</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = $movimentacoes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $m['tipo_movimento'] == 'entrada' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($m['tipo_movimento']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="<?php echo $m['tipo_movimento'] == 'entrada' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $m['tipo_movimento'] == 'entrada' ? '+' : '-'; ?>
                                    <?php echo number_format($m['quantidade'], 2, ',', '.'); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($m['quantidade_posterior'], 2, ',', '.'); ?> <?php echo $produto['unidade']; ?></td>
                            <td><?php echo $m['motivo'] ?: '-'; ?></td>
                            <td><?php echo $m['bovino_brinco'] ?: '-'; ?></td>
                            <td><?php echo $m['usuario_nome'] ?: '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">Nenhuma movimentação registrada.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>