<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../config/google_config.php";
require_once "../../models/RegisterModel.php";

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["ruolo"] === 'ristoratore') {
        header("Location: ../ristoratore/dashboard_ristoratore.php");
    } else {
        header("Location: ../consumatore/dashboard_consumatore.php");
    }
    exit;
}

$register_url  = getGoogleLoginUrl();
$registerModel = new RegisterModel($db);

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$username_err = $email_err = $ruolo_err = "";
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
        if ($registerModel->findByUsername(trim($_POST["username"]))) {
            $username_err = "Questo username è già in uso.";
        } else {
            $username = trim($_POST["username"]);
        }
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Inserisci un'email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Email non valida.";
    } else {
        if ($registerModel->findByEmail(trim($_POST["email"]))) {
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

    if (empty($username_err) && empty($email_err) && empty($ruolo_err)) {

        $result = $registerModel->registerWithEmail($username, $email, $ruolo);

        if ($result) {
            header("Location: login.php?registered=1");
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
    <link rel="stylesheet" href="../../css/style.css?v=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <button type="submit">Registrati</button>
        </div>

        <div style="text-align: center; margin: 20px 0; color: #aaa;">
            <span>oppure</span>
        </div>

        <div style="display: flex; justify-content: center; width: 100%; margin-top: 15px;">
            <a href="<?php echo filter_var($register_url, FILTER_SANITIZE_URL); ?>" style="text-decoration: none; width: 100%; max-width: 400px;">
                <div class="gsi-material-button">
                    <div class="gsi-material-button-state"></div>
                    <div class="gsi-material-button-content-wrapper">
                        <div class="gsi-material-button-icon">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" style="display: block;">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                <path fill="none" d="M0 0h48v48H0z"></path>
                            </svg>
                        </div>
                        <span class="gsi-material-button-contents">Registrati con Google</span>
                        <span style="display: none;">Registrati con Google</span>
                    </div>
                </div>
            </a>
        </div>

        <p style="text-align: center; margin-top: 20px;">
            Hai già un account? <a href="login.php">Accedi qui</a>
        </p>

    </form>
</div>
</body>
</html>
