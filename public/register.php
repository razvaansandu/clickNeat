<?php
require_once "../config/db.php";

 
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

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    
    if(!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])){
        die("Errore CSRF token");
    }
    
    
    if(empty(trim($_POST["username"]))){
        $username_err = "Inserisci un username.";
    } elseif(strlen(trim($_POST["username"])) < 3){
        $username_err = "L'username deve avere almeno 3 caratteri.";
    } elseif(strlen(trim($_POST["username"])) > 50){
        $username_err = "L'username è troppo lungo.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Questo username è già in uso.";
                } else {
                    $username = trim($_POST["username"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    
    if(empty(trim($_POST["email"]))){
        $email_err = "Inserisci un'email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Email non valida.";
    } elseif(strlen(trim($_POST["email"])) > 100){
        $email_err = "Email troppo lunga.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Questa email è già registrata.";
                } else {
                    $email = trim($_POST["email"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    
    if(empty($_POST["ruolo"])){
        $ruolo_err = "Seleziona un ruolo.";
    } elseif($_POST["ruolo"] !== "consumatore" && $_POST["ruolo"] !== "ristoratore"){
        $ruolo_err = "Ruolo non valido.";
    } else {
        $ruolo = $_POST["ruolo"];
    }
    
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Inserisci una password.";
    } elseif(strlen(trim($_POST["password"])) < PASSWORD_MIN_LENGTH){
        $password_err = "La password deve avere almeno " . PASSWORD_MIN_LENGTH . " caratteri.";
    } elseif(!preg_match('/[A-Z]/', $_POST["password"])){
        $password_err = "La password deve contenere almeno una lettera maiuscola.";
    } elseif(!preg_match('/[a-z]/', $_POST["password"])){
        $password_err = "La password deve contenere almeno una lettera minuscola.";
    } elseif(!preg_match('/[0-9]/', $_POST["password"])){
        $password_err = "La password deve contenere almeno un numero.";
    } elseif(!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?]/', $_POST["password"])){
        $password_err = "La password deve contenere almeno un carattere speciale.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Conferma la password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Le password non coincidono.";
        }
    }
    
    
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($ruolo_err)){
        $sql = "INSERT INTO users (username, email, password, ruolo) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssss", $param_username, $param_email, $param_password, $param_ruolo);
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
            $param_ruolo = $ruolo;
            
            if(mysqli_stmt_execute($stmt)){
                $user_id = mysqli_insert_id($link);
                
                
                $verify_token = bin2hex(random_bytes(16));
                $verify_token_hash = hash("sha256", $verify_token);
                
                
                $sql2 = "UPDATE users SET email_verify_token = ? WHERE id = ?";
                if($stmt2 = mysqli_prepare($link, $sql2)){
                    mysqli_stmt_bind_param($stmt2, "si", $verify_token_hash, $user_id);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                }
                
                
                $mail = require __DIR__ . "/../src/mailer.php";
                
                try {
                    $mail->setFrom("clickneat2026@gmail.com", "ClickNeat");
                    $mail->addAddress($email);
                    $mail->Subject = "Verifica la tua email - ClickNeat";
                    $mail->Body = <<<END
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #1e3c72;">Benvenuto su ClickNeat, $username!</h2>
                        <p>Grazie per esserti registrato. Per completare la registrazione, verifica la tua email cliccando sul pulsante qui sotto:</p>
                        <a href="http://localhost/verify_email.php?token=$verify_token" 
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
                echo "Qualcosa è andato storto. Riprova.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($link);
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - ClickNeat</title>
    <link rel="stylesheet" href="css/style.css?v=1.0">
</head>
<body>
    <div class="container">

        <h2>Registrazione</h2>
        <p style="text-align: center; margin-bottom: 30px;">Crea il tuo account ClickNeat</p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" autocomplete="username">
                <?php if(!empty($username_err)): ?>
                    <span class="error"><?php echo $username_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" autocomplete="email">
                <?php if(!empty($email_err)): ?>
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
                <?php if(!empty($ruolo_err)): ?>
                    <span class="error"><?php echo $ruolo_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" autocomplete="new-password">
                <?php if(!empty($password_err)): ?>
                    <span class="error"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Conferma Password</label>
                <input type="password" name="confirm_password" autocomplete="new-password">
                <?php if(!empty($confirm_password_err)): ?>
                    <span class="error"><?php echo $confirm_password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit">Registrati</button>
            </div>
            
            <p style="text-align: center; margin-top: 20px;">
                Hai già un account? <a href="login_consumatore.php">Accedi qui</a>
            </p>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.querySelector('input[name="password"]');
        
        if(passwordInput){
            const indicator = document.createElement('div');
            indicator.style.cssText = 'margin-top: 10px; padding: 10px; border-radius: 5px; font-size: 13px; font-weight: 600;';
            passwordInput.parentElement.appendChild(indicator);
            
            const strengthBar = document.createElement('div');
            strengthBar.style.cssText = 'height: 5px; border-radius: 3px; margin-top: 5px; transition: all 0.3s;';
            indicator.appendChild(strengthBar);
            
            const strengthText = document.createElement('div');
            strengthText.style.marginTop = '5px';
            indicator.appendChild(strengthText);
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if(password.length >= 8) strength++;
                if(password.length >= 12) strength++;
                if(/[a-z]/.test(password)) strength++;
                if(/[A-Z]/.test(password)) strength++;
                if(/[0-9]/.test(password)) strength++;
                if(/[!@#$%^&*()_+\-=\[\]{};:'",.<>?]/.test(password)) strength++;
                
                let color, text, width;
                
                if(strength <= 2){
                    color = '#e53e3e';
                    text = 'Debole';
                    width = '33%';
                } else if(strength <= 4){
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