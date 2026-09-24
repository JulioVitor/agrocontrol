<?php
// modules/auth/register.php
// Registro de nova fazenda (cria usuário e tenant) - VERSÃO ATUALIZADA

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    redirect(BASE_URL . 'modules/dashboard/index.php');
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($step == 1) {
        // Passo 1: Dados do usuário
        $nome = escapeString(trim($_POST['nome']));
        $email = escapeString(trim($_POST['email']));
        $telefone = escapeString(trim($_POST['telefone']));
        $senha = $_POST['senha'];
        $senha_confirm = $_POST['senha_confirm'];
        
        // Validações
        if (empty($nome) || empty($email) || empty($senha)) {
            $error = 'Todos os campos são obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido.';
        } elseif (strlen($senha) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } elseif ($senha !== $senha_confirm) {
            $error = 'As senhas não conferem.';
        } else {
            // Verificar se e-mail já existe
            $sql = "SELECT id FROM usuarios WHERE email = '$email'";
            $result = executeQuery($sql);
            
            if ($result && $result->num_rows > 0) {
                $error = 'Este e-mail já está cadastrado.';
            } else {
                // Salvar dados na sessão
                $_SESSION['register'] = [
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'senha' => password_hash($senha, PASSWORD_DEFAULT)
                ];
                
                // Ir para próximo passo
                redirect('register.php?step=2');
            }
        }
    } elseif ($step == 2) {
        // Passo 2: Dados da fazenda e escolha do plano
        $nome_fazenda = escapeString(trim($_POST['nome_fazenda']));
        $cnpj = escapeString(trim($_POST['cnpj']));
        $cidade = escapeString(trim($_POST['cidade']));
        $estado = escapeString(trim($_POST['estado']));
        $plano_id = intval($_POST['plano_id']);
        
        if (empty($nome_fazenda) || empty($plano_id)) {
            $error = 'Nome da fazenda e plano são obrigatórios.';
        } else {
            // Verificar se tem dados do usuário na sessão
            if (!isset($_SESSION['register'])) {
                redirect('register.php?step=1');
            }
            
            $userData = $_SESSION['register'];
            
            // Iniciar transação
            $conn->begin_transaction();
            
            try {
                // 1. Inserir usuário
                $sql = "INSERT INTO usuarios (nome, email, telefone, senha, email_confirmado, nivel) 
                        VALUES (
                            '{$userData['nome']}', 
                            '{$userData['email']}', 
                            " . ($userData['telefone'] ? "'{$userData['telefone']}'" : "NULL") . ", 
                            '{$userData['senha']}', 
                            0,
                            'usuario'
                        )";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Erro ao criar usuário: " . $conn->error);
                }
                
                $usuario_id = $conn->insert_id;
                
                // 2. Buscar dados do plano selecionado
                $sqlPlano = "SELECT * FROM planos WHERE id = $plano_id";
                $resultPlano = $conn->query($sqlPlano);
                $plano = $resultPlano->fetch_assoc();
                
                // 3. Criar fazenda
                $status = 'trial'; // Começa como trial
                $data_ativacao = date('Y-m-d');
                $data_expiracao = date('Y-m-d', strtotime('+30 days')); // 30 dias de trial
                
                $sql = "INSERT INTO fazendas (
                            id_proprietario, id_plano, nome_fazenda, cnpj, 
                            cidade, estado, status, data_ativacao, data_expiracao
                        ) VALUES (
                            $usuario_id, $plano_id, '$nome_fazenda', " . 
                            ($cnpj ? "'$cnpj'" : "NULL") . ", " .
                            ($cidade ? "'$cidade'" : "NULL") . ", " .
                            ($estado ? "'$estado'" : "NULL") . ",
                            '$status', '$data_ativacao', '$data_expiracao'
                        )";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Erro ao criar fazenda: " . $conn->error);
                }
                
                $fazenda_id = $conn->insert_id;
                
                // 4. Vincular usuário à fazenda (como proprietário)
                $sql = "INSERT INTO fazenda_usuarios (id_fazenda, id_usuario, papel) 
                        VALUES ($fazenda_id, $usuario_id, 'proprietario')";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Erro ao vincular usuário: " . $conn->error);
                }
                
                // 5. Criar configurações padrão
                $configs = [
                    'idioma' => 'pt_BR',
                    'timezone' => 'America/Sao_Paulo',
                    'moeda' => 'BRL',
                    'formato_data' => 'd/m/Y'
                ];
                
                foreach ($configs as $chave => $valor) {
                    $sql = "INSERT INTO fazenda_configuracoes (id_fazenda, chave, valor, tipo) 
                            VALUES ($fazenda_id, '$chave', '$valor', 'texto')";
                    $conn->query($sql); // Não precisa verificar erro aqui
                }
                
                // 6. Inserir situações padrão
                $situacoes = [
                    ['No rebanho', 'success', 1, 1],
                    ['Vendido', 'warning', 2, 0],
                    ['Morto', 'danger', 3, 0],
                    ['Abatido', 'secondary', 4, 0]
                ];
                
                foreach ($situacoes as $sit) {
                    $sql = "INSERT INTO situacoes (id_fazenda, nome, cor, ordem, padrao) 
                            VALUES ($fazenda_id, '{$sit[0]}', '{$sit[1]}', {$sit[2]}, {$sit[3]})";
                    $conn->query($sql);
                }
                
                // 7. Inserir categorias financeiras padrão baseadas no plano
                $categorias = [
                    ['Venda de Gado', 'receita', 'success'],
                    ['Venda de Leite', 'receita', 'success'],
                    ['Compra de Gado', 'despesa', 'danger'],
                    ['Ração e Suplementos', 'despesa', 'danger'],
                    ['Medicamentos', 'despesa', 'warning'],
                    ['Mão de Obra', 'despesa', 'info']
                ];
                
                foreach ($categorias as $cat) {
                    $sql = "INSERT INTO categorias_financeiras (id_fazenda, nome_categoria, tipo, cor) 
                            VALUES ($fazenda_id, '{$cat[0]}', '{$cat[1]}', '{$cat[2]}')";
                    $conn->query($sql);
                }
                
                // Commit
                $conn->commit();
                
                // Limpar sessão de registro
                unset($_SESSION['register']);
                
                // Fazer login automático
                $_SESSION['usuario_id'] = $usuario_id;
                $_SESSION['usuario_nome'] = $userData['nome'];
                $_SESSION['usuario_email'] = $userData['email'];
                $_SESSION['usuario_nivel'] = 'usuario';
                
                // Setar tenant
                $_SESSION['fazenda_ativa_id'] = $fazenda_id;
                $_SESSION['fazenda_ativa_nome'] = $nome_fazenda;
                
                $success = 'Cadastro realizado com sucesso! Redirecionando...';
                
                // Redirecionar após 3 segundos
                header("refresh:3;url=" . BASE_URL . "modules/dashboard/index.php");
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

// Buscar planos disponíveis para o step 2
if ($step == 2) {
    $sql = "SELECT * FROM planos WHERE ativo = 1 ORDER BY ordem";
    $planos = executeQuery($sql);
}

$pageTitle = 'Criar Nova Conta';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AgroControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            max-width: 900px;
            width: 100%;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 30px;
            text-align: center;
        }
        
        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 20px;
            position: relative;
        }
        
        .step.active {
            background: #28a745;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step::before {
            content: '';
            position: absolute;
            width: 60px;
            height: 2px;
            background: #e9ecef;
            right: 40px;
            top: 19px;
        }
        
        .step:first-child::before {
            display: none;
        }
        
        .plan-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
            position: relative;
        }
        
        .plan-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .plan-card.selected {
            border-color: #28a745;
            background: #f0fff0;
        }
        
        .plan-card.destaque {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .plan-card.destaque .price {
            color: white;
        }
        
        .plan-card .price {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        
        .plan-card.destaque .price {
            color: white;
        }
        
        .plan-card .badge-destaque {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #ffc107;
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin-top: 15px;
        }
        
        .plan-features li {
            padding: 5px 0;
        }
        
        .plan-features i {
            color: #28a745;
            margin-right: 10px;
        }
        
        .plan-card.destaque .plan-features i {
            color: white;
        }
        
        .limit-badge {
            background: rgba(0,0,0,0.1);
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tree-fill display-4"></i>
                <h3 class="mt-2">AgroControl</h3>
                <p class="mb-0">Crie sua conta e comece a gerenciar sua fazenda</p>
            </div>
            
            <div class="card-body p-4">
                <!-- Steps -->
                <div class="steps">
                    <div class="step <?php echo $step >= 1 ? 'completed' : ''; ?>">1</div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                </div>
                
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
                
                <?php if ($step == 1): ?>
                <!-- Step 1: Dados do Usuário -->
                <form method="POST" action="">
                    <h5 class="mb-3">Dados Pessoais</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome completo *</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control phone-mask" name="telefone">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Senha *</label>
                        <input type="password" class="form-control" name="senha" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Confirmar senha *</label>
                        <input type="password" class="form-control" name="senha_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 py-2">
                        Continuar <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>
                
                <?php elseif ($step == 2): ?>
                <!-- Step 2: Dados da Fazenda e Escolha do Plano -->
                <form method="POST" action="">
                    <h5 class="mb-3">Dados da Fazenda</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Fazenda *</label>
                        <input type="text" class="form-control" name="nome_fazenda" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">CNPJ/CPF</label>
                        <input type="text" class="form-control cnpj-mask" name="cnpj">
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UF</label>
                            <select class="form-select" name="estado">
                                <option value="">Selecione</option>
                                <?php
                                $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                foreach ($estados as $uf) {
                                    echo "<option value=\"$uf\">$uf</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Escolha seu plano</h5>
                    
                    <?php if ($planos && $planos->num_rows > 0): ?>
                        <?php while ($plano = $planos->fetch_assoc()): 
                            $isDestaque = $plano['destaque'] == 1;
                        ?>
                        <div class="plan-card <?php echo $isDestaque ? 'destaque' : ''; ?>" onclick="selectPlan(<?php echo $plano['id']; ?>)">
                            <?php if ($isDestaque): ?>
                                <div class="badge-destaque">MAIS VENDIDO</div>
                            <?php endif; ?>
                            
                            <input type="radio" name="plano_id" value="<?php echo $plano['id']; ?>" 
                                   id="plano_<?php echo $plano['id']; ?>" class="d-none" 
                                   <?php echo $isDestaque ? 'checked' : ''; ?>>
                            
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-1"><?php echo $plano['nome_plano']; ?></h4>
                                    <p class="mb-2"><?php echo $plano['descricao']; ?></p>
                                </div>
                                <div class="price">
                                    R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?><small>/mês</small>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-4 text-center">
                                    <small>Animais</small>
                                    <h6><?php echo $plano['max_animais'] ? 'Até ' . $plano['max_animais'] : 'Ilimitado'; ?></h6>
                                </div>
                                <div class="col-4 text-center">
                                    <small>Usuários</small>
                                    <h6><?php echo $plano['max_usuarios'] ? 'Até ' . $plano['max_usuarios'] : 'Ilimitado'; ?></h6>
                                </div>
                                <div class="col-4 text-center">
                                    <small>Propriedades</small>
                                    <h6><?php echo $plano['max_propriedades'] ? 'Até ' . $plano['max_propriedades'] : 'Ilimitado'; ?></h6>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="row">
                                <div class="col-12">
                                    <strong>Funcionalidades:</strong>
                                </div>
                            </div>
                            
                            <?php
                            // Buscar módulos do plano
                            $modSql = "SELECT m.* FROM modulos m
                                      JOIN plano_modulos pm ON m.id = pm.id_modulo
                                      WHERE pm.id_plano = " . $plano['id'] . "
                                      ORDER BY m.ordem";
                            $modulos = executeQuery($modSql);
                            
                            if ($modulos && $modulos->num_rows > 0):
                            ?>
                            <div class="row mt-2">
                                <?php while ($mod = $modulos->fetch_assoc()): ?>
                                <div class="col-md-6">
                                    <small>
                                        <i class="bi bi-check-circle-fill text-<?php echo $isDestaque ? 'white' : 'success'; ?> me-1"></i>
                                        <?php echo $mod['nome_modulo']; ?>
                                    </small>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success w-100 py-2">
                            Criar minha conta <i class="bi bi-check-circle ms-2"></i>
                        </button>
                    </div>
                    
                    <p class="text-center text-muted small mt-3">
                        Ao criar sua conta, você concorda com nossos 
                        <a href="#" class="text-decoration-none">Termos de Uso</a> e 
                        <a href="#" class="text-decoration-none">Política de Privacidade</a>.
                    </p>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <p class="mb-0">
                        Já tem uma conta? 
                        <a href="login.php" class="text-decoration-none">Fazer login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscaras
        $('.phone-mask').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            if (value.length > 11) value = value.substr(0,11);
            
            if (value.length > 6) {
                value = '(' + value.substr(0,2) + ') ' + value.substr(2,5) + '-' + value.substr(7);
            } else if (value.length > 2) {
                value = '(' + value.substr(0,2) + ') ' + value.substr(2);
            }
            $(this).val(value);
        });
        
        $('.cnpj-mask').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            if (value.length > 14) value = value.substr(0,14);
            
            if (value.length > 12) {
                value = value.substr(0,2) + '.' + value.substr(2,3) + '.' + value.substr(5,3) + '/' + value.substr(8,4) + '-' + value.substr(12);
            } else if (value.length > 8) {
                value = value.substr(0,2) + '.' + value.substr(2,3) + '.' + value.substr(5,3) + '/' + value.substr(8);
            } else if (value.length > 5) {
                value = value.substr(0,2) + '.' + value.substr(2,3) + '.' + value.substr(5);
            } else if (value.length > 2) {
                value = value.substr(0,2) + '.' + value.substr(2);
            }
            $(this).val(value);
        });
        
        // Selecionar plano
        function selectPlan(planId) {
            $('.plan-card').removeClass('selected');
            $(`#plano_${planId}`).closest('.plan-card').addClass('selected');
            $(`#plano_${planId}`).prop('checked', true);
        }
        
        // Selecionar plano destaque por padrão
        $(document).ready(function() {
            $('.plan-card.destaque').addClass('selected');
        });
    </script>
</body>
</html>