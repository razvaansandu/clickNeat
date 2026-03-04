<?php
require_once "LoginModel.php";

class ProfileModel extends LoginModel
{

    public function getProfileData($id)
    {
        return $this->db->selectOne("SELECT username, email, created_at FROM users WHERE id = ?", [$id]);
    }

    public function updateInfo($id, $username, $email)
    {
        $existing = $this->db->selectOne(
            "SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?",
            [$email, $username, $id]
        );

        if ($existing)
            return false;

        return $this->db->update('users', ['username' => $username, 'email' => $email], 'id = ?', [$id]);
    }

    public function deleteAccount($user_id)
    {
        $anonymous_email = 'deleted_' . $user_id . '_' . time() . '@deleted.invalid';

        return $this->db->update(
            'users',
            [
                'username' => 'Utente Eliminato',
                'email' => $anonymous_email,
                'google_id' => null,
                'deleted_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$user_id]
        );
    }
}
