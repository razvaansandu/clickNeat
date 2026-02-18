<?php
class RistoranteModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        return $this->db->select("SELECT * FROM ristoranti");
    }

    public function getById($id) {
        return $this->db->selectOne("SELECT * FROM ristoranti WHERE id = ?", [$id]);
    }

    public function search($term) {
        $term = "%$term%";
        return $this->db->select("SELECT * FROM ristoranti WHERE nome LIKE ? OR indirizzo LIKE ?", [$term, $term]);
    }

    public function getByUserId($user_id) {
        return $this->db->selectOne("SELECT * FROM ristoranti WHERE user_id = ?", [$user_id]);
    }
          
    public function create($user_id, $nome, $indirizzo, $descrizione, $image_url = null) {
        return $this->db->insert('ristoranti', [
            'user_id' => $user_id,
            'nome' => $nome,
            'indirizzo' => $indirizzo,
            'descrizione' => $descrizione,
            'image_url' => $image_url
        ]);
    }

    public function update($id, $data) {
        return $this->db->update('ristoranti', $data, 'id = ?', [$id]);
    }
}
?> 