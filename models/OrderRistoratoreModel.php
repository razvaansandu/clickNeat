<?php
class OrderRistoratoreModel
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

    public function getByUserId($user_id)
    {
        $sql = "SELECT o.id, o.created_at, o.total_amount, o.status, r.nome as nome_ristorante 
                FROM orders o 
                JOIN ristoranti r ON o.restaurant_id = r.id 
                WHERE o.user_id = ? 
                ORDER BY o.created_at DESC";
        return $this->db->select($sql, [$user_id]);
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

    public function getByOwnerId($owner_id)
    {
        $sql = "SELECT o.id, o.total_amount, o.status, o.created_at, 
                       u.username as cliente, r.nome as ristorante_nome
                FROM orders o
                JOIN ristoranti r ON o.restaurant_id = r.id
                JOIN users u ON o.user_id = u.id
                WHERE r.proprietario_id = ?
                ORDER BY o.created_at DESC";

        $orders = $this->db->select($sql, [$owner_id]);

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }
        return $orders;
    }

    public function getOwnerKPI($owner_id)
    {
        $sql = "SELECT COUNT(o.id) as num_orders, SUM(o.total_amount) as revenue 
                FROM orders o
                JOIN ristoranti r ON o.restaurant_id = r.id
                WHERE r.proprietario_id = ? AND o.status = 'completed'";
        return $this->db->selectOne($sql, [$owner_id]);
    }

    public function getWeeklyChartData($owner_id)
    {
        $sql = "SELECT DATE(o.created_at) as data_ordine, COUNT(o.id) as quanti 
                FROM orders o
                JOIN ristoranti r ON o.restaurant_id = r.id
                WHERE r.proprietario_id = ? 
                AND o.created_at >= DATE(NOW()) - INTERVAL 7 DAY 
                GROUP BY DATE(o.created_at)";
        return $this->db->select($sql, [$owner_id]);
    }

    public function getTopRestaurantsByOwner($owner_id)
    {
        $sql = "SELECT r.nome, COALESCE(SUM(o.total_amount), 0) as fatturato 
                FROM ristoranti r 
                LEFT JOIN orders o ON r.id = o.restaurant_id AND o.status = 'completed'
                WHERE r.proprietario_id = ? 
                GROUP BY r.id 
                ORDER BY fatturato DESC 
                LIMIT 5";
        return $this->db->select($sql, [$owner_id]);
    }

    public function getRestaurantStats($rest_id)
    {
        $orders = $this->db->selectOne("SELECT COUNT(*) as count FROM orders WHERE restaurant_id = ?", [$rest_id]);
        $revenue = $this->db->selectOne("SELECT SUM(total_amount) as total FROM orders WHERE restaurant_id = ? AND status = 'completed'", [$rest_id]);
        return [
            'total_orders' => $orders['count'] ?? 0,
            'revenue' => $revenue['total'] ?? 0.00
        ];
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
}
