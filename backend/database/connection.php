<?php
// Define o endereço do servidor de banco de dados (normalmente localhost para XAMPP)
$host = "localhost";
// Define o nome de usuário do banco de dados (o padrão do root no XAMPP é 'root')
$user = "root";
// Define a senha de acesso ao banco de dados (no seu caso, lima-^123)
$password = "lima-^123";
// Define o nome do banco de dados que o sistema irá utilizar (sistema-partum)
$database = "sistema-partum";
// Define a porta padrão do MySQL (3306)
$port = 3306;

// Cria uma nova instância de conexão com o banco de dados utilizando a classe nativa MySQLi do PHP
$conn = new mysqli($host, $user, $password, $database, $port);

// Verifica se houve alguma falha no processo de conexão com o banco
if ($conn->connect_error) {
    // Interrompe a execução do script e exibe a mensagem de erro detalhada
    die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
}

// Configura o conjunto de caracteres da conexão para utf8mb4, garantindo suporte a acentos e emojis
$conn->set_charset("utf8mb4");

// Retorna a variável de conexão para que outros arquivos que incluírem este possam utilizá-la
return $conn;
?>

