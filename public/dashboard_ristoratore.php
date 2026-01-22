<?php
// config.php ha già session_start(), non serve ripeterlo
require_once "../config/db.php";

// Controlla se l'utente è loggato (usa "id" non "user_id")
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "ristoratore"){
    header("Location: login_ristoratore.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ristoratore - ClickNeat</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Benvenuto Ristoratore, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <p>Qui puoi gestire il tuo ristorante e gli ordini ricevuti.</p>
        <a href="logout.php" class="btn-logout" style="display:inline-block;">Esci</a>
    </div>
</body>
</html>
