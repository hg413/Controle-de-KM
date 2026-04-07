<?php
// contratos.php — API para gerenciar os contratos (CRUD)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carrega a conexão com o banco de dados
$conn = require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../models/ContratoModel.php';

$contratoModel = new ContratoModel($conn);

// Captura método e corpo da requisição
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        // Lista todos os contratos
        echo json_encode($contratoModel->getAll());
        break;

    case 'POST':
        // Cadastra um novo contrato
        if (!isset($data['nome']) || empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(["message" => "O nome do contrato é obrigatório."]);
            break;
        }
        $res = $contratoModel->create($data['nome'], $data['cliente'] ?? '', $data['descricao'] ?? '');
        if ($res) {
            http_response_code(201);
            echo json_encode(["message" => "Contrato cadastrado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao cadastrar contrato."]);
        }
        break;

    case 'DELETE':
        // Remove um contrato
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(["message" => "ID não informado."]);
            break;
        }
        $res = $contratoModel->remove($id);
        if ($res) {
            echo json_encode(["message" => "Contrato removido."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao remover contrato."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido."]);
        break;
}
?>
