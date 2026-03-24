<?php

class ManutencaoModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT m.id, m.veiculo_id, m.tipo, m.data AS data_manutencao, m.km AS km_veiculo, m.descricao, m.valor AS valor_total, v.placa 
                FROM manutencoes m
                JOIN veiculos v ON m.veiculo_id = v.id
                ORDER BY m.data DESC";

        $result = $this->conn->query($sql);
        $dados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Adiciona campo vazio para o backend não dar erro no foreach se o front esperar
                $row['realizada_por'] = 'Não informado';
                $dados[] = $row;
            }
        }
        return $dados;
    }

    public function create($veiculo_id, $data_manutencao, $descricao, $valor_total, $km_veiculo = null, $tipo = 'preventiva', $realizada_por = null)
    {
        // Ignoramos realizada_por pois a coluna não existe no banco real
        $sql = "INSERT INTO manutencoes (veiculo_id, data, km, descricao, valor, tipo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("isidss", $veiculo_id, $data_manutencao, $km_veiculo, $descricao, $valor_total, $tipo);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function remove($id)
    {
        $sql = "DELETE FROM manutencoes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
