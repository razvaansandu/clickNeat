<?php
require_once "LoginModel.php";

class RegisterModel extends LoginModel
{
    public function registerWithEmail($username, $email, $ruolo = 'consumatore')
    {
        if ($this->findByEmail($email)) {
            return false;
        }

        return $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'ruolo' => $ruolo,
            'email_verified' => 1
        ]);
    }
}
?>