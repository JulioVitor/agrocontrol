<?php
// includes/functions.php
// Funções genéricas do sistema

// Redirecionar para uma URL
function redirect($url) {
    header("Location: " . $url);
    exit();
}


// Verificar se usuário é admin
function isAdmin() {
    return (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin');
}

// Exibir mensagens de alerta (para feedback ao usuário)
function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type // success, danger, warning, info
    ];
}

// Mostrar e limpar alerta
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['alert']);
    }
}

// Formatar data para o padrão brasileiro
function formatDateBR($date) {
    if (empty($date) || $date == '0000-00-00') return '';
    return date('d/m/Y', strtotime($date));
}

// Formatar data para o banco de dados (YYYY-MM-DD)
function formatDateDB($date) {
    if (empty($date)) return null;
    $date = explode('/', $date);
    if (count($date) == 3) {
        return $date[2] . '-' . $date[1] . '-' . $date[0];
    }
    return null;
}

// Formatar valor monetário
function formatMoney($value) {
    if (empty($value)) return 'R$ 0,00';
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Limpar CPF/CNPJ (deixar apenas números)
function cleanDocument($document) {
    return preg_replace('/[^0-9]/', '', $document);
}

// Gerar hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>