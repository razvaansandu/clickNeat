<?php
class Ristorante {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        return $this->db->select("SELECT id, nome, indirizzo, descrizione FROM ristoranti");
    }

    public function getById($id) {
        return $this->db->selectOne("SELECT * FROM ristoranti WHERE id = ?", [$id]);
    }

    public function search($term) {
        $term = "%$term%";
        return $this->db->select(
            "SELECT * FROM ristoranti WHERE nome LIKE ? OR indirizzo LIKE ?", 
            [$term, $term]
        );
    }
}
?>