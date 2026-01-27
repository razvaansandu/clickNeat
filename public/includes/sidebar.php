<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dashboard_2" />

<style>
    .sidebar {
        width: 260px;
        background: linear-gradient(180deg, #1A4D4E 0%, #153e3f 100%);
        color: white;
        display: flex;
        flex-direction: column;
        padding: 25px;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        height: 100vh;
        position: fixed;
    }

    .brand-logo {
        margin-bottom: 50px;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nav-links {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .sidebar a {
        color: #a8c5c6;
        padding: 12px 15px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }

    .btn-logout-sidebar {
        margin-top: auto;
        background-color: rgba(217, 48, 37, 0.1);
        color: #ff8a80 !important;
        border: 1px solid rgba(217, 48, 37, 0.2);
    }

    .btn-logout-sidebar:hover {
        background-color: #d93025;
        color: white !important;
    }
</style>

<div class="sidebar">
    <div class="brand-logo">
        <i class="fa-solid fa-leaf"></i> ClickNeat
    </div>

    <div class="nav-links">
        <a href="dashboard_ristoratore.php"
            class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_ristoratore.php' ? 'active' : ''; ?>">
            <i class="material-symbols-outlined">dashboard_2</i> Dashboard
        </a>
        <a href="profile.php">
            <i class="fa-solid fa-user"></i> Il mio Profilo
        </a>
        <a href="stats.php">
            <i class="fa-solid fa-chart-pie"></i> Statistiche
        </a>
    </div>

    <a href="logout.php" class="btn-logout-sidebar">
        <i class="fa-solid fa-right-from-bracket"></i> Esci
    </a>
</div>