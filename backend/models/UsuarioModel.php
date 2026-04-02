<?php
// Início da classe UsuarioModel, que concentra a lógica de manipulação de dados de usuários
class UsuarioModel
{
    // Variável privada para armazenar a instância de conexão com o banco de dados
    private $conn;

    // Construtor da classe: recebe a conexão MySQLi ao ser instanciada
    public function __construct($conn)
    {
        $this->conn = $conn; // Atribui a conexão recebida à propriedade da classe
    }

    // Método para buscar todos os usuários cadastrados no banco de dados
    public function getAll()
    {
        // SQL selecionando colunas específicas da tabela 'usuarios'
        $sql = "SELECT id, nome, email, senha, tipo FROM usuarios";
        // Executa a consulta no banco de dados
        $result = $this->conn->query($sql);

        // Inicializa um array vazio para guardar os usuários encontrados
        $usuarios = [];
        // Verifica se a consulta retornou resultados (pelo menos uma linha)
        if ($result && $result->num_rows > 0) {
            // Percorre cada linha retornada pelo banco como um array associativo
            while ($row = $result->fetch_assoc()) {
                // Adiciona uma chave 'perfil' recebendo o valor de 'tipo' para compatibilidade com o frontend
                $row['perfil'] = $row['tipo'];
                // Adiciona o array do usuário atual à lista final
                $usuarios[] = $row;
            }
        }
        // Retorna a lista completa de usuários (ou vazio se não houver nenhum)
        return $usuarios;
    }

    // Método para criar (cadastrar) um novo usuário no sistema
    public function create($nome, $email, $senha, $tipo)
    {
        try {
            // SQL com placeholders (?) para evitar injeção de SQL
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
            // Prepara a instrução SQL no servidor MySQL
            $stmt = $this->conn->prepare($sql);
            // Se a preparação falhar, retorna falso imediatamente
            if (!$stmt)
                return false;

            // Associa as variáveis PHP aos placeholders "?" (s = string)
            $stmt->bind_param("ssss", $nome, $email, $senha, $tipo);
            // Executa a operação de inserção e guarda o status de sucesso
            $success = $stmt->execute();
            // Fecha o statement para liberar memória do servidor
            $stmt->close();

            // Retorna verdadeiro se inseriu com sucesso, falso caso contrário
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    // Método para atualizar os dados de um usuário existente
    public function update($id, $nome, $email, $tipo, $senha = null)
    {
        try {
            // Se uma nova senha foi informada (não está vazia)
            if (!empty($senha)) {
                // SQL incluindo a atualização do campo senha
                $sql = "UPDATE usuarios SET nome=?, email=?, tipo=?, senha=? WHERE id=?";
                $stmt = $this->conn->prepare($sql); // Prepara a query
                if (!$stmt)
                    return false; // Erro na preparação
                // Associa os 5 parâmetros (4 strings e 1 inteiro para o ID)
                $stmt->bind_param("ssssi", $nome, $email, $tipo, $senha, $id);
            } else {
                // Se a senha não foi informada, atualiza apenas os outros campos
                $sql = "UPDATE usuarios SET nome=?, email=?, tipo=? WHERE id=?";
                $stmt = $this->conn->prepare($sql); // Prepara a query
                if (!$stmt)
                    return false; // Erro na preparação
                // Associa os 4 parâmetros (3 strings e 1 inteiro para o ID)
                $stmt->bind_param("sssi", $nome, $email, $tipo, $id);
            }

            // Executa a atualização no banco de dados
            $success = $stmt->execute();
            // Fecha o statement
            $stmt->close();

            // Retorna se a operação foi bem sucedida
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    // Método para remover (deletar) um usuário pelo seu ID único
    public function remove($id)
    {
        try {
            // SQL para deletar uma linha baseado no ID
            $sql = "DELETE FROM usuarios WHERE id=?";
            // Prepara a query de deleção
            $stmt = $this->conn->prepare($sql);
            // Retorna falso se houver erro ao preparar
            if (!$stmt)
                return false;

            // Associa o ID (i = inteiro) ao placeholder
            $stmt->bind_param("i", $id);
            // Executa a deleção e guarda o sucesso
            $success = $stmt->execute();
            // Fecha o statement
            $stmt->close();

            // Retorna o resultado da operação
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>