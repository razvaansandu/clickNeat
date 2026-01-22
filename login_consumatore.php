<?php
require_once "config.php";
require_once "rate_limiter.php";

$login_err = "";
$username = "";
$blocked = false;

// Gestisci messaggi da URL
if(isset($_GET['timeout'])){
    $login_err = "Sessione scaduta. Effettua nuovamente il login.";
}
if(isset($_GET['security'])){
    $login_err = "Rilevata attività sospetta. Effettua nuovamente il login.";
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Controlla rate limiting
    $attempts = check_login_attempts($link, $username);
    if($attempts >= 5){
        $login_err = "Troppi tentativi falliti. Riprova tra 15 minuti.";
        $blocked = true;
    }
    
    if(!$blocked){
        if(empty($username)){
            $login_err = "Inserisci username o email.";
        } elseif(empty($password)){
            $login_err = "Inserisci la password.";
        } else {
            $sql = "SELECT id, username, password, ruolo, email_verified FROM users WHERE (username = ? OR email = ?) AND ruolo = 'consumatore'";
            
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_username);
                $param_username = $username;
                
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        mysqli_stmt_bind_result($stmt, $id, $db_username, $hashed_password, $ruolo, $email_verified);
                        if(mysqli_stmt_fetch($stmt)){
                            if($hashed_password !== null && password_verify($password, $hashed_password)){
                                
                                // Verifica email confermata
                                if($email_verified == 0){
                                    $login_err = "Devi verificare la tua email prima di fare login. Controlla la tua casella di posta.";
                                } else {
                                    // Login riuscito - chiudi statement prima del redirect
                                    mysqli_stmt_close($stmt);
                                    
                                    // Pulisci tentativi falliti
                                    clear_login_attempts($link, $username);
                                    
                                    // Rigenera session ID per sicurezza
                                    session_regenerate_id(true);
                                    
                                    // Imposta variabili di sessione
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $db_username;
                                    $_SESSION["ruolo"] = $ruolo;
                                    $_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
                                    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
                                    $_SESSION['LAST_ACTIVITY'] = time();
                                    $_SESSION['CREATED'] = time();
                                    
                                    // Chiudi connessione database
                                    mysqli_close($link);
                                    
                                    // Redirect alla dashboard
                                    header("Location: dashboard_consumatore.php");
                                    exit();
                                }
                            } else {
                                // Password errata
                                record_failed_attempt($link, $username);
                                $remaining = 5 - check_login_attempts($link, $username);
                                if($remaining > 0){
                                    $login_err = "Username o password non validi. Tentativi rimasti: $remaining";
                                } else {
                                    $login_err = "Troppi tentativi falliti. Riprova tra 15 minuti.";
                                }
                            }
                        }
                    } else {
                        // Utente non trovato
                        record_failed_attempt($link, $username);
                        $remaining = 5 - check_login_attempts($link, $username);
                        if($remaining > 0){
                            $login_err = "Username o password non validi. Tentativi rimasti: $remaining";
                        } else {
                            $login_err = "Troppi tentativi falliti. Riprova tra 15 minuti.";
                        }
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
    <title>Login Consumatore - ClickNeat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Accedi come Consumatore</h2>
        
        <?php if(!empty($login_err)): ?>
            <div class="alert">
                <?php echo $login_err; ?>
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
            
            <div class="form-group">
                <button type="submit">Accedi</button>
            </div>
            
            <p style="text-align: center;">
                <a href="forgot_password.php">Hai dimenticato la password?</a>
            </p>
            
            <p style="text-align: center;">
                Non hai un account? <a href="register.php">Registrati ora</a>
            </p>
        </form>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">
        
        <p style="text-align: center;">
            Sei un ristoratore? <a href="login_ristoratore.php">Accedi come ristoratore</a>
        </p>
    </div>
</body>
</html>
