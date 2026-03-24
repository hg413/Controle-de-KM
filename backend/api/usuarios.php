<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$usuarioModel = new UsuarioModel($conn);

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

function handleGet(UsuarioModel $model): void
{
    http_response_code(200);
    echo json_encode($model->getAll());
}

function handlePost(UsuarioModel $model): void
{
    global $conn;
    $data = getInput();

    if (empty($data->nome) || empty($data->email) || empty($data->senha) || empty($data->perfil)) {
        respond(400, "Dados incompletos. Nome, email, senha e perfil são requeridos.");
        return;
    }

    if ($model->create($data->nome, $data->email, $data->senha, $data->perfil)) {
        respond(201, "Usuário criado com sucesso.");
    } else {
        respond(503, "Não foi possível criar o usuário: " . $conn->error);
    }
}

function handlePut(UsuarioModel $model): void
{
    global $conn;
    $data = getInput();

    if (empty($data->id) || empty($data->nome) || empty($data->email) || empty($data->perfil)) {
        respond(400, "Dados incompletos.");
        return;
    }

    $senha = $data->senha ?? null;
    if ($model->update($data->id, $data->nome, $data->email, $data->perfil, $senha)) {
        respond(200, "Usuário atualizado com sucesso.");
    } else {
        respond(503, "Não foi possível atualizar o usuário: " . $conn->error);
    }
}

function handleDelete(UsuarioModel $model): void
{
    $data = getInput();
    $id = $_GET['id'] ?? $data->id ?? null;

    if (!$id) {
        respond(400, "ID não fornecido.");
        return;
    }

    if ($model->remove($id)) {
        respond(200, "Usuário excluído com sucesso.");
    } else {
        respond(503, "Não foi possível excluir o usuário.");
    }
}

// -- Roteamento --

$handlers = [
    'GET'    => 'handleGet',
    'POST'   => 'handlePost',
    'PUT'    => 'handlePut',
    'DELETE' => 'handleDelete',
];

$method = $_SERVER['REQUEST_METHOD'];
if (isset($handlers[$method])) {
    $handlers[$method]($usuarioModel);
} else {
    respond(405, "Método não permitido.");
}