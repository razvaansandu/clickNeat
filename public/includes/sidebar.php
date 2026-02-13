<div class="sidebar">
    <div class="brand-logo">
        <i class="fa-solid fa-leaf"></i> ClickNeat
    </div>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=dashboard_2" />
    <div class="nav-links">
        <a href="dashboard_ristoratore.php"
            class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_ristoratore.php' ? 'active' : ''; ?>">
            <span class="material-symbols-outlined">dashboard_2</span></i> Dashboard
        </a>
        <a href="profile_ristoratore.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile_ristoratore.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i> Il mio Profilo
        </a>
        <a href="stats.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stats.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i> Statistiche
        </a>
    </div>

    <a href="../auth/logout.php" class="btn-logout-sidebar">
        <i class="fa-solid fa-right-from-bracket"></i> Esci
    </a>
</div>