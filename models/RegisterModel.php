<?php
require_once "LoginModel.php";

class RegisterModel extends LoginModel {

    public function register($username, $email, $password, $ruolo) {
        
        if ($this->findByUsernameOrEmail($email)) {
            return false; 
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $verify_token = bin2hex(random_bytes(16));
        $verify_token_hash = hash("sha256", $verify_token);

        $inserted = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $password_hash,
            'ruolo' => $ruolo,
            'email_verify_token' => $verify_token_hash,
            'email_verified' => 0
        ]);

        if ($inserted) {
            return $verify_token;
        }

        return false;
    }
}
?>