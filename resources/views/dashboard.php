<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Tableau de bord', ENT_QUOTES, 'UTF-8') ?> | MarchésPro ERP</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <!-- Chargement de Tailwind CSS pour une mise en page fluide et adaptative -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chargement de Chart.js pour la visualisation en anneau -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <!-- Police Inter pour une lisibilité claire sur web et mobile -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <style>
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #f8fafc;
      color: #0f172a;
    }
    .smooth-card {
      transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }
    .smooth-card:hover {
      transform: translateY(-1px);
    }
    .sidebar-icon-wrap {
      transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }
    .nav-btn:hover .sidebar-icon-wrap {
      transform: scale(1.05);
    }
    @media print {
      .no-print { display: none !important; }
      body { background-color: #ffffff !important; }
      .print-break-inside-avoid { break-inside: avoid; }
    }
  </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

  <div id="app" class="min-h-screen flex flex-col lg:flex-row">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-950/50 z-30 hidden lg:hidden no-print" onclick="toggleMobileSidebar(false)"></div>

    <!-- BARRE LATÉRALE GAUCHE -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-60 shrink-0 bg-[#0f294a] text-slate-100 flex flex-col justify-between transition-all duration-200 -translate-x-full lg:translate-x-0 no-print">
      <div class="overflow-y-auto">
        <div class="px-4 py-3.5 border-b border-white/10 flex items-center justify-between">
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-[#ea9a10] text-slate-950 font-extrabold flex items-center justify-center shadow-sm shrink-0 text-sm">
              MP
            </div>
            <div class="min-w-0">
              <div class="font-bold text-sm tracking-tight text-white truncate">MarchésPro <span class="text-[#ea9a10]">ERP</span></div>
              <div class="text-[10px] uppercase tracking-wider text-blue-200/80 font-medium truncate">Holding · 5 Filiales</div>
            </div>
          </div>
          <button type="button" onclick="toggleMobileSidebar(false)" class="lg:hidden p-2 rounded-lg text-blue-200 hover:bg-white/10" aria-label="Fermer le menu">✕</button>
        </div>

        <div class="px-3 pt-4 pb-2">
          <div class="px-2 mb-2 flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-200/70">Menus Métiers</span>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white/10 text-blue-100">SQUELETTE</span>
          </div>
          <nav id="mainNav" class="space-y-1" aria-label="Navigation principale"></nav>
        </div>

        <div class="px-3 pt-3 pb-4 border-t border-white/10 mt-2">
          <div class="px-2 mb-2 flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-200/70">Périmètre Holding (5)</span>
            <button type="button" onclick="selectCompany('ALL')" class="text-[11px] text-[#ea9a10] hover:underline font-medium">Réinitialiser</button>
          </div>
          <div id="companyList" class="space-y-1"></div>
        </div>
      </div>

      <div class="p-3 border-t border-white/10 bg-slate-950/25">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3 text-xs">
          <div class="flex items-center gap-2 font-semibold text-emerald-300">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Session active · Squelette V1</span>
          </div>
          <p class="text-[11px] text-blue-200/75 mt-1 leading-relaxed">
            Données fictives de démonstration pour valider l'architecture et l'ergonomie.
          </p>
        </div>
      </div>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">

      <header class="bg-white border-b border-slate-200/90 sticky top-0 z-20 no-print">
        <div class="px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <button type="button" onclick="toggleMobileSidebar(true)" class="lg:hidden p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50" aria-label="Ouvrir le menu">☰</button>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h1 id="pageTitle" class="text-base sm:text-lg font-bold text-slate-900 truncate">MarchésPro ERP — Tableau de bord</h1>
                <span id="activeVariantBadge" class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">Focus Opérationnel · Socle Validé</span>
              </div>
              <p class="text-xs text-slate-500 truncate">
                Accueil / Tableau de bord / <span id="breadcrumbScope" class="font-medium text-slate-700">Vue Groupe (toutes les sociétés)</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            <div class="hidden md:flex items-center gap-2 text-xs text-slate-600 bg-slate-100 px-3 py-2 rounded-xl border border-slate-200/70">
              <span id="liveDate">27/11/2026</span><span class="text-slate-300">|</span><span id="liveClock" class="font-semibold text-slate-800">19:55:00</span>
            </div>
            <div class="flex items-center gap-1.5">
              <label for="topbarCompanySelect" class="sr-only">Filtrer par entreprise</label>
              <select id="topbarCompanySelect" onchange="selectCompany(this.value)" class="text-xs sm:text-sm font-medium bg-white border border-slate-300 hover:border-slate-400 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#ea9a10]/50"></select>
            </div>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition" title="Aperçu impression / PDF"><span>Imprimer / PDF</span></button>
            <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
              <div class="w-8 h-8 rounded-full bg-[#0f294a] text-white text-xs font-bold flex items-center justify-center"><?= htmlspecialchars(strtoupper(substr((string) ($currentUser['username'] ?? 'U'), 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
              <div class="hidden sm:block text-left leading-tight">
                <div class="text-xs font-semibold text-slate-800"><?= htmlspecialchars((string) ($currentUser['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-[10px] text-slate-500"><?= htmlspecialchars((string) ($currentUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <form method="post" action="?page=logout" class="ml-1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-red-700 border border-slate-200 rounded-lg px-2.5 py-2">Déconnexion</button>
              </form>
            </div>
          </div>
        </div>

        <div class="px-4 sm:px-6 py-2 bg-slate-50/90 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2 text-xs text-slate-600">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Disposition retenue : <strong class="text-slate-900">Variante B — Focus Opérationnel</strong> (barre compacte & accès rapide)</span>
          </div>
          <div class="text-[11px] text-slate-500">Filtrez par <strong class="text-slate-700">filiale</strong>, <strong class="text-slate-700">statut</strong> ou <strong class="text-slate-700">phase</strong> pour tester la navigation.</div>
        </div>
      </header>

      <main id="mainContent" class="flex-1 p-4 sm:p-5 space-y-4 max-w-[1600px] w-full mx-auto">

        <?php if (!empty($currentUser['must_change_password'])): ?>
          <div class="rounded-xl bg-red-600 text-white px-4 py-3 shadow-sm flex items-center gap-3 whitespace-nowrap overflow-x-auto no-print" role="alert">
            <span class="text-sm font-semibold">Votre mot de passe initial est toujours actif. Changez-le dès que possible.</span>
            <a href="?page=mon_mot_de_passe" class="inline-flex items-center justify-center shrink-0 rounded-lg bg-white text-red-700 hover:bg-red-50 px-3 py-2 text-xs font-bold shadow-sm">Changer mon mot de passe</a>
          </div>
        <?php endif; ?>

        <div id="alertBanner" class="rounded-2xl bg-amber-50 border border-amber-200/90 px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print">
          <div class="flex items-start sm:items-center gap-3">
            <span class="w-7 h-7 rounded-xl bg-[#ea9a10]/15 text-[#b45309] font-bold flex items-center justify-center shrink-0 text-xs">!</span>
            <div class="text-xs sm:text-sm text-amber-950"><span class="font-semibold">Étape 1 validée (Squelette global) :</span> Structure prête pour accueillir le module <strong>Marchés</strong> (création, édition et cycle de vie) à l'étape suivante.</div>
          </div>
          <span id="scopePill" class="self-start sm:self-auto text-xs font-semibold px-3 py-1 rounded-full bg-white text-slate-800 border border-amber-200 shrink-0">Périmètre : Vue Groupe</span>
        </div>

        <section id="welcomeHeader" class="rounded-2xl bg-white border border-slate-200/90 p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 print-break-inside-avoid">
          <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-[#0f294a]/70 mb-1">Aperçu de structure</div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Tableau de bord — <span id="welcomeScopeTitle" class="text-[#0f294a]">Vue Groupe (5 sociétés)</span></h2>
            <p id="welcomeSubtitle" class="text-sm text-slate-600 mt-1">Suivi consolidé des marchés, phases du cycle de vie, répartition financière et derniers dossiers enregistrés.</p>
          </div>
          <div id="quickActionsBar" class="flex flex-wrap items-center gap-2 shrink-0 no-print">
            <button type="button" onclick="filterByStatus('ALL')" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#0f294a] text-white hover:bg-[#163a68] transition shadow-sm">Tous les marchés (<span id="totalCountAction">0</span>)</button>
            <button type="button" onclick="filterByStatus('en_cours')" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-amber-500/15 text-amber-900 border border-amber-300 hover:bg-amber-500/25 transition">En cours</button>
            <button type="button" onclick="filterByStatus('gagne')" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-500/15 text-emerald-900 border border-emerald-300 hover:bg-emerald-500/25 transition">Gagnés</button>
          </div>
        </section>

        <section aria-labelledby="suiviTitle" class="print-break-inside-avoid">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2"><span class="w-1.5 h-5 rounded-full bg-[#ea9a10]"></span><h3 id="suiviTitle" class="text-base sm:text-lg font-bold text-slate-900">Suivi des marchés</h3></div>
            <span class="text-xs text-slate-500">Cliquez sur une carte pour filtrer le tableau</span>
          </div>
          <div id="kpiGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3.5"></div>
        </section>

        <section aria-labelledby="lifecycleTitle" class="print-break-inside-avoid">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2"><span class="w-1.5 h-5 rounded-full bg-[#0f294a]"></span><h3 id="lifecycleTitle" class="text-base sm:text-lg font-bold text-slate-900">Cycle de vie des marchés (6 phases)</h3></div>
            <span id="activePhaseFilterLabel" class="text-xs font-medium text-slate-500">Toutes les phases</span>
          </div>
          <div id="lifecycleGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5"></div>
        </section>

        <section id="analysisAndTableGrid" class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">
          <div id="chartCard" class="xl:col-span-4 rounded-2xl bg-white border border-slate-200/90 p-5 shadow-sm print-break-inside-avoid">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2"><span class="w-1.5 h-5 rounded-full bg-[#ea9a10]"></span><h3 class="text-base font-bold text-slate-900">Répartition des marchés</h3></div>
              <span id="chartTotalBadge" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">Total : 0</span>
            </div>
            <div class="relative h-56 flex items-center justify-center">
              <canvas id="marchesDoughnutChart"></canvas>
              <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center"><span class="text-[11px] uppercase tracking-wider font-semibold text-slate-400">Total</span><span id="doughnutCenterTotal" class="text-2xl font-extrabold text-slate-900">0</span></div>
            </div>
            <div id="chartLegend" class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs"></div>
          </div>

          <div id="tableCard" class="xl:col-span-8 rounded-2xl bg-white border border-slate-200/90 shadow-sm overflow-hidden print-break-inside-avoid">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <span class="w-1.5 h-5 rounded-full bg-[#0f294a]"></span>
                <div><h3 class="text-base font-bold text-slate-900">Derniers marchés</h3><p id="tableFilterSummary" class="text-xs text-slate-500">Affichage de tous les dossiers du périmètre actif</p></div>
              </div>
              <div class="flex items-center gap-2 flex-wrap no-print">
                <input id="searchInput" type="search" oninput="renderTable()" placeholder="Rechercher (N°, objet, société)..." class="text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#ea9a10]/50 w-full sm:w-56" />
                <button type="button" onclick="resetAllFilters()" class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Réinitialiser</button>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                    <th class="py-3 px-4">N° Marché</th><th class="py-3 px-4">Objet</th><th class="py-3 px-4">Entreprise</th><th class="py-3 px-4">Phase</th><th class="py-3 px-4">Statut</th><th class="py-3 px-4 text-right">Montant (MAD)</th><th class="py-3 px-4">Date</th>
                  </tr>
                </thead>
                <tbody id="marchesTableBody" class="divide-y divide-slate-100 text-sm"></tbody>
              </table>
            </div>
            <div class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
              <span id="tableCountText" class="text-slate-600 font-medium">0 marché(s) affiché(s)</span>
              <div class="font-bold text-slate-900">Montant cumulé : <span id="tableTotalAmount" class="text-[#0f294a]">0,00 MAD</span></div>
            </div>
          </div>
        </section>

        <section aria-labelledby="modulesTitle" class="rounded-2xl bg-white border border-dashed border-slate-300 p-5 print-break-inside-avoid no-print">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div><h3 id="modulesTitle" class="text-sm font-bold text-slate-800">Emplacements réservés — Prochaines étapes du squelette</h3><p class="text-xs text-slate-500">Modules prévus dans l'arborescence pour enrichir l'application pas à pas.</p></div>
            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">Architecture modulaire</span>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 text-xs">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70"><div class="font-semibold text-slate-800">Marchés & Fiches</div><div class="text-[11px] text-slate-500 mt-0.5">Étape 2 · CRUD & Phases</div></div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70"><div class="font-semibold text-slate-800">Cautions & RG</div><div class="text-[11px] text-slate-500 mt-0.5">Étape 3 · Garanties</div></div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70"><div class="font-semibold text-slate-800">Charges</div><div class="text-[11px] text-slate-500 mt-0.5">Étape 4 · Dépenses</div></div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70"><div class="font-semibold text-slate-800">Véhicules</div><div class="text-[11px] text-slate-500 mt-0.5">Étape 5 · Parc & Affect.</div></div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70"><div class="font-semibold text-slate-800">Échéances & Notes</div><div class="text-[11px] text-slate-500 mt-0.5">Étape 6 · Rappels</div></div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70"><div class="font-semibold text-slate-800">Archives & Paramètres</div><div class="text-[11px] text-slate-500 mt-0.5">Étape 7 · Droits & Audit</div></div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <script>
    window.ERP_DASHBOARD_DATA = <?= json_encode(
      $dashboardData ?? ['companies' => [], 'markets' => []],
      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;
    window.ERP_DATABASE_ERROR = <?= !empty($databaseError) ? 'true' : 'false' ?>;
  </script>
  <script src="assets/js/dashboard.js"></script>
</body>
</html>
