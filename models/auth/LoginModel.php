<?php
class LoginModel
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findByUsernameOrEmail($input)
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE email = ? OR username = ?",
            [$input, $input]
        );
    }
    public function login($input, $password)
    {
        $user = $this->findByUsernameOrEmail($input);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }
}
?>