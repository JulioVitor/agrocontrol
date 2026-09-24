<?php
// modules/fazendas/cadastrar.php
// Cadastro de nova fazenda

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Nova Fazenda';
$error = '';
$success = '';

// Buscar planos disponíveis
$planosSql = "SELECT * FROM planos WHERE ativo = 1 ORDER BY preco_mensal";
$planos = executeQuery($planosSql);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome_fazenda = escapeString(trim($_POST['nome_fazenda']));
    $cidade = escapeString(trim($_POST['cidade']));
    $estado = escapeString(trim($_POST['estado']));
    $area_total = !empty($_POST['area_total']) ? floatval($_POST['area_total']) : 'NULL';
    $plano_id = !empty($_POST['plano_id']) ? intval($_POST['plano_id']) : 1;
    
    if (empty($nome_fazenda)) {
        $error = 'Nome da fazenda é obrigatório.';
    } else {
        
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // Inserir fazenda
            $sql = "INSERT INTO fazendas (id_proprietario, id_plano, nome_fazenda, cidade, estado, area_total_hectares, status, data_ativacao) 
                    VALUES ($userId, $plano_id, '$nome_fazenda', " . ($cidade ? "'$cidade'" : "NULL") . ", " . ($estado ? "'$estado'" : "NULL") . ", $area_total, 'ativo', CURDATE())";
            
            if (!$conn->query($sql)) {
                throw new Exception("Erro ao criar fazenda: " . $conn->error);
            }
            
            $fazenda_id = $conn->insert_id;
            
            // Vincular usuário como proprietário
            $sql2 = "INSERT INTO fazenda_usuarios (id_fazenda, id_usuario, papel) VALUES ($fazenda_id, $userId, 'proprietario')";
            
            if (!$conn->query($sql2)) {
                throw new Exception("Erro ao vincular usuário: " . $conn->error);
            }
            
            // Criar situações padrão para a fazenda
            $situacoes = [
                ['No rebanho', 'success', 1, 1],
                ['Vendido', 'warning', 2, 0],
                ['Morto', 'danger', 3, 0],
                ['Abatido', 'secondary', 4, 0]
            ];
            
            foreach ($situacoes as $sit) {
                $sql3 = "INSERT INTO situacoes (id_fazenda, nome, cor, ordem, padrao) 
                         VALUES ($fazenda_id, '{$sit[0]}', '{$sit[1]}', {$sit[2]}, {$sit[3]})";
                $conn->query($sql3);
            }
            
            // Commit
            $conn->commit();
            
            // Selecionar esta fazenda como ativa
            TenantManager::setActiveFarm($fazenda_id);
            
            $success = 'Fazenda cadastrada com sucesso!';
            
            // Redirecionar após 2 segundos
            header("refresh:2;url=index.php");
            
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
        <h1 class="h2">
            <i class="bi bi-plus-circle me-2 text-success"></i>
            Nova Fazenda
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
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome da Fazenda *</label>
                        <input type="text" class="form-control" name="nome_fazenda" required 
                               value="<?php echo isset($_POST['nome_fazenda']) ? htmlspecialchars($_POST['nome_fazenda']) : ''; ?>">
                        <div class="invalid-feedback">Informe o nome da fazenda</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Plano</label>
                        <select class="form-select" name="plano_id">
                            <?php if ($planos && $planos->num_rows > 0): ?>
                                <?php while ($plano = $planos->fetch_assoc()): ?>
                                <option value="<?php echo $plano['id']; ?>" 
                                    <?php echo (isset($_POST['plano_id']) && $_POST['plano_id'] == $plano['id']) ? 'selected' : ''; ?>>
                                    <?php echo $plano['nome_plano']; ?> - R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?>/mês
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control" name="cidade"
                               value="<?php echo isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">UF</label>
                        <select class="form-select" name="estado">
                            <option value="">Selecione</option>
                            <?php
                            $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                            foreach ($estados as $uf) {
                                $selected = (isset($_POST['estado']) && $_POST['estado'] == $uf) ? 'selected' : '';
                                echo "<option value=\"$uf\" $selected>$uf</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Área Total (hectares)</label>
                        <input type="number" step="0.01" class="form-control" name="area_total"
                               value="<?php echo isset($_POST['area_total']) ? $_POST['area_total'] : ''; ?>">
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>
                        Cadastrar Fazenda
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