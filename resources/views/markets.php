<?php
declare(strict_types=1);
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabels = [
    'en_attente' => 'En préparation',
    'en_cours' => 'En cours',
    'gagne' => 'Attribué',
    'perdu_annule' => 'Perdu / annulé',
    'cloture' => 'Clôturé',
];
$phaseLabels = [
    1 => 'Préparation',
    2 => 'Publication',
    3 => 'Pour étude',
    4 => 'Présentation',
    5 => 'Résultat',
    6 => 'Archivé',
];
$selectedStatus = (string) ($_GET['status'] ?? '');
$selectedCompany = (int) ($_GET['company'] ?? 0);
$searchTerm = (string) ($_GET['q'] ?? '');
$totalAmount = array_sum(array_map(static fn (array $market): float => (float) $market['amount'], $markets));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $h($pageTitle) ?> | ERP Manager</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
  <header class="bg-[#0f294a] text-white px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <img src="assets/images/erp-manager-mark.svg" alt="ERP Manager" class="w-10 h-10 rounded-xl">
      <div><p class="text-xs text-blue-200">ERP Manager · Module opérationnel</p><h1 class="text-xl font-bold"><?= $h($pageTitle) ?></h1></div>
    </div>
    <div class="flex items-center gap-2">
      <a href="?" class="rounded-lg bg-white/10 hover:bg-white/20 px-3 py-2 text-sm font-semibold">Tableau de bord</a>
      <span class="text-sm text-blue-100"><?= $h($currentUser['display_name'] ?? '') ?></span>
      <form method="post" action="?page=logout"><input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>"><button class="rounded-lg bg-white text-[#0f294a] px-3 py-2 text-sm font-bold">Déconnexion</button></form>
    </div>
  </header>

  <main class="max-w-7xl mx-auto p-4 sm:p-6 space-y-5">
    <?php if ($successMessage !== ''): ?><div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold"><?= $h($successMessage) ?></div><?php endif; ?>
    <?php if ($errorMessage !== ''): ?><div role="alert" class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"><?= $h($errorMessage) ?></div><?php endif; ?>

    <?php if ($page === 'marche_nouveau'): ?>
      <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm max-w-3xl mx-auto">
        <div class="flex items-center justify-between gap-3 mb-5"><div><h2 class="text-lg font-bold">Ajouter un marché</h2><p class="text-sm text-slate-500">Les champs marqués * sont obligatoires.</p></div><a href="?page=marches" class="text-sm font-semibold text-[#0f294a] hover:underline">Retour à la liste</a></div>
        <form method="post" action="?page=marche_nouveau" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
          <label class="text-sm font-semibold text-slate-700">N° du marché *<input name="reference" required maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="Ex. M-2026-009"></label>
          <label class="text-sm font-semibold text-slate-700">Entreprise *<select name="company_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"><?php foreach ($companies as $company): ?><?php if ($auth->hasPermission((int) $currentUser['id'], (string) $currentUser['role'], (int) $company['id'], 'markets.create')): ?><option value="<?= (int) $company['id'] ?>"><?= $h($company['name']) ?></option><?php endif; ?><?php endforeach; ?></select></label>
          <label class="text-sm font-semibold text-slate-700 sm:col-span-2">Objet du marché *<textarea name="object" required rows="3" maxlength="10000" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" placeholder="Description de l'objet du marché"></textarea></label>
          <label class="text-sm font-semibold text-slate-700">Date du marché *<input type="date" name="market_date" required value="<?= $h(date('Y-m-d')) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
          <label class="text-sm font-semibold text-slate-700">Montant estimatif (MAD) *<input type="number" name="amount" required min="0" max="9999999999999.99" step="0.01" value="0.00" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
          <div class="sm:col-span-2 rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-800">Cycle de vie du marché</p>
            <p class="mt-2 text-sm font-semibold text-slate-800">Phase initiale : 1 — Préparation</p>
            <p class="mt-1 text-sm text-slate-600">Statut initial : En préparation</p>
            <p class="mt-2 text-xs text-slate-500">La phase et le statut sont attribués automatiquement. Ils évolueront selon les étapes du traitement du marché.</p>
          </div>
          <div class="sm:col-span-2 flex flex-wrap justify-end gap-2 pt-2"><a href="?page=marches" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Annuler</a><button type="submit" class="rounded-lg bg-[#0f294a] hover:bg-[#163a68] text-white px-5 py-2.5 text-sm font-bold">Enregistrer le marché</button></div>
        </form>
      </section>
    <?php else: ?>
      <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white border border-slate-200 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marchés affichés</p><p class="text-2xl font-extrabold text-[#0f294a] mt-1"><?= number_format(count($markets), 0, ',', ' ') ?></p></div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entreprises accessibles</p><p class="text-2xl font-extrabold text-[#0f294a] mt-1"><?= number_format(count($companies), 0, ',', ' ') ?></p></div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Montant affiché</p><p class="text-xl font-extrabold text-[#0f294a] mt-2"><?= number_format($totalAmount, 2, ',', ' ') ?> MAD</p></div>
      </section>

      <section class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm">
        <form method="get" action="" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 items-end">
          <input type="hidden" name="page" value="marches">
          <label class="text-sm font-semibold text-slate-700 xl:col-span-2">Recherche<input type="search" name="q" value="<?= $h($searchTerm) ?>" placeholder="N° marché, objet ou entreprise..." class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
          <label class="text-sm font-semibold text-slate-700">Entreprise<select name="company" data-auto-filter class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="0">Toutes les entreprises accessibles</option><?php foreach ($companies as $company): ?><option value="<?= (int) $company['id'] ?>" <?= $selectedCompany === (int) $company['id'] ? 'selected' : '' ?>><?= $h($company['name']) ?></option><?php endforeach; ?></select></label>
          <label class="text-sm font-semibold text-slate-700">Statut<select name="status" data-auto-filter class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"><option value="">Tous les statuts</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= $h($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></label>
          <div class="sm:col-span-2 xl:col-span-4 flex flex-wrap items-center justify-between gap-2 pt-1"><a href="?page=marches" class="text-sm font-semibold text-slate-600 hover:text-[#0f294a]">Réinitialiser les filtres</a><div class="flex gap-2"><?php if ($canCreateMarket): ?><a href="?page=marche_nouveau" class="rounded-lg bg-[#0f294a] hover:bg-[#163a68] text-white px-4 py-2 text-sm font-bold">+ Nouveau marché</a><?php endif; ?></div></div>
        </form>
      </section>

      <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-600"><tr><th class="p-3">N° Marché</th><th class="p-3 min-w-64">Objet</th><th class="p-3">Entreprise</th><th class="p-3">Phase</th><th class="p-3">Statut</th><th class="p-3 text-right">Montant (MAD)</th><th class="p-3">Date</th></tr></thead>
          <tbody class="divide-y divide-slate-100">
          <?php if (!$markets): ?><tr><td colspan="7" class="p-8 text-center text-slate-500">Aucun marché ne correspond à votre recherche.</td></tr><?php else: ?>
            <?php foreach ($markets as $market): ?>
              <tr class="hover:bg-slate-50/80">
                <td class="p-3 font-bold text-[#0f294a] whitespace-nowrap"><?= $h($market['reference']) ?><?php if ((int) $market['is_demo'] === 1): ?><span class="ml-1 text-[10px] font-semibold text-slate-400">Démo</span><?php endif; ?></td>
                <td class="p-3 min-w-64"><?= nl2br($h($market['object'])) ?></td>
                <td class="p-3 whitespace-nowrap"><?= $h($market['company_name']) ?></td>
                <td class="p-3 whitespace-nowrap"><?= (int) $market['phase'] ?> — <?= $h($phaseLabels[(int) $market['phase']] ?? '') ?></td>
                <td class="p-3 whitespace-nowrap"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"><?= $h($statusLabels[$market['status']] ?? $market['status']) ?></span></td>
                <td class="p-3 text-right font-semibold whitespace-nowrap"><?= number_format((float) $market['amount'], 2, ',', ' ') ?></td>
                <td class="p-3 whitespace-nowrap"><?= $h(date('d/m/Y', strtotime((string) $market['market_date']))) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table></div>
        <div class="border-t border-slate-200 bg-slate-50/70 px-4 py-3 flex flex-wrap justify-between gap-2 text-xs text-slate-600"><span><?= count($markets) ?> marché(s) affiché(s)</span><span class="font-bold text-[#0f294a]">Total filtré : <?= number_format($totalAmount, 2, ',', ' ') ?> MAD</span></div>
      </section>
    <?php endif; ?>
  </main>

<script>
(() => {
  const form = document.querySelector('form[action=""]');
  if (!form) return;
  form.querySelectorAll('[data-auto-filter]').forEach((select) => {
    select.addEventListener('change', () => form.requestSubmit());
  });
})();
</script>
</body>
</html>
