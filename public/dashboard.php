<?php
require_once "../config/db.php";
session_start();

if(!isset($_SESSION["user_id"])){
    header("location: login.php");
    exit;
}

// Controlla timeout sessione (30 minuti di inattività)
if(isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 1800){
    session_destroy();
    header("location: login.php?timeout=1");
    exit;
}

// Aggiorna timestamp di attività
$_SESSION['login_time'] = time();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - clickNeat</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <p>Questo è il tuo pannello di controllo clickNeat.</p>
        <a href="logout.php" class="btn-logout">Esci</a>
    </div>
</body>
</html>
