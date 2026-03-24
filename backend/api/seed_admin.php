<?php
$conn = require_once __DIR__ . '/../database/connection.php';

// Insere usuário admin inicial
$nome  = 'Administrador';
$email = 'admin@controle.km';
$senha = 'admin123';
$tipo  = 'admin';

$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nome, $email, $senha, $tipo);

header('Content-Type: application/json; charset=UTF-8');

if ($stmt->execute()) {
    echo json_encode(["ok" => true, "message" => "Admin criado! Email: $email | Senha: $senha"]);
} else {
    echo json_encode(["ok" => false, "error" => $conn->error]);
}

$stmt->close();
