<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: login.php"); exit; }
$user_id = $_SESSION["id"];
$msg = ""; $msg_type = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_info'])) {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);
        if (!empty($new_username) && !empty($new_email)) {
            $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $new_username, $new_email, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['username'] = $new_username; 
                    $msg = "Dati aggiornati con successo!"; $msg_type = "success";
                } else { $msg = "Errore: Username o Email già in uso."; $msg_type = "error"; }
            }
        }
    }
    if (isset($_POST['update_pass'])) {
        $old_pass = $_POST['old_password']; $new_pass = $_POST['new_password'];
        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id); mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $hashed_password); mysqli_stmt_fetch($stmt); mysqli_stmt_close($stmt);
            if (password_verify($old_pass, $hashed_password)) {
                $new_param_password = password_hash($new_pass, PASSWORD_DEFAULT);
                $sql_upd = "UPDATE users SET password = ? WHERE id = ?";
                if ($stmt_upd = mysqli_prepare($link, $sql_upd)) {
                    mysqli_stmt_bind_param($stmt_upd, "si", $new_param_password, $user_id);
                    mysqli_stmt_execute($stmt_upd);
                    $msg = "Password aggiornata correttamente!"; $msg_type = "success";
                }
            } else { $msg = "La vecchia password non è corretta."; $msg_type = "error"; }
        }
    }
}

$username = $email = $created_at = "";
$sql = "SELECT username, email, created_at FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id); mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $username, $email, $created_at); mysqli_stmt_fetch($stmt); mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il mio Profilo - ClickNeat</title>
    <link rel="stylesheet" href="../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item">
                <i class="fa-solid fa-house"></i> <span>Home</span>
            </a>
            <a href="storico.php" class="nav-item">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span>
            </a>
            <a href="profile_consumatore.php" class="nav-item active">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="help.php" class="nav-item">
    <i class="fa-solid fa-circle-question"></i> <span>Aiuto</span>
</a>
            <a href="../auth/logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <div class="hero-title"><h1>Il mio Profilo</h1><p>Gestisci le tue informazioni e la sicurezza dell'account.</p></div>
        </div>
    </header>

    <div class="main-container">
        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="profile-wrapper">
            <div class="card-style avatar-box">
                <div class="avatar-circle"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <h2 style="color: #2B3674; font-size: 20px;"><?php echo htmlspecialchars($username); ?></h2>
                <span class="status-badge active" style="margin-top:5px;">Consumatore</span>
                <div class="info-list">
                    <div class="info-row"><span>Iscritto dal</span><b><?php echo date("d M Y", strtotime($created_at)); ?></b></div>
                    <div class="info-row"><span>Email</span><b><?php echo htmlspecialchars($email); ?></b></div>
                    <div class="info-row"><span>Stato</span><b style="color: #05CD99;">Attivo </b></div>
                </div>
            </div>

            <div class="card-style form-box">
                <div class="form-title">Modifica Dati Personali</div>
                <form method="POST" action="profile.php">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="input-group"><label>Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required></div>
                        <div class="input-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></div>
                    </div>
                    <div style="text-align: right;"><button type="submit" name="update_info" class="btn-save">Salva Modifiche</button></div>
                </form>

                <div style="margin: 40px 0;"></div>

                <div class="form-title">Sicurezza Password</div>
                <form method="POST" action="profile.php">
                    <div class="input-group"><label>Password Attuale</label><input type="password" name="old_password" placeholder="Conferma la tua identità" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="input-group"><label>Nuova Password</label><input type="password" name="new_password" placeholder="Min. 8 caratteri" required></div>
                        <div class="input-group"><label>Conferma Password</label><input type="password" name="confirm_password" placeholder="Ripeti password" required></div>
                    </div>
                    <div style="text-align: right;"><button type="submit" name="update_pass" class="btn-save" style="background-color: #2B3674;">Aggiorna Password</button></div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>