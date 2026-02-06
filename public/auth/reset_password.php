<?php
require_once "../../config/db.php";
require_once "../../models/User.php";

$token = $_POST['token'] ?? $_GET['token'] ?? null;

if (!$token) {
    die("Token non fornito.");
}

$token_hash = hash("sha256", $token);

$userModel = new User($db);

$user = $userModel->findByResetToken($token_hash);

if (!$user) {
    die("Token non valido o già utilizzato.");
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("Token scaduto. Richiedi un nuovo reset.");
}

$password_err = $confirm_password_err = "";
$password = $confirm_password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

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
        $password = trim($_POST["password"]);
    }

    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Conferma la password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Le password non coincidono.";
        }
    }

    if (empty($password_err) && empty($confirm_password_err)) {

        $param_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

        if ($userModel->resetPassword($user["id"], $param_password)) {
            header("location: login.php?password_updated=1");
            exit();
        } else {
            echo "Qualcosa è andato storto durante l'aggiornamento. Riprova.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Reset Password - ClickNeat</title>
    <link rel="stylesheet" href="../css/style.css?v=1.0">
</head>

<body>
    <div class="container">
        <h2>Reset Password</h2>
        <p>Inserisci la tua nuova password.</p>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label>Nuova Password</label>
                <input type="password" name="password"
                    class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="error"><?php echo $password_err; ?></span>
            </div>

            <div class="form-group">
                <label>Conferma Password</label>
                <input type="password" name="confirm_password"
                    class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="error"><?php echo $confirm_password_err; ?></span>
            </div>

            <div class="form-group">
                <button type="submit">Salva Password</button>
            </div>
        </form>
    </div>
</body>

</html>