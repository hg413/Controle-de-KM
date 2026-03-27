<?php
// Início da classe ManutencaoModel, responsável pela persistência de dados de manutenção
class ManutencaoModel
{
    // Variável que armazena a conexão ativa com o MySQL
    private $conn;

    // Construtor: injeta a conexão com o banco ao instanciar o objeto
    public function __construct($conn)
    {
        $this->conn = $conn; // Atribui a conexão à propriedade da classe
    }

    // Método para recuperar o histórico completo de manutenções de todos os veículos
    public function getAll()
    {
        // SQL com JOIN para trazer a placa do veículo junto com os dados de manutenção
        // Ajustamos os nomes das colunas para bater com o setup.php (data_manutencao, km_veiculo, valor_total)
        $sql = "SELECT m.id, m.veiculo_id, m.tipo, m.data_manutencao, m.km_veiculo, m.descricao, m.valor_total, m.realizada_por, v.placa 
                FROM manutencoes m
                JOIN veiculos v ON m.veiculo_id = v.id
                ORDER BY m.data_manutencao DESC"; // Ordena pelas manutenções mais recentes

        // Executa a query no banco de dados
        $result = $this->conn->query($sql);
        // Inicializa array para armazenar os registros encontrados
        $dados = [];
        // Verifica se a consulta retornou resultados
        if ($result && $result->num_rows > 0) {
            // Itera sobre cada linha do resultado
            while ($row = $result->fetch_assoc()) {
                // Adiciona o registro atual ao array de dados
                $dados[] = $row;
            }
        }
        // Retorna a lista final de manutenções
        return $dados;
    }

    // Método para cadastrar uma nova manutenção no sistema
    public function create($veiculo_id, $data_manutencao, $descricao, $valor_total, $km_veiculo = null, $tipo = 'preventiva', $realizada_por = null)
    {
        // SQL preparado para inserção, garantindo segurança contra SQL Injection
        $sql = "INSERT INTO manutencoes (veiculo_id, data_manutencao, km_veiculo, descricao, valor_total, tipo, realizada_por) VALUES (?, ?, ?, ?, ?, ?, ?)";
        // Prepara a instrução no servidor de banco de dados
        $stmt = $this->conn->prepare($sql);
        // Se houver erro na preparação, retorna falso
        if (!$stmt) return false;

        // Vincula os parâmetros aos placeholders '?' da query
        // Tipos: i=int, s=string, d=double/decimal
        $stmt->bind_param("isiddss", $veiculo_id, $data_manutencao, $km_veiculo, $descricao, $valor_total, $tipo, $realizada_por);
        // Executa a operação de inserção
        $success = $stmt->execute();
        // Fecha o statement para liberar recursos
        $stmt->close();

        // Retorna verdadeiro se inseriu com sucesso
        return $success;
    }

    // Método para remover um registro de manutenção pelo ID único
    public function remove($id)
    {
        // SQL para deleção física do registro
        $sql = "DELETE FROM manutencoes WHERE id = ?";
        // Prepara a consulta
        $stmt = $this->conn->prepare($sql);
        // Retorna falso se falhar a preparação
        if (!$stmt) return false;

        // Vincula o ID como um número inteiro
        $stmt->bind_param("i", $id);
        // Executa a remoção e armazena o resultado de sucesso
        $success = $stmt->execute();
        // Fecha o statement
        $stmt->close();

        // Retorna se a deleção foi concluída
        return $success;
    }
}
?>

