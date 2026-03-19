<?php
require_once "../../config/db.php";
require_once "../../models/RistoranteModel.php";

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'consumatore') {
    header("location: login.php");
    exit;
}

function getCategorieDaJSON() {
    $jsonPath = __DIR__ . "/../../config/categorie.json";
    if (file_exists($jsonPath)) {
        $jsonData = file_get_contents($jsonPath);
        $categorie = json_decode($jsonData, true);
        return is_array($categorie) ? $categorie : [];
    }
    return [];
}

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$restaurants = [];
$categorie_disponibili = getCategorieDaJSON();

$ristoranteModel = new RistoranteModel($db);
$raw_restaurants = $ristoranteModel->getAll();

$categoria_map = [
    'giapponese' => ['sushi', 'giapponese', 'ramen', 'sashimi', 'giappone'],
    'pizzeria' => ['pizza', 'pizzeria', 'margherita', 'diavola'],
    'hamburgeria' => ['burger', 'hamburger', 'panino', 'cheeseburger'],
    'italiano' => ['pasta', 'trattoria', 'italiano', 'lasagna', 'risotto'],
    'dolci' => ['dolci', 'gelato', 'pasticceria', 'torta', 'cannolo'],
    'messicano' => ['messicano', 'taco', 'burrito', 'quesadilla'],
    'kebab' => ['kebab', 'kebap', 'doner', 'gyros'],
    'cinese' => ['cinese', 'ravioli', 'riso cantonese', 'wok'],
    'indiano' => ['indiano', 'curry', 'tandoori'],
    'thailandese' => ['thai', 'thailandese', 'pad thai'],
    'pesce' => ['pesce', 'frittura', 'crostacei', 'molluschi']
];

foreach ($raw_restaurants as $row) {
    if (!empty($row['image_url'])) {
        $row['image_url'] = "" . htmlspecialchars($row['image_url']);
    } else {
        $row['image_url'] = "/public/img/placeholder_restaurant.jpg";
    }

    $db_cat = strtolower($row['categoria'] ?? ''); 
    $nome_lower = strtolower($row['nome']);
    $desc_lower = strtolower($row['descrizione'] ?? '');

    $display_cat = 'Ristorante';
    foreach ($categoria_map as $categoria_key => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($db_cat, $keyword) !== false || 
                strpos($nome_lower, $keyword) !== false || 
                strpos($desc_lower, $keyword) !== false) {
                $display_cat = ucfirst($categoria_key);
                break 2;
            }
        }
    }


    if ($display_cat === 'Ristorante' && !empty($row['categoria'])) {
        $display_cat = ucfirst($row['categoria']);
    }

    $row['category_label'] = $display_cat;
    $row['descrizione_breve'] = !empty($row['descrizione']) ? substr($row['descrizione'], 0, 60) . '...' : 'Gustosi piatti preparati con ingredienti freschi.';

    $restaurants[] = $row;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ClickNeat</title>
    <link rel="stylesheet" href="../css/style_consumatori.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .categories-wrapper {
            margin: 20px 0;
            position: relative;
        }
        
        .categories-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px 0;
        }
        
        .category-pill {
            background: white;
            border: 1px solid #E0E5F2;
            border-radius: 30px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            color: #2B3674;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .category-pill i {
            font-size: 14px;
        }
        
        .category-pill:hover {
            background: #FF9F43;
            border-color: #ffffff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 24, 255, 0.2);
        }
        
        .category-pill:hover i {
            color: white;
        }
        
        .category-pill.active {
            background: #FF9F43;
            border-color: #FF9F43;
            color: white;
        }
        
        .category-pill.active i {
            color: white;
        }
        
        .show-all-btn {
            background: white;
            border: 1px solid #E0E5F2;
            border-radius: 30px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            color: #2B3674;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            
        }
        
        .show-all-btn:hover {
            background: #FF9F43;
            border-color: #FF9F43;
            color: white;
        }
        
        .show-all-btn:hover i {
            color: white;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #E0E5F2;
        }
        
        .modal-header h3 {
            color: #2B3674;
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #A3AED0;
            transition: color 0.3s ease;
        }
        
        .close-modal:hover {
            color: #E31A1A;
        }
        
        .categorie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .categoria-item {
            background: #F8F9FF;
            border: 1px solid #E0E5F2;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .categoria-item:hover {
            background: #FF9F43;
            border-color: #FF9F43;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 24, 255, 0.2);
        }
        
        .categoria-item:hover .categoria-nome,
        .categoria-item:hover i {
            color: white;
        }
        
        .categoria-item i {
            font-size: 24px;
            color: #FF9F43;
        }
        
        .categoria-nome {
            font-size: 14px;
            font-weight: 500;
            color: #2B3674;
        }
        
        @media (max-width: 768px) {
            .categorie-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
        </a>
        <div class="searchBar">
            <input type="text" placeholder="Cerca ristoranti, pizza, sushi..." id="searchInput" />
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
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
            
            <a href="../auth/logout.php" class="btn-logout-nav">
                <i class="fa-solid fa-right-from-bracket"></i> Esci
            </a>
        </div>
    </nav>

    <div class="mobile-header-fixed">
        <div class="mobile-top-row">
            <a href="dashboard_consumatore.php" class="brand-logo">
                <i class="fa-solid fa-leaf" style="color: #05CD99;"></i> ClickNeat
            </a>
            <a href="../auth/logout.php" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
        <div class="mobile-search-bar">
            <input type="text" placeholder="Cerca ristoranti, pizza, sushi..." id="searchInputMobile" />
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="dashboard_consumatore.php" class="nav-item-bottom active">
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="color: #2B3674; font-size: 16px; margin: 0;">Filtra per categoria</h3>
            
        </div>
        
        <div class="categories-container">
            <div class="category-pill active" data-category="all">
                <i class="fa-solid fa-utensils"></i> Tutti
            </div>
            <?php 

            $prime_categorie = array_slice($categorie_disponibili, 0, 5);
            foreach ($prime_categorie as $categoria): 

                $icona = 'fa-solid fa-tag';
                $categoria_lower = strtolower($categoria);
                
                if (strpos($categoria_lower, 'pizza') !== false) {
                    $icona = 'fa-solid fa-pizza-slice';
                } elseif (strpos($categoria_lower, 'burger') !== false || strpos($categoria_lower, 'panino') !== false) {
                    $icona = 'fa-solid fa-burger';
                } elseif (strpos($categoria_lower, 'giapponese') !== false || strpos($categoria_lower, 'sushi') !== false) {
                    $icona = 'fa-solid fa-fish';
                } elseif (strpos($categoria_lower, 'dolci') !== false || strpos($categoria_lower, 'gelato') !== false) {
                    $icona = 'fa-solid fa-seedling';
                } elseif (strpos($categoria_lower, 'messicano') !== false) {
                    $icona = 'fa-solid fa-pepper-hot';
                } elseif (strpos($categoria_lower, 'kebab') !== false) {
                    $icona = 'fa-solid fa-drumstick-bite';
                } elseif (strpos($categoria_lower, 'cinese') !== false) {
                    $icona = 'fa-solid fa-egg';
                } elseif (strpos($categoria_lower, 'indiano') !== false) {
                    $icona = 'fa-solid fa-spoon';
                } elseif (strpos($categoria_lower, 'pesce') !== false) {
                    $icona = 'fa-solid fa-fish';
                }
            ?>
                <div class="category-pill" data-category="<?php echo htmlspecialchars(strtolower($categoria)); ?>">
                    <i class="<?php echo $icona; ?>"></i> <?php echo htmlspecialchars($categoria); ?>
                </div>
            <?php endforeach; ?>
            <button class="show-all-btn" onclick="openCategorieModal()">
                <i class="fa-solid fa-grid-2"></i> Mostra tutto
            </button>
        </div>
    </div>


    <div id="categorieModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-grid-2"></i> Tutte le categorie</h3>
                <button class="close-modal" onclick="closeCategorieModal()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            
            <div class="categorie-grid">
                <?php foreach ($categorie_disponibili as $categoria): 

                    $icona = 'fa-solid fa-tag';
                    $categoria_lower = strtolower($categoria);
                    
                    if (strpos($categoria_lower, 'pizza') !== false) {
                        $icona = 'fa-solid fa-pizza-slice';
                    } elseif (strpos($categoria_lower, 'burger') !== false || strpos($categoria_lower, 'panino') !== false) {
                        $icona = 'fa-solid fa-burger';
                    } elseif (strpos($categoria_lower, 'giapponese') !== false || strpos($categoria_lower, 'sushi') !== false) {
                        $icona = 'fa-solid fa-fish';
                    } elseif (strpos($categoria_lower, 'dolci') !== false || strpos($categoria_lower, 'gelato') !== false) {
                        $icona = 'fa-solid fa-seedling';
                    } elseif (strpos($categoria_lower, 'messicano') !== false) {
                        $icona = 'fa-solid fa-pepper-hot';
                    } elseif (strpos($categoria_lower, 'kebab') !== false) {
                        $icona = 'fa-solid fa-drumstick-bite';
                    } elseif (strpos($categoria_lower, 'cinese') !== false) {
                        $icona = 'fa-solid fa-egg';
                    } elseif (strpos($categoria_lower, 'indiano') !== false) {
                        $icona = 'fa-solid fa-spoon';
                    } elseif (strpos($categoria_lower, 'pesce') !== false) {
                        $icona = 'fa-solid fa-fish';
                    }
                ?>
                    <div class="categoria-item" onclick="selezionaCategoria('<?php echo htmlspecialchars(strtolower($categoria)); ?>')">
                        <i class="<?php echo $icona; ?>"></i>
                        <span class="categoria-nome"><?php echo htmlspecialchars($categoria); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="main-container">
        <h3 class="section-title">Ristoranti Popolari</h3>

        <div class="grid-container" id="restaurantsGrid">
            <?php if (empty($restaurants)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-shop empty-icon"></i>
                    <h3 class="empty-title">Nessun ristorante disponibile</h3>
                    <p class="empty-text">Non ci sono ristoranti attivi al momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($restaurants as $rest): ?>
                    <a href="menu.php?id=<?php echo $rest['id']; ?>" class="restaurant-card" data-name="<?php echo htmlspecialchars($rest['nome']); ?>" data-keywords="<?php echo htmlspecialchars(strtolower($rest['category_label'])); ?>">
                        <img src="<?php echo $rest['image_url']; ?>" alt="Cibo" class="card-img-top" style="object-fit: cover; height: 160px; width: 100%;">
                        <div class="card-body">
                            <div class="badge-cat"><?php echo htmlspecialchars($rest['category_label']); ?></div>
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
            <?php endif; ?>
        </div>
    </div>

    <script>
    const searchInput = document.getElementById('searchInput');
    const searchInputMobile = document.getElementById('searchInputMobile');
    const cards = document.querySelectorAll('.restaurant-card');
    const categoryPills = document.querySelectorAll('.category-pill');
    let currentCategory = 'all';
    
    function filterCards(searchTerm = '') {
        const term = searchTerm.toLowerCase();
        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const category = card.dataset.keywords.toLowerCase();
            const matchesSearch = term === '' || name.includes(term) || category.includes(term);
            
            let matchesCategory = currentCategory === 'all';
            
            if (!matchesCategory) {
                if (category.includes(currentCategory)) {
                    matchesCategory = true;
                } else {
                    const categoryWords = category.split(' ');
                    matchesCategory = categoryWords.some(word => currentCategory.includes(word) || word.includes(currentCategory));
                }
            }
            
            card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
        });
    }

    categoryPills.forEach(pill => {
        pill.addEventListener('click', function() {
            categoryPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.category;
            filterCards(searchInput ? searchInput.value : '');
            closeCategorieModal(); 
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => filterCards(searchInput.value));
    }
    if (searchInputMobile) {
        searchInputMobile.addEventListener('input', () => filterCards(searchInputMobile.value));
    }


    function openCategorieModal() {
        document.getElementById('categorieModal').style.display = 'flex';
    }
    
    function closeCategorieModal() {
        document.getElementById('categorieModal').style.display = 'none';
    }
    
    function selezionaCategoria(categoria) {

        const pill = Array.from(categoryPills).find(p => p.dataset.category === categoria);
        if (pill) {
            pill.click();
        } else {

            currentCategory = categoria;
            filterCards(searchInput ? searchInput.value : '');
        }
        closeCategorieModal();
    }


    window.addEventListener('click', function(e) {
        const modal = document.getElementById('categorieModal');
        if (e.target === modal) {
            closeCategorieModal();
        }
    });


    if (searchInput && searchInput.value) {
        filterCards(searchInput.value);
    }
    </script>
</body>
</html>