<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/ProfileModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

$user_id      = $_SESSION["id"];
$profileModel = new ProfileModel($db);
$msg          = "";
$msg_type     = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['update_info'])) {
        $new_username = trim($_POST['username']);
        $new_email    = trim($_POST['email']);

        if (!empty($new_username) && !empty($new_email)) {
            if ($profileModel->updateInfo($user_id, $new_username, $new_email)) {
                $_SESSION['username'] = $new_username;
                $msg      = "Dati aggiornati con successo!";
                $msg_type = "success";
            } else {
                $msg      = "Errore: Username o Email già in uso.";
                $msg_type = "error";
            }
        }
    }

    if (isset($_POST['delete_account'])) {
        if ($profileModel->deleteAccount($user_id)) {
            session_unset();
            session_destroy();
            header("Location: ../auth/login.php?account_deleted=1");
            exit();
        } else {
            $msg      = "Errore durante l'eliminazione dell'account.";
            $msg_type = "error";
        }
    }
}

$userData = $profileModel->getProfileData($user_id);

if ($userData) {
    $username   = $userData['username'];
    $email      = $userData['email'];
    $created_at = $userData['created_at'];
} else {
    $username   = "Utente";
    $email      = "";
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
    <style>
    @media screen and (max-width: 768px) {
        .main-content { padding: 15px !important; }
        .profile-wrapper { flex-direction: column !important; }
        .card-style { width: calc(100% - 0px) !important; margin: 0 auto !important; }
    }

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

    .danger-zone {
        margin-top: 40px;
        border-top: 2px solid #f5c6cb;
        padding-top: 30px;
    }
    .danger-zone h3 {
        color: #721c24;
        font-size: 16px;
        margin-bottom: 10px;
    }
    .danger-zone p {
        color: #A3AED0;
        font-size: 13px;
        margin-bottom: 20px;
    }
    .btn-danger {
        background: linear-gradient(135deg, #e53e3e, #c0392b);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }
    .btn-danger:hover { opacity: 0.9; }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: white;
        border-radius: 16px;
        padding: 40px 30px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .modal-box h3 {
        color: #2B3674;
        font-size: 20px;
        margin-bottom: 10px;
    }
    .modal-box p {
        color: #A3AED0;
        font-size: 14px;
        margin-bottom: 30px;
    }
    .modal-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    .btn-cancel {
        background: #E0E5F2;
        color: #2B3674;
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
    }
    </style>
</head>
<body>

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
                <div class="form-title" style="margin-bottom: 20px; font-weight:700; color:#2B3674; border-bottom:1px solid #eee; padding-bottom:10px;">
                    Modifica Dati Personali
                </div>
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
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="update_info" class="btn-save">Salva Modifiche</button>
                    </div>
                </form>

                <div class="danger-zone">
                    <h3><i class="fa-solid fa-triangle-exclamation"></i> Zona Pericolosa</h3>
                    <p>Eliminando il tuo account perderai tutti i tuoi dati, inclusi i ristoranti e gli ordini associati. Questa azione è irreversibile.</p>
                    <button class="btn-danger" onclick="document.getElementById('deleteModal').classList.add('active')">
                        <i class="fa-solid fa-trash"></i> Elimina Account
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: #e53e3e; margin-bottom: 15px;"></i>
            <h3>Sei sicuro?</h3>
            <p>Stai per eliminare definitivamente il tuo account ClickNeat. Tutti i tuoi dati, ristoranti e ordini verranno cancellati e non potrai recuperarli.</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="document.getElementById('deleteModal').classList.remove('active')">
                    Annulla
                </button>
                <form method="POST" action="">
                    <button type="submit" name="delete_account" class="btn-danger">
                        Sì, elimina
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar   = document.querySelector('.sidebar');
        const hamburger = document.querySelector('.hamburger-btn');
        const closeBtn  = document.getElementById('closeSidebarBtn');

        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.classList.add('sidebar-overlay');
            document.body.appendChild(overlay);
        }

        function openSidebar()  { sidebar.classList.add('active');    overlay.classList.add('active'); }
        function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }

        if (hamburger) hamburger.addEventListener('click', openSidebar);
        if (closeBtn)  closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
    });
    </script>

</body>
</html>
