<?php
// Habilita o compartilhamento de recursos entre origens diferentes (CORS)
header("Access-Control-Allow-Origin: *");
// Define que a saída será um arquivo JSON formatado em UTF-8
header("Content-Type: application/json; charset=UTF-8");
// Lista os métodos HTTP que esta API aceita para a gestão de usuários
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// Especifica os cabeçalhos que o cliente pode enviar na requisição
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Responde a requisições de pré-verificação do navegador (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Retorna status 200 OK para confirmar que o endpoint está disponível
    http_response_code(200);
    // Finaliza a execução para não processar nada além da verificação
    exit();
}

// Carrega a conexão com o banco de dados
$conn = require_once __DIR__ . '/../database/connection.php';
// Carrega a classe de modelo que contém a lógica de banco de dados para usuários
require_once __DIR__ . '/../models/UsuarioModel.php';

// Cria uma nova instância do modelo de usuário passando a conexão ativa
$usuarioModel = new UsuarioModel($conn);

// -- Funções Auxiliares (Helpers) --

// Função para padronizar as respostas de erro ou sucesso em JSON
function respond(int $code, string $message): void
{
    // Define o código de status HTTP da resposta
    http_response_code($code);
    // Transforma a mensagem em um objeto JSON e imprime
    echo json_encode(["message" => $message]);
}

// Função para obter os dados enviados no corpo (payload) da requisição
function getInput(): object
{
    // Lê o conteúdo bruto da entrada do PHP e decodifica o JSON para um objeto
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

// -- Funções de Tratamento (Handlers) --

// Lida com a busca de usuários (GET)
function handleGet(UsuarioModel $model): void
{
    // Define status 200 OK
    http_response_code(200);
    // Retorna todos os usuários cadastrados em formato JSON
    echo json_encode($model->getAll());
}

// Lida com a criação de novos usuários (POST)
function handlePost(UsuarioModel $model): void
{
    // Torna a variável de conexão global disponível nesta função
    global $conn;
    // Captura os dados enviados pelo cliente
    $data = getInput();

    // Valida se todos os campos necessários para o cadastro foram enviados
    if (empty($data->nome) || empty($data->email) || empty($data->senha) || empty($data->perfil)) {
        // Se faltar algo, retorna erro 400 (Bad Request)
        respond(400, "Dados incompletos. Nome, email, senha e perfil são requeridos.");
        return;
    }

    // Tenta inserir o novo usuário no banco de dados através do model
    if ($model->create($data->nome, $data->email, $data->senha, $data->perfil)) {
        // Se der certo, retorna status 201 (Created)
        respond(201, "Usuário criado com sucesso.");
    } else {
        // Se falhar, retorna erro 503 com a descrição do erro do banco
        respond(503, "Não foi possível criar o usuário: " . $conn->error);
    }
}

// Lida com a atualização de usuários existentes (PUT)
function handlePut(UsuarioModel $model): void
{
    // Torna a variável de conexão global disponível
    global $conn;
    // Captura os dados da requisição
    $data = getInput();

    // Valida se os campos básicos de identificação e alteração foram enviados
    if (empty($data->id) || empty($data->nome) || empty($data->email) || empty($data->perfil)) {
        respond(400, "Dados incompletos.");
        return;
    }

    // A senha é opcional na atualização (se vazia, mantém a atual)
    $senha = $data->senha ?? null;
    // Tenta realizar a atualização no banco de dados
    if ($model->update($data->id, $data->nome, $data->email, $data->perfil, $senha)) {
        respond(200, "Usuário atualizado com sucesso.");
    } else {
        respond(503, "Não foi possível atualizar o usuário: " . $conn->error);
    }
}

// Lida com a exclusão de usuários (DELETE)
function handleDelete(UsuarioModel $model): void
{
    // Captura os dados
    $data = getInput();
    // O ID pode vir como parâmetro na URL (?id=...) ou no corpo do JSON
    $id = $_GET['id'] ?? $data->id ?? null;

    // Verifica se o ID do usuário foi informado
    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    // Tenta remover o usuário através do model
    if ($model->remove($id)) {
        respond(200, "Usuário excluído com sucesso.");
    } else {
        respond(503, "Não foi possível excluir o usuário.");
    }
}

// -- Roteamento da API --

// Mapeia cada método HTTP para sua função de tratamento correspondente
$handlers = [
    'GET'    => 'handleGet',
    'POST'   => 'handlePost',
    'PUT'    => 'handlePut',
    'DELETE' => 'handleDelete',
];

// Obtém o método da requisição atual
$method = $_SERVER['REQUEST_METHOD'];
// Verifica se o método recebido é suportado pela API
if (isset($handlers[$method])) {
    // Executa a função mapeada passando o model como parâmetro
    $handlers[$method]($usuarioModel);
} else {
    // Se o método não for suportado (ex: PATCH), retorna erro 405
    respond(405, "Método não permitido.");
}
?>