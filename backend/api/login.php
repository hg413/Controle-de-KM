<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Método não permitido"]);
    exit();
}

$conn = require_once __DIR__ . '/../database/connection.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->senha)) {

    $sql = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        if ($data->senha === $usuario['senha']) {
            http_response_code(200);
            echo json_encode([
                "message" => "Login realizado com sucesso",
                "usuario" => [
                    "id"     => $usuario['id'],
                    "nome"   => $usuario['nome'],
                    "perfil" => $usuario['tipo']   // mapeia 'tipo' → 'perfil' para o frontend
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Senha incorreta."]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Usuário não encontrado."]);
    }

} else {
    http_response_code(400);
    echo json_encode(["message" => "Email e senha são obrigatórios."]);
}
