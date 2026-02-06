<?php
class Database {
    private $connection;

    public function __construct($host, $user, $pass, $dbname) {
        $this->connection = new mysqli($host, $user, $pass, $dbname);

        if ($this->connection->connect_error) {
            die("Connessione fallita: " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8mb4");
    }

    private function getTypes($params) {
        $types = "";
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= "i";
            } elseif (is_float($param)) {
                $types .= "d";
            } else {
                $types .= "s";
            }
        }
        return $types;
    }

    // --- 1. READ (SELECT) ---
    public function select($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        
        if ($params) {
            $types = $this->getTypes($params);
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC); 
    }

    // --- 1.1 READ SINGLE (Restituisce solo una riga) ---
    public function selectOne($sql, $params = []) {
        $data = $this->select($sql, $params);
        return !empty($data) ? $data[0] : null;
    }

    // --- 2. CREATE (INSERT) ---
    public function insert($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $this->connection->prepare($sql);
        $values = array_values($data);
        $types = $this->getTypes($values);
        
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return $this->connection->insert_id; 
        }
        return false;
    }

    // --- 3. UPDATE ---
    public function update($table, $data, $where, $whereParams = []) {
        $setPart = [];
        foreach ($data as $key => $value) {
            $setPart[] = "$key = ?";
        }
        $setString = implode(", ", $setPart);

        $sql = "UPDATE $table SET $setString WHERE $where";
        
        $stmt = $this->connection->prepare($sql);
        
        $values = array_merge(array_values($data), $whereParams);
        $types = $this->getTypes($values);
        
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    // --- 4. DELETE ---
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->connection->prepare($sql);
        
        if ($params) {
            $types = $this->getTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        return $stmt->execute();
    }

    public function close() {
        $this->connection->close();
    }
    
    public function beginTransaction() { $this->connection->begin_transaction(); }
    public function commit() { $this->connection->commit(); }
    public function rollback() { $this->connection->rollback(); }
}
?>