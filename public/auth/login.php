<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["ruolo"] === 'ristoratore') {
        header("Location: ../ristoratore/dashboard_ristoratore.php");
    } else {
        header("Location: ../consumatore/dashboard_consumatore.php");
    }
    exit;
}

require_once "../../config/database.php";
require_once "../../config/db.php";          
require_once "../../src/rate_limiter.php";   
require_once "../../config/google_config.php";  
require_once "../../models/LoginModel.php"; 
$login_url = getGoogleLoginUrl(); 
$loginModel = new LoginModel($db); 

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
if (isset($_GET['registered'])) {
    $login_err = "<span style='color:green;'>Registrazione avvenuta! Controlla l'email per verificare l'account.</span>";
}
if (isset($_GET['resent'])) {
    $login_err = "<span style='color:green;'>Nuova email di verifica inviata! Controlla la posta.</span>";
}
if (isset($_GET['password_updated'])) {
    $login_err = "<span style='color:green;'>Password aggiornata. Accedi ora.</span>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    $attempts = check_login_attempts($db, $username);
    
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

            $user = $loginModel->findByUsernameOrEmail($username); 

            if ($user) {
                if ($user['password'] !== null && password_verify($password, $user['password'])) {

                    if ($user['email_verified'] == 0) {
                        $login_err = "Email non verificata. Controlla la posta.";
                        $show_resend = true;
                        $email_to_resend = $user['email'];
                    } else {
                        clear_login_attempts($db, $username);
                        session_regenerate_id(true);     

                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $user['id'];
                        $_SESSION["username"] = $user['username'];
                        $_SESSION["ruolo"] = $user['ruolo'];
                        
                        $_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['LAST_ACTIVITY'] = time();
                        $_SESSION['CREATED'] = time();

                        if ($user['ruolo'] === 'ristoratore') {
                            header("Location: ../ristoratore/dashboard_ristoratore.php");
                        } else {
                            header("Location: ../consumatore/dashboard_consumatore.php");
                        }
                        exit();
                    }
                } else {
                    record_failed_attempt($db, $username);
                    $remaining = 5 - check_login_attempts($db, $username);
                    $login_err = "Credenziali non valide. ($remaining tentativi rimasti)";
                }
            } else {
                record_failed_attempt($db, $username);
                $remaining = 5 - check_login_attempts($db, $username);
                $login_err = "Credenziali non valide. ($remaining tentativi rimasti)";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style.css?v=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="container">
        <h1>Benvenuto in ClickNeat</h1>
        <p style="margin-bottom: 30px;">Accedi per ordinare o gestire il tuo ristorante</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert">' . $login_err . '</div>';
        }
        
        if($show_resend && !empty($email_to_resend)){
            echo '<div style="text-align:center; margin-bottom:20px;">
                    <a href="resend_verification.php?email='.urlencode($email_to_resend).'" style="font-weight:bold; color:#E89020;">
                        Rinvia email di verifica
                    </a>
                  </div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email o Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>    
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group" style="text-align: right; margin-bottom: 20px;">
                <a href="send_password_reset.php" style="font-size: 13px; color: #4A4A4A;">Password dimenticata?</a>
            </div>

            <div class="form-group">
                <button type="submit">Accedi</button>
            </div>
        </form>

        <div style="text-align: center; margin: 20px 0; color: #aaa;">
            <span>oppure</span>
        </div>

        <div style="display: flex; justify-content: center;">
            <a href="<?php echo filter_var($login_url, FILTER_SANITIZE_URL); ?>" style="text-decoration: none; width: 100%; max-width: 400px;">
                <div class="gsi-material-button">
                    <div class="gsi-material-button-state"></div>
                    <div class="gsi-material-button-content-wrapper">
                        <div class="gsi-material-button-icon">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                <path fill="none" d="M0 0h48v48H0z"></path>
                            </svg>
                        </div>
                        <span class="gsi-material-button-contents">Accedi con Google</span>
                        <span style="display: none;">Accedi con Google</span>
                    </div>
                </div>
            </a>
        </div>

        <p>Non hai un account? <a href="register.php">Registrati ora</a>.</p>
    </div>

</body>
</html>