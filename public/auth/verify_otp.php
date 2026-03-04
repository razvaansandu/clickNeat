<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/LoginModel.php";

if (!isset($_SESSION['otp_email'])) {
    header("Location: login.php");
    exit();
}

$loginModel = new LoginModel($db);
$login_err  = "";
$email      = $_SESSION['otp_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codice = trim($_POST["codice"] ?? '');

    if (empty($codice)) {
        $login_err = "Inserisci il codice ricevuto.";
    } else {
        $user = $loginModel->verifyOtp($email, $codice);

        if (!$user) {
            $login_err = "Codice non valido o scaduto.";
        } else {
            unset($_SESSION['otp_email']);
            session_regenerate_id(true);

            $_SESSION["loggedin"]      = true;
            $_SESSION["id"]            = $user['id'];
            $_SESSION["username"]      = $user['username'];
            $_SESSION["ruolo"]         = $user['ruolo'];
            $_SESSION['USER_IP']       = $_SERVER['REMOTE_ADDR'];
            $_SESSION['USER_AGENT']    = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['LAST_ACTIVITY'] = time();
            $_SESSION['CREATED']       = time();

            if ($user['ruolo'] === 'ristoratore') {
                header("Location: ../ristoratore/dashboard_ristoratore.php");
            } else {
                header("Location: ../consumatore/dashboard_consumatore.php");
            }
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Codice - ClickNeat</title>
    <link rel="stylesheet" href="../css/style.css?v=1.0">
</head>
<body>
<div class="container">
    <h1>Controlla la tua email</h1>
    <p>Abbiamo inviato un codice a <strong><?php echo htmlspecialchars($email); ?></strong></p>

    <?php if(!empty($login_err)) echo '<div class="alert">' . $login_err . '</div>'; ?>

    <form method="POST">
        <div class="form-group">
            <input type="text" name="codice" maxlength="6" 
                   style="letter-spacing:8px;font-size:24px;text-align:center;" 
                   autocomplete="one-time-code" required autofocus>
        </div>
        <div class="form-group">
            <button type="submit">Accedi</button>
        </div>
    </form>
    <p style="text-align:center;">
        <a href="login.php">← Usa un'altra email</a>
    </p>
</div>
</body>
</html>
