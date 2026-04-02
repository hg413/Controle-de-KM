<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$conn = require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../models/OcorrenciaModel.php';
$model = new OcorrenciaModel($conn);

function respond(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["message" => $message]);
}

function getInput(): object {
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

function handleGet(OcorrenciaModel $model): void {
    http_response_code(200);
    echo json_encode($model->getAll());
}

function handlePost(OcorrenciaModel $model): void {
    global $conn;
    $data = getInput();

    $v_id  = $data->veiculo_id       ?? null;
    $m_id  = $data->motorista_id     ?? null;
    $dt    = $data->data_ocorrencia  ?? null;
    $hr    = $data->hora_ocorrencia  ?? null;
    $desc  = $data->descricao        ?? null;

    if (!$v_id || !$m_id || !$dt || !$hr || !$desc) {
        respond(400, "Dados obrigatórios faltando."); return;
    }

    $km    = $data->km_atual          ?? null;
    $local = $data->local_ocorrencia  ?? null;
    $foto  = $data->foto              ?? null; // base64 opcional

    if ($model->create($v_id, $m_id, $dt, $hr, $km, $local, $desc, $foto)) {
        respond(201, "Ocorrência registrada com sucesso.");
    } else {
        respond(503, "Erro ao registrar: " . $conn->error);
    }
}

function handlePut(OcorrenciaModel $model): void {
    $data = getInput();
    $id     = $data->id     ?? null;
    $status = $data->status ?? null;

    if (!$id || !$status) { respond(400, "ID ou status não informado."); return; }
    if ($model->updateStatus($id, $status)) {
        respond(200, "Status atualizado.");
    } else {
        respond(503, "Erro ao atualizar status.");
    }
}

function handleDelete(OcorrenciaModel $model): void {
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;
    if (!$id) { respond(400, "ID não fornecido."); return; }
    if ($model->remove($id)) {
        respond(200, "Ocorrência removida.");
    } else {
        respond(503, "Erro ao remover.");
    }
}

$handlers = ['GET'=>'handleGet','POST'=>'handlePost','PUT'=>'handlePut','DELETE'=>'handleDelete'];
$method = $_SERVER['REQUEST_METHOD'];
isset($handlers[$method]) ? $handlers[$method]($model) : respond(405, "Método não permitido.");
?>
