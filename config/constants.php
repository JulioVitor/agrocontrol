<?php
// config/constants.php
// Constantes do sistema

// Caminhos do sistema
define('BASE_URL', 'http://localhost/AgroControl/');  // Altere conforme sua URL
define('ROOT_PATH', dirname(__DIR__) . '/');          // Caminho absoluto no servidor

// Configurações gerais
define('SITE_NAME', 'AgroControl');
define('SITE_VERSION', '1.0.0');

// Configurações de upload
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB em bytes

// Configurações de sessão
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos
?>