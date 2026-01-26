<?php
require_once "../config/db.php";
require_once "../src/rate_limiter.php";

$login_err = "";
$username = "";
$blocked = false;
$show_resend = false;  
$email_to_resend = "";

if (isset($_GET['timeout'])) {
    $login_err = "Sessione scaduta. Effettua nuovamente il login.";
}
if (isset($_GET['security'])) {
    $login_err = "Rilevata attività sospetta. Effettua nuovamente il login.";
}
if (isset($_GET['resent'])) {
    $login_err = "<span style='color:green;'>Nuova email di verifica inviata! Controlla la posta.</span>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $attempts = check_login_attempts($link, $username);
    if ($attempts >= 5) {
        $login_err = "Troppi tentativi falliti. Riprova tra 15 minuti.";
        $blocked = true;
    }

    if (!$blocked) {
        if (empty($username)) {
            $login_err = "Inserisci username o email.";
        } elseif (empty($password)) {
            $login_err = "Inserisci la password.";
        } else {
            $sql = "SELECT id, username, password, ruolo, email_verified, email FROM users WHERE (username = ? OR email = ?) AND ruolo = 'ristoratore'";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_username);
                $param_username = $username;

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $db_username, $hashed_password, $ruolo, $email_verified, $db_email);
                        if (mysqli_stmt_fetch($stmt)) {
                            if ($hashed_password !== null && password_verify($password, $hashed_password)) {

                                if ($email_verified == 0) {
                                    $login_err = "Email non verificata.";
                                    $show_resend = true;
                                    $email_to_resend = $db_email;
                                } else {
                                    mysqli_stmt_close($stmt);
                                    clear_login_attempts($link, $username);
                                    session_regenerate_id(true);

                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $db_username;
                                    $_SESSION["ruolo"] = $ruolo;
                                    $_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
                                    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
                                    $_SESSION['LAST_ACTIVITY'] = time();
                                    $_SESSION['CREATED'] = time();

                                    mysqli_close($link);
                                    header("Location: dashboard_ristoratore.php");
                                    exit();
                                }
                            } else {
                                record_failed_attempt($link, $username);
                                $remaining = 5 - check_login_attempts($link, $username);
                                $login_err = ($remaining > 0) ? "Username o password non validi. Tentativi rimasti: $remaining" : "Troppi tentativi falliti.";
                            }
                        }
                    } else {
                        record_failed_attempt($link, $username);
                        $remaining = 5 - check_login_attempts($link, $username);
                        $login_err = ($remaining > 0) ? "Username o password non validi. Tentativi rimasti: $remaining" : "Troppi tentativi falliti.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Ristoratore - ClickNeat</title>
    <link rel="stylesheet" href="css/style.css?v=1.0">
</head>
<body>

    <div class="container">
        <div class="logo-area" style="text-align: center;">
            <img src="image/image.png" alt="ClickNeat Logo" width="150" style="mix-blend-mode: multiply;">
        </div>

        <h2>Accedi come Ristoratore</h2>
        
        <?php if(!empty($login_err)): ?>
            <div class="alert">
                <?php echo $login_err; ?>
                
                <?php if ($show_resend): ?>
                    <div style="margin-top: 10px; font-size: 0.9em;">
                        Non hai ricevuto l'email? 
                        <a href="resend_verification.php?email=<?php echo urlencode($email_to_resend); ?>" 
                           style="color: #742a2a; font-weight: bold; text-decoration: underline;">
                            Invia di nuovo
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username o Email</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" autocomplete="username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" autocomplete="current-password" required>
            </div>
            
            <button type="submit">Accedi</button>
        </form>
        
        <div class="extra-links" style="text-align: center; margin-top: 20px;">
            <a href="forgot_password.php">Password dimenticata?</a><br><br>
            
            <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                Sei un consumatore? <a href="login_consumatore.php" style="font-weight: bold;">Accedi qui</a>
            </div>

            <div style="margin-top: 10px;">
                Non hai un account? <a href="register.php">Registrati ora</a>
            </div>
        </div>
    </div>
</body>
</html>