<?php
class RistoranteRistoratoreModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllByUserId($user_id)
    {
        return $this->db->select("SELECT * FROM ristoranti WHERE proprietario_id = ?", [$user_id]);
    }

    public function getByIdAndOwner($id, $owner_id)
    {
        return $this->db->selectOne("SELECT * FROM ristoranti WHERE id = ? AND proprietario_id = ?", [$id, $owner_id]);
    }

    public function create($user_id, $nome, $indirizzo, $descrizione, $image_url = null)
    {
        $data = [
            'proprietario_id' => $user_id,
            'nome' => $nome,
            'indirizzo' => $indirizzo,
            'descrizione' => $descrizione
        ];

        if ($image_url) {
            $data['image_url'] = $image_url;
        }

        return $this->db->insert('ristoranti', $data);
    }

    public function update($id, $data)
    {
        if (isset($data['image_url']) && empty($data['image_url'])) {
            unset($data['image_url']);
        }
        return $this->db->update('ristoranti', $data, 'id = ?', [$id]);
    }

    public function getById($id)
    {
        return $this->db->selectOne("SELECT * FROM ristoranti WHERE id = ?", [$id]);
    }
}
