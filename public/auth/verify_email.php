<?php
require_once "../../config/db.php";         
require_once "../../models/User.php";       

$token = $_GET["token"] ?? null;

if (!$token) {
    die("Token non valido.");
}

$token_hash = hash("sha256", $token);

$userModel = new User($db);

if ($userModel->verifyEmail($token_hash)) {
    $success = true;
} else {
    $error = "Token non valido, scaduto o account già attivato.";
}

$db->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Email - ClickNeat</title>
    <link rel="stylesheet" href="../css/style.css?v=1.0">
</head>
<body>
    <div class="container">
        <?php if(isset($success)): ?>
            <h2>Email Verificata!</h2>
            <p style="text-align: center;">La tua email è stata verificata con successo.</p>
            <p style="text-align: center; margin-top: 30px;">
                <a href="login.php" style="display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #1e3c72, #7e22ce); color: white; text-decoration: none; border-radius: 10px; font-weight: bold;">
                    Vai al Login
                </a>
            </p>
        <?php else: ?>
            <h2>❌ Errore</h2>
            <p style="text-align: center; color: #e53e3e;"><?php echo $error; ?></p>
            <p style="text-align: center; margin-top: 20px;">
                <a href="register.php">Torna alla registrazione</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>