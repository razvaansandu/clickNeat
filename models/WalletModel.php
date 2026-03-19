<?php
class WalletModel {
    private $db;

    public function __construct($db){
        $this->db = $db;
    }

    public function ensureWallet(int $userId): void {
        $exists = $this->db->selectOne("SELECT user_id FROM wallets WHERE user_id = ?", [$userId]);
        if (!$exists) {
            $this->db->insert('wallets', [
                'user_id' => $userId,
                'balance_cents' => 0,
                'currency' => 'EUR'
            ]);
        }
    }

    public function getBalanceCents(int $userId): int {
        $this->ensureWallet($userId);
        $row = $this->db->selectOne("SELECT balance_cents FROM wallets WHERE user_id = ?", [$userId]);
        return $row ? (int)$row["balance_cents"] : 0;
    }

    public function getBalanceEuro(int $userId): string {
        $cents = $this->getBalanceCents($userId);
        return number_format($cents / 100, 2, '.', '');
    }

    public function getTransactions(int $userId, int $limit=20): array {
        $sql = "SELECT type, amount_cents, status, created_at
                FROM wallet_transactions
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT $limit";
        return $this->db->select($sql, [$userId]);
    }  

    public function addFunds(int $userId, int $amountCents): bool {
        if ($amountCents <= 0) return false;
        
        $this->ensureWallet($userId);
        
        $newBalance = $this->getBalanceCents($userId) + $amountCents;
        
        $this->db->update('wallets',  
            ['balance_cents' => $newBalance], 
            'user_id = ?', 
            [$userId]
        );

        $this->db->insert('wallet_transactions', [
            'user_id' => $userId,
            'type' => 'topup',
            'amount_cents' => $amountCents
        ]);
        
        return true;
    } 
}  