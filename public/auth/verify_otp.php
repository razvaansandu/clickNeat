<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once "../../config/db.php";
require_once "../../models/LoginModel.php";

if (!isset($_SESSION['otp_email'])) {
    header("Location: login.php");
    exit();
}

$loginModel = new LoginModel($db);
$login_err  = "";
$email      = $_SESSION['otp_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codice = trim($_POST["codice"] ?? '');

    if (empty($codice)) {
        $login_err = "Inserisci il codice ricevuto.";
    } else {
        $user = $loginModel->verifyOtp($email, $codice);

        if (!$user) {
            $login_err = "Codice non valido o scaduto.";
        } else {
            unset($_SESSION['otp_email']);
            session_regenerate_id(true);

            $_SESSION["loggedin"]      = true;
            $_SESSION["id"]            = $user['id'];
            $_SESSION["username"]      = $user['username'];
            $_SESSION["ruolo"]         = $user['ruolo'];
            $_SESSION['USER_IP']       = $_SERVER['REMOTE_ADDR'];
            $_SESSION['USER_AGENT']    = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['LAST_ACTIVITY'] = time();
            $_SESSION['CREATED']       = time();

            if ($user['ruolo'] === 'ristoratore') {
                header("Location: ../ristoratore/dashboard_ristoratore.php");
            } else {
                header("Location: ../consumatore/dashboard_consumatore.php");
            }
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Codice - ClickNeat</title>
    <link rel="stylesheet" href="../css/style.css?v=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecf2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            color: #1A4D4E;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        strong {
            color: #1A4D4E;
            font-weight: 600;
            background: #e8f3f0;
            padding: 4px 8px;
            border-radius: 8px;
            display: inline-block;
        }

        .alert {
            background: #fee2e2;
            color: #dc2626;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
            text-align: left;
            border-left: 4px solid #dc2626;
        }

        .form-group {
            margin-bottom: 24px;
        }

        input[type="text"] {
            width: 100%;
            padding: 18px 16px;
            font-size: 32px;
            letter-spacing: 8px;
            text-align: center;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-weight: 600;
            color: #1e293b;
            background: #f8fafc;
            transition: all 0.2s ease;
            font-family: 'Inter', monospace;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #1A4D4E;
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 77, 78, 0.1);
        }

        input[type="text"]::placeholder {
            color: #94a3b8;
            font-size: 16px;
            letter-spacing: normal;
            font-weight: 400;
        }

        button {
            width: 100%;
            padding: 16px 24px;
            background: #1A4D4E;
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(26, 77, 78, 0.2);
        }

        button:hover {
            background: #153e3f;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(26, 77, 78, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        a {
            color: #64748b;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
            padding: 8px 16px;
            border-radius: 30px;
            background: #f1f5f9;
        }

        a:hover {
            color: #1A4D4E;
            background: #e2e8f0;
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 32px 24px;
            }

            h1 {
                font-size: 24px;
            }

            p {
                font-size: 14px;
                margin-bottom: 24px;
            }

            input[type="text"] {
                padding: 14px 12px;
                font-size: 28px;
                letter-spacing: 6px;
            }

            button {
                padding: 14px 20px;
                font-size: 16px;
            }

            a {
                font-size: 14px;
                padding: 6px 12px;
            }
        }

        @media screen and (max-width: 360px) {
            .container {
                padding: 24px 16px;
            }

            h1 {
                font-size: 22px;
            }

            input[type="text"] {
                font-size: 24px;
                letter-spacing: 4px;
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1> Controlla la tua email</h1>
        <p>Abbiamo inviato un codice a <strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if(!empty($login_err)) echo '<div class="alert"> ' . $login_err . '</div>'; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="codice" maxlength="6" 
                       placeholder="000000"
                       autocomplete="one-time-code" required autofocus>
            </div>
            <div class="form-group">
                <button type="submit">Verifica e accedi →</button>
            </div>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="login.php">← Usa un'altra email</a>
        </p>
    </div>
</body>
</html>