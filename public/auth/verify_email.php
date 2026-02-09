<?php
require_once "../../config/db.php";
require_once "../../models/auth/EmailVerificationModel.php"; 

$token = isset($_GET["token"]) ? $_GET["token"] : null;
$success = false;
$error = "";

if (!$token) {
    $error = "Token mancante.";
} else {
    $token_hash = hash("sha256", $token);
    $verifyModel = new EmailVerificationModel($db);

    if ($verifyModel->verifyToken($token_hash)) {
        $success = true;
    } else {
        $error = "Link non valido o scaduto.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Verifica Email</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.0">
</head>
<body>
    <div class="container" style="text-align: center;">
        <?php if ($success): ?>
            <h2 style="color: green;">Email Verificata!</h2>
            <p><a href="login.php">Vai al Login</a></p>
        <?php else: ?>
            <h2 style="color: red;">Errore</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>