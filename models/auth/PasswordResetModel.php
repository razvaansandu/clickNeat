<?php
require_once "LoginModel.php";

class PasswordResetModel extends LoginModel {

    public function saveResetToken($email, $token_hash, $expiry) {
        if (!$this->findByUsernameOrEmail($email)) {
            return false;
        }

        return $this->db->update(
            'users', 
            ['reset_token_hash' => $token_hash, 'reset_token_expires_at' => $expiry], 
            'email = ?', 
            [$email]
        );
    }

    public function findByResetToken($token_hash) {
        return $this->db->selectOne("SELECT * FROM users WHERE reset_token_hash = ?", [$token_hash]);
    }

    public function resetPassword($user_id, $new_password_hash) {
        return $this->db->update(
            'users', 
            [
                'password' => $new_password_hash,
                'reset_token_hash' => null, 
                'reset_token_expires_at' => null
            ], 
            'id = ?', 
            [$user_id]
        );
    }
}
?>