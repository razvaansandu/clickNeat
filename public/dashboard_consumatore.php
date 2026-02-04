<?php
session_start();
require_once "../config/db.php";

// Controllo accesso
if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'consumatore') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$restaurants = [];

// Query Ristoranti
$sql = "SELECT id, nome, indirizzo, descrizione FROM ristoranti";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $keyword = 'food'; 
        $category = 'Ristorante';
        $nome_lower = strtolower($row['nome']);
        
        if (strpos($nome_lower, 'pizza') !== false) { $keyword = 'pizza'; $category = 'Pizzeria'; }
        elseif (strpos($nome_lower, 'burger') !== false) { $keyword = 'burger'; $category = 'Hamburgeria'; }
        elseif (strpos($nome_lower, 'sushi') !== false) { $keyword = 'sushi'; $category = 'Giapponese'; }
        elseif (strpos($nome_lower, 'pasta') !== false) { $keyword = 'pasta'; $category = 'Italiano'; }
        elseif (strpos($nome_lower, 'dolce') !== false) { $keyword = 'cake'; $category = 'Dolci'; }

        // Immagine placeholder di qualità
        $row['image_url'] = "https://loremflickr.com/600/400/" . $keyword . "?lock=" . $row['id'];
        
        $row['category'] = $category;
        $row['descrizione_breve'] = !empty($row['descrizione']) ? substr($row['descrizione'], 0, 60) . '...' : 'Gustosi piatti preparati con ingredienti freschi.';
        
        $restaurants[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ClickNeat</title>
    <link rel="stylesheet" href="css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>

        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item active">
                <i class="fa-solid fa-house"></i> <span>Home</span>
            </a>
            <a href="history.php" class="nav-item">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span>
            </a>
            <a href="profile_consumatore.php" class="nav-item">
                <i class="fa-solid fa-user"></i> <span>Profilo</span>
            </a>
            <a href="logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <header class="hero-section">
        <div class="hero-content">
            <div style="font-size: 14px; margin-bottom: 10px; opacity: 0.8;">
                <i class="fa-regular fa-calendar"></i> <?php echo date("d F Y"); ?>
            </div>
            <div class="hero-title">
                <h1>Ciao, <?php echo htmlspecialchars($username); ?>! 🍕</h1>
                <p>Ordina dai migliori ristoranti della tua zona.</p>
            </div>
        </div>
    </header>

    <div class="categories-wrapper">
        <div class="categories-container">
            <div class="category-pill active"><i class="fa-solid fa-utensils"></i> Tutti</div>
            <div class="category-pill"><i class="fa-solid fa-pizza-slice"></i> Pizza</div>
            <div class="category-pill"><i class="fa-solid fa-burger"></i> Burger</div>
            <div class="category-pill"><i class="fa-solid fa-fish"></i> Sushi</div>
            <div class="category-pill"><i class="fa-solid fa-bowl-food"></i> Pasta</div>
            <div class="category-pill"><i class="fa-solid fa-ice-cream"></i> Dolci</div>
        </div>
    </div>

    <div class="main-container">
        <h3 class="section-title">Ristoranti Popolari</h3>

        <div class="grid-container">
            <?php foreach($restaurants as $rest): ?>
                <a href="menu.php?id=<?php echo $rest['id']; ?>" class="restaurant-card">
                    <img src="<?php echo $rest['image_url']; ?>" alt="Cibo" class="card-img-top">
                    
                    <div class="card-body">
                        <div class="badge-cat"><?php echo $rest['category']; ?></div>
                        <h3 class="card-title"><?php echo htmlspecialchars($rest['nome']); ?></h3>
                        
                        <div class="card-info">
                            <i class="fa-solid fa-location-dot"></i> 
                            <?php echo htmlspecialchars($rest['indirizzo']); ?>
                        </div>

                        <div style="font-size: 13px; color: #707EAE; margin-bottom: 15px; line-height: 1.4;">
                            <?php echo htmlspecialchars($rest['descrizione_breve']); ?>
                        </div>

                        <div class="btn-go">
                            Vedi Menu <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if(empty($restaurants)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                    <i class="fa-solid fa-shop" style="font-size: 40px; color: #ddd; margin-bottom: 15px;"></i>
                    <h3 style="color: #2B3674;">Nessun ristorante trovato</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>