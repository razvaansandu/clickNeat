<?php
session_start();
require_once "../../config/db.php";

require '../../vendor/phpmailer/phpmailer/src/Exception.php';
require '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../../vendor//phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'consumatore') {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_help'])) {
    
    $oggetto = trim($_POST['subject']);
    $messaggio_utente = trim($_POST['message']);
    
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        $mail->Username   = 'clickneat2026@gmail.com'; 
        
        $mail->Password   = 'mgtt fvkc knrh fgso'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('clickneat2026@gmail.com', 'ClickNeat Assistenza');
        
        $mail->addAddress('clickneat2026@gmail.com'); 
                
        $mail->isHTML(true);
        $mail->Subject = "[Supporto] " . $oggetto;
        $mail->Body    = "<h3>Nuova richiesta di supporto</h3>
                          <p><b>Da Utente:</b> " . htmlspecialchars($_SESSION['username']) . " (ID: $user_id)</p>
                          <hr>
                          <p><b>Messaggio:</b><br>" . nl2br(htmlspecialchars($messaggio_utente)) . "</p>";
        $mail->AltBody = "Messaggio da " . $_SESSION['username'] . ":\n" . $messaggio_utente;

        $mail->send();
        $msg = "Richiesta inviata con successo! Ti risponderemo presto.";
        $msg_type = "success";

    } catch (Exception $e) {
        $msg = "Errore nell'invio. Controlla la Password per le App. Errore: " . $mail->ErrorInfo;
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Assistenza - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-navbar">
        <a href="dashboard.php" class="brand-logo"><i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat</a>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item"><i class="fa-solid fa-house"></i> <span>Home</span></a>
            <a href="storico.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span></a>
            <a href="profile_consumatore.php" class="nav-item"><i class="fa-solid fa-user"></i> <span>Profilo</span></a>
            <a href="help.php" class="nav-item active"><i class="fa-solid fa-circle-question"></i> <span>Aiuto</span></a>
            <a href="../auth/logout.php" class="btn-logout-nav"><i class="fa-solid fa-right-from-bracket"></i> Esci</a>
        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <a href="dashboard.php" class="btn-back-hero"><i class="fa-solid fa-arrow-left"></i> Torna alla Home</a>
            <div class="hero-title">
                <h1>Centro Assistenza</h1>
                <p>Inviaci una richiesta diretta alla nostra email.</p>
            </div>
        </div>
    </header>

    <div class="main-container">
        
        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>">
                <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="help-grid">
            
            <div>
                <h3 class="section-title" style="margin-top: 0;">FAQ</h3>
                <details open>
                    <summary>Come funziona il pagamento?</summary>
                    <div class="faq-answer">Paghi direttamente al ristorante al momento del ritiro (contanti o carta).</div>
                </details>
                <details>
                    <summary>Dov'è il mio ordine?</summary>
                    <div class="faq-answer">Vai su "Ordini" per vedere lo stato in tempo reale.</div>
                </details>
            </div>

            <div>
                <h3 class="section-title" style="margin-top: 0;">Scrivici</h3>
                
                <div class="card-style form-box">
                    <div class="contact-icon-box"><i class="fa-solid fa-envelope"></i></div>
                    <h2 style="font-size: 18px; color: #2B3674; margin-bottom: 15px;">Invia una email al supporto</h2>

                    <form method="POST" action="help.php">
                        <div class="input-group">
                            <label>Oggetto</label>
                            <input type="text" name="subject" placeholder="Es: Problema ordine #123" required>
                        </div>
                        
                        <div class="input-group">
                            <label>Messaggio</label>
                            <textarea name="message" placeholder="Descrivi qui il tuo problema..." required></textarea>
                        </div>

                        <button type="submit" name="send_help" class="btn-save" style="width: 100%;">
                            Invia Email <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

</body>
</html>