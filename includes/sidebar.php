<?php
// includes/sidebar.php
// Barra lateral de navegação - VERSÃO COM SUBMENU DE VACINAS

// Verificar permissões (se necessário)
$farmId = getActiveFarmId();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];
?>

<style>
    /* CSS para corrigir a sidebar */
    html, body {
        height: 100%;
        margin: 0;
        overflow-x: hidden;
    }

    .wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
    }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
        color: #fff;
        transition: all 0.3s;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    #sidebar .sidebar-header {
        padding: 20px;
        background: rgba(0,0,0,0.2);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    #sidebar .sidebar-header h3 {
        color: #fff;
        margin: 0;
        font-size: 1.5rem;
    }

    #sidebar .sidebar-header small {
        color: rgba(255,255,255,0.7);
        font-size: 0.8rem;
    }

    #sidebar ul.components {
        padding: 20px 0;
    }

    #sidebar ul li {
        padding: 0;
    }

    #sidebar ul li a {
        padding: 12px 20px;
        display: block;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }

    #sidebar ul li a:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
        border-left-color: #28a745;
    }

    #sidebar ul li.active > a {
        background: linear-gradient(90deg, #28a745 0%, transparent 100%);
        color: #fff;
        border-left-color: #fff;
    }

    #sidebar ul li a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    /* Estilo para submenu */
    #sidebar ul li .submenu {
        list-style: none;
        padding-left: 35px;
        display: none;
    }

    #sidebar ul li .submenu.show {
        display: block;
    }

    #sidebar ul li .submenu li a {
        padding: 8px 20px;
        font-size: 0.85rem;
    }

    #sidebar ul li .dropdown-toggle::after {
        content: "\f282";
        font-family: bootstrap-icons;
        float: right;
        margin-top: 3px;
    }

    #sidebar .farm-info {
        padding: 15px 20px;
        background: rgba(0,0,0,0.2);
        margin: 10px 0;
        border-radius: 0;
        border-left: 3px solid #28a745;
    }

    #sidebar .farm-info .farm-name {
        font-weight: bold;
        color: #fff;
        margin-bottom: 5px;
    }

    #sidebar .farm-info .farm-id {
        color: rgba(255,255,255,0.6);
        font-size: 0.8rem;
    }

    #sidebar .sidebar-footer {
        padding: 20px;
        background: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.1);
        position: sticky;
        bottom: 0;
        width: 100%;
    }

    /* Sidebar mobile */
    #sidebarCollapse {
        display: none;
    }

    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px;
            position: fixed;
            z-index: 999;
            height: 100vh;
        }
        #sidebar.active {
            margin-left: 0;
        }
        #sidebarCollapse {
            display: block;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            background: #28a745;
            color: white;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
    }

    #content {
        width: 100%;
        min-height: 100vh;
        transition: all 0.3s;
        background-color: #f8f9fa;
    }

    #sidebar::-webkit-scrollbar {
        width: 5px;
    }
    #sidebar::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.1);
    }
    #sidebar::-webkit-scrollbar-thumb {
        background: #28a745;
        border-radius: 5px;
    }
    #sidebar::-webkit-scrollbar-thumb:hover {
        background: #218838;
    }
</style>

<!-- Sidebar para desktop -->
<div class="wrapper">
    <nav id="sidebar">
        <!-- Cabeçalho da sidebar -->
        <div class="sidebar-header text-center">
            <i class="bi bi-tree-fill fs-1 text-success"></i>
            <h3>AgroControl</h3>
            <small>Gestão Rural</small>
        </div>

        <!-- Informação da fazenda ativa -->
        <?php if ($farmId): ?>
            <div class="farm-info">
                <div class="farm-name">
                    <i class="bi bi-house-door-fill me-2 text-success"></i>
                    <?php echo TenantManager::getActiveFarmName(); ?>
                </div>
                <div class="farm-id">
                    <i class="bi bi-hash me-1"></i> ID: <?php echo $farmId; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Menu principal -->
        <ul class="list-unstyled components">
            <li class="<?php echo strpos($currentPath, 'dashboard') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'bovinos') !== false && strpos($currentPath, 'acoes') === false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/bovinos/index.php">
                    <i class="bi bi-tree"></i> Bovinos
                </a>
            </li>

            <!-- SUBMENU DE VACINAS -->
            <li class="<?php echo strpos($currentPath, 'vacinas') !== false ? 'active' : ''; ?>">
                <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#vacinasSubmenu" aria-expanded="false">
                    <i class="bi bi-shield-check"></i> Vacinas
                </a>
                <ul class="submenu collapse <?php echo strpos($currentPath, 'vacinas') !== false ? 'show' : ''; ?>" id="vacinasSubmenu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/vacinas/index.php" class="<?php echo strpos($currentPath, 'acoes/vacinas/index') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-list-ul"></i> Histórico de Vacinas
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/vacinas/cadastrar.php" class="<?php echo strpos($currentPath, 'acoes/vacinas/cadastrar') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-plus-circle"></i> Nova Aplicação
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/vacinas/calendario.php" class="<?php echo strpos($currentPath, 'acoes/vacinas/calendario') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-check"></i> Calendário de Doses
                        </a>
                    </li>
                </ul>
            </li>

            <li class="<?php echo strpos($currentPath, 'leite') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/leite/dashboard.php">
                    <i class="bi bi-cup-fill"></i> Produção de Leite
                </a>
            </li>

            <!-- SUBMENU DE PESAGENS -->
            <li class="<?php echo strpos($currentPath, 'pesagem') !== false ? 'active' : ''; ?>">
                <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#pesagensSubmenu" aria-expanded="false">
                    <i class="bi bi-bar-chart"></i> Pesagens
                </a>
                <ul class="submenu collapse <?php echo strpos($currentPath, 'pesagem') !== false ? 'show' : ''; ?>" id="pesagensSubmenu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/pesagem/index.php" class="<?php echo strpos($currentPath, 'acoes/pesagem/index') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-list-ul"></i> Histórico de Pesagens
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/pesagem/cadastrar.php" class="<?php echo strpos($currentPath, 'acoes/pesagem/cadastrar') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-plus-circle"></i> Nova Pesagem
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>modules/bovinos/acoes/pesagem/graficos.php" class="<?php echo strpos($currentPath, 'acoes/pesagem/graficos') !== false ? 'active' : ''; ?>">
                            <i class="bi bi-graph-up"></i> Gráficos de Peso
                        </a>
                    </li>
                </ul>
            </li>

            <li class="<?php echo strpos($currentPath, 'estoque') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/estoque/index.php">
                    <i class="bi bi-box-seam"></i> Estoque
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'pastejos') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/pastejos/index.php">
                    <i class="bi bi-map"></i> Pastagens
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'reproducao') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/reproducao/index.php">
                    <i class="bi bi-heart"></i> Reprodução
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'financeiro') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/financeiro/index.php">
                    <i class="bi bi-cash-stack"></i> Financeiro
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'relatorios') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/relatorios/index.php">
                    <i class="bi bi-file-text"></i> Relatórios
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'calendario') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/calendario/index.php">
                    <i class="bi bi-calendar"></i> Calendário
                </a>
            </li>
        </ul>

        <!-- Menu de configurações -->
        <ul class="list-unstyled components">
            <li class="nav-divider">
                <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 20px;">
            </li>

            <li class="<?php echo strpos($currentPath, 'fazendas') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/fazendas/index.php">
                    <i class="bi bi-building"></i> Minhas Fazendas
                </a>
            </li>

            <li class="<?php echo strpos($currentPath, 'configuraco') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/configuracao/index.php">
                    <i class="bi bi-gear"></i> Configurações
                </a>
            </li>

            <li>
                <a href="#" data-bs-toggle="modal" data-bs-target="#ajudaModal">
                    <i class="bi bi-question-circle"></i> Ajuda
                </a>
            </li>
        </ul>

        <!-- Rodapé da sidebar -->
        <div class="sidebar-footer text-center">
            <small class="text-white-50">
                <i class="bi bi-tree-fill text-success"></i>
                AgroControl v1.0<br>
                &copy; <?php echo date('Y'); ?>
            </small>
        </div>
    </nav>

    <!-- Botão de toggle para mobile -->
    <button type="button" id="sidebarCollapse" class="btn d-md-none">
        <i class="bi bi-list"></i>
    </button>

    <!-- Conteúdo principal -->
    <div id="content">
        <!-- O conteúdo virá aqui -->
        <!-- Isso será fechado no footer.php -->