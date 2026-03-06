<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prenota Tavolo - ClickNeat</title>
    <link rel="stylesheet" href="../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-brand);
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .step-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #2B3674;
            font-size: 17px;
            margin-bottom: 20px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .table-card {
            border: 2px solid #E0E5F2;
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.25s;
            background: white;
        }

        .table-card:hover {
            border-color: #1A4D4E;
            background: #E6FAF5;
        }

        .table-card.selected {
            border-color: #1A4D4E;
            background: #E6FAF5;
        }

        .table-icon {
            width: 52px;
            height: 52px;
            background: #F0FDF9;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1A4D4E;
            flex-shrink: 0;
        }

        .table-name {
            font-weight: 700;
            color: #2B3674;
            font-size: 15px;
        }

        .table-cap {
            font-size: 13px;
            color: #A3AED0;
            margin-top: 3px;
        }

        .table-pos {
            display: inline-block;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            background: #F4F7FE;
            color: #2B3674;
        }

        .confirm-banner {
            background: #E6FAF5;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: #1A4D4E;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .step2-section {
            display: none;
        }

        .step2-section.visible {
            display: block;
        }

        .confirm-box {
            display: none;
        }

        .confirm-box.visible {
            display: block;
        }

        .notes-textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #E0E5F2;
            border-radius: 12px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            transition: border 0.2s;
            background: #F9FAFB;
        }

        .notes-textarea:focus {
            border-color: #1A4D4E;
            background: white;
        }

        .btn-search {
            background: var(--primary-brand);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            white-space: nowrap;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-search:hover {
            background: var(--accent-orange);
            transform: translateY(-2px);
        }

        .step2-header-info {
            background: #F4F7FE;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #2B3674;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr 1fr;
            }

            .search-grid .btn-col {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color:#05CD99;"></i> ClickNeat
        </a>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item"><i class="fa-solid fa-house"></i> <span>Home</span></a>
            <a href="storico.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span></a>
            <a href="prenotazioni.php" class="nav-item active"><i class="fa-solid fa-calendar-check"></i> <span>Prenotazioni</span></a>
            <a href="profile_consumatore.php" class="nav-item"><i class="fa-solid fa-user"></i> <span>Profilo</span></a>
            <a href="help.php" class="nav-item"><i class="fa-solid fa-circle-question"></i> <span>Aiuto</span></a>
            <a href="../auth/logout.php" class="btn-logout-nav"><i class="fa-solid fa-right-from-bracket"></i> Esci</a>
        </div>
    </nav>

    <div class="mobile-header-fixed">
        <div class="mobile-top-row">
            <a href="dashboard_consumatore.php" class="brand-logo">
                <i class="fa-solid fa-leaf" style="color:#05CD99;"></i> ClickNeat
            </a>
            <a href="../auth/logout.php" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

    <header class="hero-section">
        <div class="hero-content">
            <a href="dashboard_consumatore.php" class="btn-back-hero">
                <i class="fa-solid fa-arrow-left"></i> Torna alla Home
            </a>
            <div class="hero-title">
                <h1><i class="fa-solid fa-calendar-plus" style="font-size:28px; margin-right:10px;"></i>Prenota un Tavolo</h1>
                <p>Scegli data, orario e tavolo per la tua prenotazione</p>
            </div>
        </div>
    </header>

    <div class="main-container">

        <div class="card-style" id="step1-card" style="margin-bottom: 28px;">
            <div class="step-title">
                <span class="step-badge">1</span>
                Scegli data, orario e numero di persone
            </div>

            <div class="search-grid">
                <div class="input-group" style="margin-bottom: 0;">
                    <label><i class="fa-regular fa-calendar" style="color:#1A4D4E; margin-right:5px;"></i> Data</label>
                    <input type="date" id="input-data" class="checkout-select" required>
                </div>

                <div class="input-group" style="margin-bottom: 0;">
                    <label><i class="fa-regular fa-clock" style="color:#1A4D4E; margin-right:5px;"></i> Orario</label>
                    <select id="input-ora" class="checkout-select">
                        <option value="12:00">12:00</option>
                        <option value="12:30">12:30</option>
                        <option value="13:00">13:00</option>
                        <option value="13:30">13:30</option>
                        <option value="14:00">14:00</option>
                        <option value="14:30">14:30</option>
                        <option value="19:00">19:00</option>
                        <option value="19:30">19:30</option>
                        <option value="20:00" selected>20:00</option>
                        <option value="20:30">20:30</option>
                        <option value="21:00">21:00</option>
                        <option value="21:30">21:30</option>
                        <option value="22:00">22:00</option>
                        <option value="22:30">22:30</option>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 0;">
                    <label><i class="fa-solid fa-users" style="color:#1A4D4E; margin-right:5px;"></i> Persone</label>
                    <select id="input-persone" class="checkout-select">
                        <option value="1">1 persona</option>
                        <option value="2" selected>2 persone</option>
                        <option value="3">3 persone</option>
                        <option value="4">4 persone</option>
                        <option value="5">5 persone</option>
                        <option value="6">6 persone</option>
                        <option value="7">7 persone</option>
                        <option value="8">8 persone</option>
                        <option value="9">9 persone</option>
                        <option value="10">10 persone</option>
                    </select>
                </div>

                <div class="btn-col">
                    <button class="btn-search" onclick="cercaDisponibilita()">
                        <i class="fa-solid fa-magnifying-glass"></i> Cerca
                    </button>
                </div>
            </div>
        </div>

        <div class="step2-section card-style" id="step2" style="margin-bottom: 28px;">
            <div class="step-title">
                <span class="step-badge">2</span>
                <span id="step2-label">Tavoli disponibili</span>
            </div>

            <div class="step2-header-info">
                <i class="fa-solid fa-circle-info" style="color:#1A4D4E;"></i>
                <span id="step2-info"></span>
            </div>

            <div class="tables-grid" id="tables-grid"></div>

            <div class="input-group" style="margin-bottom: 20px;">
                <label>Note aggiuntive <span style="color:#A3AED0; font-weight:400;">(facoltative)</span></label>
                <textarea class="notes-textarea" rows="2" placeholder="Es. Allergie, occasione speciale, seggiolone per bambini..."></textarea>
            </div>

            <div class="confirm-box" id="confirm-box">
                <div class="confirm-banner">
                    <i class="fa-solid fa-circle-check" style="font-size:22px; color:#05CD99;"></i>
                    Tavolo selezionato: <b id="selected-name" style="margin: 0 4px;"></b>
                    &mdash; Durata stimata <b>1h 30min</b>, fino alle <b id="ora-fine"></b>
                </div>
                <button class="btn-save" style="width:100%; padding:14px; font-size:15px;" onclick="confermaPrenotazione()">
                    <i class="fa-solid fa-calendar-plus"></i> Conferma Prenotazione
                </button>
            </div>
        </div>

        <div class="card-style" id="success-box" style="display:none; text-align:center; padding: 50px 30px;">
            <div style="width:80px; height:80px; background:#E6FAF5; border-radius:50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px auto;">
                <i class="fa-solid fa-calendar-check" style="font-size:40px; color:#05CD99;"></i>
            </div>
            <h2 style="color:#2B3674; font-size:22px; margin-bottom:10px;">Prenotazione Confermata!</h2>
            <p style="color:#A3AED0; font-size:14px; margin-bottom:30px;" id="success-detail"></p>
            <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                <a href="prenotazioni.php" class="btn-save" style="padding:12px 28px;">
                    <i class="fa-solid fa-list"></i> Le mie Prenotazioni
                </a>
                <a href="dashboard_consumatore.php" style="padding:12px 28px; background:#F4F7FE; color:#1A4D4E; border-radius:12px; font-weight:600; display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
                    <i class="fa-solid fa-house"></i> Torna alla Home
                </a>
            </div>
        </div>

    </div>

    <script>
        var tavoli = [
            { id: 1, nome: 'Tavolo 1', capacita: 2, posizione: 'Interno' }
        ];

        function renderTavoli() {
            var grid = document.getElementById('tables-grid');
            grid.innerHTML = tavoli.map(function(t) {
                return '<div class="table-card" onclick="selezionaTavolo(' + t.id + ', \'' + t.nome + '\')">' +
                    '<div class="table-icon"><i class="fa-solid fa-chair"></i></div>' +
                    '<div>' +
                        '<div class="table-name">' + t.nome + '</div>' +
                        '<div class="table-cap"><i class="fa-solid fa-users"></i> Fino a ' + t.capacita + ' persone</div>' +
                        '<span class="table-pos">' + t.posizione + '</span>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        var selectedTavoloId = null;
        var selectedTavoloNome = '';

        var today = new Date().toISOString().split('T')[0];
        var maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 30);
        document.getElementById('input-data').value = today;
        document.getElementById('input-data').min = today;
        document.getElementById('input-data').max = maxDate.toISOString().split('T')[0];

        function cercaDisponibilita() {
            var data = document.getElementById('input-data').value;
            var ora = document.getElementById('input-ora').value;
            var persone = document.getElementById('input-persone').value;

            if (!data) {
                alert('Seleziona una data.');
                return;
            }

            var d = new Date(data + 'T00:00:00');
            var dataFormatted = d.toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' });

            document.getElementById('step2-info').textContent =
                dataFormatted + ' alle ' + ora + ' \u2014 ' + persone + ' person' + (persone == 1 ? 'a' : 'e');

            document.getElementById('step2').classList.add('visible');
            document.getElementById('confirm-box').classList.remove('visible');
            selectedTavoloId = null;
            selectedTavoloNome = '';
            renderTavoli();

            setTimeout(function() {
                document.getElementById('step2').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        }

        function selezionaTavolo(id, nome) {
            selectedTavoloId = id;
            selectedTavoloNome = nome;

            document.querySelectorAll('.table-card').forEach(function(c) { c.classList.remove('selected'); });
            event.currentTarget.classList.add('selected');

            document.getElementById('selected-name').textContent = nome;

            var ora = document.getElementById('input-ora').value;
            var parts = ora.split(':');
            var total = parseInt(parts[0]) * 60 + parseInt(parts[1]) + 90;
            var hFine = String(Math.floor(total / 60) % 24).padStart(2, '0');
            var mFine = String(total % 60).padStart(2, '0');
            document.getElementById('ora-fine').textContent = hFine + ':' + mFine;

            document.getElementById('confirm-box').classList.add('visible');
        }

        function confermaPrenotazione() {
            var data = document.getElementById('input-data').value;
            var ora = document.getElementById('input-ora').value;
            var persone = document.getElementById('input-persone').value;
            var d = new Date(data + 'T00:00:00');
            var dataFormatted = d.toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' });

            document.getElementById('success-detail').textContent =
                selectedTavoloNome + ' \u2014 ' + dataFormatted + ' alle ' + ora +
                ' per ' + persone + ' person' + (persone == 1 ? 'a' : 'e');

            document.getElementById('step1-card').style.display = 'none';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('success-box').style.display = 'block';
            document.getElementById('success-box').scrollIntoView({ behavior: 'smooth' });
        }
    </script>

</body>
</html>
