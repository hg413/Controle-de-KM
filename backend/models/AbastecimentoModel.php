<?php
// Início da classe AbastecimentoModel, responsável por gerenciar os dados de abastecimento no banco
class AbastecimentoModel
{
    // Propriedade privada para armazenar a conexão com o banco de dados
    private $conn;

    // Construtor da classe que recebe e armazena a conexão MySQLi
    public function __construct($conn)
    {
        $this->conn = $conn; // Atribui a conexão à propriedade local
    }

    // Método para buscar todos os registros de abastecimento, incluindo a placa do veículo
    public function getAll()
    {
        // SQL que une as tabelas de abastecimentos e veículos para pegar a placa
        // Observação: os nomes das colunas (data, valor, km) devem bater com a tabela no banco
        $sql = "SELECT a.id, a.veiculo_id, a.data_abastecimento, a.posto, a.litros, a.valor_total, a.km_atual, v.placa 
                FROM abastecimentos a
                JOIN veiculos v ON a.veiculo_id = v.id
                ORDER BY a.data_abastecimento DESC"; // Ordena pelos mais recentes

        // Executa a consulta SQL
        $result = $this->conn->query($sql);
        // Inicializa array para guardar os resultados
        $dados = [];
        // Verifica se houve retorno de linhas
        if ($result && $result->num_rows > 0) {
            // Percorre cada linha do resultado
            while ($row = $result->fetch_assoc()) {
                // Adiciona um valor padrão para o nome do motorista (pode ser expandido depois)
                $row['motorista_nome'] = 'Motorista registrado';
                // Adiciona o registro formatado ao array de dados
                $dados[] = $row;
            }
        }
        // Retorna a lista de abastecimentos
        return $dados;
    }

    // Método para inserir um novo registro de abastecimento no banco de dados
    public function create($veiculo_id, $motorista_id, $data_abastecimento, $km_atual, $litros, $valor_total, $posto = null)
    {
        try {
            // SQL preparado com placeholders para inserção segura
            $sql = "INSERT INTO abastecimentos (veiculo_id, motorista_id, data_abastecimento, km_atual, litros, valor_total, posto) VALUES (?, ?, ?, ?, ?, ?, ?)";
            // Prepara a query no servidor MySQL
            $stmt = $this->conn->prepare($sql);
            // Se a preparação falhar, retorna falso
            if (!$stmt) return false;

            // Vincula os parâmetros à query (i=inteiro, s=string, d=double/decimal)
            $stmt->bind_param("iisidds", $veiculo_id, $motorista_id, $data_abastecimento, $km_atual, $litros, $valor_total, $posto);
            // Executa a instrução e guarda o sucesso
            $success = $stmt->execute();
            // Fecha o statement para liberar recursos
            $stmt->close();

            // Retorna se a operação foi bem-sucedida
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    // Método para remover um registro de abastecimento pelo ID
    public function remove($id)
    {
        try {
            // SQL para deletar o registro específico
            $sql = "DELETE FROM abastecimentos WHERE id = ?";
            // Prepara a query
            $stmt = $this->conn->prepare($sql);
            // Retorna falso se falhar a preparação
            if (!$stmt) return false;

            // Vincula o ID como inteiro
            $stmt->bind_param("i", $id);
            // Executa a deleção
            $success = $stmt->execute();
            // Fecha o statement
            $stmt->close();

            // Retorna o resultado da deleção
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>

