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

// Leggi i menu items dal database
$menu_items = [];
$sql = "SELECT id, name, description, price, image_url FROM menu_items WHERE restaurant_id = ? ORDER BY created_at DESC";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $ristorante_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Se non ci sono items dal database, mostra un messaggio
if (empty($menu_items)) {
    $no_items = true;
} else {
    $no_items = false;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?php echo htmlspecialchars($ristorante['nome']); ?></title>
    <link rel="stylesheet" href="css/consumatore.css?v=1.0">
    <style>
        .menu-header {
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

        .ristorante-info h1 {
            margin: 0;
            color: #1A4D4E;
        }

        .ristorante-info p {
            margin: 5px 0 0 0;
            color: #7F8C8D;
            font-size: 14px;
        }

        .categoria-title {
            font-size: 20px;
            font-weight: 700;
            color: #1A4D4E;
            margin-top: 35px;
            margin-bottom: 20px;
            padding-left: 5px;
            border-left: 4px solid #E89020;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .menu-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #E89020;
            box-shadow: 0 12px 25px rgba(26, 77, 78, 0.15);
            background: white;
        }

        .menu-card h3 {
            color: #1A4D4E;
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .menu-card p {
            color: #7F8C8D;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .menu-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .prezzo {
            font-size: 18px;
            font-weight: 700;
            color: #E89020;
        }

        .btn-aggiungi {
            background: #E89020;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-aggiungi:hover {
            background: #d67a0c;
            transform: translateY(-2px);
        }

        .carrello-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #E89020;
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(232, 144, 32, 0.3);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .carrello-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(232, 144, 32, 0.4);
        }

        @media (max-width: 768px) {
            .menu-header {
                flex-direction: column;
                text-align: center;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .carrello-btn {
                bottom: 20px;
                right: 20px;
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu-header">
            <a href="dashboard_consumatore.php" class="back-btn">← Indietro</a>
            <div class="ristorante-info">
                <h1><?php echo htmlspecialchars($ristorante['nome']); ?></h1>
                <p><?php echo htmlspecialchars($ristorante['indirizzo']); ?></p>
            </div>
        </div>

        <?php if($no_items): ?>
            <div style="text-align: center; padding: 60px 20px; color: #7F8C8D;">
                <p style="font-size: 18px; font-weight: 600;">Il menu di questo ristorante non è ancora disponibile.</p>
            </div>
        <?php else: ?>
            <h2 class="categoria-title">Menu Disponibile</h2>
            
            <div class="menu-grid">
                <?php foreach($menu_items as $item): ?>
                    <div class="menu-card">
                        <?php if(!empty($item['image_url'])): ?>
                            <div style="width: 100%; height: 150px; background: #e2e8f0; border-radius: 8px; margin-bottom: 15px; overflow: hidden;">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        
                        <div class="menu-footer">
                            <span class="prezzo">€ <?php echo number_format($item['price'], 2); ?></span>
                            <button class="btn-aggiungi" onclick="aggiungiAlCarrello(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['price']; ?>)">
                                Aggiungi
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="carrello.php?ristorante_id=<?php echo $ristorante_id; ?>" class="carrello-btn">🛒 Vai al Carrello</a>
    </div>  

    <script>
        function aggiungiAlCarrello(id, nome, prezzo) {
            // Salva nel localStorage (implementare carrello vero)
            let carrello = JSON.parse(localStorage.getItem('carrello')) || [];
            carrello.push({
                id: id,
                nome: nome,
                prezzo: prezzo,
                ristorante_id: <?php echo $ristorante_id; ?>
            });
            localStorage.setItem('carrello', JSON.stringify(carrello));
            
            alert(nome + ' aggiunto al carrello!');
        }
    </script>
</body>
</html>
