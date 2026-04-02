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
require_once __DIR__ . '/../models/RegistroDiarioModel.php';

$model = new RegistroDiarioModel($conn);

function respond(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(["message" => $message]);
}

function getInput(): object
{
    // O POST do canvas com a Base64 pode ser bem pesado, e file_get_contents vai comportá-lo integralmente
    return json_decode(file_get_contents("php://input")) ?? (object)[];
}

function handleGet(RegistroDiarioModel $model): void
{
    http_response_code(200);
    echo json_encode($model->getAll());
}

function handlePost(RegistroDiarioModel $model): void
{
    global $conn;
    $data = getInput();

    // Extrai propriedades
    $v_id = $data->veiculo_id ?? null;
    $m_id = $data->motorista_id ?? null;
    $dt_reg = $data->data_registro ?? null;
    $h_ini = $data->hora_inicio ?? null;
    $h_fim = $data->hora_final ?? null;
    $km_ini = $data->km_inicial ?? null;
    $km_fim = $data->km_final ?? null;
    $km_rodado = $data->km_rodado ?? null;
    $motivo = $data->destino_motivo ?? null;
    $assinatura = $data->assinatura_digital ?? null; // Aqui mora a Base64 provinda do canvas

    if (!$v_id || !$m_id || !$dt_reg || !$h_ini || !$h_fim || strlen($km_ini) === 0 || strlen($km_fim) === 0 || strlen($km_rodado) === 0 || !$motivo || !$assinatura) {
        respond(400, "Dados obrigatórios ou assinatura faltando.");
        return;
    }

    if ($model->create($v_id, $m_id, $dt_reg, $h_ini, $h_fim, $km_ini, $km_fim, $km_rodado, $motivo, $assinatura)) {
        respond(201, "Registro Diário salvo com sucesso.");
    } else {
        respond(503, "Erro ao salvar o registro no banco: " . $conn->error);
    }
}

function handleDelete(RegistroDiarioModel $model): void
{
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    if ($model->remove($id)) {
        respond(200, "Registro removido com sucesso.");
    } else {
        respond(503, "Não foi possível remover o registro diário.");
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
?>
