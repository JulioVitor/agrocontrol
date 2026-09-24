<?php
// modules/configuracao/index.php
// Página central de configurações

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

$pageTitle = 'Central de Configurações';

// Buscar dados da fazenda para exibir no cabeçalho
$sql = "SELECT * FROM fazendas WHERE id = $farmId";
$result = executeQuery($sql);
$fazenda = $result->fetch_assoc();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="col-lg-10 ms-auto px-4 py-3">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="bi bi-gear-wide-connected me-2 text-success"></i>
                Central de Configurações
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Configurações</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Informação da Fazenda Ativa -->
    <div class="alert alert-success mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-building fs-2 me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">Fazenda Ativa: <strong><?php echo $fazenda['nome_fazenda']; ?></strong></h5>
                <p class="mb-0">
                    <?php if ($fazenda['cidade'] && $fazenda['estado']): ?>
                        <i class="bi bi-geo-alt"></i> <?php echo $fazenda['cidade']; ?>/<?php echo $fazenda['estado']; ?> |
                    <?php endif; ?>
                    <i class="bi bi-calendar"></i> Cadastro: <?php echo date('d/m/Y', strtotime($fazenda['data_cadastro'])); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Cards de Configurações por Categoria -->
    <div class="row g-4">

        <!-- ============================================ -->
        <!-- SEÇÃO 1: DADOS DA FAZENDA -->
        <!-- ============================================ -->
        <div class="col-12">
            <h5 class="text-success border-bottom pb-2 mb-3">
                <i class="bi bi-building"></i> Dados da Fazenda
            </h5>
        </div>

        <!-- Card: Informações Gerais -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-info-circle fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Informações Gerais</h5>
                    <p class="card-text text-muted small">
                        Nome, CNPJ, contato e área total da fazenda
                    </p>
                    <a href="geral.php" class="btn btn-outline-success btn-sm stretched-link">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </div>
            </div>
        </div>

        <!-- Card: Endereço -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-geo-alt fs-1 text-info"></i>
                    </div>
                    <h5 class="card-title">Endereço</h5>
                    <p class="card-text text-muted small">
                        CEP, logradouro, cidade e estado da fazenda
                    </p>
                    <a href="endereco.php" class="btn btn-outline-info btn-sm stretched-link">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </div>
            </div>
        </div>

        <!-- Card: Configurações do Sistema -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-display fs-1 text-warning"></i>
                    </div>
                    <h5 class="card-title">Sistema</h5>
                    <p class="card-text text-muted small">
                        Idioma, formato de data e moeda
                    </p>
                    <a href="sistema.php" class="btn btn-outline-warning btn-sm stretched-link">
                        <i class="bi bi-pencil"></i> Configurar
                    </a>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SEÇÃO 2: CADASTROS BÁSICOS -->
        <!-- ============================================ -->
        <div class="col-12 mt-4">
            <h5 class="text-success border-bottom pb-2 mb-3">
                <i class="bi bi-database"></i> Cadastros Básicos
            </h5>
        </div>

        <!-- Card: Raças -->
        <div class="col-md-3">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <i class="bi bi-tags fs-1 text-primary"></i>
                    <h6 class="card-title mt-2">Raças</h6>
                    <p class="card-text text-muted small">Gerenciar raças dos animais</p>
                    <a href="racas.php" class="btn btn-outline-primary btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>

        <!-- Card: Situações -->
        <div class="col-md-3">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <i class="bi bi-emoji-neutral fs-1 text-secondary"></i>
                    <h6 class="card-title mt-2">Situações</h6>
                    <p class="card-text text-muted small">Status dos animais</p>
                    <a href="situacoes.php" class="btn btn-outline-secondary btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>

        <!-- Card: Vacinas -->
        <div class="col-md-3">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <i class="bi bi-shield fs-1 text-success"></i>
                    <h6 class="card-title mt-2">Vacinas</h6>
                    <p class="card-text text-muted small">Cadastro de vacinas</p>
                    <a href="vacinas.php" class="btn btn-outline-success btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>

        <!-- Card: Categorias Financeiras -->
        <div class="col-md-3">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack fs-1 text-warning"></i>
                    <h6 class="card-title mt-2">Categorias Financeiras</h6>
                    <p class="card-text text-muted small">Receitas e despesas</p>
                    <a href="categorias.php" class="btn btn-outline-warning btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- CATEGORIAS DO ESTOQUE -->
        <!-- ============================================ -->
        <div class="col-md-3">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam fs-1 text-info"></i>
                    <h6 class="card-title mt-2">Categorias do Estoque</h6>
                    <p class="card-text text-muted small">Organizar produtos e insumos</p>
                    <a href="../estoque/categorias.php" class="btn btn-outline-info btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SEÇÃO 3: USUÁRIOS E SEGURANÇA -->
        <!-- ============================================ -->
        <div class="col-12 mt-4">
            <h5 class="text-success border-bottom pb-2 mb-3">
                <i class="bi bi-people"></i> Usuários e Segurança
            </h5>
        </div>

        <!-- Card: Meu Perfil -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-person-circle fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Meu Perfil</h5>
                    <p class="card-text text-muted small">
                        Editar suas informações pessoais e alterar senha
                    </p>
                    <a href="perfil.php" class="btn btn-outline-primary btn-sm stretched-link">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </div>
            </div>
        </div>

        <!-- Card: Usuários da Fazenda -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm hover-card">
                <div class="card-body text-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-people fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Usuários</h5>
                    <p class="card-text text-muted small">
                        Gerenciar quem tem acesso à fazenda
                    </p>
                    <a href="usuarios.php" class="btn btn-outline-success btn-sm stretched-link">
                        <i class="bi bi-person-plus"></i> Gerenciar
                    </a>
                </div>
            </div>
        </div>

        <!-- Card: Logs de Atividades (admin) -->
        <?php
        $userId = $_SESSION['usuario_id'];
        $sqlAdmin = "SELECT nivel FROM usuarios WHERE id = $userId";
        $resultAdmin = executeQuery($sqlAdmin);
        $userAdmin = $resultAdmin->fetch_assoc();
        if ($userAdmin['nivel'] == 'admin'):
        ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card border-warning">
                    <div class="card-body text-center">
                        <div class="bg-secondary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                            <i class="bi bi-clock-history fs-1 text-secondary"></i>
                        </div>
                        <h5 class="card-title">Auditoria</h5>
                        <p class="card-text text-muted small">
                            Logs de atividades do sistema
                        </p>
                        <a href="auditoria.php" class="btn btn-outline-secondary btn-sm stretched-link">
                            <i class="bi bi-eye"></i> Visualizar
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================ -->
        <!-- SEÇÃO 4: MANUTENÇÃO (SOMENTE ADMIN) -->
        <!-- ============================================ -->
        <?php if ($userAdmin['nivel'] == 'admin'): ?>
            <div class="col-12 mt-4">
                <h5 class="text-success border-bottom pb-2 mb-3">
                    <i class="bi bi-tools"></i> Manutenção
                </h5>
            </div>

            <!-- Card: Backup -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm hover-card border-danger">
                    <div class="card-body text-center">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                            <i class="bi bi-database fs-1 text-danger"></i>
                        </div>
                        <h5 class="card-title">Backup do Sistema</h5>
                        <p class="card-text text-muted small">
                            Criar, baixar e restaurar backups do banco de dados
                        </p>
                        <a href="backup.php" class="btn btn-outline-danger btn-sm stretched-link">
                            <i class="bi bi-cloud-arrow-down"></i> Gerenciar Backups
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card: Informações do Sistema -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                            <i class="bi bi-info-circle fs-1 text-info"></i>
                        </div>
                        <h5 class="card-title">Informações do Sistema</h5>
                        <p class="card-text text-muted small">
                            Versão, banco de dados e ambiente
                        </p>
                        <a href="info.php" class="btn btn-outline-info btn-sm stretched-link">
                            <i class="bi bi-eye"></i> Visualizar
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rodapé com atalhos -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-arrow-return-left text-success"></i>
                            <a href="../dashboard/index.php" class="text-decoration-none">Voltar ao Dashboard</a>
                        </div>
                        <div>
                            <span class="text-muted small">
                                <i class="bi bi-tree-fill text-success"></i> AgroControl v1.0
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .hover-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<?php include '../../includes/footer.php'; ?>