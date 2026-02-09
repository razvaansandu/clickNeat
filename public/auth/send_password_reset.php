<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/PasswordResetModel.php"; 

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';

    if (!empty($email)) {
        $resetModel = new PasswordResetModel($db);

        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); 

        if ($resetModel->saveResetToken($email, $token_hash, $expiry)) {
            $mail = require __DIR__ . "/../../src/mailer.php";
            try {
                $mail->addAddress($email);
                $mail->Subject = "Reset Password - ClickNeat";
                $reset_link = "http://localhost:8000/public/auth/reset_password.php?token=$token";
                $mail->Body = "Clicca qui per resettare: <a href='$reset_link'>Reset Password</a>";
                $mail->send();
            } catch (Exception $e) {}
        }
    }
    $msg = "Se l'email esiste, riceverai le istruzioni.";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Recupero Password</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.0">
</head>
<body>
    <div class="container">
        <h2>Recupero Password</h2>
        <?php if ($msg): ?>
            <div class="alert" style="color: green; border-color: green;"><?php echo $msg; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <button type="submit">Invia Link</button>
            </div>
        </form>
        <p style="text-align: center;"><a href="login.php">Torna al Login</a></p>
    </div>
</body>
</html>