<?php
require_once "config.php";

$token = $_GET["token"] ?? null;

if(!$token){
    die("Token non fornito");
}

// Calcola hash del token
$token_hash = hash("sha256", $token);

// Cerca il token nel database
$sql = "SELECT * FROM users WHERE reset_token_hash = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $token_hash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if(!$user){
        die("Token non trovato");
    }
    
    // Verifica scadenza
    if(strtotime($user["reset_token_expires_at"]) <= time()){
        die("Token scaduto");
    }
}

// Gestione form
$password_err = $confirm_password_err = "";
$password = $confirm_password = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Valida password
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
    } elseif(!preg_match('/[!@#$%^&*()_+\\-=\\[\\]{};:\'",.<>?]/', $_POST["password"])){
        $password_err = "La password deve contenere almeno un carattere speciale.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Conferma password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Conferma la password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Le password non coincidono.";
        }
    }
    
    // Aggiorna password
    if(empty($password_err) && empty($confirm_password_err)){
        $sql = "UPDATE users 
                SET password = ?, 
                    reset_token_hash = NULL, 
                    reset_token_expires_at = NULL 
                WHERE id = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            $param_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
            mysqli_stmt_bind_param($stmt, "si", $param_password, $user["id"]);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: login_" . $user["ruolo"] . ".php");
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <h2>Reset Password</h2>
        <form action="" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label>Nuova Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Conferma Password</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Reset Password">
            </div>
        </form>
    </div>
</body>
</html>
