<?php
require_once "../../config/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'ristoratore') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$my_restaurants = [];

$sql = "SELECT * FROM ristoranti WHERE proprietario_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = $result->fetch_assoc()) {
        $rest_id = $row['id'];
        
        $sql_orders = "SELECT COUNT(*) as count FROM orders WHERE restaurant_id = $rest_id";
        $row['total_orders'] = mysqli_fetch_assoc(mysqli_query($link, $sql_orders))['count'] ?? 0;

        $sql_money = "SELECT SUM(total_amount) as total FROM orders WHERE restaurant_id = $rest_id AND status = 'completed'";
        $row['revenue'] = mysqli_fetch_assoc(mysqli_query($link, $sql_money))['total'] ?? 0.00;

        //dati fittizzi
        $row['trend_data'] = [rand(20, 100), rand(20, 100), rand(20, 100), rand(20, 100), rand(50, 100)];

        $my_restaurants[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ClickNeat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: #F4F7FE;
            min-height: 100vh; 
        }

        .main-content { 
            margin-left: 260px;
            padding: 40px; 
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }
        .page-header h1 { font-size: 28px; font-weight: 700; color: #2B3674; }
        .page-header p { color: #A3AED0; margin-top: 5px; }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 18px 40px rgba(112, 144, 176, 0.12);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(112, 144, 176, 0.2);
            border-color: #E89020;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: #F4F7FE;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1A4D4E;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .card h2 { font-size: 20px; color: #1B2559; margin-bottom: 5px; }
        .card .subtitle { color: #A3AED0; font-size: 14px; }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 25px;
        }
        .revenue { font-size: 24px; font-weight: 700; color: #1A4D4E; }
        .revenue span { font-size: 14px; color: #A3AED0; font-weight: 500; }

        .mini-chart {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            height: 40px;
        }
        .bar {
            width: 8px;
            background-color: #E0E5F2;
            border-radius: 10px;
        }
        .bar.active { background-color: #1A4D4E; } 

        .card-add {
            border: 2px dashed #A3AED0;
            background: transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: none;
            min-height: 250px;
        }
        .card-add:hover {
            border-color: #E89020;
            background-color: rgba(232, 144, 32, 0.05);
            transform: translateY(-5px);
        }
        .icon-plus {
            font-size: 40px;
            color: #E89020;
            margin-bottom: 15px;
        }
        .text-add {
            font-weight: 600;
            color: #1A4D4E;
            font-size: 18px;
        }

        .btn-manage {
            display: block;
            margin-top: 20px;
            text-align: center;
            padding: 10px;
            background: #F4F7FE;
            color: #1A4D4E;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-manage:hover { background: #1A4D4E; color: white; }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <p>Panoramica</p>
                <h1>Bentornato, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            </div>
            <div style="background: white; padding: 10px 20px; border-radius: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); font-weight:600; color:#1A4D4E;">
                <i class="fa-regular fa-calendar"></i> <?php echo date("d M Y"); ?>
            </div>
        </div>

        <h3 style="color: #2B3674; margin-bottom: 20px;">I tuoi Ristoranti</h3>

        <div class="grid-container">
            
            <?php foreach($my_restaurants as $rest): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between;">
                        <div class="card-icon"><i class="fa-solid fa-store"></i></div>
                        <div style="color:#A3AED0; cursor:pointer;"></div>
                    </div>

                    <h2><?php echo htmlspecialchars($rest['nome']); ?></h2>
                    <p class="subtitle">Ordini totali: <b><?php echo $rest['total_orders']; ?></b></p>

                    <div class="stat-row">
                        <div class="revenue">
                            € <?php echo number_format($rest['revenue'], 2); ?>
                            <br><span>Guadagni totali</span>
                        </div>
                        
                        <div class="mini-chart">
                            <?php foreach($rest['trend_data'] as $key => $val): ?>
                                <div class="bar <?php echo $key > 2 ? 'active' : ''; ?>" style="height: <?php echo $val; ?>%;"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <a href="manage_restaurant.php?id=<?php echo $rest['id']; ?>" class="btn-manage">
                        Gestisci Menu <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>

            <a href="create_restaurant.php" style="text-decoration:none;">
                <div class="card card-add">
                    <div class="icon-plus">+</div>
                    <div class="text-add">Aggiungi Ristorante</div>
                    <p style="color:#A3AED0; font-size:14px; margin-top:5px;">Espandi il tuo business</p>
                </div>
            </a>

        </div>
    </div>

</body>
</html>