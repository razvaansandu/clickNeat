<?php

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";         
require_once "../config/google_config.php"; 
require_once "../../models/User.php";       

$register_url = getGoogleLoginUrl();
$userModel = new User($db); 

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$username_err = $email_err = $password_err = $confirm_password_err = $ruolo_err = "";
$username = $email = $ruolo = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Errore di sicurezza (CSRF token non valido).");
    }

    if (empty(trim($_POST["username"]))) {
        $username_err = "Inserisci un username.";
    } elseif (strlen(trim($_POST["username"])) < 3) {
        $username_err = "L'username deve avere almeno 3 caratteri.";
    } elseif (strlen(trim($_POST["username"])) > 50) {
        $username_err = "L'username è troppo lungo.";
    } else {
        if ($userModel->findByUsername(trim($_POST["username"]))) {
            $username_err = "Questo username è già in uso.";
        } else {
            $username = trim($_POST["username"]);
        }
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Inserisci un'email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Email non valida.";
    } elseif (strlen(trim($_POST["email"])) > 100) {
        $email_err = "Email troppo lunga.";
    } else {
        if ($userModel->findByEmail(trim($_POST["email"]))) {
            $email_err = "Questa email è già registrata.";
        } else {
            $email = trim($_POST["email"]);
        }
    }

    if (empty($_POST["ruolo"])) {
        $ruolo_err = "Seleziona un ruolo.";
    } elseif ($_POST["ruolo"] !== "consumatore" && $_POST["ruolo"] !== "ristoratore") {
        $ruolo_err = "Ruolo non valido.";
    } else {
        $ruolo = $_POST["ruolo"];
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Inserisci una password.";
    } elseif (strlen(trim($_POST["password"])) < PASSWORD_MIN_LENGTH) {
        $password_err = "La password deve avere almeno " . PASSWORD_MIN_LENGTH . " caratteri.";
    } elseif (!preg_match('/[A-Z]/', $_POST["password"])) {
        $password_err = "La password deve contenere almeno una lettera maiuscola.";
    } elseif (!preg_match('/[a-z]/', $_POST["password"])) {
        $password_err = "La password deve contenere almeno una lettera minuscola.";
    } elseif (!preg_match('/[0-9]/', $_POST["password"])) {
        $password_err = "La password deve contenere almeno un numero.";
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?]/', $_POST["password"])) {
        $password_err = "La password deve contenere almeno un carattere speciale.";
    } else {
        $password_plain = trim($_POST["password"]);
    }

    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Conferma la password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password_plain != $confirm_password)) {
            $confirm_password_err = "Le password non coincidono.";
        }
    }

    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($ruolo_err)) {
        
        $hashed_password = password_hash($password_plain, PASSWORD_DEFAULT, ['cost' => 12]);
        
        $user_id = $userModel->create($username, $email, $hashed_password, $ruolo, 0);

        if ($user_id) {
            
            $verify_token = bin2hex(random_bytes(16));
            $verify_token_hash = hash("sha256", $verify_token);

            $userModel->updateVerifyToken($user_id, $verify_token_hash);

            $mail = require __DIR__ . "/../src/mailer.php";

            try {
                $mail->setFrom("clickneat2026@gmail.com", "ClickNeat");
                $mail->addAddress($email);
                $mail->Subject = "Verifica la tua email - ClickNeat";
                
                $verify_link = "http://localhost:8000/verify_email.php?token=$verify_token";

                $mail->Body = <<<END
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #1e3c72;">Benvenuto su ClickNeat, $username!</h2>
                    <p>Grazie per esserti registrato. Per completare la registrazione, verifica la tua email cliccando sul pulsante qui sotto:</p>
                    <a href="$verify_link" 
                       style="display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #1e3c72, #7e22ce); 
                       color: white; text-decoration: none; border-radius: 10px; font-weight: bold; margin: 20px 0;">
                       Verifica Email
                    </a>
                    <p style="color: #666; font-size: 13px;">Se non ti sei registrato, ignora questa email.</p>
                    <p style="color: #666; font-size: 13px;">Questo link scadrà tra 24 ore.</p>
                </div>
                END;

                $mail->send();
            } catch (Exception $e) {
            }

            header("location: email_sent.php?email=" . urlencode($email));
            exit();

        } else {
            echo "<div class='alert'>Si è verificato un errore durante la creazione dell'account. Riprova.</div>";
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - ClickNeat</title>
    <link rel="stylesheet" href="../css/style.css?v=1.0">
</head>

<body>
    <div class="container">

        <h2>Registrazione</h2>
        <p style="text-align: center; margin-bottom: 30px;">Crea il tuo account ClickNeat</p>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>"
                    autocomplete="username">
                <?php if (!empty($username_err)): ?>
                    <span class="error"><?php echo $username_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" autocomplete="email">
                <?php if (!empty($email_err)): ?>
                    <span class="error"><?php echo $email_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Ruolo</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="ruolo" value="consumatore" <?php echo ($ruolo == "consumatore") ? "checked" : ""; ?>>
                        <span class="custom-radio"></span>
                        Consumatore
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="ruolo" value="ristoratore" <?php echo ($ruolo == "ristoratore") ? "checked" : ""; ?>>
                        <span class="custom-radio"></span>
                        Ristoratore
                    </label>
                </div>
                <?php if (!empty($ruolo_err)): ?>
                    <span class="error"><?php echo $ruolo_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" autocomplete="new-password">
                <?php if (!empty($password_err)): ?>
                    <span class="error"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Conferma Password</label>
                <input type="password" name="confirm_password" autocomplete="new-password">
                <?php if (!empty($confirm_password_err)): ?>
                    <span class="error"><?php echo $confirm_password_err; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <button type="submit">Registrati</button>
            </div>

            <div style="display: flex; justify-content: center; width: 100%; margin-top: 15px;">

                <button type="button" class="gsi-material-button"
                    onclick="window.location.href='<?php echo $register_url; ?>'">
                    <div class="gsi-material-button-state"></div>
                    <div class="gsi-material-button-content-wrapper">
                        <div class="gsi-material-button-icon">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"
                                xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                                <path fill="#EA4335"
                                    d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z">
                                </path>
                                <path fill="#4285F4"
                                    d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z">
                                </path>
                                <path fill="#FBBC05"
                                    d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z">
                                </path>
                                <path fill="#34A853"
                                    d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z">
                                </path>
                                <path fill="none" d="M0 0h48v48H0z"></path>
                            </svg>
                        </div>
                        <span class="gsi-material-button-contents">Registrati con Google</span>
                        <span style="display: none;">Registrati con Google</span>
                    </div>
                </button>

            </div>

            <p style="text-align: center; margin-top: 20px;">
                Hai già un account? <a href="login.php">Accedi qui</a>
            </p>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.querySelector('input[name="password"]');

            if (passwordInput) {
                const indicator = document.createElement('div');
                indicator.style.cssText = 'margin-top: 10px; padding: 10px; border-radius: 5px; font-size: 13px; font-weight: 600;';
                passwordInput.parentElement.appendChild(indicator);

                const strengthBar = document.createElement('div');
                strengthBar.style.cssText = 'height: 5px; border-radius: 3px; margin-top: 5px; transition: all 0.3s;';
                indicator.appendChild(strengthBar);

                const strengthText = document.createElement('div');
                strengthText.style.marginTop = '5px';
                indicator.appendChild(strengthText);

                passwordInput.addEventListener('input', function () {
                    const password = this.value;
                    let strength = 0;

                    if (password.length >= 8) strength++;
                    if (password.length >= 12) strength++;
                    if (/[a-z]/.test(password)) strength++;
                    if (/[A-Z]/.test(password)) strength++;
                    if (/[0-9]/.test(password)) strength++;
                    if (/[!@#$%^&*()_+\-=\[\]{};:'",.<>?]/.test(password)) strength++;

                    let color, text, width;

                    if (strength <= 2) {
                        color = '#e53e3e';
                        text = 'Debole';
                        width = '33%';
                    } else if (strength <= 4) {
                        color = '#ed8936';
                        text = 'Media';
                        width = '66%';
                    } else {
                        color = '#1A4D4E';
                        text = 'Forte';
                        width = '100%';
                    }

                    indicator.style.background = color + '20';
                    strengthBar.style.background = color;
                    strengthBar.style.width = width;
                    strengthText.style.color = color;
                    strengthText.textContent = text;
                });
            }
        });
    </script>
</body>
</html>