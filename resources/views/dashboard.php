<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ERP Manager - Gestion des marchés">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | ERP Manager</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php" aria-label="ERP Manager - Accueil">
            <span class="brand-mark">EM</span>
            <span class="brand-name">ERP Manager<small>Gestion des marchés</small></span>
        </a>

        <div class="nav-caption">MENU PRINCIPAL</div>
        <nav class="navigation" aria-label="Navigation principale">
            <a class="nav-item active" href="index.php" aria-current="page">
                <span class="nav-icon">▦</span><span>Tableau de bord</span>
            </a>

            <div class="nav-caption nav-caption-spaced">GESTION</div>
            <button class="nav-item disabled" type="button" disabled aria-disabled="true" title="Fonctionnalité prévue pour une prochaine étape">
                <span class="nav-icon">▤</span><span>Marchés</span><span class="lock">Bientôt</span>
            </button>
            <button class="nav-item disabled" type="button" disabled aria-disabled="true" title="Fonctionnalité prévue pour une prochaine étape">
                <span class="nav-icon">▣</span><span>Entreprises</span><span class="lock">Bientôt</span>
            </button>
            <button class="nav-item disabled" type="button" disabled aria-disabled="true" title="Fonctionnalité prévue pour une prochaine étape">
                <span class="nav-icon">♙</span><span>Utilisateurs</span><span class="lock">Bientôt</span>
            </button>

            <div class="nav-caption nav-caption-spaced">SUIVI</div>
            <button class="nav-item disabled" type="button" disabled aria-disabled="true" title="Fonctionnalité prévue pour une prochaine étape">
                <span class="nav-icon">▧</span><span>Documents</span><span class="lock">Bientôt</span>
            </button>
            <button class="nav-item disabled" type="button" disabled aria-disabled="true" title="Fonctionnalité prévue pour une prochaine étape">
                <span class="nav-icon">◷</span><span>Rapports</span><span class="lock">Bientôt</span>
            </button>

            <div class="nav-caption nav-caption-spaced">SYSTÈME</div>
            <button class="nav-item disabled" type="button" disabled aria-disabled="true" title="Fonctionnalité prévue pour une prochaine étape">
                <span class="nav-icon">⚙</span><span>Paramètres</span><span class="lock">Bientôt</span>
            </button>
        </nav>

        <div class="sidebar-footer">Version 0.1.0 · Squelette initial</div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div>
                <div class="eyebrow">ESPACE DE TRAVAIL</div>
                <div class="topbar-title">Tableau de bord</div>
            </div>
            <div class="topbar-right">
                <div class="date-block">
                    <strong><?= htmlspecialchars($currentDate, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($currentTime, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="profile">
                    <span class="avatar">?</span>
                    <span class="profile-text"><strong>Non connecté</strong><small>Session inactive</small></span>
                </div>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <div>
                    <div class="eyebrow">BIENVENUE</div>
                    <h1>Bienvenue dans ERP Manager</h1>
                    <p>Le socle de votre application de gestion des marchés est en préparation.</p>
                </div>
                <div class="welcome-symbol">EM</div>
            </div>

            <div class="section-heading">
                <div><h2>Vue générale</h2><p>Votre espace de travail est prêt à être développé.</p></div>
                <span class="status"><i></i> Squelette opérationnel</span>
            </div>

            <div class="stats-grid">
                <article class="stat-card">
                    <span class="stat-label">Marchés</span>
                    <strong>—</strong>
                    <small>Module non activé</small>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Entreprises</span>
                    <strong>—</strong>
                    <small>Module non activé</small>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Utilisateurs</span>
                    <strong>—</strong>
                    <small>Module non activé</small>
                </article>
            </div>

            <section class="notice-card">
                <div class="notice-icon">✓</div>
                <div>
                    <h3>Base de l'application installée</h3>
                    <p>La navigation et la présentation générale sont en place. Les autres menus restent volontairement désactivés. Nous ajouterons chaque fonctionnalité après validation de cette première interface.</p>
                </div>
            </section>
            <footer class="page-footer">ERP Manager <span>·</span> Gestion des marchés</footer>
        </section>
    </main>
</div>
</body>
</html>
