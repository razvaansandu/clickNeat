<?php
require_once "../../config/db.php";
require_once "../../models/auth/PasswordResetModel.php";

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$error = "";

if (!$token) {
    die("Token mancante.");
}

$resetModel = new PasswordResetModel($db);
$token_hash = hash("sha256", $token);

$user = $resetModel->findByResetToken($token_hash);

if (!$user) {
    die("Token non valido.");
}
if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("Token scaduto.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = "La password deve essere almeno di 8 caratteri.";
    } elseif ($password !== $confirm) {
        $error = "Le password non coincidono.";
    } else {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        
        if ($resetModel->resetPassword($user['id'], $new_hash)) {
            header("Location: login.php?password_updated=1");
            exit;
        } else {
            $error = "Errore durante l'aggiornamento.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuova Password</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.0">
</head>
<body>
    <div class="container">
        <h2>Reimposta Password</h2>
        <?php if($error) echo "<div class='alert'>$error</div>"; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label>Nuova Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Conferma Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <button type="submit">Salva Password</button>
            </div>
        </form>
    </div>
</body>
</html>