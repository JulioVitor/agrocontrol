<?php
// modules/fazendas/editar.php
// Editar dados da fazenda

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../config/tenant.php';

// Verificar login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'modules/auth/login.php');
}

$userId = $_SESSION['usuario_id'];
$pageTitle = 'Editar Fazenda';
$error = '';
$success = '';

// Verificar se recebeu ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setAlert('ID inválido.', 'danger');
    redirect('index.php');
}

// Verificar se o usuário tem permissão (apenas proprietário pode editar)
$checkSql = "SELECT f.*, ufp.papel 
             FROM fazendas f
             INNER JOIN fazenda_usuarios ufp ON f.id = ufp.id_fazenda
             WHERE f.id = $id AND ufp.id_usuario = $userId AND ufp.ativo = 1";
$result = executeQuery($checkSql);

if (!$result || $result->num_rows == 0) {
    setAlert('Fazenda não encontrada ou você não tem permissão.', 'danger');
    redirect('index.php');
}

$fazenda = $result->fetch_assoc();

// Verificar se é proprietário (só proprietário pode editar)
if ($fazenda['papel'] != 'proprietario') {
    setAlert('Apenas o proprietário pode editar a fazenda.', 'danger');
    redirect('visualizar.php?id=' . $id);
}

// Buscar planos disponíveis
$planosSql = "SELECT * FROM planos WHERE ativo = 1 ORDER BY preco_mensal";
$planos = executeQuery($planosSql);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome_fazenda = escapeString(trim($_POST['nome_fazenda']));
    $cidade = escapeString(trim($_POST['cidade']));
    $estado = escapeString(trim($_POST['estado']));
    $area_total = !empty($_POST['area_total']) ? floatval($_POST['area_total']) : 'NULL';
    $plano_id = intval($_POST['plano_id']);
    
    if (empty($nome_fazenda)) {
        $error = 'Nome da fazenda é obrigatório.';
    } else {
        
        $sql = "UPDATE fazendas SET 
                nome_fazenda = '$nome_fazenda',
                cidade = " . ($cidade ? "'$cidade'" : "NULL") . ",
                estado = " . ($estado ? "'$estado'" : "NULL") . ",
                area_total_hectares = $area_total,
                id_plano = $plano_id
                WHERE id = $id";
        
        if (executeQuery($sql)) {
            $success = 'Fazenda atualizada com sucesso!';
            
            // Atualizar dados da sessão se for a fazenda ativa
            if ($id == getActiveFarmId()) {
                $_SESSION['fazenda_ativa_nome'] = $nome_fazenda;
            }
            
            // Redirecionar após 2 segundos
            header("refresh:2;url=visualizar.php?id=$id");
        } else {
            $error = 'Erro ao atualizar fazenda. Tente novamente.';
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
            <i class="bi bi-pencil me-2 text-warning"></i>
            Editar Fazenda: <?php echo $fazenda['nome_fazenda']; ?>
        </h1>
        <div>
            <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                <i class="bi bi-eye me-2"></i>
                Visualizar
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
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
                               value="<?php echo $fazenda['nome_fazenda']; ?>">
                        <div class="invalid-feedback">Informe o nome da fazenda</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Plano</label>
                        <select class="form-select" name="plano_id">
                            <?php if ($planos && $planos->num_rows > 0): ?>
                                <?php while ($plano = $planos->fetch_assoc()): ?>
                                <option value="<?php echo $plano['id']; ?>" 
                                    <?php echo ($fazenda['id_plano'] == $plano['id']) ? 'selected' : ''; ?>>
                                    <?php echo $plano['nome_plano']; ?> - R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?>/mês
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control" name="cidade"
                               value="<?php echo $fazenda['cidade'] ?: ''; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">UF</label>
                        <select class="form-select" name="estado">
                            <option value="">Selecione</option>
                            <?php
                            $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                            foreach ($estados as $uf) {
                                $selected = ($fazenda['estado'] == $uf) ? 'selected' : '';
                                echo "<option value=\"$uf\" $selected>$uf</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Área Total (hectares)</label>
                        <input type="number" step="0.01" class="form-control" name="area_total"
                               value="<?php echo $fazenda['area_total_hectares'] ?: ''; ?>">
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-2"></i>
                        Salvar Alterações
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