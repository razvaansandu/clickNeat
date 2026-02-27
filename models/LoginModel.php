<?php
class LoginModel
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findByEmail($email)
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    public function findByGoogleId($google_id)
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE google_id = ?",
            [$google_id]
        );
    }
    public function sendOtp($email)
{
    $user = $this->findByEmail($email);
    if (!$user) return false;

    // USA update() invece di execute()
    $this->db->update(
        'email_otp',
        ['used' => 1],
        'email = ? AND used = 0',
        [$email]
    );

    $codice  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $this->db->insert('email_otp', [
        'email'      => $email,
        'codice'     => $codice,
        'expires_at' => $expires
    ]);

    return $codice;
}

public function verifyOtp($email, $codice)
{
    $otp = $this->db->selectOne(
        "SELECT * FROM email_otp
         WHERE email = ?
           AND codice = ?
           AND used = 0
           AND expires_at > NOW()
         ORDER BY created_at DESC
         LIMIT 1",
        [$email, $codice]
    );

    if (!$otp) return false;

    $this->db->update(
        'email_otp',
        ['used' => 1],
        'id = ?',
        [$otp['id']]
    );

    return $this->findByEmail($email);
}

}
?>
