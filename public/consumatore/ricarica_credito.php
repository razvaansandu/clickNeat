<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once "../../config/db.php";
require_once "../../models/WalletModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$walletModel = new WalletModel($db);
$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['paypal_transaction_id']) || isset($_POST['test_mode'])) {
        $amount_euros = floatval($_POST['amount']);
        $amount_cents = intval(round($amount_euros * 100));

        if ($amount_cents > 0) {
            if ($walletModel->addFunds($user_id, $amount_cents)) {
                header("Location: profile_consumatore.php?topup=success");
                exit;
            } else {
                $msg = "Error during deposit. Please contact support.";
                $msg_type = "error";
            }
        }
    }
}

$creditoEuro = $walletModel->getBalanceEuro($user_id);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ricarica Credito - ClickNeat</title>
    <link rel="stylesheet" href="../../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://www.paypal.com/sdk/js?client-id=ATHTb2gXY3GKqi99hQwcNXNVsHisCXgf7iYt5stbRypxjqjEe-qBPaffW9hC9-LEq9ZqgitpD0UYKGkY&currency=EUR&disable-funding=card,mybank"></script>
    <style>
        .recharge-card {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            text-align: center;
        }

        .balance-display {
            background: #f8faff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px dashed #cbd5e1;
        }

        .balance-display span {
            display: block;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .balance-display strong {
            display: block;
            color: #2B3674;
            font-size: 32px;
            font-weight: 800;
        }

        .amount-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }

        .amount-option {
            border: 2px solid #E0E5F2;
            border-radius: 12px;
            padding: 15px 5px;
            cursor: pointer;
            font-weight: 700;
            color: #2B3674;
            transition: all 0.2s;
        }

        .amount-option:hover {
            border-color: var(--accent-orange);
            background: #fffafa;
        }

        .amount-option.active {
            border-color: var(--accent-orange);
            background: var(--accent-orange);
            color: white;
        }

        .custom-amount-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #E0E5F2;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            color: #2B3674;
        }

        .custom-amount-input:focus {
            border-color: var(--accent-orange);
            outline: none;
        }
    </style>
</head>

<body>
    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>
        <div class="nav-links">
            <a href="profile_consumatore.php" class="nav-item">
                <i class="fa-solid fa-arrow-left"></i> <span>Indietro</span>
            </a>
        </div>
    </nav>

    <div class="main-container">
        <div class="card-style recharge-card">
            <h1 style="color: #2B3674; margin-bottom: 10px;">Ricarica Portafoglio</h1>
            <p style="color: #A3AED0; margin-bottom: 30px;">Aggiungi credito per pagare i tuoi ordini più velocemente.</p>

            <div class="balance-display">
                <span>Saldo attuale</span>
                <strong>&euro; <?php echo $creditoEuro; ?></strong>
            </div>

            <h3 style="color: #2B3674; text-align: left; font-size: 16px; margin-bottom: 15px;">Scegli un importo</h3>
            <div class="amount-selector">
                <div class="amount-option" onclick="setAmount(5)">€ 5</div>
                <div class="amount-option active" onclick="setAmount(10)">€ 10</div>
                <div class="amount-option" onclick="setAmount(20)">€ 20</div>
                <div class="amount-option" onclick="setAmount(50)">€ 50</div>
                <div class="amount-option" onclick="setAmount(100)">€ 100</div>
                <div class="amount-option" onclick="showCustom()">Altro</div>
            </div>

            <div id="custom-amount-box" style="display: none;">
                <input type="number" id="custom-amount" class="custom-amount-input" value="10.00" min="1" step="0.50" oninput="updateAmount(this.value)">
            </div>

            <div id="paypal-button-container"></div>
            
            <form id="recharge-form" method="POST">
                <input type="hidden" name="amount" id="form-amount" value="10.00">
                <input type="hidden" name="paypal_transaction_id" id="paypal_tx_id">
                
               
            </form>
        </div>
    </div>

    <script>
        let currentAmount = 10.00;

        function setAmount(val) {
            document.querySelectorAll('.amount-option').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('custom-amount-box').style.display = 'none';
            currentAmount = val;
            document.getElementById('form-amount').value = val.toFixed(2);
        }

        function showCustom() {
            document.querySelectorAll('.amount-option').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('custom-amount-box').style.display = 'block';
            let customVal = parseFloat(document.getElementById('custom-amount').value);
            if(isNaN(customVal)) customVal = 0;
            currentAmount = customVal;
            document.getElementById('form-amount').value = currentAmount.toFixed(2);
        }

        function updateAmount(val) {
            currentAmount = parseFloat(val);
            document.getElementById('form-amount').value = currentAmount.toFixed(2);
        }

        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: 'Ricarica Credito ClickNeat',
                        amount: {
                            value: currentAmount.toFixed(2)
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    document.getElementById('paypal_tx_id').value = details.id;
                    document.getElementById('recharge-form').submit();
                });
            }
        }).render('#paypal-button-container');
    </script>
</body>

</html>