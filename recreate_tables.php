<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', 'lima-^123', 'sistema-partum');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Força MyISAM para InnoDB se tiverem sido criadas antes do default
$conn->query("ALTER TABLE usuarios ENGINE=InnoDB");
$conn->query("ALTER TABLE veiculos ENGINE=InnoDB");

// Desabilita checagem de chave estrangeira temporariamente
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$conn->query("DROP TABLE IF EXISTS manutencoes");
$conn->query("DROP TABLE IF EXISTS abastecimentos");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Inclui o setup nativo da aplicação para recriar as tabelas corretamente
require_once __DIR__ . '/backend/database/setup.php';

echo "Database fixed!";
?>
