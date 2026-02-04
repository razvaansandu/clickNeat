<?php
require_once "../../config/db.php";

$email = $_POST["email"];

$token = bin2hex(random_bytes(16));

$token_hash = hash("sha256", $token);

$expiry = date("Y-m-d H:i:s", time() + 60 * 30);

$sql = "UPDATE users 
        SET reset_token_hash = ?, 
            reset_token_expires_at = ? 
        WHERE email = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "sss", $token_hash, $expiry, $email);
    mysqli_stmt_execute($stmt);
    
    if(mysqli_stmt_affected_rows($stmt) > 0){
        $mail = require __DIR__ . "/../src/mailer.php";
        
        try {
            $mail->setFrom("noreply@clickneat.com", "ClickNeat");
            $mail->addAddress($email);
            $mail->Subject = "Reset Password - ClickNeat";
            $mail->Body = <<<END
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #1e3c72;">Reset Password ClickNeat</h2>
                <p>Hai richiesto di reimpostare la tua password.</p>
                <p>Clicca sul pulsante qui sotto per procedere:</p>
                <a href="http://localhost/reset_password.php?token=$token" 
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
            echo "Errore invio email: {$mail->ErrorInfo}";
            exit;
        }
    }
    
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
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
        <p style="text-align: center; margin-top: 30px;">
            <a href="login.php">← Torna al login consumatore</a>
        </p>
        <p style="text-align: center;">
            <a href="login.php">← Torna al login ristoratore</a>
        </p>
    </div>
</body>
</html>
