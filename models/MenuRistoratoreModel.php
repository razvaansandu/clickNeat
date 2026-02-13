<?php
class MenuRistoratoreModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getByRestaurant($restaurant_id)
    {
        return $this->db->select("SELECT * FROM menu_items WHERE restaurant_id = ?", [$restaurant_id]);
    }

    public function getById($id)
    {
        return $this->db->selectOne("SELECT * FROM menu_items WHERE id = ?", [$id]);
    }

    public function create($restaurant_id, $name, $description, $price, $image_url = null)
    {
        return $this->db->insert('menu_items', [
            'restaurant_id' => $restaurant_id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'image_url' => $image_url
        ]);
    }

    public function update($id, $name, $description, $price, $image_url = null)
    {
        $data = [
            'name' => $name,
            'description' => $description,
            'price' => $price
        ];

        if ($image_url) {
            $data['image_url'] = $image_url;
        }

        return $this->db->update('menu_items', $data, 'id = ?', [$id]);
    }

    public function delete($id)
    {
        return $this->db->delete('menu_items', 'id = ?', [$id]);
    }
}
?>