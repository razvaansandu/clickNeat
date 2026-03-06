<?php
session_start();
require_once "../../config/db.php";
require_once "../../models/OrderRistoratoreModel.php";
require_once "../../models/RistoranteRistoratoreModel.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['ruolo'] !== 'ristoratore') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard_ristoratore.php");
    exit;
}

$restaurant_id = (int) $_GET['id'];
$user_id = $_SESSION['id'];

$ristoranteModel = new RistoranteRistoratoreModel($db);
$restaurant = $ristoranteModel->getByIdAndOwner($restaurant_id, $user_id);

if (!$restaurant) {
    header("Location: dashboard_ristoratore.php");
    exit;
}

$_SESSION['ristorante_id'] = $restaurant_id;

$orderModel = new OrderRistoratoreModel($db);
$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($order_id && $action === 'accept') {
        $orderModel->accept($order_id, $user_id);
        $msg = "Ordine #$order_id accettato.";
        $msg_type = "success";
    } elseif ($order_id && $action === 'reject') {
        $orderModel->reject($order_id, $user_id);
        $msg = "Ordine #$order_id rifiutato.";
        $msg_type = "error";
    } elseif (isset($_POST['update_order'])) {
        $orderModel->updateStatus($order_id, $_POST['status']);
        $msg = "Stato aggiornato.";
        $msg_type = "success";
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $restaurant_id);
    exit;
}

$orders = $orderModel->getByRestaurantId($restaurant_id);

$totale_mese = 0;
$ordini_pending = 0;
$ordini_oggi = 0;
$oggi = date('Y-m-d');
$mese_corrente = date('Y-m');

foreach ($orders as $o) {
    if (str_starts_with($o['created_at'], $mese_corrente)) {
        if ($o['status'] === 'completed')
            $totale_mese += $o['total_amount'];
    }
    if ($o['status'] === 'pending')
        $ordini_pending++;
    if (str_starts_with($o['created_at'], $oggi))
        $ordini_oggi++;
}

$_SESSION['ordini_pending'] = $ordini_pending;
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordini - <?php echo htmlspecialchars($restaurant['nome']); ?></title>
    <link rel="stylesheet" href="../../css/style_ristoratori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            border-radius: 16px;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .kpi-icon.green {
            background: #E6FAF5;
            color: #05CD99;
        }

        .kpi-icon.blue {
            background: #F4F7FE;
            color: #4318FF;
        }

        .kpi-icon.orange {
            background: #FFF8E6;
            color: #FFB547;
        }

        .kpi-icon.red {
            background: #FFEEED;
            color: #e53e3e;
        }

        .kpi-label {
            font-size: 13px;
            color: #A3AED0;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: 700;
            color: #2B3674;
        }

        .table-card {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #2B3674;
            margin: 0;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: #F4F7FE;
            color: #A3AED0;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .filter-tab.active,
        .filter-tab:hover {
            background: #2B3674;
            color: white;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-pending {
            background: #FFF8E6;
            color: #FFB547;
        }

        .badge-accepted {
            background: #E0F7FA;
            color: #006064;
        }

        .badge-completed {
            background: #E6FAF5;
            color: #05CD99;
        }

        .badge-rejected {
            background: #FFEEED;
            color: #e53e3e;
        }

        .btn-sm {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: opacity 0.2s;
        }

        .btn-sm:hover {
            opacity: 0.85;
        }

        .btn-accept {
            background: #E6FAF5;
            color: #05CD99;
        }

        .btn-reject {
            background: #FFEEED;
            color: #e53e3e;
        }

        .btn-complete {
            background: #4318FF;
            color: white;
        }

        .btn-detail {
            background: #F4F7FE;
            color: #2B3674;
        }

        table.dataTable thead th {
            background: #F4F7FE;
            color: #2B3674;
            font-weight: 600;
            font-size: 13px;
            border-bottom: none !important;
            padding: 12px 15px;
        }

        table.dataTable tbody tr:hover {
            background: #F9FAFB !important;
        }

        table.dataTable tbody td {
            padding: 13px 15px;
            font-size: 13px;
            color: #2B3674;
            vertical-align: middle;
            border-bottom: 1px solid #F4F7FE;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #E0E5F2;
            border-radius: 10px;
            padding: 7px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #2B3674;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #E0E5F2;
            border-radius: 8px;
            padding: 5px 10px;
            font-family: 'Poppins', sans-serif;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #2B3674 !important;
            color: white !important;
            border-radius: 8px;
            border: none !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #F4F7FE !important;
            color: #2B3674 !important;
            border: none !important;
            border-radius: 8px;
        }

        .dataTables_wrapper .dataTables_info {
            color: #A3AED0;
            font-size: 13px;
        }

        .modal-bg {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99999;
            justify-content: center;
            align-items: center;
        }

        .modal-bg.open {
            display: flex;
        }

        .modal-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .order-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #F4F7FE;
            font-size: 14px;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .pulse-badge {
            background: #e53e3e;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s infinite;
            margin-left: 6px;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        @media (max-width: 768px) {
            .table-card {
                padding: 15px;
            }

            .kpi-row {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <button class="hamburger-btn" id="hamburgerBtn">
        <i class="fa-solid fa-bars"></i>
    </button>

    <?php include '../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">

        <div class="page-header">
            <div>
                <a href="manage_restaurant.php?id=<?php echo $restaurant_id; ?>" class="btn-cancel"
                    style="padding-left:0; margin-bottom:10px; display:inline-block;">
                    <i class="fa-solid fa-arrow-left"></i> Torna al Ristorante
                </a>
                <h1>
                    <i class="fa-solid fa-receipt" style="color:#1A4D4E; margin-right:10px;"></i>
                    Ordini — <?php echo htmlspecialchars($restaurant['nome']); ?>
                    <?php if ($ordini_pending > 0): ?>
                        <span class="pulse-badge" id="badge-pending"><?php echo $ordini_pending; ?></span>
                    <?php endif; ?>
                </h1>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="msg-box <?php echo $msg_type; ?>" style="margin-bottom:20px;">
                <i
                    class="fa-solid <?php echo $msg_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-icon green"><i class="fa-solid fa-euro-sign"></i></div>
                <div>
                    <div class="kpi-label">Incasso mese</div>
                    <div class="kpi-value">€<?php echo number_format($totale_mese, 2); ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <div class="kpi-label">Ordini oggi</div>
                    <div class="kpi-value"><?php echo $ordini_oggi; ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div class="kpi-label">In attesa</div>
                    <div class="kpi-value"><?php echo $ordini_pending; ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon red"><i class="fa-solid fa-chart-bar"></i></div>
                <div>
                    <div class="kpi-label">Totale ordini</div>
                    <div class="kpi-value"><?php echo count($orders); ?></div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h2><i class="fa-solid fa-table-list" style="color:#1A4D4E;"></i> Tutti gli Ordini</h2>
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filtraStato(this, '')">Tutti</button>
                    <button class="filter-tab" onclick="filtraStato(this, 'pending')">
                        In attesa
                        <?php if ($ordini_pending > 0): ?>
                            <span
                                style="background:#FFB547; color:white; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px;">
                                <?php echo $ordini_pending; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <button class="filter-tab" onclick="filtraStato(this, 'accepted')"> In preparazione</button>
                    <button class="filter-tab" onclick="filtraStato(this, 'completed')"> Completati</button>
                    <button class="filter-tab" onclick="filtraStato(this, 'rejected')"> Rifiutati</button>
                </div>
            </div>

            <table id="tabellaOrdini" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Totale</th>
                        <th>Stato</th>
                        <th>Data</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?php echo $o['id']; ?></strong></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div
                                        style="width:32px; height:32px; background:#F4F7FE; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#2B3674; font-size:13px; flex-shrink:0;">
                                        <?php echo strtoupper(substr($o['cliente_nome'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($o['cliente_nome']); ?>
                                </div>
                            </td>
                            <td><strong>€<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                            <td>
                                <?php
                                $badges = [
                                    'pending' => ['badge-pending', ' In attesa'],
                                    'accepted' => ['badge-accepted', ' In prep.'],
                                    'completed' => ['badge-completed', ' Completato'],
                                    'rejected' => ['badge-rejected', ' Rifiutato'],
                                ];
                                [$cls, $lbl] = $badges[$o['status']] ?? ['badge-pending', $o['status']];
                                ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo $lbl; ?></span>
                            </td>
                            <td data-order="<?php echo $o['created_at']; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <button class="btn-sm btn-detail" onclick="apriDettaglio(<?php echo $o['id']; ?>)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <?php if ($o['status'] === 'pending'): ?>
                                        <form method="POST" style="margin:0; display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <button type="submit" name="action" value="accept" class="btn-sm btn-accept">
                                                <i class="fa-solid fa-check"></i> Accetta
                                            </button>
                                        </form>
                                        <form method="POST" style="margin:0; display:inline;"
                                            onsubmit="return confirm('Rifiutare questo ordine?')">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <button type="submit" name="action" value="reject" class="btn-sm btn-reject">
                                                <i class="fa-solid fa-xmark"></i> Rifiuta
                                            </button>
                                        </form>
                                    <?php elseif ($o['status'] === 'accepted'): ?>
                                        <form method="POST" style="margin:0; display:inline;">
                                            <input type="hidden" name="update_order" value="1">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn-sm btn-complete">
                                                <i class="fa-solid fa-flag-checkered"></i> Concludi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-bg" id="modalDettaglio">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="color:#2B3674; font-size:18px; margin:0; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-receipt" style="color:#1A4D4E;"></i> Dettaglio Ordine
                </h3>
                <button onclick="chiudiDettaglio()"
                    style="background:none; border:none; font-size:20px; cursor:pointer; color:#A3AED0;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="dettaglio-content">
                <div style="text-align:center; padding:20px; color:#A3AED0;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:24px;"></i>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        const hamburger = document.getElementById('hamburgerBtn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('closeSidebarBtn');

        if (hamburger) hamburger.addEventListener('click', () => { sidebar.classList.add('active'); overlay.classList.add('active'); });
        if (closeBtn) closeBtn.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
        if (overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });

        const table = $('#tabellaOrdini').DataTable({
            order: [[4, 'desc']],
            pageLength: 25,
            language: {
                search: " Cerca:",
                lengthMenu: "Mostra _MENU_ ordini",
                info: "Mostra _START_–_END_ di _TOTAL_ ordini",
                infoEmpty: "Nessun ordine trovato",
                paginate: { previous: "‹", next: "›" },
                zeroRecords: "Nessun ordine corrisponde al filtro"
            },
            columnDefs: [{ orderable: false, targets: 5 }],
            initComplete: function () {
                document.querySelector('.dataTables_info').style.color = '#A3AED0';
            }
        });

        function filtraStato(btn, stato) {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');

            const map = {
                'pending': 'In attesa',
                'accepted': 'In prep',
                'completed': 'Completato',
                'rejected': 'Rifiutato'
            };

            table.column(3).search(stato === '' ? '' : (map[stato] || stato)).draw();
        }

        function apriDettaglio(orderId) {
            document.getElementById('modalDettaglio').classList.add('open');
            document.getElementById('dettaglio-content').innerHTML =
                '<div style="text-align:center; padding:30px; color:#A3AED0;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;"></i></div>';

            fetch('get_order_detail.php?id=' + orderId)
                .then(async response => {
                    const text = await response.text();

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Risposta non JSON: ' + text);
                    }
                })
                .then(data => {
                    if (!data.success) {
                        document.getElementById('dettaglio-content').innerHTML = `
                    <div style="color:#e53e3e; padding:10px 0;">
                        <strong>Errore:</strong> ${data.message || 'Impossibile caricare il dettaglio ordine.'}
                        ${data.debug ? `<div style="margin-top:8px; font-size:12px; color:#A3AED0;">${data.debug}</div>` : ''}
                    </div>
                `;
                        return;
                    }

                    const o = data.order;
                    const items = Array.isArray(data.items) ? data.items : [];

                    let itemsHtml = '';

                    if (items.length === 0) {
                        itemsHtml = `
                    <div style="padding:12px 0; color:#A3AED0;">
                        Nessun dettaglio piatti trovato per questo ordine.
                    </div>
                `;
                    } else {
                        itemsHtml = items.map(i => {
                            const qty = parseInt(i.quantity ?? 1);
                            const unitPrice = parseFloat(i.price_at_time ?? i.price ?? 0);
                            const rowTotal = (qty * unitPrice).toFixed(2);

                            return `
                        <div class="order-item-row">
                            <div>
                                <strong>${i.name ?? 'Piatto'}</strong>
                                <span style="color:#A3AED0; font-size:12px; margin-left:8px;">x${qty}</span>
                            </div>
                            <strong>€${rowTotal}</strong>
                        </div>
                    `;
                        }).join('');
                    }

                    const badges = {
                        pending: '<span class="badge badge-pending"> In attesa</span>',
                        accepted: '<span class="badge badge-accepted"> In preparazione</span>',
                        completed: '<span class="badge badge-completed"> Completato</span>',
                        rejected: '<span class="badge badge-rejected"> Rifiutato</span>'
                    };

                    document.getElementById('dettaglio-content').innerHTML = `
                <div style="display:flex; justify-content:space-between; gap:20px; margin-bottom:15px;">
                    <div>
                        <div style="font-size:13px; color:#A3AED0;">Cliente</div>
                        <div style="font-weight:700; color:#2B3674;">${o.cliente_nome ?? 'Cliente'}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:13px; color:#A3AED0;">Stato</div>
                        ${badges[o.status] || `<span>${o.status}</span>`}
                    </div>
                </div>

                <div style="font-size:13px; color:#A3AED0; margin-bottom:15px;">
                    <i class="fa-regular fa-clock"></i>
                    ${o.created_at ? new Date(o.created_at).toLocaleString('it-IT') : '-'}
                </div>

                <div style="border-top:1px solid #F4F7FE; margin-bottom:15px;"></div>

                <div style="font-weight:700; color:#2B3674; margin-bottom:10px;">
                Piatti ordinati
                </div>

                ${itemsHtml}

                <div style="border-top:2px solid #F4F7FE; margin-top:10px; padding-top:12px; display:flex; justify-content:space-between; font-weight:700; color:#2B3674; font-size:16px;">
                    <span>Totale</span>
                    <span>€${parseFloat(o.total_amount ?? 0).toFixed(2)}</span>
                </div>

                ${data.debug_items_error ? `
                    <div style="margin-top:12px; font-size:12px; color:#A3AED0;">
                        Debug items: ${data.debug_items_error}
                    </div>
                ` : ''}
            `;
                })
                .catch(err => {
                    document.getElementById('dettaglio-content').innerHTML = `
                <div style="color:#e53e3e;">
                    Errore nel caricamento del dettaglio ordine.
                    <div style="margin-top:8px; font-size:12px; color:#A3AED0;">${err.message}</div>
                </div>
            `;
                });
        }


        function chiudiDettaglio() {
            document.getElementById('modalDettaglio').classList.remove('open');
        }

        window.addEventListener('click', e => {
            if (e.target === document.getElementById('modalDettaglio')) chiudiDettaglio();
        });

        setInterval(() => {
            fetch('api_ordini.php?action=check_new&ristorante_id=<?php echo $restaurant_id; ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.pending > 0) {
                        document.title = `(${data.pending}) Ordini - ClickNeat`;
                        const badge = document.getElementById('badge-pending');
                        if (badge) badge.textContent = data.pending;
                    }
                })
                .catch(() => { });
        }, 30000);
    </script>
</body>

</html>