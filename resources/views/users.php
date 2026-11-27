<?php
declare(strict_types=1);
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$csrf = $auth->csrfToken();
$eligibleUsers = array_values(array_filter($users, static fn (array $user): bool => $user['role'] !== 'GEST'));
?>
<?php require __DIR__ . '/partials/app-layout-start.php'; ?>
  <main class="max-w-6xl mx-auto p-4 sm:p-6 space-y-5">
    <?php if ($successMessage !== ''): ?><div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold"><?= $h($successMessage) ?></div><?php endif; ?>
    <?php if ($errorMessage !== ''): ?><div role="alert" class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"><?= $h($errorMessage) ?></div><?php endif; ?>

    <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
      <h2 class="font-bold text-lg mb-1">Créer un compte</h2>
      <p class="text-sm text-slate-500 mb-4">Le nouvel utilisateur aura accès à toutes les entreprises et à tous les droits par défaut. Son mot de passe initial sera 123.</p>
      <form method="post" action="?page=utilisateurs" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="create_user">
        <label class="text-sm font-semibold text-slate-700">Identifiant<input name="username" required minlength="3" maxlength="80" pattern="[A-Za-z0-9._-]+" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="ex. utilisateur"></label>
        <label class="text-sm font-semibold text-slate-700">Nom affiché<input name="display_name" required maxlength="160" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="Nom et prénom"></label>
        <label class="text-sm font-semibold text-slate-700">Rôle<select name="role" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="SECR">SECR — Secrétaire</option><option value="CHAD">CHAD — Chargé d’études</option></select></label>
        <button class="rounded-lg bg-[#0f294a] hover:bg-[#163a68] text-white font-semibold px-4 py-2.5">Créer le compte</button>
      </form>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
      <h2 class="font-bold text-lg mb-3">Comptes utilisateurs</h2>
      <div class="overflow-x-auto"><table class="w-full text-sm text-left">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Identifiant</th><th class="p-3">Nom</th><th class="p-3">Rôle</th><th class="p-3">État</th><th class="p-3">Mot de passe</th><th class="p-3">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($users as $user): ?>
          <tr>
            <td class="p-3 font-semibold"><?= $h($user['username']) ?></td><td class="p-3"><?= $h($user['display_name']) ?></td><td class="p-3"><?= $h($user['role']) ?></td>
            <td class="p-3"><?= (int) $user['is_active'] === 1 ? '<span class="text-emerald-700 font-semibold">Actif</span>' : '<span class="text-red-700 font-semibold">Désactivé</span>' ?></td>
            <td class="p-3"><?= (int) $user['must_change_password'] === 1 ? 'Initial à modifier' : 'Personnalisé' ?></td>
            <td class="p-3"><div class="flex flex-wrap gap-2">
              <?php if ($user['role'] !== 'GEST'): ?>
                <form method="post" action="?page=utilisateurs"><input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><button class="rounded-md border border-amber-300 bg-amber-50 text-amber-900 px-2.5 py-1.5 text-xs font-semibold">Réinitialiser à 123</button></form>
                <form method="post" action="?page=utilisateurs"><input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><button class="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold"><?= (int) $user['is_active'] === 1 ? 'Désactiver' : 'Réactiver' ?></button></form>
              <?php else: ?><span class="text-xs text-slate-400">Administrateur général</span><?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
      <h2 class="font-bold text-lg mb-1">Restreindre l’accès à une entreprise</h2>
      <p class="text-sm text-slate-500 mb-4">Par défaut, tous les utilisateurs ont accès à toutes les entreprises. Une restriction peut être annulée à tout moment.</p>
      <form method="post" action="?page=utilisateurs" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="company_restriction">
        <label class="text-sm font-semibold">Utilisateur<select name="user_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach ($eligibleUsers as $user): ?><option value="<?= (int) $user['id'] ?>"><?= $h($user['username']) ?> (<?= $h($user['role']) ?>)</option><?php endforeach; ?></select></label>
        <label class="text-sm font-semibold">Entreprise<select name="company_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach ($companies as $company): ?><option value="<?= (int) $company['id'] ?>"><?= $h($company['name']) ?></option><?php endforeach; ?></select></label>
        <label class="text-sm font-semibold">Action<select name="mode" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><option value="block">Retirer l’accès</option><option value="allow">Rétablir l’accès</option></select></label>
        <button class="rounded-lg bg-[#0f294a] text-white font-semibold px-4 py-2.5">Appliquer</button>
      </form>
      <h3 class="font-semibold mt-5 mb-2">Restrictions d’entreprise actives</h3>
      <?php if (!$companyRestrictions): ?><p class="text-sm text-slate-500">Aucune restriction : tous les utilisateurs ont accès à toutes les entreprises.</p><?php else: ?>
        <ul class="space-y-2"><?php foreach ($companyRestrictions as $restriction): ?><li class="text-sm rounded-lg bg-slate-50 p-3"><?= $h($restriction['username']) ?> — <?= $h($restriction['company_name']) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
      <h2 class="font-bold text-lg mb-1">Restreindre une fonction dans une entreprise</h2>
      <p class="text-sm text-slate-500 mb-4">Tous les droits sont accordés par défaut. Vous pouvez retirer un droit précis pour un utilisateur et une entreprise.</p>
      <form method="post" action="?page=utilisateurs" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>"><input type="hidden" name="action" value="permission_restriction">
        <label class="text-sm font-semibold">Utilisateur<select name="user_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach ($eligibleUsers as $user): ?><option value="<?= (int) $user['id'] ?>"><?= $h($user['username']) ?></option><?php endforeach; ?></select></label>
        <label class="text-sm font-semibold">Entreprise<select name="company_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach ($companies as $company): ?><option value="<?= (int) $company['id'] ?>"><?= $h($company['name']) ?></option><?php endforeach; ?></select></label>
        <label class="text-sm font-semibold md:col-span-2">Fonction<select name="permission_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><?php foreach ($permissions as $permission): ?><option value="<?= (int) $permission['id'] ?>"><?= $h($permission['label']) ?></option><?php endforeach; ?></select></label>
        <label class="text-sm font-semibold">Action<select name="mode" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"><option value="block">Retirer</option><option value="allow">Rétablir</option></select></label>
        <button class="md:col-span-5 justify-self-start rounded-lg bg-[#0f294a] text-white font-semibold px-4 py-2.5">Appliquer la restriction</button>
      </form>
      <h3 class="font-semibold mt-5 mb-2">Restrictions fonctionnelles actives</h3>
      <?php if (!$permissionRestrictions): ?><p class="text-sm text-slate-500">Aucune restriction : tous les droits restent accordés.</p><?php else: ?>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-2 text-left">Utilisateur</th><th class="p-2 text-left">Entreprise</th><th class="p-2 text-left">Fonction retirée</th></tr></thead><tbody><?php foreach ($permissionRestrictions as $restriction): ?><tr class="border-t border-slate-100"><td class="p-2"><?= $h($restriction['username']) ?></td><td class="p-2"><?= $h($restriction['company_name']) ?></td><td class="p-2"><?= $h($restriction['permission_label']) ?></td></tr><?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/partials/app-layout-end.php'; ?>
