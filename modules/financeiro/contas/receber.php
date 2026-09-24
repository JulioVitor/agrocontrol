<?php
// modules/financeiro/contas/receber.php
// Gerenciamento de contas a receber

require_once '../../../config/database.php';
require_once '../../../config/constants.php';
require_once '../../../includes/functions.php';
require_once '../../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    redirect(BASE_URL . 'modules/fazendas/selector.php');
}

$pageTitle = 'Contas a Receber';

// Filtros
$status = isset($_GET['status']) ? $_GET['status'] : 'pendente';
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '30';

// Definir intervalo de datas baseado no período
$hoje = date('Y-m-d');
switch ($periodo) {
    case '7':
        $dataLimite = date('Y-m-d', strtotime('+7 days'));
        break;
    case '15':
        $dataLimite = date('Y-m-d', strtotime('+15 days'));
        break;
    case '30':
        $dataLimite = date('Y-m-d', strtotime('+30 days'));
        break;
    case '60':
        $dataLimite = date('Y-m-d', strtotime('+60 days'));
        break;
    case '90':
        $dataLimite = date('Y-m-d', strtotime('+90 days'));
        break;
    default:
        $dataLimite = date('Y-m-d', strtotime('+30 days'));
}

// Construir WHERE para contas a receber (receitas)
$where = TenantManager::addTenantFilter('l') . " AND c.tipo = 'receita'";

if ($status == 'pendente') {
    $where .= " AND l.status = 'pendente'";
} elseif ($status == 'vencidas') {
    $where .= " AND l.status = 'pendente' AND l.data_vencimento < CURDATE()";
} elseif ($status == 'recebidas') {
    $where .= " AND l.status = 'pago'";
}

if ($categoria > 0) {
    $where .= " AND l.id_categoria = $categoria";
}

if ($status != 'recebidas' && $status != 'vencidas') {
    $where .= " AND (l.data_vencimento IS NULL OR l.data_vencimento <= '$dataLimite')";
}

// Estatísticas
$stats = [];

// Total a receber
$sql = "SELECT COALESCE(SUM(l.valor - COALESCE(l.valor_pago, 0)), 0) as total_receber
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . " 
        AND c.tipo = 'receita'
        AND l.status = 'pendente'";
$result = executeQuery($sql);
$stats['total_receber'] = $result->fetch_assoc()['total_receber'];

// Vencidas (atrasadas)
$sql = "SELECT COALESCE(SUM(l.valor - COALESCE(l.valor_pago, 0)), 0) as total_vencido
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . " 
        AND c.tipo = 'receita'
        AND l.status = 'pendente'
        AND l.data_vencimento < CURDATE()";
$result = executeQuery($sql);
$stats['total_vencido'] = $result->fetch_assoc()['total_vencido'];

// Já recebido no mês
$sql = "SELECT COALESCE(SUM(l.valor), 0) as total_recebido
        FROM lancamentos_financeiros l
        JOIN categorias_financeiras c ON l.id_categoria = c.id
        WHERE " . TenantManager::addTenantFilter('l') . " 
        AND c.tipo = 'receita'
        AND l.status = 'pago'
        AND MONTH(l.data_pagamento) = MONTH(CURDATE())
        AND YEAR(l.data_pagamento) = YEAR(CURDATE())";
$result = executeQuery($sql);
$stats['total_recebido'] = $result->fetch_assoc()['total_recebido'];

// Buscar contas
$sql = "SELECT l.*, c.nome_categoria, c.cor,
               b.brinco as bovino_brinco, b.nome as bovino_nome
        FROM lancamentos_financeiros l
        LEFT JOIN categorias_financeiras c ON l.id_categoria = c.id
        LEFT JOIN bovinos b ON l.id_bovino = b.id
        WHERE $where
        ORDER BY 
            CASE 
                WHEN l.status = 'pendente' AND l.data_vencimento < CURDATE() THEN 1
                WHEN l.status = 'pendente' THEN 2
                ELSE 3
            END,
            l.data_vencimento ASC,
            l.data_emissao DESC";
$contas = executeQuery($sql);

// Buscar categorias para filtro
$sqlCats = "SELECT id, nome_categoria FROM categorias_financeiras 
            WHERE id_fazenda = $farmId AND tipo = 'receita' AND ativo = 1 
            ORDER BY nome_categoria";
$categorias = executeQuery($sqlCats);

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-arrow-up-circle me-2 text-success"></i>
                Contas a Receber
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Contas a Receber</li>
                </ol>
            </nav>
        </div>
        <a href="../receita.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>
            Nova Receita
        </a>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total a Receber</h6>
                    <h3>R$ <?php echo number_format($stats['total_receber'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Atrasado</h6>
                    <h3>R$ <?php echo number_format($stats['total_vencido'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Recebido no Mês</h6>
                    <h3>R$ <?php echo number_format($stats['total_recebido'], 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="vencidas" <?php echo $status == 'vencidas' ? 'selected' : ''; ?>>Atrasadas</option>
                        <option value="recebidas" <?php echo $status == 'recebidas' ? 'selected' : ''; ?>>Recebidas</option>
                        <option value="todas" <?php echo $status == 'todas' ? 'selected' : ''; ?>>Todas</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria">
                        <option value="0">Todas</option>
                        <?php if ($categorias && $categorias->num_rows > 0): ?>
                            <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['nome_categoria']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <select class="form-select" name="periodo">
                        <option value="7" <?php echo $periodo == '7' ? 'selected' : ''; ?>>Próximos 7 dias</option>
                        <option value="15" <?php echo $periodo == '15' ? 'selected' : ''; ?>>Próximos 15 dias</option>
                        <option value="30" <?php echo $periodo == '30' ? 'selected' : ''; ?>>Próximos 30 dias</option>
                        <option value="60" <?php echo $periodo == '60' ? 'selected' : ''; ?>>Próximos 60 dias</option>
                        <option value="90" <?php echo $periodo == '90' ? 'selected' : ''; ?>>Próximos 90 dias</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success mt-4">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de contas -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Lista de Contas a Receber</h6>
            <span class="badge bg-success"><?php echo $contas ? $contas->num_rows : 0; ?> conta(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($contas && $contas->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Vencimento</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Recebido</th>
                            <th>Saldo</th>
                            <th>Dias</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($conta = $contas->fetch_assoc()): 
                            $valor_original = $conta['valor'];
                            $valor_recebido = $conta['valor_pago'] ?: 0;
                            $saldo = $valor_original - $valor_recebido;
                            
                            $dias_vencimento = null;
                            $classe_vencimento = '';
                            
                            if ($conta['data_vencimento'] && $conta['status'] != 'pago') {
                                $vencimento = new DateTime($conta['data_vencimento']);
                                $hoje = new DateTime();
                                $dias = $hoje->diff($vencimento)->days;
                                
                                if ($vencimento < $hoje) {
                                    $dias_vencimento = -$dias;
                                    $classe_vencimento = 'danger';
                                } elseif ($dias <= 3) {
                                    $dias_vencimento = $dias;
                                    $classe_vencimento = 'warning';
                                } else {
                                    $dias_vencimento = $dias;
                                    $classe_vencimento = 'success';
                                }
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $conta['data_vencimento'] ? date('d/m/Y', strtotime($conta['data_vencimento'])) : 'À vista'; ?></strong>
                            </td>
                            <td>
                                <strong><?php echo $conta['descricao']; ?></strong>
                                <?php if ($conta['bovino_brinco']): ?>
                                    <br><small class="text-muted">
                                        <i class="bi bi-tree"></i> <?php echo $conta['bovino_brinco']; ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($conta['observacoes']): ?>
                                    <br><small class="text-muted"><?php echo $conta['observacoes']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $conta['cor'] ?: '#6c757d'; ?>">
                                    <?php echo $conta['nome_categoria']; ?>
                                </span>
                            </td>
                            <td class="text-success">
                                <strong>R$ <?php echo number_format($valor_original, 2, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <?php if ($valor_recebido > 0): ?>
                                    R$ <?php echo number_format($valor_recebido, 2, ',', '.'); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="<?php echo $saldo > 0 ? 'text-success' : 'text-secondary'; ?>">
                                <strong>R$ <?php echo number_format($saldo, 2, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <?php if ($dias_vencimento !== null): ?>
                                    <span class="badge bg-<?php echo $classe_vencimento; ?>">
                                        <?php 
                                        if ($dias_vencimento < 0) {
                                            echo abs($dias_vencimento) . ' dias atrasado';
                                        } elseif ($dias_vencimento == 0) {
                                            echo 'Vence hoje';
                                        } else {
                                            echo $dias_vencimento . ' dias';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($conta['status'] != 'pago'): ?>
                                    <a href="../acoes/baixar.php?id=<?php echo $conta['id']; ?>&tipo=receber" 
                                       class="btn btn-success" title="Registrar Recebimento">
                                        <i class="bi bi-check-circle"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="../editar.php?id=<?php echo $conta['id']; ?>" class="btn btn-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($conta['status'] == 'pago'): ?>
                                    <a href="../acoes/estornar.php?id=<?php echo $conta['id']; ?>" 
                                       class="btn btn-secondary" title="Estornar"
                                       onclick="return confirm('Estornar este recebimento?')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle display-1 text-success"></i>
                <h4 class="mt-3">Nenhuma conta a receber!</h4>
                <p class="text-muted">Todas as contas estão em dia.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Previsão de recebimentos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calendar-check me-2 text-success"></i>
                        Previsão de Recebimentos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $proximos7 = [];
                        $proximos15 = [];
                        $proximos30 = [];
                        
                        if ($contas && $contas->num_rows > 0) {
                            $contas->data_seek(0);
                            while ($c = $contas->fetch_assoc()) {
                                if ($c['status'] == 'pago' || !$c['data_vencimento']) continue;
                                
                                $venc = new DateTime($c['data_vencimento']);
                                $hoje = new DateTime();
                                $dias = $hoje->diff($venc)->days;
                                
                                if ($venc > $hoje) {
                                    if ($dias <= 7) $proximos7[] = $c;
                                    elseif ($dias <= 15) $proximos15[] = $c;
                                    elseif ($dias <= 30) $proximos30[] = $c;
                                }
                            }
                        }
                        ?>
                        
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10">
                                <div class="card-body">
                                    <h6><i class="bi bi-1-circle"></i> Próximos 7 dias</h6>
                                    <h3><?php echo count($proximos7); ?></h3>
                                    <small>R$ <?php 
                                        $total = array_sum(array_column($proximos7, 'valor'));
                                        echo number_format($total, 2, ',', '.');
                                    ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-info bg-opacity-10">
                                <div class="card-body">
                                    <h6><i class="bi bi-2-circle"></i> 8 a 15 dias</h6>
                                    <h3><?php echo count($proximos15); ?></h3>
                                    <small>R$ <?php 
                                        $total = array_sum(array_column($proximos15, 'valor'));
                                        echo number_format($total, 2, ',', '.');
                                    ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10">
                                <div class="card-body">
                                    <h6><i class="bi bi-3-circle"></i> 16 a 30 dias</h6>
                                    <h3><?php echo count($proximos30); ?></h3>
                                    <small>R$ <?php 
                                        $total = array_sum(array_column($proximos30, 'valor'));
                                        echo number_format($total, 2, ',', '.');
                                    ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../../includes/footer.php'; ?>