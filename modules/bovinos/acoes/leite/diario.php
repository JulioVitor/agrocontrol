<?php
// modules/bovinos/acoes/leite/diario.php
// Produção diária de leite de toda a fazenda

require_once '../../../../config/database.php';
require_once '../../../../config/constants.php';
require_once '../../../../includes/functions.php';
require_once '../../../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

// Verificar se tem fazenda ativa
$farmId = getActiveFarmId();
if (!$farmId) {
    redirect(BASE_URL . 'modules/fazendas/selector.php');
}

$pageTitle = 'Produção Diária de Leite';

// Filtro de data
$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Buscar produção do dia
$sql = "SELECT pl.*, 
               b.brinco, 
               b.nome as nome_bovino,
               b.id as bovino_id
        FROM producao_leite pl
        JOIN bovinos b ON pl.id_bovino = b.id
        WHERE " . TenantManager::addTenantFilter('b') . "
        AND pl.data_producao = '$data'
        ORDER BY b.brinco, FIELD(pl.turno, 'manha', 'tarde', 'noite', 'unico')";

$producoes = executeQuery($sql);

// Calcular total do dia
$totalDia = 0;
$producoesArray = [];
if ($producoes && $producoes->num_rows > 0) {
    while ($p = $producoes->fetch_assoc()) {
        $producoesArray[] = $p;
        $totalDia += $p['quantidade_litros'];
    }
}

// Buscar animais em lactação (que já produziram algo)
$sqlLactacao = "SELECT DISTINCT b.id, b.brinco, b.nome
                FROM bovinos b
                LEFT JOIN producao_leite pl ON b.id = pl.id_bovino
                WHERE " . TenantManager::addTenantFilter('b') . "
                AND b.sexo = 'F'
                AND b.ativo = 1
                ORDER BY b.brinco";
$lactacao = executeQuery($sqlLactacao);

include '../../../../includes/header.php';
include '../../../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-calendar-day me-2 text-success"></i>
                Produção Diária de Leite
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Produção Diária</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="graficos.php" class="btn btn-info me-2">
                <i class="bi bi-graph-up"></i> Gráficos
            </a>
            <a href="javascript:window.print()" class="btn btn-secondary">
                <i class="bi bi-printer"></i> Imprimir
            </a>
        </div>
    </div>

    <!-- Seletor de data -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label">Data</label>
                    <input type="date" class="form-control" name="data" value="<?php echo $data; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success mt-4">
                        <i class="bi bi-search"></i> Visualizar
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <h4 class="mb-0">
                        Total do Dia: 
                        <span class="text-success"><?php echo number_format($totalDia, 2, ',', '.'); ?> L</span>
                    </h4>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo do dia -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <small>Animais em Lactação</small>
                    <h3><?php echo $lactacao ? $lactacao->num_rows : 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <small>Animais com Produção</small>
                    <h3><?php echo count($producoesArray); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <small>Média por Animal</small>
                    <h3>
                        <?php 
                        $media = count($producoesArray) > 0 ? $totalDia / count($producoesArray) : 0;
                        echo number_format($media, 2, ',', '.'); ?> L
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de produção do dia -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                Produção do Dia - <?php echo date('d/m/Y', strtotime($data)); ?>
            </h5>
            <a href="cadastrar.php" class="btn btn-sm btn-success">
                <i class="bi bi-plus-circle"></i> Novo Registro
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($producoesArray)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Animal</th>
                            <th>Manhã</th>
                            <th>Tarde</th>
                            <th>Noite</th>
                            <th>Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Organizar por animal
                        $porAnimal = [];
                        foreach ($producoesArray as $p) {
                            $id = $p['bovino_id'];
                            if (!isset($porAnimal[$id])) {
                                $porAnimal[$id] = [
                                    'brinco' => $p['brinco'],
                                    'nome' => $p['nome_bovino'],
                                    'manha' => 0,
                                    'tarde' => 0,
                                    'noite' => 0,
                                    'registros' => []
                                ];
                            }
                            $porAnimal[$id][$p['turno']] = $p['quantidade_litros'];
                            $porAnimal[$id]['registros'][] = $p;
                        }
                        
                        foreach ($porAnimal as $id => $animal):
                            $totalAnimal = $animal['manha'] + $animal['tarde'] + $animal['noite'];
                        ?>
                        <tr>
                            <td>
                                <a href="index.php?bovino_id=<?php echo $id; ?>">
                                    <strong><?php echo $animal['brinco']; ?></strong>
                                    <?php if ($animal['nome']): ?>
                                        <br><small><?php echo $animal['nome']; ?></small>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td><?php echo $animal['manha'] > 0 ? number_format($animal['manha'], 2, ',', '.') . ' L' : '-'; ?></td>
                            <td><?php echo $animal['tarde'] > 0 ? number_format($animal['tarde'], 2, ',', '.') . ' L' : '-'; ?></td>
                            <td><?php echo $animal['noite'] > 0 ? number_format($animal['noite'], 2, ',', '.') . ' L' : '-'; ?></td>
                            <td><strong><?php echo number_format($totalAnimal, 2, ',', '.'); ?> L</strong></td>
                            <td>
                                <?php foreach ($animal['registros'] as $reg): ?>
                                <div class="btn-group btn-group-sm me-1">
                                    <a href="editar.php?id=<?php echo $reg['id']; ?>&bovino_id=<?php echo $id; ?>" 
                                       class="btn btn-outline-warning" title="Editar <?php echo $reg['turno']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <td colspan="4" class="text-end"><strong>Total do Dia:</strong></td>
                            <td><strong><?php echo number_format($totalDia, 2, ',', '.'); ?> L</strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-cup display-1 text-muted"></i>
                <h4 class="mt-3">Nenhuma produção registrada para esta data</h4>
                <a href="cadastrar.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>
                    Registrar Produção
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../../../includes/footer.php'; ?>