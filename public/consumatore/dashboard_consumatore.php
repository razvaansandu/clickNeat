<?php
require_once "../../config/db.php";
require_once "../../models/RistoranteModel.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["ruolo"] !== 'consumatore') {
    header("location: login.php");
    exit;
}

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

// Funzione data italiana
function dataItaliana($timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    $giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
    $mesi = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

    $g = (int)date('w', $timestamp);
    $m = (int)date('n', $timestamp) - 1;
    $anno = date('Y', $timestamp);
    $giorno = date('d', $timestamp);

    return $giorni[$g] . ' ' . $giorno . ' ' . $mesi[$m] . ' ' . $anno;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>ClickNeat - Ristoranti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- ATTENZIONE: modifica il percorso se necessario -->
    <link rel="stylesheet" href="/css/style_consumatori.css">
</head>
<body>

    <!-- DESKTOP NAVBAR -->
    <nav class="top-navbar">
        <a href="dashboard_consumatore.php" class="brand-logo">
            <i class="fa-solid fa-leaf"></i> ClickNeat
        </a>
        <div class="searchBar">
            <input type="text" placeholder="Cerca ristoranti..." id="searchInput">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg>
        </div>
        <div class="nav-links">
            <a href="dashboard_consumatore.php" class="nav-item active"><i class="fa-solid fa-house"></i> <span>Home</span></a>
            <a href="storico.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span></a>
            <a href="profile_consumatore.php" class="nav-item"><i class="fa-solid fa-user"></i> <span>Profilo</span></a>
            <a href="help.php" class="nav-item"><i class="fa-solid fa-circle-question"></i> <span>Aiuto</span></a>
            <a href="../auth/logout.php" class="btn-logout-nav"><i class="fa-solid fa-right-from-bracket"></i> <span>Esci</span></a>
        </div>
    </nav>


    <!-- HERO SECTION -->
    <header class="hero-section">
        <div class="hero-content">
            <div class="hero-date"><i class="fa-regular fa-calendar"></i> <?php echo dataItaliana(); ?></div>
            <div class="hero-title">
                <h1>Ciao, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>Ordina dai migliori ristoranti della tua zona.</p>
            </div>
        </div>
    </header>

    <!-- CATEGORIES -->
    <div class="categories-wrapper">
        <div class="categories-container">
            <div class="category-pill active" data-category="all"><i class="fa-solid fa-utensils"></i> Tutti</div>
            <div class="category-pill" data-category="pizzeria"><i class="fa-solid fa-pizza-slice"></i> Pizza</div>
            <div class="category-pill" data-category="hamburgeria"><i class="fa-solid fa-burger"></i> Burger</div>
            <div class="category-pill" data-category="giapponese"><i class="fa-solid fa-fish"></i> Sushi</div>
            <div class="category-pill" data-category="italiano"><i class="fa-solid fa-bowl-food"></i> Pasta</div>
            <div class="category-pill" data-category="dolci"><i class="fa-solid fa-ice-cream"></i> Dolci</div>
        </div>
    </div> 

    <div class="main-container <?php echo (count($restaurants) > 0) ? 'content-with-bottom-nav' : ''; ?>">
        <h3 class="section-title" id="sectionTitle">Ristoranti Popolari</h3>
        <div class="results-count" id="resultsCount" style="display: none;">
            <i class="fa-solid fa-shop"></i> <span id="countNumber">0</span> ristoranti trovati
        </div>

        <div class="grid-container" id="restaurantsGridDesktop">
            <?php foreach ($restaurants as $rest): ?>
                <a href="menu.php?id=<?php echo $rest['id']; ?>" class="restaurant-card">
                    <img src="<?php echo $rest['image_url']; ?>" alt="<?php echo htmlspecialchars($rest['nome']); ?>" class="card-img-top" loading="lazy" onerror="this.src='https://via.placeholder.com/600x400/FF9F43/ffffff?text=Food'">
                    <div class="card-body">
                        <div class="badge-cat"><?php echo $rest['category']; ?></div>
                        <h3 class="card-title"><?php echo htmlspecialchars($rest['nome']); ?></h3>
                        <div class="card-info"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars(substr($rest['indirizzo'], 0, 40)) . (strlen($rest['indirizzo']) > 40 ? '...' : ''); ?></div>
                        <div style="font-size:13px; color:#707EAE; margin-bottom:15px; line-height:1.4;"><?php echo htmlspecialchars($rest['descrizione_breve']); ?></div>
                        <div class="btn-go">Vedi Menu <i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="restaurant-list" id="restaurantsGridMobile">
            <?php foreach ($restaurants as $rest): ?>
                <a href="menu.php?id=<?php echo $rest['id']; ?>" class="restaurant-card">
                    <img src="<?php echo $rest['image_url']; ?>" alt="<?php echo htmlspecialchars($rest['nome']); ?>" class="restaurant-img" loading="lazy" onerror="this.src='https://via.placeholder.com/80x80/FF9F43/ffffff?text=Food'">
                    <div class="restaurant-info">
                        <div class="restaurant-category"><?php echo $rest['category']; ?></div>
                        <h3 class="restaurant-name"><?php echo htmlspecialchars($rest['nome']); ?></h3>
                        <div class="restaurant-address"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars(substr($rest['indirizzo'], 0, 25)) . (strlen($rest['indirizzo']) > 25 ? '...' : ''); ?></div>
                        <div class="restaurant-desc"><?php echo htmlspecialchars($rest['descrizione_breve']); ?></div>
                        <span class="btn-go-small">Vedi Menu <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (empty($restaurants)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-shop empty-icon"></i>
                    <h3 class="empty-title">Nessun ristorante trovato</h3>
                    <p class="empty-text">Prova a cercare un'altra categoria o ristorante.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard_consumatore.php" class="nav-item-bottom active"><i class="fa-solid fa-house"></i> <span>Home</span></a>
        <a href="storico.php" class="nav-item-bottom"><i class="fa-solid fa-clock-rotate-left"></i> <span>Ordini</span></a>
        <a href="profile_consumatore.php" class="nav-item-bottom"><i class="fa-solid fa-user"></i> <span>Profilo</span></a>
        <a href="help.php" class="nav-item-bottom"><i class="fa-solid fa-circle-question"></i> <span>Aiuto</span></a>
    </div>

    <script>
    let allRestaurants = <?php echo json_encode($restaurants); ?>;
    let currentCategory = 'all';

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('searchInput');
        const searchToggle = document.getElementById('searchToggle');
        const searchBar = document.getElementById('searchBar');
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuOverlay = document.getElementById('menuOverlay');
        const closeMenu = document.getElementById('closeMenu');
        const mobileSearchInput = document.getElementById('mobileSearchInput');
        const searchButton = document.getElementById('searchButton'); 

        const gridDesktop = document.getElementById('restaurantsGridDesktop');
        const gridMobile = document.getElementById('restaurantsGridMobile');
        const sectionTitle = document.getElementById('sectionTitle');
        const resultsCount = document.getElementById('resultsCount');
        const countNumber = document.getElementById('countNumber');
        const categoryPills = document.querySelectorAll('.category-pill');

        if (searchToggle) {
            searchToggle.addEventListener('click', function (e) {
                e.preventDefault(); 
                searchBar.classList.toggle('active');
                if (searchBar.classList.contains('active') && mobileSearchInput) {
                    setTimeout(() => mobileSearchInput.focus(), 400);
                }
            });
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', function (e) {
                e.preventDefault();
                mobileMenu.classList.add('active');
                menuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (closeMenu) closeMenu.addEventListener('click', closeMobileMenu);
        if (menuOverlay) menuOverlay.addEventListener('click', closeMobileMenu);

        function renderRestaurants(restaurants) {
            if (restaurants.length === 0) {
                const emptyHtml = `
                    <div class="empty-state">
                        <i class="fa-solid fa-shop empty-icon"></i>
                        <h3 class="empty-title">Nessun ristorante trovato</h3>
                        <p class="empty-text">Prova a cercare un'altra categoria o ristorante.</p>
                    </div>`;
                if (gridDesktop) gridDesktop.innerHTML = emptyHtml;
                if (gridMobile) gridMobile.innerHTML = emptyHtml;
                if (resultsCount) resultsCount.style.display = 'none';
                return;
            }

            let desktopHtml = '', mobileHtml = '';
            restaurants.forEach(rest => {
                desktopHtml += `
                    <a href="menu.php?id=${rest.id}" class="restaurant-card">
                        <img src="${rest.image_url}" alt="${escapeHtml(rest.nome)}" class="card-img-top" loading="lazy" onerror="this.src='https://via.placeholder.com/600x400/FF9F43/ffffff?text=Food'">
                        <div class="card-body">
                            <div class="badge-cat">${rest.category}</div>
                            <h3 class="card-title">${escapeHtml(rest.nome)}</h3>
                            <div class="card-info"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(rest.indirizzo.substring(0,40))}${rest.indirizzo.length>40?'...':''}</div>
                            <div style="font-size:13px; color:#707EAE; margin-bottom:15px; line-height:1.4;">${escapeHtml(rest.descrizione_breve)}</div>
                            <div class="btn-go">Vedi Menu <i class="fa-solid fa-arrow-right"></i></div>
                        </div>
                    </a>`;
                mobileHtml += `
                    <a href="menu.php?id=${rest.id}" class="restaurant-card">
                        <img src="${rest.image_url}" alt="${escapeHtml(rest.nome)}" class="restaurant-img" loading="lazy" onerror="this.src='https://via.placeholder.com/80x80/FF9F43/ffffff?text=Food'">
                        <div class="restaurant-info">
                            <div class="restaurant-category">${rest.category}</div>
                            <h3 class="restaurant-name">${escapeHtml(rest.nome)}</h3>
                            <div class="restaurant-address"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(rest.indirizzo.substring(0,25))}${rest.indirizzo.length>25?'...':''}</div>
                            <div class="restaurant-desc">${escapeHtml(rest.descrizione_breve)}</div>
                            <span class="btn-go-small">Vedi Menu <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>`;
            });
            if (gridDesktop) gridDesktop.innerHTML = desktopHtml;
            if (gridMobile) gridMobile.innerHTML = mobileHtml;
            if (countNumber) countNumber.textContent = restaurants.length;
            if (resultsCount) resultsCount.style.display = 'block';
        }

        function filterRestaurants(searchTerm = '', category = 'all') {
            let filtered = allRestaurants;
            if (category !== 'all') filtered = filtered.filter(r => r.category.toLowerCase().includes(category.toLowerCase()));
            if (searchTerm.trim().length >= 2) {
                const term = searchTerm.toLowerCase();
                filtered = filtered.filter(r => r.nome.toLowerCase().includes(term) || r.indirizzo.toLowerCase().includes(term) || (r.descrizione && r.descrizione.toLowerCase().includes(term)));
                sectionTitle.textContent = `Risultati per "${searchTerm}"`;
            } else if (category !== 'all') {
                const names = { 'pizzeria': 'Pizzerie', 'hamburgeria': 'Burger', 'giapponese': 'Sushi', 'italiano': 'Italiani', 'dolci': 'Dolci' };
                sectionTitle.textContent = names[category] || category;
            } else {
                sectionTitle.textContent = 'Ristoranti Popolari';
                if (searchTerm === '' && resultsCount) resultsCount.style.display = 'none';
            }
            renderRestaurants(filtered);
        }

        if (input) {
            let timeout;
            input.addEventListener('input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => filterRestaurants(this.value, currentCategory), 300);
            });
        }

        if (mobileSearchInput) {
            let timeout;
            mobileSearchInput.addEventListener('input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => filterRestaurants(this.value, currentCategory), 300);
            });
            if (searchButton) searchButton.addEventListener('click', () => filterRestaurants(mobileSearchInput.value, currentCategory));
        }

        categoryPills.forEach(pill => {
            pill.addEventListener('click', function () {
                categoryPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                currentCategory = this.dataset.category;
                const term = mobileSearchInput ? mobileSearchInput.value : (input ? input.value : '');
                filterRestaurants(term, currentCategory);
            });
        });

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        renderRestaurants(allRestaurants);
    });
    </script>
</body>
</html> 