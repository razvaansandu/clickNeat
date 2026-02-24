<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/ProfileModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$profileModel = new ProfileModel($db);
$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['update_info'])) {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);

        if (!empty($new_username) && !empty($new_email)) {
            if ($profileModel->updateInfo($user_id, $new_username, $new_email)) {
                $_SESSION['username'] = $new_username;
                $msg = "Dati aggiornati con successo!";
                $msg_type = "success";
            } else {
                $msg = "Errore: Username o Email già in uso.";
                $msg_type = "error";
            }
        }
    }

    if (isset($_POST['update_pass'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass !== $confirm_pass) {
            $msg = "Le nuove password non coincidono.";
            $msg_type = "error";
        } else {
            $hashed_password = $profileModel->getPasswordHash($user_id);

            if ($hashed_password && password_verify($old_pass, $hashed_password)) {
                $new_param_password = password_hash($new_pass, PASSWORD_DEFAULT);

                if ($profileModel->updatePassword($user_id, $new_param_password)) {
                    $msg = "Password aggiornata correttamente!";
                    $msg_type = "success";
                } else {
                    $msg = "Errore aggiornamento password.";
                    $msg_type = "error";
                }
            } else {
                $msg = "La vecchia password non è corretta.";
                $msg_type = "error";
            }
        }
    }
}

$userData = $profileModel->getProfileData($user_id);
$username = $userData['username'];
$email = $userData['email'];
$created_at = $userData['created_at'];
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il mio Profilo - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .msg-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .msg-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .msg-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
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

    <div class="mobile-header-fixed">
        <div class="mobile-top-row">
            <a href="dashboard_consumatore.php" class="brand-logo">
                <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
            </a>
            <a href="../auth/logout.php" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="dashboard_consumatore.php" class="nav-item-bottom <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_consumatore.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i><span>Home</span>
        </a>
        <a href="storico.php" class="nav-item-bottom <?php echo basename($_SERVER['PHP_SELF']) == 'storico.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Ordini</span>
        </a>
        <a href="profile_consumatore.php" class="nav-item-bottom <?php echo basename($_SERVER['PHP_SELF']) == 'profile_consumatore.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i><span>Profilo</span>
        </a>
        <a href="help.php" class="nav-item-bottom <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-question"></i><span>Aiuto</span>
        </a>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <div class="hero-title">
                <h1>Il mio Profilo</h1>
                <p>Gestisci le tue informazioni e la sicurezza dell'account.</p>
            </div>
        </div>
    </header>

    <div class="main-container">

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="profile-wrapper">

            <div class="card-style avatar-box" style="padding: 30px; text-align: center;">
                <div class="avatar-circle" style="width: 100px; height: 100px; background: #E0E5F2; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #2B3674; font-weight: bold;">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <h2 style="color: #2B3674; font-size: 20px; margin-bottom: 5px;"><?php echo htmlspecialchars($username); ?></h2>
                <span class="status-badge active" style="background: #E6FAF5; color: #05CD99; padding: 5px 15px; border-radius: 20px; font-size: 13px; font-weight: 600;">Consumatore</span>

                <div class="info-list" style="margin-top: 30px; text-align: left;">
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
                        <span style="color: #A3AED0;">Iscritto dal</span>
                        <b style="color: #2B3674;"><?php echo date("d M Y", strtotime($created_at)); ?></b>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
                        <span style="color: #A3AED0;">Email</span>
                        <b style="color: #2B3674;"><?php echo htmlspecialchars($email); ?></b>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span style="color: #A3AED0;">Stato</span>
                        <b style="color: #05CD99;">Attivo</b>
                    </div>
                </div>
            </div>

            <div class="card-style form-box" style="padding: 30px;">

                <div class="form-title" style="color: #2B3674; font-size: 18px; font-weight: 700; margin-bottom: 20px;">Modifica Dati Personali</div>
                <form method="POST" action="profile_consumatore.php">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="input-group">
                            <label style="display: block; color: #2B3674; font-size: 14px; margin-bottom: 8px;">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required style="width: 100%; padding: 10px; border: 1px solid #E0E5F2; border-radius: 10px;">
                        </div>
                        <div class="input-group">
                            <label style="display: block; color: #2B3674; font-size: 14px; margin-bottom: 8px;">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required style="width: 100%; padding: 10px; border: 1px solid #E0E5F2; border-radius: 10px;">
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" name="update_info" class="btn-save" style="background: linear-gradient(135deg, #05CD99, #02A176); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 600;">Salva Modifiche</button>
                    </div>
                </form>

                <div style="margin: 40px 0; border-top: 1px solid #eee;"></div>

                <div class="form-title" style="color: #2B3674; font-size: 18px; font-weight: 700; margin-bottom: 20px;">Sicurezza Password</div>
                <form method="POST" action="profile_consumatore.php">
                    <div class="input-group" style="margin-bottom: 20px;">
                        <label style="display: block; color: #2B3674; font-size: 14px; margin-bottom: 8px;">Password Attuale</label>
                        <input type="password" name="old_password" placeholder="Conferma la tua identità" required style="width: 100%; padding: 10px; border: 1px solid #E0E5F2; border-radius: 10px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="input-group">
                            <label style="display: block; color: #2B3674; font-size: 14px; margin-bottom: 8px;">Nuova Password</label>
                            <input type="password" name="new_password" placeholder="Min. 8 caratteri" required style="width: 100%; padding: 10px; border: 1px solid #E0E5F2; border-radius: 10px;">
                        </div>
                        <div class="input-group">
                            <label style="display: block; color: #2B3674; font-size: 14px; margin-bottom: 8px;">Conferma Password</label>
                            <input type="password" name="confirm_password" placeholder="Ripeti password" required style="width: 100%; padding: 10px; border: 1px solid #E0E5F2; border-radius: 10px;">
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" name="update_pass" class="btn-save" style="background: #2B3674; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 600;">Aggiorna Password</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>

</html> 