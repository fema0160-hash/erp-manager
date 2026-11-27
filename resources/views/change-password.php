<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier mon mot de passe | ERP Manager</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
  <main class="w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl p-7">
    <h1 class="text-xl font-bold text-slate-900">Modifier mon mot de passe</h1>
    <p class="text-sm text-slate-500 mt-1 mb-6">Choisissez un nouveau mot de passe pour sécuriser votre compte.</p>
    <?php if (!empty($errorMessage)): ?>
      <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="?page=mon_mot_de_passe" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
      <div><label for="current_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Mot de passe actuel</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
      <div><label for="new_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Nouveau mot de passe</label><input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="10" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"><p class="text-xs text-slate-500 mt-1">10 caractères minimum.</p></div>
      <div><label for="confirm_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmer le nouveau mot de passe</label><input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="10" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
      <div class="flex flex-wrap gap-2 pt-2"><button type="submit" class="rounded-xl bg-[#0f294a] hover:bg-[#163a68] text-white font-semibold px-5 py-3">Enregistrer le nouveau mot de passe</button><a href="?" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Retour au tableau de bord</a></div>
    </form>
  </main>
</body>
</html>
