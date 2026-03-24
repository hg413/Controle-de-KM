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
require_once __DIR__ . '/../models/ManutencaoModel.php';

$model = new ManutencaoModel($conn);

function respond(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(["message" => $message]);
}

function getInput(): object
{
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

function handleGet(ManutencaoModel $model): void
{
    http_response_code(200);
    echo json_encode($model->getAll());
}

function handlePost(ManutencaoModel $model): void
{
    global $conn;
    $data = getInput();

    $v_id = $data->veiculo_id ?? null;
    $data_man = $data->data_manutencao ?? null;
    $desc = $data->descricao ?? null;
    $valor = $data->valor_total ?? null;

    if (!$v_id || !$data_man || !$desc || !$valor) {
        respond(400, "Dados obrigatórios faltando.");
        return;
    }

    $km_v = $data->km_veiculo ?? null;
    $tipo = $data->tipo ?? 'preventiva';
    $realizada = $data->realizada_por ?? null;

    if ($model->create($v_id, $data_man, $desc, $valor, $km_v, $tipo, $realizada)) {
        respond(201, "Manutenção registrada com sucesso.");
    } else {
        respond(503, "Erro ao registrar: " . $conn->error);
    }
}

function handleDelete(ManutencaoModel $model): void
{
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    if ($model->remove($id)) {
        respond(200, "Manutenção removida com sucesso.");
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
