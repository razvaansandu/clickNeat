<?php
class Order
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user_id, $restaurant_id, $total_amount)
    {
        $data = [
            'user_id' => $user_id,
            'restaurant_id' => $restaurant_id,
            'total_amount' => $total_amount,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert('orders', $data);
    }

    public function addItem($order_id, $dish_id, $quantity, $price)
    {
        $data = [
            'order_id' => $order_id,
            'dish_id' => $dish_id,
            'quantity' => $quantity,
            'price_at_time' => $price
        ];
        return $this->db->insert('order_items', $data);
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollback()
    {
        $this->db->rollback();
    }

    public function getByUserId($user_id)
    {
        $sql = "SELECT o.id, o.created_at, o.total_amount, o.status, r.nome as nome_ristorante 
                FROM orders o 
                JOIN ristoranti r ON o.restaurant_id = r.id 
                WHERE o.user_id = ? 
                ORDER BY o.created_at DESC";
        return $this->db->select($sql, [$user_id]);
    }
}
?>