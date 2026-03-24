<?php

class UsuarioModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $sql = "SELECT id, nome, email, senha, tipo FROM usuarios";
        $result = $this->conn->query($sql);
        
        $usuarios = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // expõe como 'perfil' para o frontend
                $row['perfil'] = $row['tipo'];
                $usuarios[] = $row;
            }
        }
        return $usuarios;
    }

    public function create($nome, $email, $senha, $tipo) {
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("ssss", $nome, $email, $senha, $tipo);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    public function update($id, $nome, $email, $tipo, $senha = null) {
        if (!empty($senha)) {
            $sql = "UPDATE usuarios SET nome=?, email=?, tipo=?, senha=? WHERE id=?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("ssssi", $nome, $email, $tipo, $senha, $id);
        } else {
            $sql = "UPDATE usuarios SET nome=?, email=?, tipo=? WHERE id=?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("sssi", $nome, $email, $tipo, $id);
        }

        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    public function remove($id) {
        $sql = "DELETE FROM usuarios WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
}
