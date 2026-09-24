<?php
// modules/bovinos/cadastrar.php
// Formulário de cadastro de bovinos
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$pageTitle = 'Novo Bovino';
$error = '';
$success = '';

// Buscar raças para o select
$racasSql = "SELECT id, nome_raca FROM racas WHERE " . TenantManager::addTenantFilter() . " ORDER BY nome_raca";
$racas = executeQuery($racasSql);

// Buscar situações para o select
$situacoesSql = "SELECT id, nome FROM situacoes WHERE " . TenantManager::addTenantFilter() . " ORDER BY ordem";
$situacoes = executeQuery($situacoesSql);

// Buscar piquetes para o select
$piquetesSql = "SELECT id, nome_piquete FROM piquetes WHERE " . TenantManager::addTenantFilter() . " AND disponivel = 1 ORDER BY nome_piquete";
$piquetes = executeQuery($piquetesSql);

// Buscar pais e mães para select (apenas animais do rebanho)
$paisSql = "SELECT id, brinco, nome FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND sexo = 'M' AND ativo = 1 ORDER BY brinco";
$pais = executeQuery($paisSql);

$maesSql = "SELECT id, brinco, nome FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND sexo = 'F' AND ativo = 1 ORDER BY brinco";
$maes = executeQuery($maesSql);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Dados obrigatórios
    $brinco = escapeString(trim($_POST['brinco']));
    $sexo = $_POST['sexo'];
    $data_entrada = $_POST['data_entrada'];

    // Validar campos obrigatórios
    if (empty($brinco) || empty($sexo) || empty($data_entrada)) {
        $error = 'Brinco, Sexo e Data de Entrada são obrigatórios.';
    } else {

        // Verificar se brinco já existe na fazenda
        $checkSql = "SELECT id FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND brinco = '$brinco'";
        $checkResult = executeQuery($checkSql);

        if ($checkResult && $checkResult->num_rows > 0) {
            $error = 'Já existe um bovino com este brinco nesta fazenda.';
        } else {

            // Montar array de dados
            $dados = [
                'id_fazenda' => $farmId,
                'brinco' => $brinco,
                'brinco_eletronico' => !empty($_POST['brinco_eletronico']) ? "'" . escapeString($_POST['brinco_eletronico']) . "'" : "NULL",
                'nome' => !empty($_POST['nome']) ? "'" . escapeString($_POST['nome']) . "'" : "NULL",
                'registro_abcrh' => !empty($_POST['registro_abcrh']) ? "'" . escapeString($_POST['registro_abcrh']) . "'" : "NULL",
                'registro_anc' => !empty($_POST['registro_anc']) ? "'" . escapeString($_POST['registro_anc']) . "'" : "NULL",
                'id_raca' => !empty($_POST['id_raca']) ? intval($_POST['id_raca']) : "NULL",
                'id_situacao' => !empty($_POST['id_situacao']) ? intval($_POST['id_situacao']) : "NULL",
                'id_piquete_atual' => !empty($_POST['id_piquete_atual']) ? intval($_POST['id_piquete_atual']) : "NULL",
                'sexo' => "'$sexo'",
                'data_nascimento' => !empty($_POST['data_nascimento']) ? "'" . $_POST['data_nascimento'] . "'" : "NULL",
                'data_entrada' => "'$data_entrada'",
                'peso_nascimento' => !empty($_POST['peso_nascimento']) ? floatval($_POST['peso_nascimento']) : "NULL",
                'peso_atual' => !empty($_POST['peso_atual']) ? floatval($_POST['peso_atual']) : "NULL",
                'cor_pelagem' => !empty($_POST['cor_pelagem']) ? "'" . escapeString($_POST['cor_pelagem']) . "'" : "NULL",
                'origem' => !empty($_POST['origem']) ? "'" . $_POST['origem'] . "'" : "'nascido'",
                'valor_compra' => !empty($_POST['valor_compra']) ? floatval($_POST['valor_compra']) : "NULL",
                'id_pai' => !empty($_POST['id_pai']) ? intval($_POST['id_pai']) : "NULL",
                'id_mae' => !empty($_POST['id_mae']) ? intval($_POST['id_mae']) : "NULL",
                'nome_pai' => !empty($_POST['nome_pai']) ? "'" . escapeString($_POST['nome_pai']) . "'" : "NULL",
                'nome_mae' => !empty($_POST['nome_mae']) ? "'" . escapeString($_POST['nome_mae']) . "'" : "NULL",
                'observacoes' => !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL"
            ];

            // Construir SQL
            $sql = "INSERT INTO bovinos (" . implode(', ', array_keys($dados)) . ") 
                    VALUES (" . implode(', ', array_values($dados)) . ")";

            if (executeQuery($sql)) {
                $success = 'Bovino cadastrado com sucesso!';

                // Redirecionar após 2 segundos
                header("refresh:2;url=index.php");
            } else {
                $error = 'Erro ao cadastrar bovino. Tente novamente.';
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="bi bi-plus-circle me-2 text-success"></i>
            Novo Bovino
        </h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Voltar
        </a>
    </div>

    <!-- Mensagens -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>

                <!-- Abas -->
                <ul class="nav nav-tabs mb-4" id="formTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="identificacao-tab" data-bs-toggle="tab" data-bs-target="#identificacao" type="button" role="tab">
                            <i class="bi bi-tag me-2"></i>
                            Identificação
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                            <i class="bi bi-info-circle me-2"></i>
                            Dados Básicos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="origem-tab" data-bs-toggle="tab" data-bs-target="#origem" type="button" role="tab">
                            <i class="bi bi-tree me-2"></i>
                            Origem
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="genealogia-tab" data-bs-toggle="tab" data-bs-target="#genealogia" type="button" role="tab">
                            <i class="bi bi-diagram-3 me-2"></i>
                            Genealogia
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="observacoes-tab" data-bs-toggle="tab" data-bs-target="#observacoes" type="button" role="tab">
                            <i class="bi bi-chat me-2"></i>
                            Observações
                        </button>
                    </li>
                </ul>

                <!-- Conteúdo das abas -->
                <div class="tab-content" id="formTabsContent">

                    <!-- Aba: Identificação -->
                    <div class="tab-pane fade show active" id="identificacao" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Brinco *</label>
                                <input type="text" class="form-control" name="brinco" required
                                    value="<?php echo isset($_POST['brinco']) ? htmlspecialchars($_POST['brinco']) : ''; ?>">
                                <div class="invalid-feedback">Informe o brinco do animal</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Brinco Eletrônico</label>
                                <input type="text" class="form-control" name="brinco_eletronico"
                                    value="<?php echo isset($_POST['brinco_eletronico']) ? htmlspecialchars($_POST['brinco_eletronico']) : ''; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="nome"
                                    value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Registro ABCRH</label>
                                <input type="text" class="form-control" name="registro_abcrh"
                                    value="<?php echo isset($_POST['registro_abcrh']) ? htmlspecialchars($_POST['registro_abcrh']) : ''; ?>">
                                <small class="text-muted">Associação Brasileira de Criadores de Holandês</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Registro ANC</label>
                                <input type="text" class="form-control" name="registro_anc"
                                    value="<?php echo isset($_POST['registro_anc']) ? htmlspecialchars($_POST['registro_anc']) : ''; ?>">
                                <small class="text-muted">Associação Nacional de Criadores</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Raça</label>
                                <select class="form-select" name="id_raca">
                                    <option value="">Selecione</option>
                                    <?php if ($racas && $racas->num_rows > 0): ?>
                                        <?php while ($raca = $racas->fetch_assoc()): ?>
                                            <option value="<?php echo $raca['id']; ?>"
                                                <?php echo (isset($_POST['id_raca']) && $_POST['id_raca'] == $raca['id']) ? 'selected' : ''; ?>>
                                                <?php echo $raca['nome_raca']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Situação</label>
                                <select class="form-select" name="id_situacao">
                                    <option value="">Selecione</option>
                                    <?php if ($situacoes && $situacoes->num_rows > 0): ?>
                                        <?php while ($sit = $situacoes->fetch_assoc()): ?>
                                            <option value="<?php echo $sit['id']; ?>"
                                                <?php echo (isset($_POST['id_situacao']) && $_POST['id_situacao'] == $sit['id']) ? 'selected' : ''; ?>>
                                                <?php echo $sit['nome']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Dados Básicos -->
                    <div class="tab-pane fade" id="dados" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Sexo *</label>
                                <select class="form-select" name="sexo" required>
                                    <option value="">Selecione</option>
                                    <option value="M" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'M') ? 'selected' : ''; ?>>Macho</option>
                                    <option value="F" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'F') ? 'selected' : ''; ?>>Fêmea</option>
                                </select>
                                <div class="invalid-feedback">Selecione o sexo</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" name="data_nascimento"
                                    value="<?php echo isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ''; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Cor da Pelagem</label>
                                <input type="text" class="form-control" name="cor_pelagem"
                                    placeholder="Ex: Vermelha, Branca, Malhada"
                                    value="<?php echo isset($_POST['cor_pelagem']) ? htmlspecialchars($_POST['cor_pelagem']) : ''; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Peso ao Nascer (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="peso_nascimento"
                                    value="<?php echo isset($_POST['peso_nascimento']) ? $_POST['peso_nascimento'] : ''; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Peso Atual (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="peso_atual"
                                    value="<?php echo isset($_POST['peso_atual']) ? $_POST['peso_atual'] : ''; ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Piquete Atual</label>
                                <select class="form-select" name="id_piquete_atual">
                                    <option value="">Nenhum</option>
                                    <?php if ($piquetes && $piquetes->num_rows > 0): ?>
                                        <?php while ($piquete = $piquetes->fetch_assoc()): ?>
                                            <option value="<?php echo $piquete['id']; ?>"
                                                <?php echo (isset($_POST['id_piquete_atual']) && $_POST['id_piquete_atual'] == $piquete['id']) ? 'selected' : ''; ?>>
                                                <?php echo $piquete['nome_piquete']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Origem -->
                    <div class="tab-pane fade" id="origem" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Origem</label>
                                <select class="form-select" name="origem">
                                    <option value="nascido" <?php echo (!isset($_POST['origem']) || $_POST['origem'] == 'nascido') ? 'selected' : ''; ?>>Nascido na Fazenda</option>
                                    <option value="comprado" <?php echo (isset($_POST['origem']) && $_POST['origem'] == 'comprado') ? 'selected' : ''; ?>>Comprado</option>
                                    <option value="doacao" <?php echo (isset($_POST['origem']) && $_POST['origem'] == 'doacao') ? 'selected' : ''; ?>>Doação</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Data de Entrada *</label>
                                <input type="date" class="form-control" name="data_entrada" required
                                    value="<?php echo isset($_POST['data_entrada']) ? $_POST['data_entrada'] : date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Informe a data de entrada</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Valor de Compra (R$)</label>
                                <input type="number" step="0.01" class="form-control" name="valor_compra"
                                    placeholder="0,00"
                                    value="<?php echo isset($_POST['valor_compra']) ? $_POST['valor_compra'] : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Genealogia -->
                    <div class="tab-pane fade" id="genealogia" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pai (cadastrado)</label>
                                <select class="form-select" name="id_pai">
                                    <option value="">Nenhum</option>
                                    <?php if ($pais && $pais->num_rows > 0): ?>
                                        <?php while ($pai = $pais->fetch_assoc()): ?>
                                            <option value="<?php echo $pai['id']; ?>"
                                                <?php echo (isset($_POST['id_pai']) && $_POST['id_pai'] == $pai['id']) ? 'selected' : ''; ?>>
                                                <?php echo $pai['brinco']; ?> - <?php echo $pai['nome'] ?: 'Sem nome'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mãe (cadastrada)</label>
                                <select class="form-select" name="id_mae">
                                    <option value="">Nenhum</option>
                                    <?php if ($maes && $maes->num_rows > 0): ?>
                                        <?php while ($mae = $maes->fetch_assoc()): ?>
                                            <option value="<?php echo $mae['id']; ?>"
                                                <?php echo (isset($_POST['id_mae']) && $_POST['id_mae'] == $mae['id']) ? 'selected' : ''; ?>>
                                                <?php echo $mae['brinco']; ?> - <?php echo $mae['nome'] ?: 'Sem nome'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nome do Pai (se não cadastrado)</label>
                                <input type="text" class="form-control" name="nome_pai"
                                    value="<?php echo isset($_POST['nome_pai']) ? htmlspecialchars($_POST['nome_pai']) : ''; ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nome da Mãe (se não cadastrada)</label>
                                <input type="text" class="form-control" name="nome_mae"
                                    value="<?php echo isset($_POST['nome_mae']) ? htmlspecialchars($_POST['nome_mae']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Aba: Observações -->
                    <div class="tab-pane fade" id="observacoes" role="tabpanel">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes" rows="5"><?php echo isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                </div> <!-- Fim do tab-content -->

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>
                        Salvar Bovino
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>

<script>
    // Validação do formulário
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php include '../../includes/footer.php'; ?>