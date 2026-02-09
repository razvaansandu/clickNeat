<?php
require_once "../../config/db.php";
require_once "../../models/consumatore/RistoranteModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'consumatore') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$restaurants = [];

$ristoranteModel = new RistoranteModel($db);

$raw_restaurants = $ristoranteModel->getAll();

foreach ($raw_restaurants as $row) {
    
    $keyword = 'food'; 
    $category = 'Ristorante';
    $nome_lower = strtolower($row['nome']);
    
    if (strpos($nome_lower, 'pizza') !== false) { $keyword = 'pizza'; $category = 'Pizzeria'; }
    elseif (strpos($nome_lower, 'burger') !== false) { $keyword = 'burger'; $category = 'Hamburgeria'; }
    elseif (strpos($nome_lower, 'sushi') !== false) { $keyword = 'sushi'; $category = 'Giapponese'; }
    elseif (strpos($nome_lower, 'pasta') !== false) { $keyword = 'pasta'; $category = 'Italiano'; }
    elseif (strpos($nome_lower, 'dolce') !== false) { $keyword = 'cake'; $category = 'Dolci'; }

    $row['image_url'] = "https://loremflickr.com/600/400/" . $keyword . "?lock=" . $row['id'];
    $row['category'] = $category;
    $row['descrizione_breve'] = !empty($row['descrizione']) ? substr($row['descrizione'], 0, 60) . '...' : 'Gustosi piatti preparati con ingredienti freschi.';
    
    $restaurants[] = $row;
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ClickNeat</title>
    <link rel="stylesheet" href="../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body> 

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a> 
        <div class="searchBar">
            <input type="text" placeholder="Cerca ristoranti..." id="searchInput" />
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                 class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg>
        </div>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item active">
                <i class="fa-solid fa-house"></i> <span>Home</span>
            </a>
            <a href="storico.php" class="nav-item">
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

    <header class="hero-section">
        <div class="hero-content">
            <div style="font-size: 14px; margin-bottom: 10px; opacity: 0.8;">
                <i class="fa-regular fa-calendar"></i> <?php echo date("d F Y"); ?>
            </div>
            <div class="hero-title">
                <h1>Ciao, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>Ordina dai migliori ristoranti della tua zona.</p>
            </div>
        </div>
    </header>

    <div class="categories-wrapper">
        <div class="categories-container"> 
            <div class="category-pill active" data-category="all">
                <i class="fa-solid fa-utensils"></i> Tutti
            </div>
            <div class="category-pill" data-category="pizza">
                <i class="fa-solid fa-pizza-slice"></i> Pizza
            </div>
            <div class="category-pill" data-category="burger">
                <i class="fa-solid fa-burger"></i> Burger
            </div>
            <div class="category-pill" data-category="sushi">
                <i class="fa-solid fa-fish"></i> Sushi
            </div>
            <div class="category-pill" data-category="pasta">
                <i class="fa-solid fa-bowl-food"></i> Pasta
            </div>
            <div class="category-pill" data-category="dolci">
                <i class="fa-solid fa-ice-cream"></i> Dolci
            </div>
        </div> 
    </div>

    <div class="main-container">
        <h3 class="section-title" id="sectionTitle">Ristoranti Popolari</h3>
        
        <div id="resultsCount" style="font-size: 14px; color: #707EAE; margin-bottom: 20px; display: none;">
            <span id="countNumber">0</span> ristoranti trovati
        </div>

        <div class="grid-container" id="restaurantsGrid">
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
                <div class="empty-state" id="noResults">
                    <i class="fa-solid fa-shop empty-icon"></i>
                    <h3 class="empty-title">Nessun ristorante trovato</h3>
                    <p class="empty-text">Prova a cercare un'altra categoria o ristorante.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    let allRestaurants = <?php echo json_encode($restaurants); ?>;
    let currentCategory = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('searchInput');
        const gridContainer = document.getElementById('restaurantsGrid');
        const sectionTitle = document.getElementById('sectionTitle');
        const resultsCount = document.getElementById('resultsCount');
        const countNumber = document.getElementById('countNumber');
        const categoryPills = document.querySelectorAll('.category-pill');
        
        function renderRestaurants(restaurants) {
            if (restaurants.length === 0) {
                gridContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-shop empty-icon"></i>
                        <h3 class="empty-title">Nessun ristorante trovato</h3>
                        <p class="empty-text">Prova a cercare un'altra categoria o ristorante.</p>
                    </div>
                `;
                resultsCount.style.display = 'none';
                return;
            }
            
            let html = '';
            restaurants.forEach(rest => {
                html += `
                    <a href="menu.php?id=${rest.id}" class="restaurant-card">
                        <img src="${rest.image_url}" alt="Cibo" class="card-img-top">
                        <div class="card-body"> 
                            <div class="badge-cat">${rest.category}</div>
                            <h3 class="card-title">${escapeHtml(rest.nome)}</h3>
                            <div class="card-info">
                                <i class="fa-solid fa-location-dot"></i> 
                                ${escapeHtml(rest.indirizzo)}
                            </div> 
                            <div style="font-size: 13px; color: #707EAE; margin-bottom: 15px; line-height: 1.4;">
                                ${escapeHtml(rest.descrizione_breve)}
                            </div>
                            <div class="btn-go">
                                Vedi Menu <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </div>
                    </a>
                `;
            });
            
            gridContainer.innerHTML = html;
            countNumber.textContent = restaurants.length;
            resultsCount.style.display = 'block';
        }
        
        function filterRestaurants(searchTerm = '', category = 'all') {
            let filtered = allRestaurants;
            
            if (category !== 'all') {
                filtered = filtered.filter(rest => 
                    rest.category.toLowerCase().includes(category.toLowerCase())
                );
            }
            
            if (searchTerm.trim().length >= 2) {
                const term = searchTerm.toLowerCase();
                filtered = filtered.filter(rest => 
                    rest.nome.toLowerCase().includes(term) ||
                    rest.indirizzo.toLowerCase().includes(term) ||
                    rest.descrizione.toLowerCase().includes(term)
                );
                sectionTitle.textContent = `Risultati per "${searchTerm}"`;
            } else if (category !== 'all') {
                sectionTitle.textContent = `${category.charAt(0).toUpperCase() + category.slice(1)}`;
            } else {
                sectionTitle.textContent = 'Ristoranti Popolari';
                if (searchTerm === '') {
                    resultsCount.style.display = 'none';
                }
            }
            
            renderRestaurants(filtered);
        }
                let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                filterRestaurants(searchTerm, currentCategory);
            }, 1);  
        });
        
        categoryPills.forEach(pill => {
            pill.addEventListener('click', function() {
                categoryPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                
                currentCategory = this.dataset.category;
                
                filterRestaurants(input.value.trim(), currentCategory);
            });
        });
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        renderRestaurants(allRestaurants);
    });
    </script>
</body>
</html>