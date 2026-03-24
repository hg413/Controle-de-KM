<?php
$host = "localhost";
$user = "root";
$password = "lima-^123";
$database = "sistema-partum";
$port = 3306;

// Cria a conexão com o banco de dados
$conn = new mysqli($host, $user, $password, $database, $port);

// Verifica se houve erro de conexão
if ($conn->connect_error) {
    die("Erro ao conectar: " . $conn->connect_error);
}

// Configura o charset para evitar problemas de acentuação
$conn->set_charset("utf8mb4");

return $conn;
