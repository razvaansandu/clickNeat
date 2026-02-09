<?php
require_once "LoginModel.php";

class GoogleAuthModel extends LoginModel
{

    public function findByGoogleId($google_id)
    {
        return $this->db->selectOne("SELECT * FROM users WHERE google_id = ?", [$google_id]);
    }

    public function linkGoogleId($user_id, $google_id)
    {
        return $this->db->update(
            'users',
            ['google_id' => $google_id, 'email_verified' => 1],
            'id = ?',
            [$user_id]
        );
    }

    public function createFromGoogle($email, $name, $google_id, $ruolo = 'consumatore')
    {
        $username = explode('@', $email)[0];

        if ($this->findByUsernameOrEmail($username)) {
            $username .= rand(100, 999);
        }

        $data = [
            'username' => $username,
            'email' => $email,
            'password' => null,
            'ruolo' => $ruolo,
            'email_verified' => 1,
            'google_id' => $google_id
        ];

        return $this->db->insert('users', $data);
    }
}
