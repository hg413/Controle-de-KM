<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../models/AbastecimentoModel.php';

$model = new AbastecimentoModel($conn);

function respond(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(["message" => $message]);
}

function getInput(): object
{
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

function handleGet(AbastecimentoModel $model): void
{
    http_response_code(200);
    echo json_encode($model->getAll());
}

function handlePost(AbastecimentoModel $model): void
{
    global $conn;
    $data = getInput();

    $v_id = $data->veiculo_id ?? null;
    $m_id = $data->motorista_id ?? null;
    $data_ab = $data->data_abastecimento ?? null;
    $km = $data->km_atual ?? null;
    $litros = $data->litros ?? null;
    $valor = $data->valor_total ?? null;

    if (!$v_id || !$m_id || !$data_ab || !$km || !$litros || !$valor) {
        respond(400, "Dados obrigatórios faltando.");
        return;
    }

    if ($model->create($v_id, $data_ab, $km, $litros, $valor, $data->posto ?? null)) {
        respond(201, "Abastecimento registrado com sucesso.");
    } else {
        respond(503, "Erro ao registrar: " . $conn->error);
    }
}

function handleDelete(AbastecimentoModel $model): void
{
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    if ($model->remove($id)) {
        respond(200, "Abastecimento removido com sucesso.");
    } else {
        respond(503, "Não foi possível remover.");
    }
}

$handlers = [
    'GET' => 'handleGet',
    'POST' => 'handlePost',
    'DELETE' => 'handleDelete',
];

$method = $_SERVER['REQUEST_METHOD'];
if (isset($handlers[$method])) {
    $handlers[$method]($model);
} else {
    respond(405, "Método não permitido.");
}
