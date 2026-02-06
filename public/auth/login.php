<?php
require_once "../../config/db.php";          
require_once "../../src/rate_limiter.php";   
require_once "../../config/google_config.php";
require_once "../../models/User.php";        

$login_url = getGoogleLoginUrl(); 
$userModel = new User($db); 

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
            $user = $userModel->findByUsernameOrEmail($username);

            if ($user) {
                if ($user['password'] !== null && password_verify($password, $user['password'])) {

                    if ($user['email_verified'] == 0) {
                        $login_err = "Email non verificata.";
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
                    $login_err = "Username o password non validi. ($remaining tentativi rimasti)";
                }
            } else {
                record_failed_attempt($db, $username);
                $remaining = 5 - check_login_attempts($db, $username);
                $login_err = "Username o password non validi. ($remaining tentativi rimasti)";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=1.0">
    
    <style>
        .gsi-material-button {
            -moz-user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            -webkit-appearance: none;
            background-color: WHITE;
            background-image: none;
            border: 1px solid #747775;
            -webkit-border-radius: 4px;
            border-radius: 4px;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            color: #1f1f1f;
            cursor: pointer;
            font-family: 'Roboto', arial, sans-serif;
            font-size: 14px;
            height: 40px;
            letter-spacing: 0.25px;
            outline: none;
            overflow: hidden;
            padding: 0 12px;
            position: relative;
            text-align: center;
            -webkit-transition: background-color .218s, border-color .218s, box-shadow .218s;
            transition: background-color .218s, border-color .218s, box-shadow .218s;
            vertical-align: middle;
            white-space: nowrap;
            width: 100%;
            max-width: 400px;
            min-width: min-content;
        }
        .gsi-material-button .gsi-material-button-icon {
            height: 20px;
            margin-right: 12px;
            min-width: 20px;
            width: 20px;
        }
        .gsi-material-button .gsi-material-button-content-wrapper {
            -webkit-align-items: center;
            align-items: center;
            display: flex;
            -webkit-flex-direction: row;
            flex-direction: row;
            -webkit-flex-wrap: nowrap;
            flex-wrap: nowrap;
            height: 100%;
            justify-content: center;
            position: relative;
            width: 100%;
        }
        .gsi-material-button .gsi-material-button-contents {
            -webkit-flex-grow: 0;
            flex-grow: 0;
            font-family: 'Roboto', arial, sans-serif;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: top;
        }
        .gsi-material-button .gsi-material-button-state {
            -webkit-transition: opacity .218s;
            transition: opacity .218s;
            bottom: 0;
            left: 0;
            opacity: 0;
            position: absolute;
            right: 0;
            top: 0;
        }
        .gsi-material-button:not(:disabled):active .gsi-material-button-state, 
        .gsi-material-button:not(:disabled):focus .gsi-material-button-state {
            background-color: #303030;
            opacity: 12%;
        }
        .gsi-material-button:not(:disabled):hover {
            -webkit-box-shadow: 0 1px 2px 0 rgba(60, 64, 67, .30), 0 1px 3px 1px rgba(60, 64, 67, .15);
            box-shadow: 0 1px 2px 0 rgba(60, 64, 67, .30), 0 1px 3px 1px rgba(60, 64, 67, .15);
        }
        .gsi-material-button:not(:disabled):hover .gsi-material-button-state {
            background-color: #303030;
            opacity: 8%;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="logo-area" style="text-align: center; margin-bottom: 20px;">
            <img src="../image/image.png" alt="ClickNeat Logo" width="150" style="mix-blend-mode: multiply;">
        </div>

        <h2>Accedi a ClickNeat</h2>

        <?php if (!empty($login_err)): ?>
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
                <label for="username">Username o Email</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       autocomplete="username" required placeholder="Inserisci username o email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       autocomplete="current-password" required placeholder="Inserisci la tua password">
            </div>

            <button type="submit" class="btn-login">Accedi</button>
        </form>

        <div style="text-align: center; margin: 15px 0; color: #666;">oppure</div>

        <div style="display: flex; justify-content: center; width: 100%;">
            <button type="button" class="gsi-material-button" onclick="window.location.href='<?php echo $login_url; ?>'">
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
            </button>
        </div>

        <div class="extra-links" style="text-align: center; margin-top: 25px;">
            <a href="forgot_password.php">Password dimenticata?</a>
            <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                Non hai un account? <a href="register.php" style="font-weight: bold;">Registrati ora</a>
            </div>
        </div>
    </div>

</body>
</html>