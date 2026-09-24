<?php
// modules/reproducao/touros.php
// Lista de touros e reprodutores

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

$pageTitle = 'Touros e Reprodutores';

// Buscar touros
$sql = "SELECT b.*, 
               (SELECT COUNT(*) FROM reproducao WHERE id_bovino_macho = b.id) as total_coberturas,
               (SELECT COUNT(*) FROM reproducao WHERE id_bovino_macho = b.id AND tipo_evento = 'inseminacao') as total_inseminacoes
        FROM bovinos b
        WHERE " . TenantManager::addTenantFilter() . " 
        AND b.sexo = 'M' AND b.ativo = 1
        ORDER BY b.brinco";
$touros = executeQuery($sql);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-gender-male me-2 text-primary"></i>
                Touros e Reprodutores
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Touros</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Lista de touros -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if ($touros && $touros->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Brinco</th>
                            <th>Nome</th>
                            <th>Raça</th>
                            <th>Idade</th>
                            <th>Peso</th>
                            <th>Coberturas</th>
                            <th>Inseminações</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($touro = $touros->fetch_assoc()): 
                            $idade = '';
                            if ($touro['data_nascimento']) {
                                $nasc = new DateTime($touro['data_nascimento']);
                                $hoje = new DateTime();
                                $idade = $nasc->diff($hoje)->y . ' anos';
                            }
                        ?>
                        <tr>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $touro['id']; ?>">
                                    <strong><?php echo $touro['brinco']; ?></strong>
                                </a>
                            </td>
                            <td><?php echo $touro['nome'] ?: '-'; ?></td>
                            <td><?php 
                                $sqlRaca = "SELECT nome_raca FROM racas WHERE id = " . $touro['id_raca'];
                                $resRaca = executeQuery($sqlRaca);
                                echo $resRaca->fetch_assoc()['nome_raca'] ?? '-';
                            ?></td>
                            <td><?php echo $idade; ?></td>
                            <td><?php echo $touro['peso_atual'] ? number_format($touro['peso_atual'], 2, ',', '.') . ' kg' : '-'; ?></td>
                            <td><span class="badge bg-primary"><?php echo $touro['total_coberturas']; ?></span></td>
                            <td><span class="badge bg-info"><?php echo $touro['total_inseminacoes']; ?></span></td>
                            <td>
                                <a href="../bovinos/visualizar.php?id=<?php echo $touro['id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-gender-male display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum touro encontrado</h4>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>