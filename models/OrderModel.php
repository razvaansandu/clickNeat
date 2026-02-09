<?php
class OrderModel
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

    public function getByRestaurantId($restaurant_id)
    {
        $sql = "SELECT o.*, u.username as cliente_nome 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.restaurant_id = ? 
                ORDER BY o.created_at DESC";
        return $this->db->select($sql, [$restaurant_id]);
    }

    public function getOrderItems($order_id)
    {
        $sql = "SELECT oi.*, m.name 
                FROM order_items oi 
                JOIN menu_items m ON oi.dish_id = m.id 
                WHERE oi.order_id = ?";
        return $this->db->select($sql, [$order_id]);
    }

    public function updateStatus($order_id, $status)
    {
        return $this->db->update('orders', ['status' => $status], 'id = ?', [$order_id]);
    }
}
