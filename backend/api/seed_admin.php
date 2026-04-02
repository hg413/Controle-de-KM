<?php
// =========================================================
// seed_admin.php — Script Utilitário para Usuário Inicial
// =========================================================

// Importa a conexão com o banco de dados
$conn = require_once __DIR__ . '/../database/connection.php';

// Define as credenciais padrão do administrador principal
$nome  = 'Administrador';
$email = 'admin@controle.km';
$senha = 'admin123'; // Nota: Recomenda-se alterar esta senha após o primeiro acesso
$tipo  = 'admin';

// Prepara a query SQL para inserir o usuário admin na tabela 'usuarios'
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
// Associa as variáveis aos parâmetros da query (ss-ssss indica que todos são strings)
$stmt->bind_param("ssss", $nome, $email, $senha, $tipo);

// Define que a resposta será enviada no formato JSON para o navegador
header('Content-Type: application/json; charset=UTF-8');

// Executa a inserção e verifica o resultado
if ($stmt->execute()) {
    // Retorna sucesso e as credenciais para conferência
    echo json_encode(["ok" => true, "message" => "Admin criado! Email: $email | Senha: $senha"]);
} else {
    // Caso ocorra erro (ex: e-mail já existente), retorna a mensagem de falha do MySQL
    echo json_encode(["ok" => false, "error" => $conn->error]);
}

// Fecha a conexão do statement para liberar recursos do servidor
$stmt->close();
?>

