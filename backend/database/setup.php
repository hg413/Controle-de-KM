<?php
// Exibe todos os erros no navegador para facilitar a depuração durante o desenvolvimento
error_reporting(E_ALL);
// Habilita a exibição de erros na saída do script PHP
ini_set('display_errors', 1);

// Imprime um cabeçalho HTML indicando que o script de configuração começou
echo "<h1>Setup do Banco de Dados - Controle KM</h1>";

// Define o endereço do servidor de banco de dados (normalmente localhost para XAMPP)
$host = "localhost";
// Define o nome de usuário do banco de dados (o padrão do root no XAMPP é 'root')
$user = "root";
// Define a senha do banco de dados (no seu caso, lima-^123)
$password = "lima-^123";
// Define a porta padrão do MySQL (3306)
$port = 3306;

// 1. Tenta estabelecer uma conexão inicial com o MySQL sem especificar o banco de dados
$conn = new mysqli($host, $user, $password, "", $port);
// Verifica se a conexão falhou e interrompe o script exibindo uma mensagem de erro
if ($conn->connect_error) {
    die("❌ Erro ao conectar ao MySQL: " . $conn->connect_error . "<br>Verifique se o MySQL está rodando e se a senha no arquivo está correta.");
}

// Define o nome do banco de dados que será criado/utilizado pelo sistema
$db_name = "sistema-partum";

// Script SQL para criar o banco de dados caso ele não exista, usando charset utf8mb4 (suporta emojis)
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
// Executa a query de criação e verifica se foi bem-sucedida
if ($conn->query($sql_create_db) === TRUE) {
    echo "✅ Banco de dados `$db_name` verificado/criado com sucesso.<br>";
} else {
    // Interrompe o script se houver erro crítico na criação do banco
    die("❌ Erro ao criar banco de dados: " . $conn->error);
}

// 2. Seleciona o banco de dados recém-criado ou já existente para as próximas operações
$conn->select_db($db_name);

// 3. Script SQL para criar a tabela de 'usuarios' (administradores e motoristas)
$sql_usuarios = "
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Identificador único numérico que cresce automaticamente
    nome VARCHAR(100) NOT NULL, -- Nome do usuário (obrigatório)
    email VARCHAR(100) NOT NULL UNIQUE, -- E-mail único para login (não permite duplicatas)
    senha VARCHAR(255) NOT NULL, -- Senha armazenada (texto simples no momento)
    tipo ENUM('admin', 'motorista') DEFAULT 'motorista', -- Define o nível de acesso (padrão motorista)
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Data e hora de criação automática
);";

// Executa a criação da tabela de usuários e informa o resultado
if ($conn->query($sql_usuarios) === TRUE) {
    echo "✅ Tabela `usuarios` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `usuarios`: " . $conn->error . "<br>";
}

// 4. Script SQL para criar a tabela de 'veiculos'
$sql_veiculos = "
CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID único do veículo
    placa VARCHAR(20) NOT NULL UNIQUE, -- Placa do carro (única no sistema)
    motorista_responsavel_id INT, -- Referência ao ID do motorista associado
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data de cadastro
    FOREIGN KEY (motorista_responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL -- Chave estrangeira ligando ao usuario
);";

// Executa a criação da tabela de veículos
if ($conn->query($sql_veiculos) === TRUE) {
    echo "✅ Tabela `veiculos` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `veiculos`: " . $conn->error . "<br>";
}

// 6. Script SQL para criar a tabela de 'abastecimentos' que registra cada vez que um carro é abastecido
$sql_abastecimentos = "
CREATE TABLE IF NOT EXISTS abastecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID único do abastecimento
    veiculo_id INT NOT NULL, -- ID do veículo abastecido
    motorista_id INT NOT NULL, -- ID do motorista que realizou o abastecimento
    data_abastecimento DATE NOT NULL, -- Data em que ocorreu o abastecimento
    km_atual INT NOT NULL, -- Quilometragem registrada no painel no momento
    litros DECIMAL(10,2) NOT NULL, -- Quantidade de combustível abastecida
    valor_total DECIMAL(10,2) NOT NULL, -- Custo total do abastecimento
    posto VARCHAR(100), -- Nome do posto de combustível
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data do registro no sistema
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE, -- Link com a tabela veiculos
    FOREIGN KEY (motorista_id) REFERENCES usuarios(id) ON DELETE CASCADE -- Link com a tabela usuarios
);";

// Executa a criação da tabela de abastecimentos
if ($conn->query($sql_abastecimentos) === TRUE) {
    echo "✅ Tabela `abastecimentos` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `abastecimentos`: " . $conn->error . "<br>";
}

// 7. Script SQL para criar a tabela de 'manutencoes' para histórico de reparos e revisões
$sql_manutencoes = "
CREATE TABLE IF NOT EXISTS manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID único da manutenção
    veiculo_id INT NOT NULL, -- ID do veículo que passou por manutenção
    data_manutencao DATE NOT NULL, -- Data do serviço
    descricao TEXT NOT NULL, -- Descrição detalhada do que foi feito
    valor_total DECIMAL(10,2) NOT NULL, -- Custo total do serviço
    km_veiculo INT, -- KM do veículo na data da manutenção
    tipo ENUM('preventiva', 'corretiva') DEFAULT 'preventiva', -- Tipo de serviço realizado
    realizada_por VARCHAR(100), -- Nome da oficina ou mecânico
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Registro automático da data
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE -- Link com veículo
);";

// Executa a criação da tabela de manutenções
if ($conn->query($sql_manutencoes) === TRUE) {
    echo "✅ Tabela `manutencoes` verificada/criada com sucesso.<br>";
} else {
    echo "❌ Erro ao criar tabela `manutencoes`: " . $conn->error . "<br>";
}

// 5. Verifica se o usuário Administrador padrão já existe no banco
$sql_check_admin = "SELECT id FROM usuarios WHERE email = 'admin@frota.com'";
$result = $conn->query($sql_check_admin);

// Se o administrador não for encontrado no resultado da pesquisa
if ($result->num_rows == 0) {
    // Insere o usuário administrador mestre com credenciais padrões para o primeiro acesso
    $sql_insert_admin = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES ('Administrador Geral', 'admin@frota.com', '123123', 'admin')";
    if ($conn->query($sql_insert_admin) === TRUE) {
        echo "✅ Usuário administrador `admin@frota.com` criado com sucesso (Senha: 123123).<br>";
    } else {
        echo "❌ Erro ao criar admin: " . $conn->error . "<br>";
    }
} else {
    // Se o admin já estiver cadastrado, apenas informa que ele existe
    echo "ℹ️ Usuário `admin@frota.com` já existe.<br>";
}

// Encerra a conexão com o banco de dados MySQL para liberar recursos
$conn->close();

// Mensagem final informando que todo o processo foi concluído com sucesso
echo "<h3>🎉 Setup finalizado! Você já pode acessar o sistema e fazer login com admin@frota.com e senha 123123</h3>";
?>

