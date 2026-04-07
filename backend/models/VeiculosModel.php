<?php
// Início da classe VeiculoModel, responsável pela gestão dos dados da frota no banco
class VeiculoModel
{
    // Propriedade privada para armazenar o objeto de conexão MySQLi
    private $conn;

    // Construtor: recebe a conexão ativa ao instanciar a classe
    public function __construct($conn)
    {
        $this->conn = $conn; // Atribui a conexão recebida à propriedade local
    }

    // Método para listar todos os veículos cadastrados no sistema
    public function getAll()
    {
        // SQL com LEFT JOIN para buscar o nome do motorista responsável associado ao veículo
        // Ajustado para 'motorista_responsavel_id' conforme definido no setup.php
        $sql = "SELECT v.id, v.placa, v.modelo, v.motorista_responsavel_id, u.nome AS motorista_responsavel_nome 
                FROM veiculos v 
                LEFT JOIN usuarios u ON v.motorista_responsavel_id = u.id";

        // Executa a consulta no banco de dados
        $result = $this->conn->query($sql);

        // Inicializa um array para guardar a lista de veículos
        $veiculos = [];
        // Verifica se houve retorno de dados
        if ($result && $result->num_rows > 0) {
            // Percorre cada registro retornado pelo MySQL
            while ($row = $result->fetch_assoc()) {
                // Adiciona o nome do motorista ao campo esperado pelo frontend (se existir)
                $row['motorista_responsavel'] = $row['motorista_responsavel_nome'] ?? 'Sem motorista';
                // Adiciona o veículo formatado à lista final
                $veiculos[] = $row;
            }
        }
        // Retorna o array com todos os veículos encontrados
        return $veiculos;
    }

    // Método para cadastrar um novo veículo na frota
    public function create($placa, $motorista_id = null, $modelo = null)
    {
        try {
            // Se o motorista não foi selecionado ou é inválido, garante que será nulo
            if (empty($motorista_id)) {
                $motorista_id = null;
            }

            // SQL preparado para inserção, evitando ataques de injeção
            $sql = "INSERT INTO veiculos (placa, motorista_responsavel_id, modelo) VALUES (?, ?, ?)";
            // Prepara a query no servidor MySQL
            $stmt = $this->conn->prepare($sql);
            // Se a preparação falhar, retorna falso imediatamente
            if (!$stmt)
                return false;

            // Vincula a placa (string), ID do motorista (inteiro) e modelo (string).
            $stmt->bind_param("sis", $placa, $motorista_id, $modelo);
            
            // Executa a inserção no banco
            $success = $stmt->execute();
            // Fecha o statement para liberar memória
            $stmt->close();
            
            // Retorna verdadeiro se o veículo foi cadastrado com sucesso
            return $success;
        } catch (Exception $e) {
            // Em caso de exceção (ex: placa duplicada ou erro de banco de dados), captura o erro de forma segura
            // e retorna falso para que a controller possa lidar com a falha
            return false;
        }
    }

    // Método para excluir um veículo do sistema pelo seu ID único
    public function remove($id)
    {
        try {
            // SQL para deleção do registro baseado na chave primária
            $sql = "DELETE FROM veiculos WHERE id = ?";
            // Prepara a instrução de deleção
            $stmt = $this->conn->prepare($sql);
            // Retorna falso caso ocorra erro na preparação
            if (!$stmt)
                return false;

            // Vincula o ID (inteiro) ao placeholder da query
            $stmt->bind_param("i", $id);
            // Executa a remoção física no banco de dados
            $success = $stmt->execute();
            // Fecha o statement
            $stmt->close();

            // Retorna se a operação de exclusão foi concluída
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>