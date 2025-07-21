<?php
// Activez l'affichage des erreurs PHP pour le débogage (À DÉSACTIVER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarre la session

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

// --- Vérification des privilèges de l'administrateur unique ---
// On vérifie si la variable de session spécifique à l'admin fixe est définie et vraie.
$isAdmin = false;
if (isset($_SESSION['is_admin_fixed']) && $_SESSION['is_admin_fixed'] === true) {
    $isAdmin = true;
}

if (!$isAdmin) {
    // Si l'utilisateur n'est PAS l'administrateur fixe, rediriger.
    header("Location: connexion.php"); // Rediriger vers la page de connexion
    exit("Accès non autorisé. Vous n'avez pas les privilèges d'administrateur.");
}

// Optionnel: Récupérer des statistiques rapides pour le tableau de bord
$totalUsers = 0;
$totalEvents = 0;
$totalTicketsSold = 0;
$totalRevenue = 0;

// Requête pour le nombre total d'utilisateurs
$sqlUsers = "SELECT COUNT(Id_Utilisateur) AS total_users FROM utilisateur";
if ($result = $conn->query($sqlUsers)) {
    $row = $result->fetch_assoc();
    $totalUsers = $row['total_users'];
    $result->free();
}

// Requête pour le nombre total d'événements
$sqlEvents = "SELECT COUNT(Id_Evenement) AS total_events FROM evenement";
if ($result = $conn->query($sqlEvents)) {
    $row = $result->fetch_assoc();
    $totalEvents = $row['total_events'];
    $result->free();
}

// Requête pour le nombre total de tickets vendus (nombre d'enregistrements dans la table 'achat')
$sqlTicketsSold = "SELECT COUNT(Id_Achat) AS total_tickets_sold FROM achat";
if ($result = $conn->query($sqlTicketsSold)) {
    $row = $result->fetch_assoc();
    $totalTicketsSold = $row['total_tickets_sold'];
    $result->free();
}

// Requête pour le chiffre d'affaires total (somme des prix des tickets achetés)
$sqlRevenue = "SELECT SUM(te.Prix) AS total_revenue
               FROM achat a
               JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement";
if ($result = $conn->query($sqlRevenue)) {
    $row = $result->fetch_assoc();
    $totalRevenue = $row['total_revenue'] ?? 0; // Assurez-vous que c'est 0 si SUM est null
    $result->free();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Administrateur - Avancé</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4CAF50 0%, #2E8B57 100%); /* Vert forêt profond */
            --secondary-gradient: linear-gradient(135deg, #8A2BE2 0%, #6A5ACD 100%); /* Violet royal */
            --header-bg: #1A202C; /* Gris très foncé / Noir doux */
            --sidebar-bg: #2D3748; /* Gris ardoise foncé */
            --main-bg: #F7FAFC; /* Blanc cassé / Gris très clair */
            --card-bg: #FFFFFF;
            --text-dark: #2D3748;
            --text-light: #F7FAFC;
            --accent-green: #48BB78; /* Vert plus clair pour certains accents */
            --accent-orange: #F6AD55; /* Orange doux */
            --accent-blue: #63B3ED; /* Bleu ciel doux */
            --accent-purple: #B794F4; /* Violet pastel */
            --shadow-light: 0 5px 20px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 10px 30px rgba(0, 0, 0, 0.15);
            --border-radius-lg: 15px;
            --border-radius-md: 10px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* --- Global Reset & Utilities --- */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            color: var(--text-dark);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* --- Header --- */
        .header {
            background: var(--header-bg); /* Couleur de fond foncée */
            color: var(--text-light);
            padding: 20px 40px;
            font-size: 1.8em;
            box-shadow: var(--shadow-medium);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header span {
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
        }

        .header .logout-btn {
            background: linear-gradient(45deg, #EF4444 0%, #DC2626 100%); /* Dégradé de rouge vif */
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 0.95em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .header .logout-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 15px rgba(239, 68, 68, 0.6);
        }

        /* --- Conteneur principal --- */
        .container {
            display: flex;
            min-height: calc(100vh - 80px); /* Ajusté pour le header */
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 280px; /* Un peu plus large */
            background-color: var(--sidebar-bg);
            padding: 30px 0;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 80px; /* Commence après le header */
            height: calc(100vh - 80px);
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.05); /* Bordure subtile */
        }

        .sidebar h2 {
            text-align: center;
            color: var(--accent-green); /* Couleur d'accent pour le titre */
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 25px;
            font-size: 1.8em;
            letter-spacing: 1.5px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800; /* Extra bold */
            text-transform: uppercase;
        }

        .sidebar ul {
            list-style: none;
            padding: 0 25px;
            flex-grow: 1;
        }

        .sidebar ul li {
            margin-bottom: 12px;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-light);
            text-decoration: none;
            padding: 16px 20px;
            border-radius: var(--border-radius-md);
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 1.1em;
            position: relative;
            overflow: hidden; /* Pour l'effet d'onde au survol */
        }

        .sidebar ul li a i {
            font-size: 1.3em;
            color: var(--accent-green); /* Icônes avec couleur d'accent */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(72, 187, 120, 0.2); /* Vert transparent */
            color: #fff;
            transform: translateX(8px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .sidebar ul li a.active {
            font-weight: 700;
            background: var(--primary-gradient); /* Dégradé pour l'actif */
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
            color: white; /* Texte blanc sur fond dégradé */
        }
        .sidebar ul li a.active i {
            color: white; /* Icône blanche aussi */
        }

        /* Effet "wave" sur les liens de la sidebar au survol */
        .sidebar ul li a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease-in-out;
            transform: skewX(-20deg);
        }

        .sidebar ul li a:hover::before {
            left: 100%;
        }


        /* --- Main Content --- */
        .main-content {
            flex-grow: 1;
            padding: 40px; /* Plus d'espace */
            background-color: var(--main-bg);
            margin: 25px; /* Marge plus grande */
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            position: relative; /* Pour les motifs de fond */
            z-index: 1;
        }

        /* Motif de fond subtil pour le contenu principal */
        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://www.transparenttextures.com/patterns/clean-gray-paper.png'); /* Exemple de texture */
            opacity: 0.05; /* Très léger */
            z-index: -1;
        }


        .section-title {
            font-size: 2.5em; /* Plus grand */
            color: var(--text-dark);
            margin-bottom: 35px;
            border-bottom: 3px solid var(--accent-green); /* Bordure plus épaisse et colorée */
            padding-bottom: 18px;
            font-weight: 800; /* Extra bold */
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Montserrat', sans-serif;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        /* --- Dashboard Cards --- */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Cartes un peu plus grandes */
            gap: 30px; /* Espacement accru */
            margin-bottom: 50px;
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-light);
            text-align: center;
            border-bottom: 8px solid; /* Bordure plus épaisse en bas */
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* Animation plus fluide */
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            opacity: 0.1; /* Opacité faible pour l'image de fond */
            filter: grayscale(100%); /* Désaturer l'image */
            z-index: 0;
            transition: all 0.5s ease;
            transform: scale(1.05);
        }

        .card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: var(--shadow-medium);
        }

        .card:hover::before {
            opacity: 0.2;
            transform: scale(1);
        }


        .card h3 {
            color: #555;
            margin-top: 0;
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            z-index: 2; /* S'assurer que le texte est au-dessus */
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .card p {
            font-size: 3.5em; /* Taille de police massive */
            font-weight: 800; /* Extra bold */
            color: var(--text-dark);
            margin: 0;
            position: relative;
            z-index: 2;
            line-height: 1;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.1);
        }

        .card .icon-bg {
            position: absolute;
            top: 15px; /* Positionnement en haut à droite */
            right: 20px;
            font-size: 3.5em;
            color: rgba(0, 0, 0, 0.08); /* Plus visible mais toujours subtil */
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .card:hover .icon-bg {
            transform: rotate(10deg) scale(1.1);
        }

        /* Couleurs des bordures et fonds d'icônes spécifiques */
        .card.users {
            border-color: var(--accent-green);
            background-image: url('https://via.placeholder.com/600/4CAF50/FFFFFF?text='); /* Image de fond subtile */
        }
        .card.users::before {
            background-image: url('https://images.unsplash.com/photo-1517486804561-12502ec3b499?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHw1fHx1c2VyJTIwcHJvZmlsZSUyMGJhY2tncm91bmR8ZW58MHx8fHwxNzIwOTgwNTQwfDA&ixlib=rb-4.0.3&q=80&w=1080'); /* Remplacez par une image réelle d'utilisateur */
        }

        .card.events {
            border-color: var(--accent-orange);
            background-image: url('https://via.placeholder.com/600/F6AD55/FFFFFF?text=');
        }
        .card.events::before {
            background-image: url('https://images.unsplash.com/photo-1514525253164-ffc749007f7a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHw3fHxldmVudCUyMGJhY2tncm91bmR8ZW58MHx8fHwxNzIwOTgwNTgwfDA&ixlib=rb-4.0.3&q=80&w=1080'); /* Remplacez par une image réelle d'événement */
        }

        .card.tickets {
            border-color: var(--accent-purple);
            background-image: url('https://via.placeholder.com/600/B794F4/FFFFFF?text=');
        }
        .card.tickets::before {
            background-image: url('https://images.unsplash.com/photo-1582236371191-23d3856e8976?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHwzfHx0aWNrZXRzJTIwYmFja2dyb3VuZHxlbnwwfHx8fDE3MjA5ODA1OTZ8MA&ixlib=rb-4.0.3&q=80&w=1080'); /* Remplacez par une image réelle de tickets */
        }

        .card.revenue {
            border-color: var(--accent-blue);
            background-image: url('https://via.placeholder.com/600/63B3ED/FFFFFF?text=');
        }
        .card.revenue::before {
            background-image: url('https://images.unsplash.com/photo-1549925232-a5e1e5e0d4d2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHwzfHxyZXZlbnVlJTIwYmFja2dyb3VuZHxlbnwwfHx8fDE3MjA5ODA1ODN8MA&ixlib=rb-4.0.3&q=80&w=1080'); /* Remplacez par une image réelle de revenus */
        }

        /* --- Quick Actions --- */
        .quick-actions {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Grille pour les actions */
            gap: 25px;
        }

        .quick-actions a {
            background: var(--secondary-gradient); /* Dégradé violet */
            color: white;
            padding: 16px 30px;
            border-radius: 30px;
            font-size: 1.05em;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.4);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center; /* Centrer le contenu */
            border: 2px solid transparent; /* Bordure transparente pour l'effet de survol */
        }

        .quick-actions a:hover {
            background: white; /* Fond blanc au survol */
            color: #8A2BE2; /* Texte violet au survol */
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 20px rgba(138, 43, 226, 0.6);
            border-color: #8A2BE2; /* Bordure violette au survol */
        }

        .quick-actions a i {
            font-size: 1.2em;
            color: white; /* Icône blanche par défaut */
            transition: color 0.3s ease;
        }

        .quick-actions a:hover i {
            color: #8A2BE2; /* Icône violette au survol */
        }


        /* --- Responsive Design --- */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding-bottom: 0;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            }
            .sidebar h2 {
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 0 10px;
            }
            .sidebar ul li {
                margin: 5px 10px;
            }
            .sidebar ul li a {
                padding: 10px 15px;
                gap: 8px;
            }
            .sidebar ul li a.active::before {
                display: none;
            }
            .main-content {
                margin: 20px 15px;
                padding: 25px;
            }
            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 20px;
            }
            .card .icon-bg {
                font-size: 3em;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 15px;
                font-size: 1.5em;
            }
            .header span {
                margin-bottom: 10px;
            }
            .section-title {
                font-size: 2em;
                text-align: center;
                margin-bottom: 25px;
            }
            .card h3 {
                font-size: 1.1em;
            }
            .card p {
                font-size: 2.8em;
            }
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .quick-actions a {
                padding: 12px 20px;
                font-size: 0.95em;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin: 10px;
                padding: 15px;
            }
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            .sidebar ul {
                flex-direction: column;
                align-items: center;
            }
            .sidebar ul li {
                width: 90%;
                text-align: center;
                margin-bottom: 8px;
            }
            .sidebar ul li a {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <header class="header animate__animated animate__fadeInDown">
        <span>Tableau de Bord Administrateur</span>
        <a href="deconnexion.php" class="logout-btn animate__animated animate__pulse animate__infinite">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </header>

    <div class="container">
        <aside class="sidebar animate__animated animate__fadeInLeft">
            <h2>Navigation Admin</h2>
            <ul>
                <li><a href="menu_admin.php" class="active"><i class="fas fa-chart-line"></i> Tableau de Bord</a></li>
                <li><a href="gerer_utilisateurs.php"><i class="fas fa-users"></i> Gérer les Utilisateurs</a></li>
                <li><a href="gerer_evenements.php"><i class="fas fa-calendar-alt"></i> Gérer les Événements</a></li>
                <li><a href="gerer_tickets.php"><i class="fas fa-ticket-alt"></i> Gérer les Tickets</a></li>
                <li><a href="gerer_categories.php"><i class="fas fa-tags"></i> Gérer les Catégories</a></li>
                <li><a href="rapports_stats.php"><i class="fas fa-chart-bar"></i> Rapports et Stats</a></li>
                <li><a href="parametres_site.php"><i class="fas fa-cogs"></i> Paramètres du Site</a></li>
            </ul>
        </aside>

        <main class="main-content animate__animated animate__fadeInRight">
            <h2 class="section-title animate__animated animate__fadeIn">Aperçu du Système</h2>

            <div class="dashboard-cards">
                <div class="card users animate__animated animate__zoomIn">
                    <i class="icon-bg fas fa-users"></i>
                    <h3>Total Utilisateurs</h3>
                    <p><?= htmlspecialchars($totalUsers) ?></p>
                </div>
                <div class="card events animate__animated animate__zoomIn animate__delay-0-1s">
                    <i class="icon-bg fas fa-calendar-alt"></i>
                    <h3>Total Événements</h3>
                    <p><?= htmlspecialchars($totalEvents) ?></p>
                </div>
                <div class="card tickets animate__animated animate__zoomIn animate__delay-0-2s">
                    <i class="icon-bg fas fa-ticket-alt"></i>
                    <h3>Tickets Vendus</h3>
                    <p><?= htmlspecialchars($ticketsSold) ?></p>
                </div>
                <div class="card revenue animate__animated animate__zoomIn animate__delay-0-3s">
                    <i class="icon-bg fas fa-dollar-sign"></i>
                    <h3>Chiffre d'Affaires Total</h3>
                    <p><?= htmlspecialchars($totalRevenue) ?> CFA</p>
                </div>
            </div>

            <h2 class="section-title animate__animated animate__fadeIn">Actions Rapides</h2>

            <div class="quick-actions">
                <a href="ajouter_utilisateur.php" class="animate__animated animate__fadeInUp"><i class="fas fa-user-plus"></i> Ajouter un nouvel utilisateur</a>
                <a href="creer_evenement.php" class="animate__animated animate__fadeInUp animate__delay-0-1s"><i class="fas fa-plus-circle"></i> Créer un événement (Admin)</a>
                <a href="modifier_mot_de_passe.php" class="animate__animated animate__fadeInUp animate__delay-0-2s"><i class="fas fa-key"></i> Modifier mon mot de passe</a>
                <a href="parametres_email.php" class="animate__animated animate__fadeInUp animate__delay-0-3s"><i class="fas fa-envelope"></i> Paramètres Email</a>
            </div>

        </main>
    </div>
</body>

</html>