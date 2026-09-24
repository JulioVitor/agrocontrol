<?php

define('DB_HOST', 'localhost');
define('DB_USER','');
define('DB_PASSWORD', '');
define('DB_NAME', 'agrocontrol');

// Cria a conexão com o banco de dados usando MySQLi (orientado a objetos)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

// Define o charset para UTF-8 (evita problemas com acentos)
$conn->set_charset("utf8");

// Função para facilitar a consulta ao banco (opcional mas útil)
function executeQuery($sql) {
    global $conn;
    $result = $conn->query($sql);
    return $result;
}

// Função para escapar strings e evitar SQL Injection
function escapeString($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// Função para obter o último ID inserido
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

// Inicia a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
