<?php
class WalletModel {
    private $db;

    public function __construct($db){
        $this->db = $db;
    }

    public function ensureWallet(int $userId): void {
        $sql = "INSERT INTO wallets (user_id, balance_cents, currency)
                VALUES (:uid, 0, 'EUR')
                ON DUPLICATE KEY UPDATE user_id = user_id";
        $this->db->query($sql, [":uid" => $userId]);
    }

    public function getBalanceCents(int $userId): int {
        $this->ensureWallet($userId);
        $row = $this->db->single("SELECT balance_cents FROM wallets WHERE user_id = :uid", [":uid"=>$userId]);
        return $row ? (int)$row["balance_cents"] : 0;
    }

    public function getTransactions(int $userId, int $limit=20): array {
        $sql = "SELECT type, amount_cents, status, created_at
                FROM wallet_transactions
                WHERE user_id = :uid
                ORDER BY created_at DESC
                LIMIT $limit";
        return $this->db->resultSet($sql, [":uid"=>$userId]);
    }
}