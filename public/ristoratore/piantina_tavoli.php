<?php
session_start();
require_once "../../config/db.php";
require_once "../../models/RistoranteTavoloModel.php";
require_once "../../models/PrenotazioneTavoloModel.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['ruolo'] !== 'ristoratore') {
    header("Location: ../auth/login.php");
    exit;
}

$ristorante_id = $_SESSION['ristorante_id'] ?? $_GET['ristorante_id'] ?? null;

if (!$ristorante_id) {
    $ristorante = $db->selectOne(
        "SELECT id FROM ristoranti WHERE proprietario_id = ?",
        [$_SESSION['id']]
    );
    if (!$ristorante) die("Nessun ristorante trovato.");
    $ristorante_id = $ristorante['id'];
    $_SESSION['ristorante_id'] = $ristorante_id;
}

$tavoloModel = new RistoranteTavoloModel($db);
$prenotModel = new PrenotazioneTavoloModel($db);

$data_oggi = $_GET['data'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    switch ($_POST['action']) {
        case 'update_pos':
            $tavoloModel->updatePosizione($_POST['tavolo_id'], $_POST['pos_x'], $_POST['pos_y']);
            echo json_encode(['success' => true]);
            break;
        case 'add_tavolo':
            $tavoloModel->create($ristorante_id, $_POST['nome'], $_POST['capacita'], $_POST['forma'], $_POST['zona']);
            echo json_encode(['success' => true]);
            break;
        case 'delete_tavolo':
            $tavoloModel->delete($_POST['tavolo_id']);
            echo json_encode(['success' => true]);
            break;
        case 'update_stato':
            $prenotModel->updateStato($_POST['prenotazione_id'], $_POST['stato']);
            echo json_encode(['success' => true]);
            break;
    }
    exit;
}

$tavoli       = $tavoloModel->getTavoliConDisponibilita($ristorante_id, $data_oggi, '00:00', '23:59');
$prenotazioni = $prenotModel->getByRistoranteEData($ristorante_id, $data_oggi);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piantina Tavoli - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── PIANTINA ────────────────────────────────────────── */
        .piantina-wrapper {
            display: flex;
            gap: 25px;
            align-items: flex-start;
        }
        .piantina-canvas {
            flex: 1;
            position: relative;
            background:
                linear-gradient(rgba(67,24,255,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(67,24,255,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            background-color: #f8f9ff;
            border: 2px solid #E0E5F2;
            border-radius: 16px;
            min-height: 520px;
            overflow: hidden;
        }
        .tavolo-pin {
            position: absolute;
            cursor: grab;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transition: box-shadow 0.2s, transform 0.1s;
            user-select: none;
            z-index: 10;
            width: 65px;
            height: 65px;
        }
        .tavolo-pin:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
            transform: scale(1.05);
        }
        .tavolo-pin:active { cursor: grabbing; }
        .tavolo-pin.libero  { background: linear-gradient(135deg, #05CD99, #02A176); }
        .tavolo-pin.occupato { background: linear-gradient(135deg, #e53e3e, #c0392b); }
        .tavolo-pin.rotondo { border-radius: 50%; }
        .tavolo-pin.rettangolo { width: 90px !important; height: 50px !important; }
        .tavolo-pin .posti { font-size: 10px; opacity: 0.9; margin-top: 2px; }

        /* ── SIDEBAR PIANTINA ────────────────────────────────── */
        .sidebar-piantina {
            width: 300px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ── LEGENDA ─────────────────────────────────────────── */
        .legenda {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .legenda-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #2B3674;
            font-weight: 500;
        }
        .dot { width: 14px; height: 14px; border-radius: 4px; }
        .dot.libero  { background: #05CD99; }
        .dot.occupato { background: #e53e3e; }

        /* ── FORM AGGIUNGI TAVOLO ─────────────────────────────── */
        .form-add-tavolo input,
        .form-add-tavolo select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #E0E5F2;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            color: #2B3674;
            background: #F9FAFB;
        }
        .form-add-tavolo input:focus,
        .form-add-tavolo select:focus {
            border-color: #1A4D4E;
            outline: none;
            background: white;
        }

        /* ── PRENOTAZIONI ─────────────────────────────────────── */
        .prenotazione-item {
            background: #f8f9ff;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #4318FF;
            font-size: 13px;
        }
        .prenotazione-item.in_attesa  { border-color: #FFB547; }
        .prenotazione-item.confermata { border-color: #05CD99; }
        .prenotazione-item.cancellata { border-color: #e53e3e; opacity: 0.6; }

        /* ── CONTEXT MENU ─────────────────────────────────────── */
        .ctx-menu {
            display: none;
            position: fixed;
            background: white;
            border: 1px solid #E0E5F2;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            z-index: 9999;
            padding: 8px 0;
            min-width: 190px;
        }
        .ctx-item {
            padding: 10px 16px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.15s;
        }
        .ctx-item:hover { background: #F4F7FE; }
        .ctx-item.danger { color: #e53e3e; }
        .ctx-item.danger:hover { background: #FFEEED; }

        /* ── MODAL ────────────────────────────────────────────── */
        .modal-bg {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99999;
            justify-content: center;
            align-items: center;
        }
        .modal-bg.open { display: flex; }
        .modal-card {
            background: white;
            border-radius: 16px;
            padding: 35px 30px;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-card h3 {
            color: #2B3674;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-field {
            margin-bottom: 12px;
        }
        .modal-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #2B3674;
            margin-bottom: 5px;
        }
        .modal-field input,
        .modal-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #E0E5F2;
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
            color: #2B3674;
            background: #F9FAFB;
        }
        .modal-field input:focus,
        .modal-field textarea:focus {
            border-color: #1A4D4E;
            outline: none;
            background: white;
        }
        .modal-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-modal-cancel {
            flex: 1;
            background: #E0E5F2;
            color: #2B3674;
            border: none;
            padding: 11px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-modal-confirm {
            flex: 2;
            background: linear-gradient(135deg, #1A4D4E, #153e3f);
            color: white;
            border: none;
            padding: 11px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-modal-confirm:hover { opacity: 0.9; }

        /* ── TOOLBAR ──────────────────────────────────────────── */
        .piantina-toolbar {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            padding: 18px 25px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            margin-bottom: 25px;
            border: 1px solid #E0E5F2;
        }
        .toolbar-date input {
            padding: 8px 14px;
            border: 1px solid #E0E5F2;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: #2B3674;
            cursor: pointer;
        }
        .toolbar-hint {
            margin-left: auto;
            font-size: 12px;
            color: #A3AED0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── RESPONSIVE ───────────────────────────────────────── */
        @media (max-width: 900px) {
            .piantina-wrapper { flex-direction: column; }
            .sidebar-piantina { width: 100%; }
            .piantina-canvas { min-height: 350px; width: 100% !important; }
        }
    </style>
</head>
<body>

    <!-- HAMBURGER MOBILE -->
    <button class="hamburger-btn" id="hamburgerBtn">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- SIDEBAR -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- OVERLAY MOBILE -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <p>Gestione Ristorante</p>
                <h1><i class="fa-solid fa-table-cells" style="color:#1A4D4E; margin-right:10px;"></i>Piantina Tavoli</h1>
            </div>
            <a href="manage_restaurant.php?id=<?php echo $ristorante_id; ?>" class="btn-cancel" style="display:flex; align-items:center; gap:8px; background:#F4F7FE; padding:10px 20px; border-radius:12px; color:#2B3674; font-weight:600;">
                <i class="fa-solid fa-arrow-left"></i> Torna al Ristorante
            </a>
        </div>

        <!-- TOOLBAR -->
        <div class="piantina-toolbar">
            <div class="toolbar-date" style="display:flex; align-items:center; gap:10px;">
                <label style="font-weight:600; color:#2B3674; font-size:14px;">
                    <i class="fa-regular fa-calendar"></i> Data:
                </label>
                <input type="date" id="filtro-data"
                       value="<?php echo $data_oggi; ?>"
                       min="<?php echo date('Y-m-d'); ?>"
                       onchange="window.location.href='piantina_tavoli.php?data='+this.value+'&ristorante_id=<?php echo $ristorante_id; ?>'">
            </div>
            <div class="legenda">
                <div class="legenda-item"><div class="dot libero"></div> Libero</div>
                <div class="legenda-item"><div class="dot occupato"></div> Occupato</div>
            </div>
            <div class="toolbar-hint">
                <i class="fa-solid fa-arrows-up-down-left-right"></i>
                Trascina per spostare &nbsp;·&nbsp;
                <i class="fa-solid fa-computer-mouse"></i>
                Click destro per opzioni
            </div>
        </div>

        <!-- PIANTINA + SIDEBAR -->
        <div class="piantina-wrapper">

            <!-- CANVAS -->
            <div class="piantina-canvas" id="piantina" style="width: 100%;">
                <?php if (empty($tavoli)): ?>
                    <div style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#A3AED0;">
                        <i class="fa-solid fa-chair" style="font-size:48px; margin-bottom:15px;"></i>
                        <p style="font-weight:600; font-size:16px;">Nessun tavolo</p>
                        <p style="font-size:13px;">Aggiungi il primo tavolo dalla barra laterale</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($tavoli as $t): ?>
                    <div class="tavolo-pin <?php echo $t['disponibilita']; ?> <?php echo $t['forma'] ?? 'quadrato'; ?>"
                         id="tavolo-<?php echo $t['id']; ?>"
                         data-id="<?php echo $t['id']; ?>"
                         data-disponibilita="<?php echo $t['disponibilita']; ?>"
                         style="left:<?php echo $t['pos_x']; ?>px; top:<?php echo $t['pos_y']; ?>px;"
                         oncontextmenu="showCtxMenu(event, <?php echo $t['id']; ?>)">
                        <span><?php echo htmlspecialchars($t['nome']); ?></span>
                        <span class="posti"><i class="fa-solid fa-user"></i> <?php echo $t['capacita']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SIDEBAR PIANTINA -->
            <div class="sidebar-piantina">

                <!-- Aggiungi tavolo -->
                <div class="card">
                    <h3 style="color:#2B3674; font-size:16px; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-plus" style="color:#1A4D4E;"></i> Aggiungi Tavolo
                    </h3>
                    <div class="form-add-tavolo">
                        <input type="text" id="add-nome" placeholder="Nome (es. T1, Terrazza)">
                        <input type="number" id="add-capacita" placeholder="Numero posti" min="1" max="20">
                        <select id="add-forma">
                            <option value="quadrato">⬜ Quadrato</option>
                            <option value="rotondo">⭕ Rotondo</option>
                            <option value="rettangolo">▬ Rettangolo</option>
                        </select>
                        <select id="add-zona">
                            <option value="interno">🏠 Interno</option>
                            <option value="esterno">🌿 Esterno</option>
                            <option value="privé">🔒 Privé</option>
                        </select>
                        <button onclick="addTavolo()"
                                style="width:100%; background:linear-gradient(135deg,#1A4D4E,#153e3f); color:white; border:none; padding:11px; border-radius:10px; cursor:pointer; font-weight:600; font-size:14px; font-family:'Poppins',sans-serif;">
                            <i class="fa-solid fa-plus"></i> Aggiungi
                        </button>
                    </div>
                </div>

                <!-- Prenotazioni del giorno -->
                <div class="card" style="max-height:450px; overflow-y:auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h3 style="color:#2B3674; font-size:16px; margin:0; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-calendar-check" style="color:#1A4D4E;"></i> Prenotazioni
                        </h3>
                        <span style="background:#E6FAF5; color:#05CD99; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                            <?php echo date('d/m/Y', strtotime($data_oggi)); ?>
                        </span>
                    </div>

                    <?php if (empty($prenotazioni)): ?>
                        <div style="text-align:center; padding:30px 10px; color:#A3AED0;">
                            <i class="fa-solid fa-calendar-xmark" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                            <p style="font-size:13px;">Nessuna prenotazione</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($prenotazioni as $p): ?>
                            <div class="prenotazione-item <?php echo $p['stato']; ?>">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                    <strong style="color:#2B3674;"><?php echo htmlspecialchars($p['nome_cliente']); ?></strong>
                                    <span style="font-size:11px; background:#F4F7FE; color:#2B3674; padding:2px 8px; border-radius:8px;">
                                        <?php echo htmlspecialchars($p['tavolo_nome']); ?>
                                    </span>
                                </div>
                                <div style="color:#A3AED0; font-size:12px; margin-bottom:6px;">
                                    <i class="fa-regular fa-clock"></i>
                                    <?php echo substr($p['ora_prenotazione'], 0, 5); ?> –
                                    <?php echo substr($p['ora_fine'], 0, 5); ?>
                                    &nbsp;·&nbsp;
                                    <i class="fa-solid fa-users"></i> <?php echo $p['numero_persone']; ?> pers.
                                    <?php if (!empty($p['telefono'])): ?>
                                        &nbsp;·&nbsp;
                                        <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($p['telefono']); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($p['note'])): ?>
                                    <div style="font-size:11px; color:#A3AED0; font-style:italic; margin-bottom:6px;">
                                        <?php echo htmlspecialchars($p['note']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($p['stato'] === 'in_attesa'): ?>
                                    <div style="display:flex; gap:6px; margin-top:8px;">
                                        <button onclick="updateStato(<?php echo $p['id']; ?>, 'confermata')"
                                                style="flex:1; background:#E6FAF5; color:#05CD99; border:none; padding:6px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600; font-family:'Poppins',sans-serif;">
                                            <i class="fa-solid fa-check"></i> Conferma
                                        </button>
                                        <button onclick="updateStato(<?php echo $p['id']; ?>, 'cancellata')"
                                                style="flex:1; background:#FFEEED; color:#e53e3e; border:none; padding:6px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600; font-family:'Poppins',sans-serif;">
                                            <i class="fa-solid fa-xmark"></i> Rifiuta
                                        </button>
                                    </div>
                                <?php elseif ($p['stato'] === 'confermata'): ?>
                                    <div style="font-size:11px; color:#05CD99; font-weight:600; margin-top:4px;">
                                        <i class="fa-solid fa-circle-check"></i> Confermata
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- CONTEXT MENU -->
    <div class="ctx-menu" id="ctxMenu">
        <div class="ctx-item" onclick="ctxNuovaPrenotazione()">
            <i class="fa-solid fa-calendar-plus" style="color:#1A4D4E;"></i> Nuova Prenotazione
        </div>
        <div class="ctx-item danger" onclick="ctxElimina()">
            <i class="fa-solid fa-trash"></i> Elimina Tavolo
        </div>
        <div class="ctx-item" style="color:#A3AED0;" onclick="closeCtxMenu()">
            <i class="fa-solid fa-xmark"></i> Chiudi
        </div>
    </div>

    <!-- MODAL PRENOTAZIONE -->
    <div class="modal-bg" id="modalPrenotazione">
        <div class="modal-card">
            <h3>
                <i class="fa-solid fa-calendar-plus" style="color:#1A4D4E;"></i>
                Nuova Prenotazione
            </h3>
            <input type="hidden" id="modal-tavolo-id">
            <div class="modal-field">
                <label>Nome cliente *</label>
                <input type="text" id="pren-nome" placeholder="Es. Mario Rossi">
            </div>
            <div class="modal-grid-2">
                <div class="modal-field">
                    <label>Telefono</label>
                    <input type="tel" id="pren-tel" placeholder="3xx xxxxxxx">
                </div>
                <div class="modal-field">
                    <label>Email</label>
                    <input type="email" id="pren-email" placeholder="email@email.com">
                </div>
            </div>
            <div class="modal-grid-2">
                <div class="modal-field">
                    <label>Data *</label>
                    <input type="date" id="pren-data" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="modal-field">
                    <label>Ora * (12:00–22:00)</label>
                    <input type="time" id="pren-ora" min="12:00" max="22:00">
                </div>
            </div>
            <div class="modal-field">
                <label>Numero persone *</label>
                <input type="number" id="pren-persone" placeholder="Es. 4" min="1" max="20">
            </div>
            <div class="modal-field">
                <label>Note (allergie, occasioni…)</label>
                <textarea id="pren-note" rows="2" placeholder="Es. Compleanno, allergia ai crostacei..."></textarea>
            </div>
            <div class="modal-actions">
                <button class="btn-modal-cancel" onclick="closeModal()">Annulla</button>
                <button class="btn-modal-confirm" onclick="salvaPrenotazione()">
                    <i class="fa-solid fa-check"></i> Conferma Prenotazione
                </button>
            </div>
        </div>
    </div>

    <script>
    const hamburgerBtn  = document.getElementById('hamburgerBtn');
    const sidebar       = document.querySelector('.sidebar');
    const overlay       = document.getElementById('sidebarOverlay');
    const closeSidebar  = document.getElementById('closeSidebarBtn');

    function openSidebar()  { sidebar.classList.add('active'); overlay.classList.add('active'); }
    function closeSidebarFn() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', openSidebar);
    if (closeSidebar) closeSidebar.addEventListener('click', closeSidebarFn);
    if (overlay)      overlay.addEventListener('click', closeSidebarFn);

    document.querySelectorAll('.tavolo-pin').forEach(pin => {
        let dragging = false, startX, startY, origX, origY;

        pin.addEventListener('mousedown', e => {
            if (e.button !== 0) return;
            dragging = true;
            startX = e.clientX; startY = e.clientY;
            origX = parseInt(pin.style.left); origY = parseInt(pin.style.top);
            pin.style.zIndex = 100;
            e.preventDefault();
        });

        document.addEventListener('mousemove', e => {
            if (!dragging) return;
            pin.style.left = Math.max(0, origX + e.clientX - startX) + 'px';
            pin.style.top  = Math.max(0, origY + e.clientY - startY) + 'px';
        });

        document.addEventListener('mouseup', () => {
            if (!dragging) return;
            dragging = false;
            pin.style.zIndex = 10;
            fetch('piantina_tavoli.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update_pos',
                    tavolo_id: pin.dataset.id,
                    pos_x: parseInt(pin.style.left),
                    pos_y: parseInt(pin.style.top)
                })
            });
        });
    });

    let ctxTavoloId = null;
    const ctxMenu = document.getElementById('ctxMenu');

    function showCtxMenu(e, id) {
        e.preventDefault();
        ctxTavoloId = id;
        ctxMenu.style.display = 'block';
        ctxMenu.style.left = e.clientX + 'px';
        ctxMenu.style.top  = e.clientY + 'px';
    }

    function closeCtxMenu() { ctxMenu.style.display = 'none'; }
    document.addEventListener('click', closeCtxMenu);

    function ctxNuovaPrenotazione() {
        document.getElementById('modal-tavolo-id').value = ctxTavoloId;
        document.getElementById('pren-data').value = '<?php echo $data_oggi; ?>';
        document.getElementById('modalPrenotazione').classList.add('open');
        closeCtxMenu();
    }

    function ctxElimina() {
        if (!confirm('Eliminare questo tavolo?')) return;
        fetch('piantina_tavoli.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'delete_tavolo', tavolo_id: ctxTavoloId })
        }).then(() => location.reload());
        closeCtxMenu();
    }

    function closeModal() {
        document.getElementById('modalPrenotazione').classList.remove('open');
        ['pren-nome','pren-tel','pren-email','pren-ora','pren-persone','pren-note']
            .forEach(id => document.getElementById(id).value = '');
    }

    function salvaPrenotazione() {
        const nome    = document.getElementById('pren-nome').value.trim();
        const data    = document.getElementById('pren-data').value;
        const ora     = document.getElementById('pren-ora').value;
        const persone = document.getElementById('pren-persone').value;

        if (!nome || !data || !ora || !persone) {
            alert('Compila tutti i campi obbligatori (*).');
            return;
        }

        const oraInt = parseInt(ora.split(':')[0]);
        if (oraInt < 12 || oraInt >= 22) {
            alert('Orario non valido. Accettiamo prenotazioni dalle 12:00 alle 22:00.');
            return;
        }

        const btn = document.querySelector('.btn-modal-confirm');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvataggio...';

        fetch('salva_prenotazione.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                tavolo_id:        document.getElementById('modal-tavolo-id').value,
                nome_cliente:     nome,
                telefono:         document.getElementById('pren-tel').value,
                email:            document.getElementById('pren-email').value,
                data_prenotazione: data,
                ora_prenotazione:  ora,
                numero_persone:    persone,
                note:             document.getElementById('pren-note').value
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeModal();
                location.reload();
            } else {
                alert('Errore: ' + (res.message || 'Riprova.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Conferma Prenotazione';
            }
        });
    }

    function updateStato(id, stato) {
        fetch('piantina_tavoli.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'update_stato', prenotazione_id: id, stato })
        }).then(() => location.reload());
    }

    function addTavolo() {
        const nome     = document.getElementById('add-nome').value.trim();
        const capacita = document.getElementById('add-capacita').value;
        const forma    = document.getElementById('add-forma').value;
        const zona     = document.getElementById('add-zona').value;

        if (!nome || !capacita) { alert('Inserisci nome e numero posti.'); return; }

        fetch('piantina_tavoli.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'add_tavolo', nome, capacita, forma, zona })
        }).then(() => location.reload());
    }
    </script>

</body>
</html>
