<?php
// includes/header.php
// Cabeçalho padrão do sistema
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- DataTables (para tabelas) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <style>
    /* Ajustes gerais */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    </style>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png">

    <!-- PWA Manifest -->
<link rel="manifest" href="<?php echo BASE_URL; ?>manifest.json">
<meta name="theme-color" content="#28a745">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="AgroControl">
<link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/img/icon-192x192.png">

<!-- Service Worker -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?php echo BASE_URL; ?>assets/js/sw.js')
            .then(function(registration) {
                console.log('Service Worker registrado com sucesso:', registration.scope);
            })
            .catch(function(error) {
                console.log('Falha ao registrar Service Worker:', error);
            });
    });
}

// Instalar PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar botão de instalação
    const installBtn = document.getElementById('installPwaBtn');
    if (installBtn) {
        installBtn.style.display = 'block';
        installBtn.addEventListener('click', () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('Usuário aceitou instalar o PWA');
                }
                deferredPrompt = null;
            });
        });
    }
});
</script>


</head>
<body>

<!-- Navbar superior -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
    <div class="container-fluid">
        <!-- Botão para sidebar no mobile -->
        <button class="btn btn-outline-light me-2 d-lg-none" type="button" id="sidebarCollapseBtn">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Logo -->
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>modules/dashboard/index.php">
            <i class="bi bi-tree-fill me-2"></i>
            <strong>AgroControl</strong>
        </a>
        
        <!-- Seletor de Fazenda (apenas para usuários logados) -->
        <?php if (isLoggedIn()): ?>
        <div class="mx-auto d-none d-md-block">
            <?php include 'farm_selector.php'; ?>
        </div>
        <?php endif; ?>
        
        <!-- Menu do usuário -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo $_SESSION['usuario_nome'] ?? 'Usuário'; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <li>
                    <h6 class="dropdown-header">
                        <i class="bi bi-person"></i> Minha Conta
                    </h6>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/configuracao/perfil.php">
                        <i class="bi bi-gear me-2"></i> Meu Perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/fazendas/index.php">
                        <i class="bi bi-building me-2"></i> Minhas Fazendas
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>modules/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Seletor de Fazenda para mobile -->
<?php if (isLoggedIn()): ?>
<div class="d-md-none p-2 bg-light border-bottom">
    <?php include 'farm_selector.php'; ?>
</div>
<?php endif; ?>

<!-- Início do wrapper (aberto no header, fechado no footer) -->
<div class="wrapper">