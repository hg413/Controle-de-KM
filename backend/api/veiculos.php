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
require_once __DIR__ . '/../models/VeiculosModel.php';

$veiculoModel = new VeiculoModel($conn);

// -- Helpers --

function respond(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(["message" => $message]);
}

function getInput(): object
{
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

// -- Handlers --

function handleGet(VeiculoModel $model): void
{
    http_response_code(200);
    echo json_encode($model->getAll());
}

function handlePost(VeiculoModel $model): void
{
    global $conn;
    $data = getInput();
    $placa = $data->placa ?? null;

    if (!$placa) {
        respond(400, "A placa é obrigatória.");
        return;
    }

    $motorista_id = $data->motorista_responsavel_id ?? null;
    
    if ($model->create($placa, $motorista_id)) {
        respond(201, "Veículo cadastrado com sucesso.");
    } else {
        respond(503, "Não foi possível cadastrar o veículo: " . $conn->error);
    }
}

function handleDelete(VeiculoModel $model): void
{
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    if ($model->remove($id)) {
        respond(200, "Veículo excluído com sucesso.");
    } else {
        respond(503, "Não foi possível excluir o veículo.");
    }
}

// -- Roteamento --

$handlers = [
    'GET' => 'handleGet',
    'POST' => 'handlePost',
    'DELETE' => 'handleDelete',
];

$method = $_SERVER['REQUEST_METHOD'];
if (isset($handlers[$method])) {
    $handlers[$method]($veiculoModel);
} else {
    respond(405, "Método não permitido.");
}