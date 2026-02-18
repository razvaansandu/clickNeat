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

$userData = $profileModel->getProfileData($user_id);

if ($userData) {
    $username = $userData['username'];
    $email = $userData['email'];
    $created_at = $userData['created_at'];
} else {
    $username = "Utente";
    $email = "";
    $created_at = date("Y-m-d");
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il mio Profilo - Ristoratore</title>
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dashboard_2" />
</head>

<body>

    <style>
    @media screen and (max-width: 768px) {
        .main-content {
            padding: 15px !important;
        }
        .profile-wrapper {
            flex-direction: column !important;
        }
        .card-style {
            width: calc(100% - 0px) !important;
            margin: 0 auto !important;
        }
    }
    </style>
    <div class="mobile-header">  
        <button class="hamburger-btn">  
            <i class="fa-solid fa-bars"></i> 
        </button>  
    </div>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <p>Area Personale</p>
                <h1>Il mio Profilo</h1>
            </div>
        </div>

        <div class="profile-wrapper">

            <div class="card-style avatar-box">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <h2 style="color: #2B3674; font-size: 20px; text-align: center;"><?php echo htmlspecialchars($username); ?></h2>
                <span class="status-badge active" style="margin-top:5px;">Ristoratore</span>

                <div class="info-list">
                    <div class="info-row">
                        <span>Iscritto dal</span>
                        <b><?php echo date("d M Y", strtotime($created_at)); ?></b>
                    </div>
                    <div class="info-row">
                        <span>Email</span>
                        <b><?php echo htmlspecialchars($email); ?></b>
                    </div>
                </div>
            </div>

            <div class="card-style form-box">
                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Modifica Dati Personali</div>
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div class="input-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" name="update_info" class="btn-save">Salva Modifiche</button>
                    </div>
                </form>

                <div style="margin: 40px 0; border-top: 1px solid #eee;"></div>

                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">Sicurezza Password</div>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Password Attuale</label>
                        <input type="password" name="old_password" required>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div class="input-group">
                            <label>Nuova Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" name="update_pass" class="btn-save" style="background-color: #2B3674;">Aggiorna Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        const hamburger = document.querySelector('.hamburger-btn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.classList.add('sidebar-overlay');
            document.body.appendChild(overlay);
        }

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }

        if (hamburger) {
            hamburger.addEventListener('click', openSidebar);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        overlay.addEventListener('click', closeSidebar);
    });
    </script>

</body>

</html>