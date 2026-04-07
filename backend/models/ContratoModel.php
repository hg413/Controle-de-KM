<?php
// ContratoModel.php — Gerencia os contratos da empresa
class ContratoModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lista todos os contratos ativos
    public function getAll()
    {
        $sql = "SELECT * FROM contratos ORDER BY nome ASC";
        $result = $this->conn->query($sql);
        $dados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dados[] = $row;
            }
        }
        return $dados;
    }

    // Cria um novo contrato
    public function create($nome, $cliente, $descricao)
    {
        try {
            $sql = "INSERT INTO contratos (nome, cliente, descricao) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;

            $stmt->bind_param("sss", $nome, $cliente, $descricao);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    // Deleta um contrato (Cuidado: Registros diários vinculados terão contrato_id setado como NULL)
    public function remove($id)
    {
        try {
            $sql = "DELETE FROM contratos WHERE id = ?";
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
