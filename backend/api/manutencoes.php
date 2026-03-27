<?php
// Configura o cabeçalho para permitir requisições de outras origens (CORS)
header("Access-Control-Allow-Origin: *");
// Define que a resposta será enviada no formato JSON com codificação UTF-8
header("Content-Type: application/json; charset=UTF-8");
// Lista os métodos HTTP que podem ser utilizados neste endpoint
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
// Especifica os cabeçalhos permitidos nas requisições vindas do navegador
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Trata requisições do tipo OPTIONS (pré-verificação automática do navegador para CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Retorna status 200 OK confirmando a permissão de acesso
    http_response_code(200);
    // Encerra a execução do script para esta fase de verificação
    exit();
}

// Carrega o arquivo de conexão com o banco de dados
$conn = require_once __DIR__ . '/../database/connection.php';
// Inclui o modelo que contém a lógica de banco de dados para manutenções
require_once __DIR__ . '/../models/ManutencaoModel.php';

// Cria uma nova instância do modelo de manutenção injetando a conexão ativa
$model = new ManutencaoModel($conn);

// -- Funções Auxiliares (Helpers) --

// Função para padronizar as respostas JSON com status HTTP e mensagem de texto
function respond(int $code, string $message): void
{
    // Define o código de status HTTP da resposta
    http_response_code($code);
    // Transforma a mensagem em JSON e a imprime para o cliente
    echo json_encode(["message" => $message]);
}

// Função para obter o corpo da requisição (JSON) e transformá-lo em um objeto PHP
function getInput(): object
{
    // Lê a entrada bruta do PHP e decodifica o JSON; retorna um objeto vazio se falhar
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

// -- Funções de Tratamento de Métodos (Handlers) --

// Lida com requisições GET para listar todas as manutenções cadastradas
function handleGet(ManutencaoModel $model): void
{
    // Define status 200 OK
    http_response_code(200);
    // Busca os dados através do model e retorna a lista completa em formato JSON
    echo json_encode($model->getAll());
}

// Lida com requisições POST para registrar uma nova manutenção de veículo
function handlePost(ManutencaoModel $model): void
{
    // Torna a variável de conexão global disponível nesta função
    global $conn;
    // Captura os dados enviados no corpo da requisição
    $data = getInput();

    // Extrai e valida os campos obrigatórios para o registro
    $v_id = $data->veiculo_id ?? null; // ID do veículo
    $data_man = $data->data_manutencao ?? null; // Data do serviço
    $desc = $data->descricao ?? null; // O que foi feito
    $valor = $data->valor_total ?? null; // Custo total

    // Verifica se algum campo essencial está ausente
    if (!$v_id || !$data_man || !$desc || !$valor) {
        // Retorna erro 400 (Bad Request) caso falte informação
        respond(400, "Dados obrigatórios faltando.");
        return;
    }

    // Captura campos opcionais ou com valor padrão
    $km_v = $data->km_veiculo ?? null; // KM do carro no momento
    $tipo = $data->tipo ?? 'preventiva'; // Tipo (preventiva ou corretiva)
    $realizada = $data->realizada_por ?? null; // Oficina responsável

    // Tenta registrar a manutenção no banco através do model
    if ($model->create($v_id, $data_man, $desc, $valor, $km_v, $tipo, $realizada)) {
        // Sucesso: retorna status 201 (Created)
        respond(201, "Manutenção registrada com sucesso.");
    } else {
        // Falha: retorna erro 503 com a descrição do erro do servidor MySQL
        respond(503, "Erro ao registrar: " . $conn->error);
    }
}

// Lida com requisições DELETE para remover um registro de manutenção específico
function handleDelete(ManutencaoModel $model): void
{
    // Captura os dados (o ID pode vir na URL ou no corpo do JSON)
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    // Bloqueia a execução se nenhum ID for informado
    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    // Tenta remover o registro no banco através do model
    if ($model->remove($id)) {
        // Sucesso: retorna status 200 OK
        respond(200, "Manutenção removida com sucesso.");
    } else {
        // Falha: retorna erro 503
        respond(503, "Não foi possível remover.");
    }
}

// -- Mecanismo de Roteamento Simples --

// Associa os métodos HTTP disponíveis às suas funções de execução
$handlers = [
    'GET' => 'handleGet',
    'POST' => 'handlePost',
    'DELETE' => 'handleDelete',
];

// Identifica qual método HTTP está sendo usado na chamada atual (GET, POST, etc)
$method = $_SERVER['REQUEST_METHOD'];
// Verifica se o método recebido é suportado pela API
if (isset($handlers[$method])) {
    // Executa o handler correspondente passando a instância do model
    $handlers[$method]($model);
} else {
    // Retorna erro 405 se o método for desconhecido (ex: PUT)
    respond(405, "Método não permitido.");
}
?>

