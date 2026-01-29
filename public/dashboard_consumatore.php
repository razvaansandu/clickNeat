<?php
require_once "../config/db.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "consumatore"){
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Consumatore - ClickNeat</title>
    <link rel="stylesheet" href="css/style.css?v=1.0">
</head>
<body>
    <div class="container">
        <h1>Benvenuto Consumatore, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <p>Qui puoi ordinare dai ristoranti.</p>
        <a href="logout.php" class="btn-logout" style="display:inline-block;">Esci</a>
    </div>
</body>
</html>
