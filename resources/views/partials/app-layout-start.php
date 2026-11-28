<?php
declare(strict_types=1);
$layoutPage = (string) ($page ?? 'dashboard');
$layoutTitle = (string) ($pageTitle ?? 'ERP Manager');
$layoutUser = $currentUser ?? [];
$layoutRole = (string) ($layoutUser['role'] ?? '');
$layoutName = (string) ($layoutUser['display_name'] ?? $layoutUser['username'] ?? '');
$layoutInitials = strtoupper(substr((string) ($layoutUser['username'] ?? 'U'), 0, 2));
$layoutH = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $layoutH($layoutTitle) ?> | ERP Manager</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div id="sidebarOverlay" class="fixed inset-0 bg-slate-950/50 z-30 hidden lg:hidden" onclick="toggleAppSidebar(false)"></div>
<div class="min-h-screen flex flex-col lg:flex-row">
  <aside id="appSidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-60 shrink-0 bg-[#0f294a] text-slate-100 flex flex-col justify-between transition-transform duration-200 -translate-x-full lg:translate-x-0">
    <div>
      <div class="px-4 py-4 border-b border-white/10 flex items-center gap-3">
        <img src="assets/images/erp-manager-mark.svg" alt="ERP Manager" class="w-10 h-10 rounded-xl">
        <div><div class="font-bold text-sm text-white">ERP <span class="text-[#ea9a10]">Manager</span></div><div class="text-[10px] uppercase tracking-wider text-blue-200/80">Gestion des marchés</div></div>
        <button type="button" onclick="toggleAppSidebar(false)" class="lg:hidden ml-auto p-2 rounded-lg text-blue-200 hover:bg-white/10" aria-label="Fermer le menu">✕</button>
      </div>
      <nav class="px-3 py-4 space-y-1" aria-label="Navigation principale">
        <a href="?" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold <?= $layoutPage === 'dashboard' ? 'bg-[#ea9a10] text-white' : 'text-blue-100 hover:bg-white/10' ?>"><span>⌂</span> Tableau de bord</a>
        <a href="?page=marches" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold <?= in_array($layoutPage, ['marches','marche_nouveau','marche'], true) ? 'bg-[#ea9a10] text-white' : 'text-blue-100 hover:bg-white/10' ?>"><span>▤</span> Marchés</a>
        <?php if ($layoutRole === 'GEST'): ?>
        <a href="?page=utilisateurs" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold <?= $layoutPage === 'utilisateurs' ? 'bg-[#ea9a10] text-white' : 'text-blue-100 hover:bg-white/10' ?>"><span>♙</span> Utilisateurs et droits</a>
        <?php endif; ?>
        <a href="?page=mon_mot_de_passe" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold <?= $layoutPage === 'mon_mot_de_passe' ? 'bg-[#ea9a10] text-white' : 'text-blue-100 hover:bg-white/10' ?>"><span>⚙</span> Mon mot de passe</a>
      </nav>
    </div>
    <div class="p-3 border-t border-white/10 text-xs text-blue-200/80">ERP Manager · Espace sécurisé</div>
  </aside>
  <div class="flex-1 min-w-0 flex flex-col">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
      <div class="px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <button type="button" onclick="toggleAppSidebar(true)" class="lg:hidden p-2.5 rounded-xl border border-slate-200 text-slate-700" aria-label="Ouvrir le menu">☰</button>
          <div class="min-w-0"><h1 class="text-base sm:text-lg font-bold text-slate-900 truncate"><?= $layoutH($layoutTitle) ?></h1><p class="text-xs text-slate-500">ERP Manager / <?= $layoutH($layoutTitle) ?></p></div>
        </div>
        <div class="flex items-center gap-3">
          <div class="hidden md:flex items-center gap-2 text-xs text-slate-600 bg-slate-100 px-3 py-2 rounded-xl border border-slate-200"><span id="appLiveDate"></span><span class="text-slate-300">|</span><strong id="appLiveClock"></strong></div>
          <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
            <div class="w-8 h-8 rounded-full bg-[#0f294a] text-white text-xs font-bold flex items-center justify-center"><?= $layoutH($layoutInitials) ?></div>
            <div class="hidden sm:block leading-tight"><div class="text-xs font-semibold text-slate-800"><?= $layoutH($layoutName) ?></div><div class="text-[10px] text-slate-500"><?= $layoutH($layoutRole) ?></div></div>
            <form method="post" action="?page=logout"><input type="hidden" name="csrf_token" value="<?= $layoutH($auth->csrfToken()) ?>"><button type="submit" class="text-xs font-semibold text-slate-600 hover:text-red-700 border border-slate-200 rounded-lg px-2.5 py-2">Déconnexion</button></form>
          </div>
        </div>
      </div>
    </header>
    <main class="flex-1 p-4 sm:p-6 space-y-5 max-w-[1600px] w-full mx-auto">
