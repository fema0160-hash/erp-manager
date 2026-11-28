<?php
declare(strict_types=1);
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabels = MarketLifecycle::statusLabels();
$phaseLabels = MarketLifecycle::phaseLabels();
$phase = (int) ($market['lifecycle_phase'] ?? 0);
$status = (string) ($market['lifecycle_status'] ?? '');
$userId = (int) ($currentUser['id'] ?? 0);
$role = (string) ($currentUser['role'] ?? '');
$companyId = (int) ($market['company_id'] ?? 0);
$canAdvance = $auth->hasPermission($userId, $role, $companyId, 'markets.lifecycle.advance') && ($schemaReady || $phase <= 4);
$canQualify = $schemaReady && $auth->hasPermission($userId, $role, $companyId, 'markets.results.qualify');
$canValidate = $schemaReady && $auth->hasPermission($userId, $role, $companyId, 'markets.results.validate');
$canReopen = $schemaReady && $role === 'GEST' && $auth->hasPermission($userId, $role, $companyId, 'markets.lifecycle.reopen');
$pendingExists = false;
foreach ($qualifications as $qualification) {
    if (empty($qualification['validation_decision'])) { $pendingExists = true; break; }
}
$canSubmitQualification = $canQualify && $phase === 5 && in_array($status, ['en_cours', 'a_qualifier'], true) && !$pendingExists;
$hTitle = (string) ($market['reference'] ?? 'Marché');
$selectedLotId = (int) ($selectedLotId ?? 0);
$detailAction = '?page=marche&id=' . (int) ($market['id'] ?? 0) . ($lotsSchemaReady && $selectedLotId > 0 ? '&lot_id=' . $selectedLotId : '');
$canCreateLot = $lotsSchemaReady && $auth->hasPermission($userId, $role, $companyId, 'markets.create');
?>
<?php require __DIR__ . '/partials/app-layout-start.php'; ?>
<div class="flex flex-wrap items-center justify-between gap-3">
  <a href="?page=marches" class="text-sm font-semibold text-[#0f294a] hover:underline">← Retour aux marchés</a>
  <span class="text-xs text-slate-500">Fiche n° <?= $h($market['id'] ?? '') ?></span>
</div>

<?php if ($successMessage !== ''): ?>
  <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold"><?= $h($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
  <div role="alert" class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"><?= $h($errorMessage) ?></div>
<?php endif; ?>

<section class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-sm">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= $h($market['company_name'] ?? '') ?></p>
      <h2 class="text-xl font-extrabold text-[#0f294a] mt-1"><?= $h($hTitle) ?></h2>
      <p class="text-sm text-slate-700 mt-2 whitespace-pre-line"><?= $h($market['object'] ?? '') ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
      <span class="rounded-full bg-blue-50 text-blue-800 px-3 py-1.5 text-xs font-bold">Phase <?= $phase ?> — <?= $h($phaseLabels[$phase] ?? 'Inconnue') ?></span>
      <span class="rounded-full bg-slate-100 text-slate-700 px-3 py-1.5 text-xs font-bold"><?= $h($statusLabels[$status] ?? $status) ?></span>
    </div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
    <div><p class="text-xs text-slate-500">Montant estimatif</p><p class="font-bold text-slate-900"><?= number_format((float) ($market['amount'] ?? 0), 2, ',', ' ') ?> MAD</p></div>
    <div><p class="text-xs text-slate-500">Date du marché</p><p class="font-semibold text-slate-900"><?= $h(date('d/m/Y', strtotime((string) ($market['market_date'] ?? 'now')))) ?></p></div>
    <div><p class="text-xs text-slate-500">Identifiant</p><p class="font-semibold text-slate-900">#<?= (int) ($market['id'] ?? 0) ?></p></div>
  </div>
</section>

<?php if ($lotsSchemaReady): ?>
<section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h3 class="font-extrabold text-[#0f294a]">Lots indépendants</h3>
      <p class="text-xs text-slate-500 mt-1">Chaque lot possède son propre résultat, ses phases et son historique.</p>
    </div>
    <span class="rounded-full bg-blue-50 text-blue-800 px-3 py-1.5 text-xs font-bold"><?= count($lots) ?> lot(s)</span>
  </div>
  <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
    <?php foreach ($lots as $lotRow): ?>
      <?php $isSelectedLot = (int) $lotRow['id'] === $selectedLotId; ?>
      <a href="?page=marche&id=<?= (int) $market['id'] ?>&lot_id=<?= (int) $lotRow['id'] ?>"
         class="rounded-xl border p-4 transition <?= $isSelectedLot ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50' ?>">
        <div class="flex items-start justify-between gap-2">
          <span class="font-extrabold text-[#0f294a]">Lot n° <?= (int) $lotRow['lot_number'] ?></span>
          <?php if ($isSelectedLot): ?><span class="text-[10px] font-bold text-blue-800">Sélectionné</span><?php endif; ?>
        </div>
        <p class="mt-1 text-sm font-semibold text-slate-800"><?= $h($lotRow['lot_label']) ?></p>
        <?php if (!empty($lotRow['lot_reference'])): ?><p class="mt-1 text-xs text-slate-500">Réf. <?= $h($lotRow['lot_reference']) ?></p><?php endif; ?>
        <p class="mt-3 text-xs text-slate-600">Phase <?= (int) $lotRow['lifecycle_phase'] ?> — <?= $h($phaseLabels[(int) $lotRow['lifecycle_phase']] ?? '') ?></p>
        <p class="mt-1 text-xs font-semibold text-slate-700"><?= $h($statusLabels[$lotRow['lifecycle_status']] ?? $lotRow['lifecycle_status']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if ($canCreateLot): ?>
    <form method="post" action="<?= $h($detailAction) ?>" class="mt-4 border-t border-slate-100 pt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
      <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
      <input type="hidden" name="action" value="create_lot">
      <label class="text-sm font-semibold text-slate-700">Nom du nouveau lot *
        <input name="lot_label" required maxlength="180" placeholder="Ex. Travaux de voirie" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal">
      </label>
      <label class="text-sm font-semibold text-slate-700">Référence du lot
        <input name="lot_reference" maxlength="100" placeholder="Référence facultative" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal">
      </label>
      <div class="flex items-end"><button class="w-full rounded-lg bg-[#0f294a] text-white px-4 py-2.5 text-sm font-bold">+ Ajouter un lot</button></div>
    </form>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php
$phaseStates = [];
foreach ($phaseLabels as $phaseNumber => $phaseLabel) {
    $isClosed = $status === 'cloture' && $phase === 8;
    $isNegativeResult = in_array($status, ['perdu', 'annule', 'infructueux'], true) && $phase === 5;
    $isDone = $phase > $phaseNumber || ($phaseNumber === 5 && $isNegativeResult) || $isClosed;
    $phaseStates[$phaseNumber] = $isDone ? 'done' : ($phase === $phaseNumber ? 'current' : 'upcoming');
}
$decisionCards = [
    ['code' => 'attribue', 'title' => 'Attribué', 'description' => 'Poursuite vers la phase 6', 'action' => 'Continuer'],
    ['code' => 'perdu', 'title' => 'Perdu', 'description' => 'Fin du parcours normal', 'action' => 'Terminé'],
    ['code' => 'annule', 'title' => 'Annulé', 'description' => 'Fin du parcours normal', 'action' => 'Terminé'],
    ['code' => 'infructueux', 'title' => 'Infructueux', 'description' => 'Fin du parcours normal', 'action' => 'Terminé'],
];
?>
<section class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-sm" aria-label="Cycle de vie du lot">
  <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
    <h3 class="font-extrabold text-[#0f294a]">Cycle de vie <?= $lotsSchemaReady ? "du lot" : "du marché" ?></h3>
    <span class="rounded-full bg-blue-50 text-blue-800 px-3 py-1.5 text-xs font-bold">
      <?= $phase >= 1 && $phase <= 8 ? 'Phase ' . str_pad((string) $phase, 2, '0', STR_PAD_LEFT) . ' — ' . $h($phaseLabels[$phase] ?? '') : 'Phase inconnue' ?>
    </span>
  </div>

  <div class="overflow-x-auto pb-2" role="region" aria-label="Les huit phases, défilement horizontal possible">
    <div class="grid grid-cols-8 gap-2 min-w-[900px] lg:min-w-0">
      <?php foreach ($phaseLabels as $phaseNumber => $phaseLabel): ?>
        <?php
          $visualState = $phaseStates[$phaseNumber];
          $circleClass = $visualState === 'done'
              ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
              : ($visualState === 'current'
                  ? 'bg-blue-100 text-blue-800 border-blue-500 ring-2 ring-blue-100'
                  : 'bg-slate-100 text-slate-500 border-slate-200');
          $labelClass = $visualState === 'done'
              ? 'text-emerald-800'
              : ($visualState === 'current' ? 'text-blue-800' : 'text-slate-500');
          $stateLabel = $visualState === 'done' ? 'Terminée' : ($visualState === 'current' ? 'En cours' : 'À venir');
          $symbol = $visualState === 'done' ? '✓' : ($visualState === 'current' ? '●' : '○');
        ?>
        <div class="min-w-0 text-center flex flex-col items-center">
          <span class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-extrabold <?= $circleClass ?>" aria-label="<?= $h($stateLabel) ?>">
            <?= $symbol ?>
          </span>
          <span class="mt-2 text-sm font-extrabold <?= $labelClass ?>"><?= str_pad((string) $phaseNumber, 2, '0', STR_PAD_LEFT) ?></span>
          <span class="mt-1 min-h-[2.6rem] text-[11px] leading-tight font-semibold text-slate-700 break-words"><?= $h($phaseLabel) ?></span>
          <span class="mt-1 text-[10px] font-semibold <?= $labelClass ?>"><?= $h($stateLabel) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="mt-5 border-t border-slate-100 pt-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
      <h4 class="text-sm font-extrabold uppercase tracking-wide text-slate-700">Résultat après la phase 5</h4>
      <?php if ($phase < 5): ?>
        <span class="text-xs text-slate-500">Disponible à partir de la phase 5</span>
      <?php elseif (in_array($status, ['attribue', 'perdu', 'annule', 'infructueux'], true)): ?>
        <span class="text-xs font-semibold text-emerald-700">Résultat officiel enregistré</span>
      <?php else: ?>
        <span class="text-xs text-slate-500">En attente de qualification et de validation</span>
      <?php endif; ?>
    </div>
    <div class="overflow-x-auto pb-2" role="region" aria-label="Les quatre décisions, défilement horizontal possible">
      <div class="grid grid-cols-4 gap-2 min-w-[680px] lg:min-w-0">
        <?php foreach ($decisionCards as $decision): ?>
          <?php
            $selected = $status === $decision['code'];
            $decisionStyle = $selected
                ? ($decision['code'] === 'attribue'
                    ? 'border-emerald-400 bg-emerald-50'
                    : 'border-amber-400 bg-amber-50')
                : 'border-slate-200 bg-slate-50';
            $decisionIcon = $decision['code'] === 'attribue' ? '✓'
                : ($decision['code'] === 'perdu' ? '×'
                    : ($decision['code'] === 'annule' ? '⊘' : '!'));
            $decisionIconStyle = $decision['code'] === 'attribue'
                ? 'text-emerald-700 bg-emerald-100'
                : 'text-slate-600 bg-white';
          ?>
          <div class="min-w-0 rounded-xl border p-3 <?= $decisionStyle ?> <?= $phase < 5 && !$selected ? 'opacity-60' : '' ?>">
            <div class="flex items-center gap-2">
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-base font-extrabold <?= $decisionIconStyle ?>"><?= $decisionIcon ?></span>
              <span class="text-sm font-extrabold text-slate-900"><?= $h($decision['title']) ?></span>
            </div>
            <p class="mt-2 min-h-[2.2rem] text-xs leading-snug text-slate-600"><?= $h($decision['description']) ?></p>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
              <span class="rounded-full bg-white/80 px-2 py-1 text-[10px] font-bold text-slate-700"><?= $h($decision['action']) ?></span>
              <?php if ($selected): ?>
                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-800">Décision validée</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php if (!$schemaReady): ?>
  <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
    <strong>Migration 004 requise.</strong>
    La fiche et l'avancement séquentiel des phases 1→2→3→4→5 restent disponibles. Les qualifications, validations et phases 6 à 8 nécessitent la migration 004, à installer après sauvegarde et autorisation explicite.
  </section>
<?php endif; ?>

<?php if ($phase >= 1 && $phase <= 4 && $canAdvance && $status === 'en_cours'): ?>
  <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <h3 class="font-bold text-slate-900">Faire avancer le dossier</h3>
    <p class="text-sm text-slate-600 mt-1">Prochaine étape : phase <?= $phase + 1 ?> — <?= $h($phaseLabels[$phase + 1]) ?>. Aucune phase ne peut être sautée.</p>
    <form method="post" action="<?= $h($detailAction) ?>" class="mt-4 flex flex-col sm:flex-row gap-3">
      <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
      <input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>">
      <input type="hidden" name="action" value="advance">
      <input name="reason" maxlength="2000" placeholder="Observation (facultative)" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <button class="rounded-lg bg-[#0f294a] text-white px-4 py-2.5 text-sm font-bold">Passer à la phase <?= $phase + 1 ?></button>
    </form>
  </section>
<?php elseif ($phase === 6 && $canAdvance && $status === 'attribue'): ?>
  <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <h3 class="font-bold text-slate-900">Préparer le démarrage du marché</h3>
    <p class="text-sm text-slate-600 mt-1">Confirmez les conditions de démarrage avant le passage en phase 7 — Exécution / suivi du chantier.</p>
    <form method="post" action="?page=marche&id=<?= (int) $market['id'] ?>" class="mt-4 flex flex-col sm:flex-row gap-3">
      <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>"><input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>"><input type="hidden" name="action" value="advance">
      <input name="reason" required minlength="5" maxlength="2000" placeholder="Conditions de démarrage vérifiées" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <button class="rounded-lg bg-[#0f294a] text-white px-4 py-2.5 text-sm font-bold">Démarrer l'exécution</button>
    </form>
  </section>
<?php elseif ($phase === 7 && $canAdvance && $status === 'attribue'): ?>
  <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <h3 class="font-bold text-slate-900">Clôturer le marché</h3>
    <p class="text-sm text-slate-600 mt-1">La clôture est une attestation de contrôle : les pièces et éléments du dossier final doivent avoir été vérifiés.</p>
    <form method="post" action="?page=marche&id=<?= (int) $market['id'] ?>" class="mt-4 space-y-3">
      <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>"><input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>"><input type="hidden" name="action" value="advance">
      <textarea name="reason" required minlength="10" maxlength="5000" rows="3" placeholder="Décrivez les pièces et éléments de clôture contrôlés..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
      <label class="flex items-start gap-2 text-sm text-slate-700"><input type="checkbox" name="closure_confirmed" value="1" required class="mt-1"><span>Je confirme avoir vérifié la complétude du dossier final et identifié les éventuelles pièces manquantes.</span></label>
      <button class="rounded-lg bg-[#0f294a] text-white px-4 py-2.5 text-sm font-bold">Clôturer et archiver</button>
    </form>
  </section>
<?php endif; ?>

<?php if ($schemaReady): ?>
<?php if ($canSubmitQualification): ?>
  <section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <h3 class="font-bold text-slate-900">Qualifier le résultat <?= $lotsSchemaReady ? "du lot" : "du marché" ?></h3>
    <p class="text-sm text-slate-600 mt-1">La proposition reste en attente. Elle ne devient officielle qu'après validation.</p>
    <form method="post" action="?page=marche&id=<?= (int) $market['id'] ?>" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
      <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>"><input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>"><input type="hidden" name="action" value="qualify">
      <label class="text-sm font-semibold text-slate-700 sm:col-span-2">Résultat proposé *
        <select name="result_code" id="resultCode" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal">
          <option value="">Choisir...</option><option value="attribue">Attribué</option><option value="perdu">Perdu</option><option value="annule">Annulé</option><option value="infructueux">Infructueux</option>
        </select>
      </label>
      <label data-result-field="attribue" class="text-sm font-semibold text-slate-700">Date de décision *<input type="date" name="decision_date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
      <label data-result-field="attribue" class="text-sm font-semibold text-slate-700">Montant attribué (MAD) *<input type="number" name="awarded_amount" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
      <label data-result-field="perdu" class="text-sm font-semibold text-slate-700">Entreprise concurrente *<input name="winner_company_name" maxlength="180" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
      <label data-result-field="perdu" class="text-sm font-semibold text-slate-700">Montant concurrent (MAD) *<input type="number" name="winner_amount" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>
      <label data-result-field="annule infructueux" class="text-sm font-semibold text-slate-700 sm:col-span-2">Motif *
        <textarea name="decision_reason" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></textarea>
      </label>
      <label data-result-field="perdu" class="text-sm font-semibold text-slate-700 sm:col-span-2">Observations *
        <textarea name="decision_observations" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></textarea>
      </label>
      <div class="sm:col-span-2 flex justify-end"><button class="rounded-lg bg-[#0f294a] text-white px-5 py-2.5 text-sm font-bold">Enregistrer la qualification</button></div>
    </form>
  </section>
<?php endif; ?>

<section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold text-slate-900">Qualifications et validations <?= $lotsSchemaReady ? "du lot" : "du marché" ?></h3><span class="text-xs text-slate-500"><?= count($qualifications) ?> proposition(s)</span></div>
  <?php if (!$qualifications): ?>
    <p class="text-sm text-slate-500 mt-4">Aucune qualification enregistrée.</p>
  <?php else: ?>
    <div class="mt-4 space-y-3">
    <?php foreach ($qualifications as $q): ?>
      <?php $qStatus = $q['validation_decision'] ?: 'en_attente'; ?>
      <article class="rounded-xl border border-slate-200 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div><p class="font-bold text-slate-900"><?= $h($statusLabels[$q['result_code']] ?? $q['result_code']) ?></p><p class="text-xs text-slate-500 mt-1">Proposé par <?= $h($q['qualified_by_name']) ?> · <?= $h(date('d/m/Y H:i', strtotime((string) $q['qualified_at']))) ?></p></div>
          <span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $qStatus === 'validee' ? 'bg-emerald-100 text-emerald-800' : ($qStatus === 'refusee' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>"><?= $qStatus === 'validee' ? 'Validée' : ($qStatus === 'refusee' ? 'Refusée' : 'En attente') ?></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3 text-sm text-slate-700">
          <?php if ($q['decision_date']): ?><p>Date de décision : <strong><?= $h(date('d/m/Y', strtotime((string) $q['decision_date']))) ?></strong></p><?php endif; ?>
          <?php if ($q['awarded_amount'] !== null): ?><p>Montant attribué : <strong><?= number_format((float) $q['awarded_amount'], 2, ',', ' ') ?> MAD</strong></p><?php endif; ?>
          <?php if ($q['winner_company_name']): ?><p>Concurrent : <strong><?= $h($q['winner_company_name']) ?></strong></p><?php endif; ?>
          <?php if ($q['winner_amount'] !== null): ?><p>Montant concurrent : <strong><?= number_format((float) $q['winner_amount'], 2, ',', ' ') ?> MAD</strong></p><?php endif; ?>
          <?php if ($q['decision_reason']): ?><p class="sm:col-span-2">Motif : <?= $h($q['decision_reason']) ?></p><?php endif; ?>
          <?php if ($q['decision_observations']): ?><p class="sm:col-span-2">Observations : <?= $h($q['decision_observations']) ?></p><?php endif; ?>
          <?php if (!empty($q['validated_by_name'])): ?><p class="sm:col-span-2 text-xs text-slate-500"><?= $q['validation_decision'] === 'validee' ? 'Validé' : 'Refusé' ?> par <?= $h($q['validated_by_name']) ?><?php if (!empty($q['validated_at'])): ?> · <?= $h(date('d/m/Y H:i', strtotime((string) $q['validated_at']))) ?><?php endif; ?></p><?php endif; ?>
          <?php if ($q['validation_reason']): ?><p class="sm:col-span-2 text-red-700">Motif du refus : <?= $h($q['validation_reason']) ?></p><?php endif; ?>
        </div>
        <?php if (empty($q['validation_decision']) && $canValidate && $phase === 5 && in_array($status, ['en_cours', 'a_qualifier'], true)): ?>
          <form method="post" action="?page=marche&id=<?= (int) $market['id'] ?>" class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>"><input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>"><input type="hidden" name="action" value="validate"><input type="hidden" name="qualification_id" value="<?= (int) $q['id'] ?>">
            <input name="validation_reason" maxlength="2000" placeholder="Motif obligatoire si refus" class="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <div class="flex gap-2"><button name="decision" value="validee" class="flex-1 rounded-lg bg-emerald-700 text-white px-3 py-2 text-xs font-bold">Valider</button><button name="decision" value="refusee" class="flex-1 rounded-lg bg-red-700 text-white px-3 py-2 text-xs font-bold">Refuser</button></div>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($canReopen && in_array($status, ['attribue','perdu','annule','infructueux','cloture'], true)): ?>
  <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
    <h3 class="font-bold text-amber-950">Réouverture exceptionnelle — GEST</h3>
    <p class="text-sm text-amber-900 mt-1">Le marché retournera en phase 5. La décision précédente restera dans l'historique.</p>
    <form method="post" action="?page=marche&id=<?= (int) $market['id'] ?>" class="mt-3 flex flex-col sm:flex-row gap-2">
      <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>"><input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>"><input type="hidden" name="action" value="reopen">
      <input name="reason" required minlength="10" maxlength="2000" placeholder="Motif détaillé de réouverture" class="flex-1 rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">
      <button class="rounded-lg bg-amber-800 text-white px-4 py-2.5 text-sm font-bold">Réouvrir le marché</button>
    </form>
  </section>
<?php endif; ?>

<?php endif; ?>

<section class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
  <div class="flex items-center justify-between gap-2"><h3 class="font-bold text-slate-900">Historique <?= $lotsSchemaReady ? "du lot" : "du marché" ?></h3><span class="text-xs text-slate-500"><?= count($history) ?> événement(s)</span></div>
  <?php if (!$history): ?>
    <p class="text-sm text-slate-500 mt-4">Aucun événement d'historique pour ce marché.</p>
  <?php else: ?>
    <div class="overflow-x-auto mt-4"><table class="w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs text-slate-600"><tr><th class="p-2">Date</th><th class="p-2">Action</th><th class="p-2">Évolution</th><th class="p-2">Utilisateur</th><th class="p-2">Motif / détail</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($history as $event): ?>
        <tr class="align-top"><td class="p-2 whitespace-nowrap text-xs"><?= $h(date('d/m/Y H:i', strtotime((string) $event['changed_at']))) ?></td><td class="p-2 font-semibold"><?= $h(ucfirst(str_replace('_', ' ', (string) $event['action_type']))) ?></td><td class="p-2 text-xs"><?= $event['from_phase'] === null ? 'Création' : 'P' . (int) $event['from_phase'] . ' / ' . $h($event['from_status']) ?> → P<?= (int) $event['to_phase'] ?> / <?= $h($event['to_status']) ?></td><td class="p-2"><?= $h($event['actor_name'] ?? 'Système') ?></td><td class="p-2 min-w-48 text-slate-600"><?= $h($event['reason'] ?? '') ?><?php if (!empty($event['result_code'])): ?><span class="block text-xs text-slate-500">Résultat : <?= $h($event['result_code']) ?></span><?php endif; ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</section>

<script>
(() => {
  const select = document.getElementById('resultCode');
  if (!select) return;
  const fields = document.querySelectorAll('[data-result-field]');
  const refresh = () => {
    const code = select.value;
    fields.forEach((field) => {
      const active = (field.dataset.resultField || '').split(' ').includes(code);
      field.hidden = !active;
      field.querySelectorAll('input, textarea').forEach((input) => {
        input.required = active;
        if (!active) input.value = '';
      });
    });
  };
  select.addEventListener('change', refresh);
  refresh();
})();
</script>
<?php require __DIR__ . '/partials/app-layout-end.php'; ?>
