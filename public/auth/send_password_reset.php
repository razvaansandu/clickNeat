<?php
require_once "../../config/db.php";         
require_once "../../models/User.php";       

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$email = $_POST["email"] ?? '';

if (!empty($email)) {
    $userModel = new User($db);

    $user = $userModel->findByEmail($email);

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); 

        if ($userModel->savePasswordResetToken($email, $token_hash, $expiry)) {

            $mail = require __DIR__ . "/../../src/mailer.php";

            try {
                $mail->addAddress($email);
                $mail->Subject = "Reset Password - ClickNeat";

                $reset_link = "http://localhost/public/auth/reset_password.php?token=$token";

                $mail->Body = <<<END
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <h2 style="color: #1e3c72;">Reset Password ClickNeat</h2>
                    <p>Hai richiesto di reimpostare la tua password.</p>
                    <p>Clicca sul pulsante qui sotto per procedere:</p>
                    <a href="$reset_link" 
                       style="display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #1e3c72, #7e22ce); 
                       color: white; text-decoration: none; border-radius: 10px; font-weight: bold; margin: 20px 0;">
                       Reset Password
                    </a>
                    <p style="color: #666; font-size: 13px;">Questo link scadrà tra 30 minuti.</p>
                    <p style="color: #666; font-size: 13px;">Se non hai richiesto questo reset, ignora questa email.</p>
                </div>
                END;

                $mail->send();
            } catch (Exception $e) {
       
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Inviata - ClickNeat</title>
    <link rel="stylesheet" href="../css/style.css?v=1.0">
</head>

<body>
    <div class="container">
        <h2>✉️ Controlla la tua email</h2>
        <p style="text-align: center; margin-bottom: 20px;">
            Se l'indirizzo email è registrato nel nostro sistema, riceverai un link per reimpostare la password.
        </p>
        <p style="text-align: center; color: #7e22ce; font-weight: 600;">
            Controlla anche la cartella spam!
        </p>
        <div style="text-align: center; margin-top: 30px;">
            <a href="login.php" style="text-decoration: none; color: #1e3c72;">← Torna al login</a>
        </div>
    </div>
</body>

</html>