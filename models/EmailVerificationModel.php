<?php
require_once "LoginModel.php";

class EmailVerificationModel extends LoginModel {

    public function verifyToken($token_hash) {
        $user = $this->db->selectOne("SELECT id FROM users WHERE email_verify_token = ?", [$token_hash]);

        if ($user) {
            return $this->db->update(
                'users', 
                ['email_verified' => 1, 'email_verify_token' => null], 
                'id = ?', 
                [$user['id']]
            );
        }
        return false;
    }

    public function refreshVerifyToken($email, $new_token_hash) {
        return $this->db->update(
            'users',
            ['email_verify_token' => $new_token_hash],
            'email = ? AND email_verified = 0', 
            [$email]
        );
    }
}
?>