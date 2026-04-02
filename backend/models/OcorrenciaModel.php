<?php
class OcorrenciaModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT o.id, o.veiculo_id, o.motorista_id, o.data_ocorrencia, o.hora_ocorrencia,
                       o.km_atual, o.local_ocorrencia, o.descricao, o.foto, o.status, o.criado_em,
                       v.placa, u.nome AS motorista_nome
                FROM ocorrencias o
                JOIN veiculos v ON o.veiculo_id = v.id
                JOIN usuarios u ON o.motorista_id = u.id
                ORDER BY o.data_ocorrencia DESC, o.hora_ocorrencia DESC";

        $result = $this->conn->query($sql);
        $dados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dados[] = $row;
            }
        }
        return $dados;
    }

    public function create($veiculo_id, $motorista_id, $data_ocorrencia, $hora_ocorrencia, $km_atual, $local_ocorrencia, $descricao, $foto = null)
    {
        try {
            $sql = "INSERT INTO ocorrencias (veiculo_id, motorista_id, data_ocorrencia, hora_ocorrencia, km_atual, local_ocorrencia, descricao, foto)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;

            $stmt->bind_param("iissiiss", $veiculo_id, $motorista_id, $data_ocorrencia, $hora_ocorrencia, $km_atual, $local_ocorrencia, $descricao, $foto);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateStatus($id, $status)
    {
        try {
            $sql = "UPDATE ocorrencias SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("si", $status, $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    public function remove($id)
    {
        try {
            $sql = "DELETE FROM ocorrencias WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
