<?php
require_once "LoginModel.php";

class GoogleAuthModel extends LoginModel
{
    public function findByUsername($username)
    {
        return $this->db->selectOne(
            "SELECT id FROM users WHERE username = ?",
            [$username]
        );
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
        $username = preg_replace('/\s+/', '_', strtolower($name));

        if ($this->findByUsername($username)) {
            $username .= rand(100, 999);
        }

        $data = [
            'username'       => $username,
            'email'          => $email,
            'ruolo'          => $ruolo,
            'email_verified' => 1,
            'google_id'      => $google_id
        ];

        return $this->db->insert('users', $data);
    }

    public function handleGoogleLogin($google_id, $email, $name)
    {
        $user = $this->findByGoogleId($google_id);
        if ($user) return $user;

        $user = $this->findByEmail($email);
        if ($user) {
            $this->linkGoogleId($user['id'], $google_id);
            return $this->findByEmail($email);
        }

        $this->createFromGoogle($email, $name, $google_id);
        return $this->findByEmail($email);
    }
}
?>
