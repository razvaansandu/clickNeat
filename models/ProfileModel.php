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
        $anonymous_username = 'deleted_' . $user_id;

        return $this->db->update(
            'users',
            [
                'username' => $anonymous_username,
                'email' => $anonymous_email,
                'google_id' => null,
                'deleted_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$user_id]
        );
    }

    public function updateBillingInfo($user_id, $data)
    {
        return $this->db->update(
            'users',
            [
                'codice_fiscale' => $data['codice_fiscale'],
                'partita_iva' => $data['partita_iva'],
                'indirizzo' => $data['indirizzo'],
                'citta' => $data['citta'],
                'cap' => $data['cap'],
                'provincia' => $data['provincia']
            ],
            'id = ?',
            [$user_id]
        );
    }


}
