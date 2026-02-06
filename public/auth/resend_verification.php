<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";         
require_once "../../models/User.php";       

if (!isset($_GET['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_GET['email'];

$userModel = new User($db);

$user = $userModel->findByEmail($email);

if ($user && $user['email_verified'] == 0) {
    
    $new_token = bin2hex(random_bytes(16));
    $new_token_hash = hash("sha256", $new_token);

    if ($userModel->updateVerifyToken($user['id'], $new_token_hash)) {
        
        $mail = require __DIR__ . "/../../src/mailer.php";
        
        try {            
            $mail->addAddress($email);
            $mail->Subject = "Verifica la tua email (Nuovo tentativo) - ClickNeat";
            
            $verify_link = "http://localhost:8000/verify_email.php?token=$new_token";

            $mail->Body = <<<END
            <div style="font-family: Arial, sans-serif; padding: 20px;">
                <h2 style="color: #1e3c72;">Verifica il tuo account</h2>
                <p>Hai richiesto un nuovo link di verifica.</p>
                <a href="$verify_link" 
                   style="display: inline-block; padding: 10px 20px; background-color: #1A4D4E; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                   Verifica Email
                </a>
                <p style="color: #666; font-size: 13px; margin-top: 20px;">Se non hai richiesto questa email, ignorala.</p>
            </div>
            END;
            
            $mail->send();
        } catch (Exception $e) {
        }
    }
}

header("Location: login.php?resent=1");
exit();
?>