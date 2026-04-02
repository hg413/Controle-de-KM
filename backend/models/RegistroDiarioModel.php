<?php
// Início da classe RegistroDiarioModel, responsável pela persistência dos diários de bordo listando a assinatura na base de dados
class RegistroDiarioModel
{
    // Variável que armazena a conexão com o banco
    private $conn;

    // Recebe a conexão via injeção
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Busca o histórico ordenando pelos mais recentes. Faz JOIN para trazer o nome legível e a placa
    public function getAll()
    {
        $sql = "SELECT r.id, r.veiculo_id, r.motorista_id, r.data_registro, r.hora_inicio, r.hora_final, 
                       r.km_inicial, r.km_final, r.km_rodado, r.destino_motivo, r.assinatura_digital, r.criado_em,
                       v.placa, u.nome AS motorista_nome 
                FROM registros_diarios r
                JOIN veiculos v ON r.veiculo_id = v.id
                JOIN usuarios u ON r.motorista_id = u.id
                ORDER BY r.data_registro DESC, r.hora_inicio DESC";

        $result = $this->conn->query($sql);
        $dados = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dados[] = $row;
            }
        }
        return $dados;
    }

    // Cria um registro diário a partir da submissão do formulário. Inclui a base64 string gerada no canvas.
    public function create($veiculo_id, $motorista_id, $data_registro, $hora_inicio, $hora_final, $km_inicial, $km_final, $km_rodado, $destino_motivo, $assinatura_digital)
    {
        try {
            $sql = "INSERT INTO registros_diarios (veiculo_id, motorista_id, data_registro, hora_inicio, hora_final, km_inicial, km_final, km_rodado, destino_motivo, assinatura_digital) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;

            // i=int, s=string. Temos 5 ints e 5 strings (date e time entram como string)
            $stmt->bind_param("iisssiiiss", $veiculo_id, $motorista_id, $data_registro, $hora_inicio, $hora_final, $km_inicial, $km_final, $km_rodado, $destino_motivo, $assinatura_digital);
            $success = $stmt->execute();
            $stmt->close();

            return $success;
        } catch (Exception $e) {
            return false; // Previne fatais decorrentes de constraints
        }
    }

    // Deleta um registro caso seja solicitado pelo administrador
    public function remove($id)
    {
        try {
            $sql = "DELETE FROM registros_diarios WHERE id = ?";
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
