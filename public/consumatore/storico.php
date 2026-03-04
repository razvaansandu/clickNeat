<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once "../../config/db.php";
require_once "../../models/OrderModel.php";
require_once "../../models/FatturaModel.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$orders  = [];
$error_message = "";
$msg      = "";
$msg_type = "";

if (isset($_GET['fattura_creata'])) {
    $msg      = "Fattura generata con successo!";
    $msg_type = "success";
}
if (isset($_GET['errore'])) {
    $msg      = "Errore nella generazione della fattura.";
    $msg_type = "error";
}

try {
    $orderModel   = new OrderModel($db);
    $fatturaModel = new FatturaModel($db);
    $orders       = $orderModel->getByUserId($user_id);
} catch (Exception $e) {
    $error_message = "Errore nel recupero ordini: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei Ordini - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        .fattura-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }
        .btn-fattura {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-fattura:hover { opacity: 0.85; }
        .btn-fattura.genera {
            background: #2B3674;
            color: white;
        }
        .btn-fattura.scarica {
            background: #4318FF;
            color: white;
        }
        .btn-fattura.email {
            background: #05CD99;
            color: white;
        }
        .btn-fattura.stampa {
            background: #A3AED0;
            color: white;
        }

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
            padding: 35px 30px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-box h3 {
            color: #2B3674;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .modal-box p {
            color: #A3AED0;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn-modal-cancel {
            background: #E0E5F2;
            color: #2B3674;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-modal-confirm {
            background: linear-gradient(135deg, #4318FF, #6B46C1);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }

        .toast {
            position: fixed;
            bottom: 80px;
            right: 20px;
            padding: 14px 20px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            z-index: 99999;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
            pointer-events: none;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.success { background: #05CD99; }
        .toast.error   { background: #e53e3e; }
    </style>
</head>
<body>

    <div class="toast" id="toast"></div>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item">
                <i class="fa-solid fa-house"></i> <span>Home</span>
            </a>
            <a href="storico.php" class="nav-item active">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span>
            </a>
            <a href="profile_consumatore.php" class="nav-item">
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
                <h1>I miei Ordini</h1>
                <p>Controlla lo stato dei tuoi ordini recenti.</p>
            </div>
        </div>
    </header>

    <div class="main-container">

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ffcdd2;">
                <strong><i class="fa-solid fa-triangle-exclamation"></i> Ops! C'è un problema:</strong><br>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-container">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $ordine):

                    $stato_db = strtolower($ordine['status']);

                    $badgeClass  = 'pending';
                    $icona       = '<i class="fa-regular fa-clock"></i>';
                    $testo_stato = 'In Attesa';

                    if ($stato_db == 'accepted') {
                        $badgeClass  = 'accepted';
                        $icona       = '<i class="fa-solid fa-fire-burner"></i>';
                        $testo_stato = 'In Preparazione';
                    } elseif ($stato_db == 'completed') {
                        $badgeClass  = 'completed';
                        $icona       = '<i class="fa-solid fa-check-double"></i>';
                        $testo_stato = 'Ritirato';
                    } elseif ($stato_db == 'cancelled' || $stato_db == 'rejected') {
                        $badgeClass  = 'cancelled';
                        $icona       = '<i class="fa-solid fa-xmark"></i>';
                        $testo_stato = 'Annullato';
                    }

                    $fattura = ($stato_db == 'completed')
                        ? $fatturaModel->getFatturaByOrderId($ordine['id'])
                        : null;

                    $userData       = $fatturaModel->getUserBillingData($user_id);
                    $has_billing    = !empty($userData['codice_fiscale']);
                ?>
                    <div class="card-style order-card">
                        <div>
                            <div class="order-header">
                                <span class="order-id">Ordine #<?php echo $ordine['id']; ?></span>
                                <span class="status-badge <?php echo $badgeClass; ?>">
                                    <?php echo $icona; ?> <?php echo htmlspecialchars($testo_stato); ?>
                                </span>
                            </div>

                            <div class="order-rest-name"><?php echo htmlspecialchars($ordine['nome_ristorante']); ?></div>

                            <div class="order-date">
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo date("d/m/Y - H:i", strtotime($ordine['created_at'])); ?>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div style="font-weight: 700; color: #1A4D4E; font-size: 18px;">
                                € <?php echo number_format($ordine['total_amount'], 2, ',', '.'); ?>
                            </div>
                            <div style="font-size: 13px; color: #A3AED0; cursor: default;">
                                <?php if ($stato_db == 'accepted'): ?>
                                    <span style="color: #006064; font-weight:600;">In cucina...</span>
                                <?php elseif ($stato_db == 'completed'): ?>
                                    <span style="color: #05CD99; font-weight:600;">Buon appetito!</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($stato_db == 'completed'): ?>
                            <div class="fattura-actions">
                                <?php if ($fattura && $fattura['pdf_path']): ?>
                                    <a href="/<?php echo htmlspecialchars($fattura['pdf_path']); ?>"
                                       download="Fattura_<?php echo $fattura['numero']; ?>.pdf"
                                       class="btn-fattura scarica">
                                        <i class="fa-solid fa-download"></i> Scarica
                                    </a>
                                    <button class="btn-fattura email"
                                            onclick="inviaFattura(<?php echo $fattura['id']; ?>)">
                                        <i class="fa-solid fa-envelope"></i> Invia Email
                                    </button>
                                    <a href="/<?php echo htmlspecialchars($fattura['pdf_path']); ?>"
                                       target="_blank"
                                       class="btn-fattura stampa">
                                        <i class="fa-solid fa-print"></i> Stampa
                                    </a>
                                    <span style="font-size: 11px; color: #A3AED0; align-self: center;">
                                        <?php echo htmlspecialchars($fattura['numero']); ?>
                                    </span>
                                <?php elseif ($has_billing): ?>
                                    <a href="genera_fattura.php?order_id=<?php echo $ordine['id']; ?>"
                                       class="btn-fattura genera">
                                        <i class="fa-solid fa-file-invoice"></i> Genera Fattura
                                    </a>
                                <?php else: ?>
                                    <button class="btn-fattura genera"
                                            onclick="document.getElementById('modalDatiFiscali').classList.add('active')">
                                        <i class="fa-solid fa-file-invoice"></i> Richiedi Fattura
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (empty($error_message)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                        <i class="fa-solid fa-burger" style="font-size: 50px; color: #E0E5F2; margin-bottom: 20px;"></i>
                        <h3 style="color: #2B3674; margin-bottom: 10px;">Nessun ordine trovato</h3>
                        <p style="color: #A3AED0; margin-bottom: 25px;">Non hai ancora effettuato ordini su ClickNeat.</p>
                        <a href="dashboard_consumatore.php" class="btn-save" style="text-decoration:none; display:inline-block;">
                            Ordina qualcosa ora
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-overlay" id="modalDatiFiscali">
        <div class="modal-box">
            <i class="fa-solid fa-file-invoice" style="font-size: 38px; color: #4318FF; margin-bottom: 15px;"></i>
            <h3>Dati fiscali mancanti</h3>
            <p>Per generare la fattura devi prima inserire il tuo <strong>Codice Fiscale</strong> e i dati di fatturazione nel tuo profilo.</p>
            <div class="modal-actions">
                <button class="btn-modal-cancel"
                        onclick="document.getElementById('modalDatiFiscali').classList.remove('active')">
                    Annulla
                </button>
                <a href="profile_consumatore.php#fatturazione" class="btn-modal-confirm">
                    <i class="fa-solid fa-user-pen"></i> Vai al Profilo
                </a>
            </div>
        </div>
    </div>

    <script>
        function inviaFattura(fatturaId) {
            const btn = event.currentTarget;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Invio...';

            fetch('invia_fattura.php?fattura_id=' + fatturaId)
                .then(r => r.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-envelope"></i> Invia Email';
                })
                .catch(() => {
                    showToast('Errore di connessione.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-envelope"></i> Invia Email';
                });
        }

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => toast.classList.remove('show'), 3500);
        }

        <?php if ($msg): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showToast(<?php echo json_encode($msg); ?>, <?php echo json_encode($msg_type); ?>);
            });
        <?php endif; ?>
    </script>

</body>
</html>
