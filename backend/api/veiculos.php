<?php
// Habilita o acesso de diferentes origens (CORS) para integrar o frontend com o backend
header("Access-Control-Allow-Origin: *");
// Define que o conteúdo de resposta será no formato JSON com codificação UTF-8
header("Content-Type: application/json; charset=UTF-8");
// Lista os métodos HTTP permitidos para as operações com veículos
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
// Especifica os cabeçalhos permitidos nas requisições do cliente
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Trata requisições OPTIONS (pré-verificação de CORS enviada pelo navegador)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Retorna status 200 OK para confirmar a disponibilidade do endpoint
    http_response_code(200);
    // Encerra a execução sem processar lógica adicional
    exit();
}

// Estabelece a conexão com o banco de dados carregando o arquivo de conexão
$conn = require_once __DIR__ . '/../database/connection.php';
// Inclui o modelo que contém a lógica de banco de dados para a entidade Veículos
require_once __DIR__ . '/../models/VeiculosModel.php';

// Cria uma instância de VeiculoModel injetando a conexão MySQL
$veiculoModel = new VeiculoModel($conn);

// -- Funções de Utilidade (Helpers) --

// Função para padronizar o envio de respostas JSON com código de status e mensagem
function respond(int $code, string $message): void
{
    // Define o código de resposta HTTP
    http_response_code($code);
    // Codifica a mensagem em JSON e a exibe
    echo json_encode(["message" => $message]);
}

// Função para capturar e converter o corpo da requisição JSON em um objeto PHP
function getInput(): object
{
    // Lê o fluxo de entrada bruta do PHP e decodifica o JSON
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

// -- Funções de Tratamento de Requisições (Handlers) --

// Lida com a requisição GET para listar todos os veículos
function handleGet(VeiculoModel $model): void
{
    // Define status 200 OK
    http_response_code(200);
    // Retorna o resultado da busca de todos os veículos no formato JSON
    echo json_encode($model->getAll());
}

// Lida com a requisição POST para cadastrar um novo veículo
function handlePost(VeiculoModel $model): void
{
    // Torna a variável de conexão global disponível para depuração de erros se necessário
    global $conn;
    // Captura os dados enviados pelo cliente
    $data = getInput();
    // Obtém a placa do veículo (campo obrigatório)
    $placa = $data->placa ?? null;

    // Verifica se a placa foi informada
    if (!$placa) {
        // Se não houver placa, retorna erro 400 (Bad Request)
        respond(400, "A placa é obrigatória.");
        return;
    }

    // Obtém o nome do modelo, se houver
    $modelo = $data->modelo ?? null;
    $motorista_id = $data->motorista_responsavel_id ?? null;
    
    // Tenta realizar o cadastro do veículo através do model
    if ($model->create($placa, $motorista_id, $modelo)) {
        // Sucesso: retorna status 201 (Created)
        respond(201, "Veículo cadastrado com sucesso.");
    } else {
        // Falha: retorna erro 503 com detalhes do erro SQL
        respond(503, "Não foi possível cadastrar o veículo: " . $conn->error);
    }
}

// Lida com a requisição DELETE para remover um veículo do sistema
function handleDelete(VeiculoModel $model): void
{
    // Busca os dados da requisição
    $data = getInput();
    // Tenta identificar o ID do veículo por parâmetro na URL ou pelo corpo do JSON
    $id = $_GET['id'] ?? $data->id ?? null;

    // Bloqueia a operação se o ID não for fornecido
    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    // Tenta remover o veículo através do model
    if ($model->remove($id)) {
        // Sucesso: retorna status 200 OK
        respond(200, "Veículo excluído com sucesso.");
    } else {
        // Falha: retorna erro 503 (Serviço Indisponível)
        respond(503, "Não foi possível excluir o veículo.");
    }
}

// -- Mecanismo de Roteamento Simples --

// Associa os métodos HTTP às suas respectivas funções de tratamento
$handlers = [
    'GET' => 'handleGet',
    'POST' => 'handlePost',
    'DELETE' => 'handleDelete',
];

// Identifica qual método HTTP está sendo usado na chamada atual
$method = $_SERVER['REQUEST_METHOD'];
// Verifica se o método é suportado pelo nosso roteador
if (isset($handlers[$method])) {
    // Chama o handler correspondente passando a instância do model
    $handlers[$method]($veiculoModel);
} else {
    // Caso contrário, informa que o método não é permitido
    respond(405, "Método não permitido.");
}
?>