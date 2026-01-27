<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login_ristoratore.php");
    exit;
}

$user_id = $_SESSION["id"];
$msg = "";
$msg_type = "";  

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
                    $msg = "Dati aggiornati con successo!";
                    $msg_type = "success";
                } else {
                    $msg = "Errore: Username o Email già in uso.";
                    $msg_type = "error";
                }
            }
        }
    }

    if (isset($_POST['update_pass'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $hashed_password);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (password_verify($old_pass, $hashed_password)) {
                $new_param_password = password_hash($new_pass, PASSWORD_DEFAULT);
                $sql_upd = "UPDATE users SET password = ? WHERE id = ?";
                if ($stmt_upd = mysqli_prepare($link, $sql_upd)) {
                    mysqli_stmt_bind_param($stmt_upd, "si", $new_param_password, $user_id);
                    mysqli_stmt_execute($stmt_upd);
                    $msg = "Password aggiornata correttamente!";
                    $msg_type = "success";
                }
            } else {
                $msg = "La vecchia password non è corretta.";
                $msg_type = "error";
            }
        }
    }
}

$username = $email = $created_at = "";
$sql = "SELECT username, email, created_at FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $username, $email, $created_at);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il mio Profilo - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #F4F7FE; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 40px; }

        .page-header { margin-bottom: 35px; }
        .page-header h1 { font-size: 28px; font-weight: 700; color: #2B3674; }
        .page-header p { color: #A3AED0; margin-top: 5px; }

        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
        }

        .profile-card {
            text-align: center;
        }
        .avatar-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #1A4D4E 0%, #4FD1C5 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            font-weight: bold;
            box-shadow: 0 10px 20px rgba(26, 77, 78, 0.2);
        }
        .role-badge {
            background-color: #E0E5F2;
            color: #2B3674;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 10px;
        }
        .info-list {
            margin-top: 30px;
            text-align: left;
        }
        .info-item {
            padding: 15px 0;
            border-bottom: 1px solid #F4F7FE;
            font-size: 14px;
            color: #707EAE;
            display: flex;
            justify-content: space-between;
        }
        .info-item b { color: #2B3674; }

        .form-section { margin-bottom: 30px; }
        .form-section h3 { color: #2B3674; font-size: 18px; margin-bottom: 20px; font-weight: 700; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #2B3674; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E0E5F2;
            border-radius: 10px;
            font-size: 14px;
            color: #1B2559;
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #1A4D4E;
            outline: none;
        }

        button {
            background-color: #1A4D4E;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background-color: #E89020; }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert.success { background-color: #E6FFFA; color: #1A4D4E; border: 1px solid #B2F5EA; }
        .alert.error { background-color: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; }

        @media (max-width: 900px) {
            .profile-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

  

    <div class="main-content">
        <div class="page-header">
            <p>Impostazioni Account</p>
            <h1>Il mio Profilo</h1>
        </div>

        <?php if ($msg): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            
            <div class="card profile-card"> 
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <h2 style="color: #2B3674;"><?php echo htmlspecialchars($username); ?></h2>
                <span class="role-badge"><?php echo htmlspecialchars($_SESSION['ruolo']); ?></span>

                <div class="info-list">
                    <div class="info-item">
                        <span>Iscritto dal:</span>
                        <b><?php echo date("d M Y", strtotime($created_at)); ?></b>
                    </div>
                    <div class="info-item">
                        <span>Email:</span>
                        <b><?php echo htmlspecialchars($email); ?></b>
                    </div>
                    <div class="info-item">
                        <span>Stato:</span>
                        <b style="color: green;">Attivo</b>
                    </div>
                </div>
            </div>

            <div class="card">
                
                <div class="form-section">
                    <h3>Modifica Dati Personali</h3>
                    <form method="POST" action="profile.php">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_info">Salva Modifiche</button>
                    </form>
                </div>

                <hr style="border: 0; border-top: 1px solid #E0E5F2; margin: 30px 0;">

                <div class="form-section">
                    <h3>Sicurezza Password</h3>
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label>Vecchia Password</label>
                            <input type="password" name="old_password" placeholder="Inserisci la password attuale" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Nuova Password</label>
                                <input type="password" name="new_password" placeholder="Nuova password" required>
                            </div>
                            <div class="form-group">
                                <label>Conferma Nuova Password</label>
                                <input type="password" name="confirm_password" placeholder="Ripeti password" required>
                            </div>
                        </div>
                        <button type="submit" name="update_pass" style="background-color: #2B3674;">Aggiorna Password</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

</body> 
</html> 