<?php

class AbastecimentoModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT a.id, a.veiculo_id, a.data AS data_abastecimento, a.posto, a.litros, a.valor AS valor_total, a.km AS km_atual, v.placa 
                FROM abastecimentos a
                JOIN veiculos v ON a.veiculo_id = v.id
                ORDER BY a.data DESC";

        $result = $this->conn->query($sql);
        $dados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Adiciona um motorista fixo ou vazio já que a coluna não existe no banco real
                $row['motorista_nome'] = 'Não registrado';
                $dados[] = $row;
            }
        }
        return $dados;
    }

    public function create($veiculo_id, $data_abastecimento, $km_atual, $litros, $valor_total, $posto = null)
    {
        // Ignoramos motorista_id pois a coluna não existe na tabela real do usuário
        $sql = "INSERT INTO abastecimentos (veiculo_id, data, km, litros, valor, posto) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("isidds", $veiculo_id, $data_abastecimento, $km_atual, $litros, $valor_total, $posto);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function remove($id)
    {
        $sql = "DELETE FROM abastecimentos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
