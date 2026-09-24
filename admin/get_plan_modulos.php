<?php
// modules/admin/get_plan_modulos.php
// API para buscar módulos de um plano

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once 'auth.php'; 

header('Content-Type: application/json');

$plano_id = isset($_GET['plano_id']) ? intval($_GET['plano_id']) : 0;

if ($plano_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id_modulo FROM plano_modulos WHERE id_plano = $plano_id";
$result = executeQuery($sql);
$modulos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modulos[] = $row['id_modulo'];
    }
}

echo json_encode($modulos);
?>