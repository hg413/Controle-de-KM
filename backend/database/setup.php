<?php
// Exibe erros para facilitar o debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Setup do Banco de Dados - Controle KM</h1>";

$host = "localhost";
$user = "root";
$password = "lima-^123";
$port = 3306;

// 1. Conecta sem selecionar banco para criar o banco se não existir
$conn = new mysqli($host, $user, $password, "", $port);
if ($conn->connect_error) {
    die("❌ Erro ao conectar ao MySQL: " . $conn->connect_error . "<br>Verifique se o MySQL está rodando e se a senha no arquivo está correta.");
}

$db_name = "sistema-partum";

$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
if ($conn->query($sql_create_db) === TRUE) {
    echo "✅ Banco de dados `$db_name` verificado/criado com sucesso.<br>";
} else {
    die("❌ Erro ao criar banco de dados: " . $conn->error);
}

// 2. Seleciona o banco
$conn->select_db($db_name);

// 3. Criação da tabela de Usuários
$sql_usuarios = "
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'motorista') DEFAULT 'motorista',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

if ($conn->query($sql_usuarios) === TRUE) {
    echo "✅ Tabela `usuarios` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `usuarios`: " . $conn->error . "<br>";
}

// 4. Criação da tabela de Veículos
$sql_veiculos = "
CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) NOT NULL UNIQUE,
    motorista_responsavel_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (motorista_responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL
);";

if ($conn->query($sql_veiculos) === TRUE) {
    echo "✅ Tabela `veiculos` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `veiculos`: " . $conn->error . "<br>";
}

// 6. Criação da tabela de Abastecimentos
$sql_abastecimentos = "
CREATE TABLE IF NOT EXISTS abastecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    motorista_id INT NOT NULL,
    data_abastecimento DATE NOT NULL,
    km_atual INT NOT NULL,
    litros DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    posto VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (motorista_id) REFERENCES usuarios(id) ON DELETE CASCADE
);";

if ($conn->query($sql_abastecimentos) === TRUE) {
    echo "✅ Tabela `abastecimentos` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `abastecimentos`: " . $conn->error . "<br>";
}

// 7. Criação da tabela de Manutenções
$sql_manutencoes = "
CREATE TABLE IF NOT EXISTS manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    data_manutencao DATE NOT NULL,
    descricao TEXT NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    km_veiculo INT,
    tipo ENUM('preventiva', 'corretiva') DEFAULT 'preventiva',
    realizada_por VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);";

if ($conn->query($sql_manutencoes) === TRUE) {
    echo "✅ Tabela `manutencoes` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `manutencoes`: " . $conn->error . "<br>";
}

// 5. Inserir usuário Admin padrão caso não exista
$sql_check_admin = "SELECT id FROM usuarios WHERE email = 'admin@frota.com'";
$result = $conn->query($sql_check_admin);

if ($result->num_rows == 0) {
    // Atenção: No nosso backend de login.php ele não usa hash no momento, compara a senha direta
    // Para simplificar agora, insere direto. Depois recomenda-se usar password_hash()
    $sql_insert_admin = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES ('Administrador Geral', 'admin@frota.com', '123123', 'admin')";
    if ($conn->query($sql_insert_admin) === TRUE) {
        echo "✅ Usuário administrador `admin@frota.com` criado com sucesso (Senha: 123123).<br>";
    } else {
        echo "❌ Erro ao criar admin: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Usuário `admin@frota.com` já existe.<br>";
}

$conn->close();

echo "<h3>🎉 Setup finalizado! Você já pode acessar o sistema e fazer login com admin@frota.com e senha 123123</h3>";
?>
