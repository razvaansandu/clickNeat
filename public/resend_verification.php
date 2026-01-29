<?php
require_once "../config/db.php";

if (!isset($_GET['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_GET['email'];

$new_token = bin2hex(random_bytes(16));
$new_token_hash = hash("sha256", $new_token);

$sql = "UPDATE users SET email_verify_token = ? WHERE email = ? AND email_verified = 0";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $new_token_hash, $email);
    mysqli_stmt_execute($stmt);
    
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        
        $mail = require __DIR__ . "/../src/mailer.php";
        
        try {
            $mail->addAddress($email);
            $mail->Subject = "Verifica la tua email (Nuovo tentativo) - ClickNeat";
            $mail->Body = <<<END
            <div style="font-family: Arial, sans-serif; padding: 20px;">
                <h2 style="color: #1e3c72;">Verifica il tuo account</h2>
                <p>Hai richiesto un nuovo link di verifica.</p>
                <a href="http://localhost/verify_email.php?token=$new_token" 
                   style="padding: 10px 20px; background-color: #1A4D4E; color: white; text-decoration: none; border-radius: 5px;">
                   Verifica Email
                </a>
            </div>
            END;
            
            $mail->send();
        } catch (Exception $e) {
            // errore log
    }
    mysqli_stmt_close($stmt);
}

header("Location: login.php?resent=1");
exit();}
?>