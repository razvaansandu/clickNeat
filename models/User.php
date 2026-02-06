<?php
class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findByEmail($email)
    {
        return $this->db->selectOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public function findByUsername($username)
    {
        return $this->db->selectOne("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public function findByUsernameOrEmail($identifier)
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$identifier, $identifier]
        );
    }

    public function create($username, $email, $password, $ruolo, $verified = 0, $google_id = null)
    {
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'ruolo' => $ruolo,
            'email_verified' => $verified,
            'google_id' => $google_id
        ];
        return $this->db->insert('users', $data);
    }

    public function linkGoogleId($user_id, $google_id)
    {
        return $this->db->update('users', ['google_id' => $google_id, 'email_verified' => 1], 'id = ?', [$user_id]);
    }

    public function updateVerifyToken($user_id, $token_hash)
    {
        return $this->db->update('users', ['email_verify_token' => $token_hash], 'id = ?', [$user_id]);
    }
    public function findByResetToken($token_hash)
    {
        return $this->db->selectOne("SELECT * FROM users WHERE reset_token_hash = ?", [$token_hash]);
    }

    public function resetPassword($user_id, $new_password_hash)
    {
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

    public function savePasswordResetToken($email, $token_hash, $expiry)
    {
        return $this->db->update(
            'users',
            [
                'reset_token_hash' => $token_hash,
                'reset_token_expires_at' => $expiry
            ],
            'email = ?',
            [$email]
        );
    }

    public function verifyEmail($token_hash)
    {
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

    public function updateProfile($id, $username, $email)
    {
        return $this->db->update('users', ['username' => $username, 'email' => $email], 'id = ?', [$id]);
    }

    public function getPasswordHash($id)
    {
        $user = $this->db->selectOne("SELECT password FROM users WHERE id = ?", [$id]);
        return $user ? $user['password'] : null;
    }

    public function updatePassword($id, $new_hash)
    {
        return $this->db->update('users', ['password' => $new_hash], 'id = ?', [$id]);
    }

    public function getProfileData($id)
    {
        return $this->db->selectOne("SELECT username, email, created_at FROM users WHERE id = ?", [$id]);
    }
}
?>