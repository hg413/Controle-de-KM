<?php

class VeiculoModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lista todos os veículos cadastrados
    public function getAll()
    {
        // Faz um JOIN básico com a tabela usuarios para trazer o nome do motorista fixo, se existir
        $sql = "SELECT v.id, v.placa, v.motorista_responsavel, u.nome AS motorista_responsavel_nome 
                FROM veiculos v 
                LEFT JOIN usuarios u ON v.motorista_responsavel = u.id";

        $result = $this->conn->query($sql);

        $veiculos = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Mapeia para o frontend manter o padrão esperado
                $row['motorista_responsavel'] = $row['motorista_responsavel_nome'];
                $veiculos[] = $row;
            }
        }
        return $veiculos;
    }

    // Cadastra um novo veículo
    public function create($placa, $motorista_id = null)
    {
        $sql = "INSERT INTO veiculos (placa, motorista_responsavel) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt)
            return false;

        $stmt->bind_param("si", $placa, $motorista_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    // Exclui um veículo (apenas admin deve conseguir)
    public function remove($id)
    {
        $sql = "DELETE FROM veiculos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt)
            return false;

        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
