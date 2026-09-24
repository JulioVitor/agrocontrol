<?php
// modules/reproducao/novo_parto.php
// Registrar parto

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

$pageTitle = 'Registrar Parto';
$error = '';
$success = '';

// Buscar fêmeas gestantes
$sqlFemeas = "SELECT b.*, m.status_reprodutivo, r.data_prevista_parto
              FROM bovinos b
              LEFT JOIN matrizes m ON b.id = m.id_bovino
              LEFT JOIN (
                  SELECT id_bovino_femea, data_prevista_parto 
                  FROM reproducao 
                  WHERE tipo_evento = 'prenhez' AND confirmada = 1
                  GROUP BY id_bovino_femea
              ) r ON b.id = r.id_bovino_femea
              WHERE " . TenantManager::addTenantFilter('b') . " 
              AND b.sexo = 'F' AND b.ativo = 1
              AND (m.status_reprodutivo = 'prenhe' OR r.data_prevista_parto IS NOT NULL)
              ORDER BY b.brinco";
$femeas = executeQuery($sqlFemeas);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_bovino_femea = intval($_POST['id_bovino_femea']);
    $data_parto = $_POST['data_parto'];
    $crias_nascidas = intval($_POST['crias_nascidas']);
    $crias_vivas = intval($_POST['crias_vivas']);
    $crias_mortas = $crias_nascidas - $crias_vivas;
    $dificuldade_parto = $_POST['dificuldade_parto'];
    $observacoes = !empty($_POST['observacoes']) ? "'" . escapeString($_POST['observacoes']) . "'" : "NULL";
    
    // Dados das crias (se houver)
    $crias = [];
    for ($i = 1; $i <= $crias_nascidas; $i++) {
        if (isset($_POST["cria_brinco_$i"]) && !empty($_POST["cria_brinco_$i"])) {
            $crias[] = [
                'brinco' => escapeString($_POST["cria_brinco_$i"]),
                'sexo' => $_POST["cria_sexo_$i"],
                'peso' => floatval($_POST["cria_peso_$i"]),
                'viva' => isset($_POST["cria_viva_$i"]) ? 1 : 0
            ];
        }
    }
    
    if ($id_bovino_femea <= 0 || empty($data_parto) || $crias_nascidas <= 0) {
        $error = 'Preencha todos os campos obrigatórios.';
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Registrar parto
            $sql = "INSERT INTO reproducao (
                    id_fazenda, id_bovino_femea, data_evento, tipo_evento,
                    data_parto, crias_nascidas, crias_vivas, crias_mortas,
                    dificuldade_parto, observacoes
                ) VALUES (
                    $farmId, $id_bovino_femea, '$data_parto', 'parto',
                    '$data_parto', $crias_nascidas, $crias_vivas, $crias_mortas,
                    '$dificuldade_parto', $observacoes
                )";
            
            if (!$conn->query($sql)) {
                throw new Exception('Erro ao registrar parto.');
            }
            
            // Registrar as crias como novos bovinos
            foreach ($crias as $cria) {
                $brinco = $cria['brinco'];
                $sexo = $cria['sexo'];
                $peso = $cria['peso'] ?: 'NULL';
                $viva = $cria['viva'] ? 1 : 0;
                
                // Verificar se brinco já existe
                $checkSql = "SELECT id FROM bovinos WHERE " . TenantManager::addTenantFilter() . " AND brinco = '$brinco'";
                $checkResult = $conn->query($checkSql);
                
                if ($checkResult && $checkResult->num_rows > 0) {
                    throw new Exception("Brinco $brinco já existe para outra cria.");
                }
                
                // Buscar situação padrão (No rebanho)
                $sitSql = "SELECT id FROM situacoes WHERE id_fazenda = $farmId AND padrao = 1 LIMIT 1";
                $sitResult = $conn->query($sitSql);
                $situacao_id = $sitResult->fetch_assoc()['id'];
                
                // Inserir cria
                $sqlCria = "INSERT INTO bovinos (
                    id_fazenda, brinco, sexo, data_nascimento, peso_nascimento,
                    id_mae, id_situacao, origem, data_entrada, ativo
                ) VALUES (
                    $farmId, '$brinco', '$sexo', '$data_parto', $peso,
                    $id_bovino_femea, $situacao_id, 'nascido', '$data_parto', $viva
                )";
                
                if (!$conn->query($sqlCria)) {
                    throw new Exception('Erro ao registrar cria: ' . $conn->error);
                }
            }
            
            // Atualizar matriz
            $sqlMatriz = "UPDATE matrizes SET 
                         data_ultimo_parto = '$data_parto',
                         numero_partos = numero_partos + 1,
                         total_crias = total_crias + $crias_nascidas,
                         status_reprodutivo = 'lactacao'
                         WHERE id_bovino = $id_bovino_femea";
            
            if (!$conn->query($sqlMatriz)) {
                // Se não existir, inserir
                $sqlInsert = "INSERT INTO matrizes (id_bovino, data_ultimo_parto, numero_partos, total_crias, status_reprodutivo) 
                              VALUES ($id_bovino_femea, '$data_parto', 1, $crias_nascidas, 'lactacao')";
                $conn->query($sqlInsert);
            }
            
            $conn->commit();
            
            setAlert('Parto registrado com sucesso!', 'success');
            redirect('partos.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-egg me-2 text-success"></i>
                Registrar Parto
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Reprodução</a></li>
                    <li class="breadcrumb-item active">Novo Parto</li>
                </ol>
            </nav>
        </div>
        <a href="partos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Matriz *</label>
                        <select class="form-select" name="id_bovino_femea" id="matriz" required>
                            <option value="">Selecione</option>
                            <?php if ($femeas && $femeas->num_rows > 0): ?>
                                <?php while ($f = $femeas->fetch_assoc()): ?>
                                <option value="<?php echo $f['id']; ?>" 
                                        data-previsto="<?php echo $f['data_prevista_parto']; ?>">
                                    <?php echo $f['brinco']; ?> - <?php echo $f['nome'] ?: 'Sem nome'; ?>
                                    <?php if ($f['data_prevista_parto']): ?>
                                        (Previsto: <?php echo date('d/m/Y', strtotime($f['data_prevista_parto'])); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Data do Parto *</label>
                        <input type="date" class="form-control" name="data_parto" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Nº de Crias *</label>
                        <input type="number" class="form-control" name="crias_nascidas" 
                               id="crias_nascidas" value="1" min="1" max="3" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Crias Vivas *</label>
                        <input type="number" class="form-control" name="crias_vivas" 
                               id="crias_vivas" value="1" min="0" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Dificuldade do Parto</label>
                        <select class="form-select" name="dificuldade_parto">
                            <option value="normal">Normal</option>
                            <option value="dificil">Difícil</option>
                            <option value="cesariana">Cesariana</option>
                        </select>
                    </div>
                </div>

                <hr>
                <h6 class="mb-3">Registro das Crias</h6>
                
                <div id="crias-container">
                    <!-- Será preenchido via JavaScript -->
                </div>

                <hr class="my-4">

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="partos.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Registrar Parto
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function atualizarCrias() {
    var numCrias = parseInt(document.getElementById('crias_nascidas').value) || 0;
    var container = document.getElementById('crias-container');
    var vivas = parseInt(document.getElementById('crias_vivas').value) || 0;
    
    var html = '';
    for (var i = 1; i <= numCrias; i++) {
        var isViva = i <= vivas;
        html += `
            <div class="card mb-3 bg-light">
                <div class="card-body">
                    <h6>Cria ${i}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Brinco</label>
                            <input type="text" class="form-control" name="cria_brinco_${i}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sexo</label>
                            <select class="form-select" name="cria_sexo_${i}" required>
                                <option value="M">Macho</option>
                                <option value="F">Fêmea</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" step="0.1" class="form-control" name="cria_peso_${i}">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="cria_viva_${i}" 
                                       id="cria_viva_${i}" ${isViva ? 'checked' : ''}>
                                <label class="form-check-label" for="cria_viva_${i}">
                                    Viva
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    container.innerHTML = html;
}

document.getElementById('crias_nascidas').addEventListener('change', atualizarCrias);
document.getElementById('crias_vivas').addEventListener('change', atualizarCrias);

// Inicializar
atualizarCrias();

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