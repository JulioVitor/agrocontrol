<?php
// modules/configuracao/api/viacep.php
// API para buscar endereço por CEP

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$cep = isset($_GET['cep']) ? preg_replace('/[^0-9]/', '', $_GET['cep']) : '';

if (strlen($cep) != 8) {
    echo json_encode(['erro' => 'CEP inválido']);
    exit;
}

// Consultar ViaCEP
$url = "https://viacep.com.br/ws/{$cep}/json/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>