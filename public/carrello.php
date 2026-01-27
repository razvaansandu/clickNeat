<?php
require_once "../config/db.php";

// Controlla autenticazione
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "consumatore"){
    header("Location: login_consumatore.php");
    exit;
}

// Verifica che sia stato passato un ristorante_id
if(!isset($_GET['ristorante_id']) || !is_numeric($_GET['ristorante_id'])){
    header("Location: dashboard_consumatore.php");
    exit;
}

$ristorante_id = intval($_GET['ristorante_id']);
$user_id = $_SESSION["id"];

// Ottieni i dati del ristorante
$sql = "SELECT * FROM ristoranti WHERE id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $ristorante_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ristorante = $result->fetch_assoc();
mysqli_stmt_close($stmt);

if(!$ristorante){
    header("Location: dashboard_consumatore.php");
    exit;
}

$msg = "";
$msg_type = "";

// Processa l'ordine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $carrello = isset($_POST['carrello']) ? json_decode($_POST['carrello'], true) : [];
    
    if (!empty($carrello)) {
        $total_amount = 0;
        foreach ($carrello as $item) {
            $total_amount += $item['prezzo'];
        }
        
        // Inserisci l'ordine nel database
        $sql = "INSERT INTO orders (user_id, restaurant_id, total_amount, status, created_at) VALUES (?, ?, ?, ?, NOW())";
        if ($stmt = mysqli_prepare($link, $sql)) {
            $status = "pending";
            mysqli_stmt_bind_param($stmt, "iids", $user_id, $ristorante_id, $total_amount, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $msg = "Ordine effettuato con successo! Il tuo ordine è stato confermato.";
                $msg_type = "success";
                // Pulisci il carrello dopo l'ordine
                echo "<script>localStorage.removeItem('carrello');</script>";
            } else {
                $msg = "Errore nell'elaborazione dell'ordine. Riprova.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrello - <?php echo htmlspecialchars($ristorante['nome']); ?></title>
    <link rel="stylesheet" href="css/consumatore.css?v=1.0">
    <style>
        .carrello-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .back-btn {
            background: #f7fafc;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #1A4D4E;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #1A4D4E;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .carrello-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        .carrello-items {
            background: white;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .carrello-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .carrello-item:last-child {
            border-bottom: none;
        }

        .carrello-item:hover {
            background: #f7fafc;
            border-radius: 8px;
        }

        .item-info h3 {
            color: #1A4D4E;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .item-price {
            color: #E89020;
            font-weight: 700;
            font-size: 16px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-remove {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 6px;
            color: #d93025;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .btn-remove:hover {
            background: #d93025;
            color: white;
            border-color: #d93025;
        }

        .carrello-vuoto {
            text-align: center;
            padding: 60px 20px;
            color: #7F8C8D;
        }

        .carrello-vuoto p {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .carrello-vuoto a {
            display: inline-block;
            background: #E89020;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .carrello-vuoto a:hover {
            background: #d67a0c;
            transform: translateY(-2px);
        }

        .carrello-riepilogo {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .riepilogo-title {
            font-size: 18px;
            font-weight: 700;
            color: #1A4D4E;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .riepilogo-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #7F8C8D;
        }

        .riepilogo-row.total {
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 18px;
            font-weight: 700;
            color: #1A4D4E;
        }

        .btn-checkout {
            width: 100%;
            background: #E89020;
            color: white;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-checkout:hover {
            background: #d67a0c;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(232, 144, 32, 0.3);
        }

        .btn-checkout:disabled {
            background: #A3AED0;
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 768px) {
            .carrello-container {
                grid-template-columns: 1fr;
            }

            .carrello-riepilogo {
                position: static;
            }

            .carrello-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .item-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="carrello-header">
            <a href="menu.php?ristorante_id=<?php echo $ristorante_id; ?>" class="back-btn">← Continua Shopping</a>
            <div>
                <h1 style="margin: 0; color: #1A4D4E;">🛒 Carrello</h1>
                <p style="margin: 5px 0 0 0; color: #7F8C8D; font-size: 14px;"><?php echo htmlspecialchars($ristorante['nome']); ?></p>
            </div>
        </div>

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
                <?php if($msg_type == "success"): ?>
                    <br><a href="dashboard_consumatore.php" style="color: inherit; text-decoration: underline; margin-top: 10px; display: inline-block;">Torna alla dashboard</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="carrello-container">
            <div class="carrello-items" id="carrello-items">
                <div class="carrello-vuoto">
                    <p>Il tuo carrello è vuoto</p>
                    <a href="menu.php?ristorante_id=<?php echo $ristorante_id; ?>">Scopri il menu</a>
                </div>
            </div>

            <div class="carrello-riepilogo">
                <div class="riepilogo-title">Riepilogo Ordine</div>
                
                <div class="riepilogo-row">
                    <span>Articoli:</span>
                    <span id="num-articoli">0</span>
                </div>
                
                <div class="riepilogo-row total">
                    <span>Totale:</span>
                    <span id="totale-prezzo">€ 0.00</span>
                </div>

                <form method="POST" id="checkout-form">
                    <input type="hidden" id="carrello-input" name="carrello" value="[]">
                    <button type="submit" name="checkout" class="btn-checkout" id="btn-checkout" disabled>
                        Procedi all'Ordine
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function caricaCarrello() {
            let carrello = JSON.parse(localStorage.getItem('carrello')) || [];
            let container = document.getElementById('carrello-items');
            let totale = 0;

            // Filtra solo gli items del ristorante corrente
            carrello = carrello.filter(item => item.ristorante_id == <?php echo $ristorante_id; ?>);

            if (carrello.length === 0) {
                container.innerHTML = `
                    <div class="carrello-vuoto">
                        <p>Il tuo carrello è vuoto</p>
                        <a href="menu.php?ristorante_id=<?php echo $ristorante_id; ?>">Scopri il menu</a>
                    </div>
                `;
                document.getElementById('num-articoli').textContent = '0';
                document.getElementById('totale-prezzo').textContent = '€ 0.00';
                document.getElementById('btn-checkout').disabled = true;
                return;
            }

            let html = '';
            carrello.forEach((item, index) => {
                html += `
                    <div class="carrello-item">
                        <div class="item-info">
                            <h3>${item.nome}</h3>
                            <span class="item-price">€ ${parseFloat(item.prezzo).toFixed(2)}</span>
                        </div>
                        <div class="item-actions">
                            <button class="btn-remove" onclick="rimuoviDalCarrello(${index})">Rimuovi</button>
                        </div>
                    </div>
                `;
                totale += parseFloat(item.prezzo);
            });

            container.innerHTML = html;
            document.getElementById('num-articoli').textContent = carrello.length;
            document.getElementById('totale-prezzo').textContent = '€ ' + totale.toFixed(2);
            document.getElementById('carrello-input').value = JSON.stringify(carrello);
            document.getElementById('btn-checkout').disabled = false;
        }

        function rimuoviDalCarrello(index) {
            let carrello = JSON.parse(localStorage.getItem('carrello')) || [];
            
            // Filtra per ristorante e rimuovi l'index globale
            let globalIndex = 0;
            for (let i = 0; i < carrello.length; i++) {
                if (carrello[i].ristorante_id == <?php echo $ristorante_id; ?>) {
                    if (globalIndex === index) {
                        carrello.splice(i, 1);
                        break;
                    }
                    globalIndex++;
                }
            }

            localStorage.setItem('carrello', JSON.stringify(carrello));
            caricaCarrello();
        }

        // Carica il carrello al page load
        window.addEventListener('load', caricaCarrello);
    </script>
</body>
</html>
