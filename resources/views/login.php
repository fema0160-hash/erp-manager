<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion | ERP Manager</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
  <main class="w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-xl p-7">
    <div class="flex items-center gap-3 mb-7">
      <img src="assets/images/erp-manager-mark.svg" alt="ERP Manager" class="w-12 h-12 rounded-xl shadow-sm">
      <div><h1 class="text-xl font-bold text-slate-900">ERP Manager</h1><p class="text-sm text-slate-500">Espace de connexion</p></div>
    </div>
    <?php if (!empty($errorMessage)): ?>
      <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($databaseUnavailable)): ?>
      <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">La base de données est indisponible. Vérifiez que MySQL est démarré et que la configuration locale est correcte.</div>
    <?php else: ?>
      <form method="post" action="?page=login" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <div><label for="username" class="block text-sm font-semibold text-slate-700 mb-1.5">Identifiant</label><input id="username" name="username" autocomplete="username" required autofocus class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
        <div><label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Mot de passe</label><input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
        <button type="submit" class="w-full rounded-xl bg-[#0f294a] hover:bg-[#163a68] text-white font-semibold py-3 transition">Se connecter</button>
      </form>
    <?php endif; ?>
    <p class="text-xs text-slate-400 mt-6 text-center">Accès réservé aux utilisateurs autorisés.</p>
  </main>
</body>
</html>
