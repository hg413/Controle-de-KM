<?php
// Define cabeçalhos para permitir acesso de diferentes origens (CORS), essencial para o frontend se comunicar com o backend
header("Access-Control-Allow-Origin: *");
// Define que o conteúdo retornado por esta API será sempre no formato JSON com codificação UTF-8
header("Content-Type: application/json; charset=UTF-8");
// Define quais métodos HTTP são permitidos para este endpoint
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
// Define quais cabeçalhos personalizados são permitidos nas requisições vindas do cliente
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Trata a requisição do tipo OPTIONS (pre-flight) enviada por navegadores para verificar permissões de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Retorna status 200 OK e encerra a execução para requisições de verificação
    http_response_code(200);
    exit();
}

// Inclui o arquivo de conexão com o banco de dados e armazena a instância em $conn
$conn = require_once __DIR__ . '/../database/connection.php';
// Inclui a definição da classe AbastecimentoModel para manipulação dos dados de abastecimento
require_once __DIR__ . '/../models/AbastecimentoModel.php';

// Instancia o modelo de abastecimento injetando a conexão com o banco
$model = new AbastecimentoModel($conn);

// Função auxiliar para enviar respostas JSON padronizadas com um código de status HTTP e uma mensagem
function respond(int $code, string $message): void
{
    // Define o código de resposta HTTP (ex: 200, 400, 500)
    http_response_code($code);
    // Converte um array associativo em uma string JSON e a imprime na tela
    echo json_encode(["message" => $message]);
}

// Função para capturar e decodificar os dados enviados no corpo da requisição (JSON)
function getInput(): object
{
    // Lê o fluxo de entrada bruta (php://input) e decodifica o JSON para um objeto PHP
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

// Função para lidar com requisições do tipo GET (buscar dados)
function handleGet(AbastecimentoModel $model): void
{
    // Define status 200 OK
    http_response_code(200);
    // Busca todos os registros através do model e os retorna como JSON
    echo json_encode($model->getAll());
}

// Função para lidar com requisições do tipo POST (inserir novos dados)
function handlePost(AbastecimentoModel $model): void
{
    // Torna a variável de conexão global acessível dentro da função
    global $conn;
    // Captura os dados enviados pelo frontend
    $data = getInput();

    // Extrai os campos individuais do objeto de dados, usando null se não existirem
    $v_id = $data->veiculo_id ?? null;
    $m_id = $data->motorista_id ?? null;
    $data_ab = $data->data_abastecimento ?? null;
    $km = $data->km_atual ?? null;
    $litros = $data->litros ?? null;
    $valor = $data->valor_total ?? null;

    // Validação básica: verifica se todos os campos obrigatórios foram preenchidos
    if (!$v_id || !$m_id || !$data_ab || !$km || !$litros || !$valor) {
        // Retorna erro 400 (Bad Request) caso falte algum dado
        respond(400, "Dados obrigatórios faltando.");
        return;
    }

    // Tenta criar o registro de abastecimento usando o método do model
    if ($model->create($v_id, $m_id, $data_ab, $km, $litros, $valor, $data->posto ?? null)) {
        // Retorna sucesso 201 (Created)
        respond(201, "Abastecimento registrado com sucesso.");
    } else {
        // Retorna erro 503 (Service Unavailable) caso a query falhe
        respond(503, "Erro ao registrar: " . $conn->error);
    }
}

// Função para lidar com requisições do tipo DELETE (remover registros)
function handleDelete(AbastecimentoModel $model): void
{
    // Captura os dados (o ID pode vir no corpo da requisição ou na URL)
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    // Verifica se o ID do registro a ser removido foi fornecido
    if (!$id) {
        // Retorna erro 400 caso o ID esteja ausente
        respond(400, "ID não fornecido.");
        return;
    }

    // Tenta remover o registro através do model
    if ($model->remove($id)) {
        // Retorna sucesso 200 OK
        respond(200, "Abastecimento removido com sucesso.");
    } else {
        // Retorna erro 503 se houver falha na remoção
        respond(503, "Não foi possível remover.");
    }
}

// Mapeamento dos métodos HTTP para suas respectivas funções de tratamento
$handlers = [
    'GET' => 'handleGet',
    'POST' => 'handlePost',
    'DELETE' => 'handleDelete',
];

// Captura o método utilizado na requisição atual (ex: GET, POST, etc)
$method = $_SERVER['REQUEST_METHOD'];
// Verifica se existe um handler definido para o método recebido
if (isset($handlers[$method])) {
    // Chama a função correspondente passando o model como argumento
    $handlers[$method]($model);
} else {
    // Caso o método não seja suportado, retorna erro 405 (Method Not Allowed)
    respond(405, "Método não permitido.");
}
?>

