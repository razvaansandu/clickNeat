<?php
require_once "../../config/db.php";
require_once "../../models/PrenotazioneTavoloModel.php";

if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$prenotazioneModel = new PrenotazioneTavoloModel($db);
$prenotazioni = $prenotazioneModel->getByCliente($_SESSION['id']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le mie Prenotazioni - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .prenotazione-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .prenotazione-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #F0FDF9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1A4D4E;
            flex-shrink: 0;
        }

        .prenotazione-info {
            flex: 1;
        }

        .prenotazione-ristorante {
            font-size: 16px;
            font-weight: 700;
            color: #2B3674;
            margin-bottom: 4px;
        }

        .prenotazione-dettagli {
            font-size: 13px;
            color: #A3AED0;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 6px;
        }

        .prenotazione-dettagli span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .badge-stato {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .badge-in_attesa {
            background: #FFF8DD;
            color: #B8860B;
        }

        .badge-confermata {
            background: #E6FAF5;
            color: #05CD99;
        }

        .badge-cancellata {
            background: #FDECEA;
            color: #E53E3E;
        }

        .badge-completata {
            background: #F4F7FE;
            color: #A3AED0;
        }

        .page-header {
            background: linear-gradient(105deg, var(--accent-orange) 0%, var(--accent-red) 100%);
            padding: 60px 60px 50px;
            color: white;
            margin-bottom: 40px;
            border-radius: 0 0 40px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .page-header h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .empty-state i {
            font-size: 4em;
            color: #E0E5F2;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #2B3674;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            color: #A3AED0;
            font-size: 14px;
            margin-bottom: 24px;
        }

        @media (max-width: 600px) {
            .prenotazione-card {
                flex-wrap: wrap;
            }

            .page-header {
                padding: 40px 20px 30px;
            }

            .page-header h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>

    <div class="mobile-header-fixed">
        <div class="mobile-top-row">
            <a href="dashboard_consumatore.php" class="brand-logo">
                <i class="fa-solid fa-leaf"></i> ClickNeat
            </a>
            <a href="../auth/logout.php" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

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
            <a href="prenotazioni.php" class="nav-item active">
                <i class="fa-solid fa-calendar-check"></i> <span>Prenotazioni</span>
            </a>
            <a href="profile_consumatore.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="../auth/logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="page-header">
        <a href="dashboard_consumatore.php" class="btn-back-hero">
            <i class="fa-solid fa-arrow-left"></i> Torna alla Home
        </a>
        <h1 style="margin-top: 16px;"><i class="fa-solid fa-calendar-check" style="margin-right: 10px;"></i>Le mie Prenotazioni</h1>
        <p>Storico di tutte le tue prenotazioni tavolo</p>
    </header>

    <div class="main-container">

        <?php if (!empty($prenotazioni)): ?>
            <?php foreach ($prenotazioni as $p): ?>
                <?php
                    $data_fmt = date('d/m/Y', strtotime($p['data_prenotazione']));
                    $ora_fmt  = substr($p['ora_prenotazione'], 0, 5);
                    $ora_fine = substr($p['ora_fine'], 0, 5);
                    $stato    = $p['stato'];
                    $is_passata = strtotime($p['data_prenotazione']) < strtotime(date('Y-m-d'));
                    if ($is_passata && $stato === 'confermata') $stato = 'completata';
                ?>
                <div class="prenotazione-card">
                    <div class="prenotazione-icon">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <div class="prenotazione-info">
                        <div class="prenotazione-ristorante"><?php echo htmlspecialchars($p['ristorante_nome']); ?></div>
                        <div style="font-size: 14px; color: #2B3674; font-weight: 600; margin-top: 2px;">
                            <?php echo htmlspecialchars($p['tavolo_nome']); ?>
                        </div>
                        <div class="prenotazione-dettagli">
                            <span><i class="fa-regular fa-calendar"></i> <?php echo $data_fmt; ?></span>
                            <span><i class="fa-regular fa-clock"></i> <?php echo $ora_fmt; ?> – <?php echo $ora_fine; ?></span>
                            <?php if (!empty($p['numero_persone'])): ?>
                                <span><i class="fa-solid fa-users"></i> <?php echo (int)$p['numero_persone']; ?> person<?php echo $p['numero_persone'] == 1 ? 'a' : 'e'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge-stato badge-<?php echo htmlspecialchars($stato); ?>">
                        <?php
                            $label = [
                                'in_attesa'  => 'In attesa',
                                'confermata' => 'Confermata',
                                'cancellata' => 'Cancellata',
                                'completata' => 'Completata',
                            ];
                            echo $label[$stato] ?? ucfirst($stato);
                        ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-calendar-xmark"></i>
                <h3>Nessuna prenotazione</h3>
                <p>Non hai ancora effettuato nessuna prenotazione tavolo.</p>
                <a href="dashboard_consumatore.php" class="btn-save" style="padding: 12px 28px;">
                    <i class="fa-solid fa-utensils"></i> Scopri i Ristoranti
                </a>
            </div>
        <?php endif; ?>

    </div>

    <div class="bottom-nav">
        <a href="dashboard_consumatore.php" class="nav-item-bottom">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="storico.php" class="nav-item-bottom">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Ordini</span>
        </a>
        <a href="profile_consumatore.php" class="nav-item-bottom">
            <i class="fa-solid fa-user"></i>
            <span>Profilo</span>
        </a>
    </div>

</body>
</html>
