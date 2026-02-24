<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/EmailVerificationModel.php";

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    header("Location: login.php");
    exit;
}

$verifyModel = new EmailVerificationModel($db);

$user = $verifyModel->findByUsernameOrEmail($email);

if ($user && $user['email_verified'] == 0) {
    
    $new_token = bin2hex(random_bytes(16));
    $new_hash = hash("sha256", $new_token);

    if ($verifyModel->refreshVerifyToken($email, $new_hash)) {
        
        $mail = require __DIR__ . "/../../src/mailer.php";
        
        try {
            $mail->addAddress($email);
            $mail->Subject = "Verifica la tua email - ClickNeat";
            $verify_link = "http://localhost:8000/auth/verify_email.php?token=$new_token";

            $mail->Body = "<h3>Verifica Account</h3>
                           <p>Clicca qui per verificare la tua email:</p>
                           <a href='$verify_link'>Verifica Email</a>";
            $mail->send();
        } catch (Exception $e) {}
    }
}

header("Location: login.php?resent=1");
exit;
?>