<?php
session_start();
$email = "";
$email_err = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["email"]))){
        $email_err = "Inserisci la tua email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Email non valida.";
    } else {
        $email = trim($_POST["email"]);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dimentica password - clickNeat</title>
    <link rel="stylesheet" href="css/style.css?v=1.0">
</head>
<body>
    <div class="container">
        <h2>Recupera la password</h2>
        <?php if($email_err): ?>
            <div class="alert"> <?php echo $email_err; ?> </div>
        <?php endif; ?>
        <form action="send_password_reset.php" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <button type="submit">Invia link di reset</button>
            </div>
        </form>
        <p><a href="login_consumatore.php">Torna al login consumatore</a> | <a href="login_ristoratore.php">Torna al login ristoratore</a></p>
    </div>
</body>
</html>
