<?php
// Define cabeçalho para permitir requisições de outras origens (CORS)
header("Access-Control-Allow-Origin: *");
// Define que a resposta será no formato JSON
header("Content-Type: application/json; charset=UTF-8");
// Permite apenas o método POST para este endpoint de login
header("Access-Control-Allow-Methods: POST");

// Verifica se o método de requisição é diferente de POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Retorna erro 405 (Método Não Permitido) se não for POST
    http_response_code(405);
    // Exibe mensagem de erro em formato JSON
    echo json_encode(["message" => "Método não permitido"]);
    // Encerra a execução do script
    exit();
}

// Inclui o arquivo de conexão com o banco de dados e obtém a instância em $conn
$conn = require_once __DIR__ . '/../database/connection.php';

// Captura e decodifica o corpo da requisição JSON enviada pelo frontend
$data = json_decode(file_get_contents("php://input"));

// Verifica se os campos obrigatórios 'email' e 'senha' foram fornecidos
if (!empty($data->email) && !empty($data->senha)) {

    // Define a query SQL para buscar o usuário através do e-mail informado
    $sql = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?";
    // Prepara a instrução SQL para evitar injeção de dados maliciosos
    $stmt = $conn->prepare($sql);
    // Vincula o e-mail recebido ao placeholder "?" da query
    $stmt->bind_param("s", $data->email);
    // Executa a busca no banco de dados
    $stmt->execute();
    // Obtém o conjunto de resultados da execução
    $result = $stmt->get_result();

    // Verifica se pelo menos um usuário foi encontrado com esse e-mail
    if ($result->num_rows > 0) {
        // Extrai os dados do usuário encontrado em um array associativo
        $usuario = $result->fetch_assoc();

        // Compara a senha informada com a senha armazenada no banco (comparação simples)
        if ($data->senha === $usuario['senha']) {
            // Define status 200 OK para login bem-sucedido
            http_response_code(200);
            // Retorna os dados resumidos do usuário para o frontend salvar na sessão
            echo json_encode([
                "message" => "Login realizado com sucesso",
                "usuario" => [
                    "id"     => $usuario['id'], // ID único do usuário
                    "nome"   => $usuario['nome'], // Nome completo
                    "perfil" => $usuario['tipo']   // Perfil de acesso (admin ou motorista)
                ]
            ]);
        } else {
            // Retorna erro 401 (Não Autorizado) se a senha estiver errada
            http_response_code(401);
            echo json_encode(["message" => "Senha incorreta."]);
        }
    } else {
        // Retorna erro 404 (Não Encontrado) se o e-mail não estiver cadastrado
        http_response_code(404);
        echo json_encode(["message" => "Usuário não encontrado."]);
    }

} else {
    // Retorna erro 400 (Requisição Inválida) se faltar e-mail ou senha
    http_response_code(400);
    echo json_encode(["message" => "Email e senha são obrigatórios."]);
}
?>

