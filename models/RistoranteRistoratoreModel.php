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
        return $this->db->select(
            "SELECT * FROM ristoranti WHERE proprietario_id = ? AND deleted_at IS NULL",
            [$user_id]
        );
    }

    public function getByIdAndOwner($id, $owner_id)
    {
        return $this->db->selectOne(
            "SELECT * FROM ristoranti WHERE id = ? AND proprietario_id = ? AND deleted_at IS NULL",
            [$id, $owner_id]
        );
    }

    public function create($user_id, $nome, $indirizzo, $descrizione, $categoria, $image_url = null)
    {
        $data = [
            'proprietario_id' => $user_id,
            'nome' => $nome,
            'indirizzo' => $indirizzo,
            'descrizione' => $descrizione,
            'categoria' => 'altro',
            'image_url' => $image_url
        ];

        return $this->db->insert('ristoranti', $data);
    }

    public function update($id, $data)
    {

        if (array_key_exists('image_url', $data) && empty($data['image_url'])) {
            unset($data['image_url']);
        }

        return $this->db->update('ristoranti', $data, 'id = ?', [$id]);
    }

    public function getById($id)
    {
        return $this->db->selectOne("SELECT * FROM ristoranti WHERE id = ?", [$id]);
    }

    public function delete($id)
    {
        $this->db->update(
            'menu_items',
            ['deleted_at' => date('Y-m-d H:i:s')],
            'restaurant_id = ?',
            [$id]
        );

        return $this->db->update(
            'ristoranti',
            ['deleted_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        );
    }

}