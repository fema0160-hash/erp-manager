/* =========================================================================
   DONNÉES DE DÉMONSTRATION POUR LE SQUELETTE ERP MANAGER
   ========================================================================= */
const COMPANIES = [
  { id: 'AIT_KHAYI_DRISS', name: 'AIT KHAYI DRISS', short: 'AIT KHAYI DRISS', color: '#3b82f6' },
  { id: 'ONCADEMY', name: 'ONCADEMY / DEUTSCH ZENTRUM', short: 'ONCADEMY / DEUTSCH Z.', color: '#10b981' },
  { id: 'DROGUEMED', name: 'DROGUEMED', short: 'DROGUEMED', color: '#a855f7' },
  { id: 'ATLAS_INFRA', name: 'ATLAS INFRA & SERVICES', short: 'ATLAS INFRA & SERVICES', color: '#06b6d4' },
  { id: 'NORD_INGENIERIE', name: 'NORD INGÉNIERIE CONSEIL', short: 'NORD INGÉNIERIE CONSEIL', color: '#f43f5e' }
];

const NAV_ITEMS = [
  { id: 'dashboard', label: 'Tableau de bord', badge: null, active: true },
  { id: 'marches', label: 'Marchés', badge: '8', active: false },
  { id: 'archive', label: 'Archive', badge: null, active: false },
  { id: 'cautions', label: 'Cautions & RG', badge: null, active: false },
  { id: 'charges', label: 'Charges', badge: null, active: false },
  { id: 'vehicules', label: 'Véhicules', badge: null, active: false },
  { id: 'echeances', label: 'Prochaines échéances', badge: '2', active: false },
  { id: 'notes', label: 'Notes', badge: null, active: false },
  { id: 'parametres', label: 'Paramètres', badge: null, active: false }
];

function getSidebarSvgIcon(id, isActive) {
  const duotoneOpacity = isActive ? '0.26' : '0.18';
  const accentStroke = isActive ? '#ffffff' : '#ea9a10';

  const icons = {
    dashboard: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="3" width="8" height="10" rx="2.2" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <rect x="13" y="3" width="8" height="6" rx="2" />
        <rect x="13" y="11" width="8" height="10" rx="2.2" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <rect x="3" y="15" width="8" height="6" rx="2" />
      </svg>`,
    marches: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M8.5 7V5.2A1.7 1.7 0 0 1 10.2 3.5h3.6a1.7 1.7 0 0 1 1.7 1.7V7" />
        <rect x="3" y="7" width="18" height="13.5" rx="2.6" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="M3 12.2c5.4 1.6 12.6 1.6 18 0" />
        <rect x="10.2" y="10.8" width="3.6" height="2.8" rx="0.9" fill="${accentStroke}" stroke="${accentStroke}" stroke-width="1.4" />
      </svg>`,
    archive: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="2.8" y="4" width="18.4" height="4.2" rx="1.5" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="M4.2 8.2v9.6A2.2 2.2 0 0 0 6.4 20h11.2a2.2 2.2 0 0 0 2.2-2.2V8.2" fill="currentColor" fill-opacity="0.08" />
        <rect x="9.2" y="11.6" width="5.6" height="2.4" rx="1" stroke="${accentStroke}" />
      </svg>`,
    cautions: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 2.8 4.5 6v5.7c0 5 3.2 9.2 7.5 10.5 4.3-1.3 7.5-5.5 7.5-10.5V6L12 2.8Z" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="m9 12.2 2.1 2.1 4.1-4.3" stroke="${accentStroke}" stroke-width="2" />
      </svg>`,
    charges: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 3.2h12a1.8 1.8 0 0 1 1.8 1.8v15.6l-2.6-1.7-2.6 1.7-2.6-1.7-2.6 1.7-2.6-1.7-2.6 1.7V5A1.8 1.8 0 0 1 6 3.2Z" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="M8.5 8.2h7" />
        <path d="M8.5 11.8h4.5" />
        <circle cx="15.2" cy="13.5" r="2.1" stroke="${accentStroke}" stroke-width="1.6" />
      </svg>`,
    vehicules: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2.8 6.5A1.7 1.7 0 0 1 4.5 4.8h9.2a1.3 1.3 0 0 1 1.3 1.3v9.4H2.8V6.5Z" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="M15 8.5h3.2c.7 0 1.3.4 1.6 1l1.4 2.7c.2.3.3.7.3 1.1v2.2H15V8.5Z" fill="currentColor" fill-opacity="0.1" />
        <circle cx="7" cy="17.2" r="2.3" fill="#0f294a" stroke="${accentStroke}" stroke-width="1.8" />
        <circle cx="17.6" cy="17.2" r="2.3" fill="#0f294a" stroke="${accentStroke}" stroke-width="1.8" />
        <path d="M9.3 15.5h6" />
      </svg>`,
    echeances: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11.5" cy="12.5" r="8.2" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="M11.5 8.2v4.5l3 1.8" stroke="${accentStroke}" stroke-width="1.9" />
        <path d="M8.5 2.8h6" />
        <circle cx="18.6" cy="5.6" r="2.2" fill="${accentStroke}" stroke="none" />
      </svg>`,
    notes: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M13.5 4H6.4A2.4 2.4 0 0 0 4 6.4v11.2A2.4 2.4 0 0 0 6.4 20h11.2a2.4 2.4 0 0 0 2.4-2.4v-7.1" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <path d="M8 9.2h4.2" />
        <path d="M8 13h2.8" />
        <path d="m14.2 13.8 5.2-5.2a1.6 1.6 0 0 0-2.3-2.3l-5.2 5.2-.6 2.9 2.9-.6Z" stroke="${accentStroke}" fill="currentColor" fill-opacity="0.15" />
      </svg>`,
    parametres: `
      <svg viewBox="0 0 24 24" fill="none" class="w-[19px] h-[19px]" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3.2v2.1M12 18.7v2.1M3.2 12h2.1M18.7 12h2.1M5.8 5.8l1.5 1.5M16.7 16.7l1.5 1.5M18.2 5.8l-1.5 1.5M7.3 16.7l-1.5 1.5" stroke="${accentStroke}" />
        <circle cx="12" cy="12" r="6.2" fill="currentColor" fill-opacity="${duotoneOpacity}" />
        <circle cx="12" cy="12" r="2.5" fill="#0f294a" stroke="${accentStroke}" stroke-width="1.7" />
      </svg>`
  };

  return icons[id] || '';
}

function getHoldingGroupSvgIcon(isActive) {
  return `
    <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4 shrink-0" stroke="${isActive ? '#ffffff' : '#ea9a10'}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <rect x="3" y="9" width="6.5" height="11" rx="1.4" fill="#ea9a10" fill-opacity="0.22" />
      <rect x="14.5" y="9" width="6.5" height="11" rx="1.4" fill="#ea9a10" fill-opacity="0.22" />
      <rect x="8.8" y="4" width="6.4" height="16" rx="1.5" fill="#ea9a10" fill-opacity="0.35" />
      <path d="M11.2 7.5h1.6M11.2 11h1.6M11.2 14.5h1.6" />
    </svg>`;
}

function getSubsidiaryBuildingSvgIcon() {
  return `
    <svg viewBox="0 0 24 24" fill="none" class="w-3.5 h-3.5 text-blue-200/75 group-hover:text-white transition-colors shrink-0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <rect x="5" y="3.5" width="14" height="17" rx="2" fill="currentColor" fill-opacity="0.14" />
      <path d="M9 8h2M13 8h2M9 12h2M13 12h2M10.5 20.5v-4h3v4" />
    </svg>`;
}

const PHASES = [
  { num: 1, title: 'Phase 1 · Étape initiale', desc: 'Repérage & DCE', accent: 'border-t-blue-500', textAccent: 'text-blue-700' },
  { num: 2, title: 'Phase 2 · Préparation', desc: 'Montage dossier', accent: 'border-t-emerald-500', textAccent: 'text-emerald-700' },
  { num: 3, title: 'Phase 3 · Pour étude', desc: 'Chiffrage & offre', accent: 'border-t-amber-500', textAccent: 'text-amber-700' },
  { num: 4, title: 'Phase 4 · Présentation', desc: 'Dépôt & soutenance', accent: 'border-t-purple-500', textAccent: 'text-purple-700' },
  { num: 5, title: 'Phase 5 · Résultat', desc: 'Attribution / Verdict', accent: 'border-t-cyan-500', textAccent: 'text-cyan-700' },
  { num: 6, title: 'Phase 6 · Archivé', desc: 'Clôture & historique', accent: 'border-t-slate-500', textAccent: 'text-slate-700' }
];

const STATUS_META = {
  en_cours: { label: 'En cours', color: '#2563eb', badgeClass: 'bg-blue-50 text-blue-700 border-blue-200', borderTop: 'border-t-blue-500' },
  cloture: { label: 'Clôturés', color: '#16a34a', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200', borderTop: 'border-t-emerald-500' },
  gagne: { label: 'Gagnés', color: '#ef4444', badgeClass: 'bg-rose-50 text-rose-700 border-rose-200', borderTop: 'border-t-rose-500' },
  perdu_annule: { label: 'Perdus / Annulés', color: '#9333ea', badgeClass: 'bg-purple-50 text-purple-700 border-purple-200', borderTop: 'border-t-purple-500' },
  en_attente: { label: 'En attente', color: '#0891b2', badgeClass: 'bg-cyan-50 text-cyan-700 border-cyan-200', borderTop: 'border-t-cyan-500' }
};

const MARCHES_DATA = [
  { numero: 'M-2026-008', objet: 'Aménagement voirie et réseaux divers — Lot 2', companyId: 'AIT_KHAYI_DRISS', status: 'en_attente', phase: 1, amount: 1250000, date: '25/11/2026' },
  { numero: 'M-2026-007', objet: 'Construction annexe administrative & salles', companyId: 'ONCADEMY', status: 'en_cours', phase: 2, amount: 987500, date: '21/11/2026' },
  { numero: 'M-2026-006', objet: 'Fourniture équipements médicaux spécialisés', companyId: 'DROGUEMED', status: 'en_cours', phase: 3, amount: 750000, date: '18/11/2026' },
  { numero: 'M-2026-005', objet: 'Réhabilitation infrastructure routière régionale', companyId: 'ATLAS_INFRA', status: 'gagne', phase: 5, amount: 1640000, date: '14/11/2026' },
  { numero: 'M-2026-004', objet: 'Étude technique et maîtrise d’œuvre génie civil', companyId: 'NORD_INGENIERIE', status: 'en_attente', phase: 2, amount: 420000, date: '10/11/2026' },
  { numero: 'M-2026-003', objet: 'Travaux de terrassement et soutènement', companyId: 'AIT_KHAYI_DRISS', status: 'en_cours', phase: 4, amount: 860000, date: '06/11/2026' },
  { numero: 'M-2026-002', objet: 'Audit qualité et contrôle des ouvrages', companyId: 'NORD_INGENIERIE', status: 'cloture', phase: 6, amount: 315000, date: '02/11/2026' },
  { numero: 'M-2026-001', objet: 'Maintenance réseaux et génie urbain', companyId: 'ATLAS_INFRA', status: 'perdu_annule', phase: 5, amount: 540000, date: '28/10/2026' }
];

let state = {
  variant: 'B',
  company: 'ALL',
  statusFilter: 'ALL',
  phaseFilter: 'ALL'
};

let doughnutChartInstance = null;

function formatMAD(amount) {
  return new Intl.NumberFormat('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' MAD';
}

function getCompanyById(id) {
  return COMPANIES.find(c => c.id === id) || { name: 'Vue Groupe (toutes les sociétés)', short: 'Vue Groupe', color: '#ea9a10' };
}

function getScopedMarches() {
  if (state.company === 'ALL') return MARCHES_DATA;
  return MARCHES_DATA.filter(m => m.companyId === state.company);
}

function getFilteredMarches() {
  const query = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
  return getScopedMarches().filter(m => {
    const company = getCompanyById(m.companyId);
    const matchesStatus = state.statusFilter === 'ALL' || m.status === state.statusFilter;
    const matchesPhase = state.phaseFilter === 'ALL' || m.phase === state.phaseFilter;
    const matchesQuery = !query ||
      m.numero.toLowerCase().includes(query) ||
      m.objet.toLowerCase().includes(query) ||
      company.name.toLowerCase().includes(query);
    return matchesStatus && matchesPhase && matchesQuery;
  });
}

function renderSidebarNav() {
  const navEl = document.getElementById('mainNav');
  const isCompact = state.variant === 'B';

  navEl.innerHTML = NAV_ITEMS.map(item => {
    const activeClasses = item.active
      ? 'bg-[#ea9a10] text-white font-bold shadow-md shadow-amber-950/25'
      : 'text-blue-100/90 hover:bg-white/10 hover:text-white font-medium';
    const iconBoxClasses = item.active
      ? 'bg-white/20 text-white'
      : 'bg-white/[0.06] text-blue-200 group-hover:bg-white/15 group-hover:text-[#ea9a10]';
    const badgeClasses = item.active
      ? 'bg-white text-slate-950 shadow-sm'
      : 'bg-[#ea9a10] text-slate-950';

    return `
      <button type="button" onclick="handleNavClick('${item.id}')" class="nav-btn group w-full flex items-center justify-between ${isCompact ? 'px-2.5 py-2 text-xs' : 'px-3 py-2 text-sm'} rounded-xl transition ${activeClasses}">
        <span class="flex items-center gap-3 min-w-0">
          <span class="sidebar-icon-wrap w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${iconBoxClasses}">
            ${getSidebarSvgIcon(item.id, item.active)}
          </span>
          <span class="truncate">${item.label}</span>
        </span>
        ${item.badge ? `<span class="text-[11px] font-extrabold px-2 py-0.5 rounded-full ${badgeClasses}">${item.badge}</span>` : ''}
      </button>
    `;
  }).join('');
}

function renderCompanySelectors() {
  const listEl = document.getElementById('companyList');
  const selectEl = document.getElementById('topbarCompanySelect');

  const allSelected = state.company === 'ALL';
  let listHtml = `
    <button type="button" onclick="selectCompany('ALL')" class="group w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs transition ${allSelected ? 'bg-white/15 text-white font-semibold ring-1 ring-[#ea9a10]' : 'text-blue-100/85 hover:bg-white/10 hover:text-white'}">
      <span class="flex items-center gap-2.5 truncate">
        <span class="w-7 h-7 rounded-lg bg-[#ea9a10]/20 border border-[#ea9a10]/40 flex items-center justify-center shrink-0">
          ${getHoldingGroupSvgIcon(allSelected)}
        </span>
        <span class="truncate">Vue Groupe (5 sociétés)</span>
      </span>
      <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-md bg-white/10 text-blue-100">${MARCHES_DATA.length}</span>
    </button>
  `;

  let selectHtml = `<option value="ALL" ${allSelected ? 'selected' : ''}>Entreprise : Groupe (toutes les sociétés)</option>`;

  COMPANIES.forEach((c, index) => {
    const isSelected = state.company === c.id;
    const count = MARCHES_DATA.filter(m => m.companyId === c.id).length;
    listHtml += `
      <button type="button" onclick="selectCompany('${c.id}')" class="group w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs transition ${isSelected ? 'bg-white/15 text-white font-semibold ring-1 ring-white/40' : 'text-blue-100/80 hover:bg-white/10 hover:text-white'}">
        <span class="flex items-center gap-2.5 truncate">
          <span class="relative w-7 h-7 rounded-lg bg-white/[0.05] group-hover:bg-white/10 flex items-center justify-center shrink-0 transition">
            ${getSubsidiaryBuildingSvgIcon()}
            <span class="w-2.5 h-2.5 rounded-full ring-2 ring-[#0f294a] absolute -top-0.5 -right-0.5 shadow-sm" style="background-color: ${c.color}; box-shadow: 0 0 8px ${c.color}88;"></span>
          </span>
          <span class="truncate"><span class="text-blue-300/60 font-semibold mr-1">${index + 1}.</span>${c.name}</span>
        </span>
        <span class="text-[11px] opacity-80 font-medium">${count}</span>
      </button>
    `;
    selectHtml += `<option value="${c.id}" ${isSelected ? 'selected' : ''}>${index + 1}. ${c.name}</option>`;
  });

  listEl.innerHTML = listHtml;
  selectEl.innerHTML = selectHtml;
}

function setVariant(variantKey) {
  state.variant = variantKey;

  const badgeEl = document.getElementById('activeVariantBadge');
  const sidebarEl = document.getElementById('sidebar');
  const mainEl = document.getElementById('mainContent');
  const quickBar = document.getElementById('quickActionsBar');
  const subtitleEl = document.getElementById('welcomeSubtitle');
  const chartCard = document.getElementById('chartCard');
  const tableCard = document.getElementById('tableCard');

  badgeEl.textContent = 'Focus Opérationnel · Socle Validé';
  sidebarEl.classList.add('lg:w-60');
  mainEl.classList.add('space-y-4');
  chartCard.classList.add('xl:col-span-4');
  tableCard.classList.add('xl:col-span-8');
  quickBar.classList.add('ring-2', 'ring-[#ea9a10]/60', 'p-1.5', 'rounded-2xl', 'bg-amber-50/60');
  subtitleEl.textContent = 'Vue opérationnelle compacte : accès direct aux marchés actifs, filtres rapides par statut et tableau élargi.';

  renderSidebarNav();
  renderKPIs();
  renderLifecycle();
  renderChartAndTable();
}

function selectCompany(companyId) {
  state.company = companyId;
  const scope = getCompanyById(companyId);
  const label = state.company === 'ALL' ? 'Vue Groupe (toutes les sociétés)' : scope.name;

  document.getElementById('breadcrumbScope').textContent = label;
  document.getElementById('scopePill').textContent = 'Périmètre : ' + (state.company === 'ALL' ? 'Vue Groupe' : scope.short);
  document.getElementById('welcomeScopeTitle').textContent = state.company === 'ALL' ? 'Vue Groupe (5 sociétés)' : scope.name;

  renderCompanySelectors();
  renderKPIs();
  renderLifecycle();
  renderChartAndTable();
}

function filterByStatus(statusKey) {
  state.statusFilter = (state.statusFilter === statusKey && statusKey !== 'ALL') ? 'ALL' : statusKey;
  renderKPIs();
  renderTable();
}

function filterByPhase(phaseNum) {
  state.phaseFilter = (state.phaseFilter === phaseNum) ? 'ALL' : phaseNum;
  renderLifecycle();
  renderTable();
}

function resetAllFilters() {
  state.statusFilter = 'ALL';
  state.phaseFilter = 'ALL';
  const searchEl = document.getElementById('searchInput');
  if (searchEl) searchEl.value = '';
  renderKPIs();
  renderLifecycle();
  renderTable();
}

function handleNavClick(navId) {
  toggleMobileSidebar(false);
  if (navId !== 'dashboard') {
    const banner = document.getElementById('alertBanner');
    banner.classList.add('ring-2', 'ring-[#ea9a10]');
    setTimeout(() => banner.classList.remove('ring-2', 'ring-[#ea9a10]'), 900);
  }
}

function toggleMobileSidebar(open) {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (open) {
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
  } else {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
  }
}

function renderKPIs() {
  const scoped = getScopedMarches();
  const total = scoped.length;
  document.getElementById('totalCountAction').textContent = total;

  const counts = {
    en_cours: scoped.filter(m => m.status === 'en_cours').length,
    cloture: scoped.filter(m => m.status === 'cloture').length,
    gagne: scoped.filter(m => m.status === 'gagne').length,
    perdu_annule: scoped.filter(m => m.status === 'perdu_annule').length,
    en_attente: scoped.filter(m => m.status === 'en_attente').length
  };

  const decided = counts.gagne + counts.perdu_annule;
  const winRate = decided > 0 ? Math.round((counts.gagne / decided) * 100) + ' %' : '—';
  const winRateSub = decided > 0 ? `${counts.gagne} gagné(s) sur ${decided} décidé(s)` : 'Aucun marché décidé';

  const cardPadding = state.variant === 'B' ? 'p-3.5' : (state.variant === 'C' ? 'p-5' : 'p-4');
  const numberSize = state.variant === 'C' ? 'text-3xl' : 'text-2xl';

  let html = '';
  Object.keys(STATUS_META).forEach(key => {
    const meta = STATUS_META[key];
    const isSelected = state.statusFilter === key;
    html += `
      <button type="button" onclick="filterByStatus('${key}')" class="smooth-card text-left rounded-2xl bg-white border ${isSelected ? 'border-[#0f294a] ring-2 ring-[#0f294a]/20' : 'border-slate-200/90'} border-t-4 ${meta.borderTop} ${cardPadding} shadow-sm flex flex-col justify-between">
        <div class="text-xs font-semibold text-slate-500">${meta.label}</div>
        <div class="mt-2 flex items-end justify-between">
          <span class="${numberSize} font-extrabold text-slate-900">${counts[key]}</span>
          <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full ${meta.badgeClass}">
            ${total > 0 ? Math.round((counts[key] / total) * 100) : 0}%
          </span>
        </div>
      </button>
    `;
  });

  html += `
    <div class="smooth-card rounded-2xl bg-white border border-slate-200/90 border-t-4 border-t-[#ea9a10] ${cardPadding} shadow-sm flex flex-col justify-between">
      <div class="text-xs font-semibold text-slate-500">Taux de réussite</div>
      <div class="mt-2">
        <div class="${numberSize} font-extrabold text-slate-900">${winRate}</div>
        <div class="text-[11px] text-slate-500 mt-1 truncate">${winRateSub}</div>
      </div>
    </div>
  `;

  document.getElementById('kpiGrid').innerHTML = html;
}

function renderLifecycle() {
  const scoped = getScopedMarches();
  const cardPadding = state.variant === 'B' ? 'p-3.5' : (state.variant === 'C' ? 'p-5' : 'p-4');
  const labelEl = document.getElementById('activePhaseFilterLabel');
  labelEl.textContent = state.phaseFilter === 'ALL' ? 'Toutes les phases' : `Filtre actif : Phase ${state.phaseFilter}`;

  document.getElementById('lifecycleGrid').innerHTML = PHASES.map(p => {
    const count = scoped.filter(m => m.phase === p.num).length;
    const selected = state.phaseFilter === p.num;
    return `
      <button type="button" onclick="filterByPhase(${p.num})" class="smooth-card text-left rounded-2xl bg-white border ${selected ? 'border-[#ea9a10] ring-2 ring-[#ea9a10]/25' : 'border-slate-200/90'} border-t-4 ${p.accent} ${cardPadding} shadow-sm flex flex-col justify-between">
        <div>
          <div class="text-xs font-bold ${p.textAccent}">${p.title}</div>
          <div class="text-[11px] text-slate-500 mt-0.5">${p.desc}</div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
          <span class="text-2xl font-extrabold text-slate-900">${count}</span>
          <span class="text-[11px] text-slate-400">marché(s)</span>
        </div>
      </button>
    `;
  }).join('');
}

function renderChartAndTable() {
  renderDoughnutChart();
  renderTable();
}

function renderDoughnutChart() {
  const scoped = getScopedMarches();
  const total = scoped.length;

  document.getElementById('chartTotalBadge').textContent = `Total : ${total}`;
  document.getElementById('doughnutCenterTotal').textContent = total;

  const keys = Object.keys(STATUS_META);
  const values = keys.map(k => scoped.filter(m => m.status === k).length);
  const colors = keys.map(k => STATUS_META[k].color);

  document.getElementById('chartLegend').innerHTML = keys.map((k, idx) => {
    const val = values[idx];
    const pct = total > 0 ? Math.round((val / total) * 100) : 0;
    return `
      <div class="flex items-center justify-between py-0.5">
        <span class="flex items-center gap-2 text-slate-700 font-medium">
          <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color:${colors[idx]}"></span>
          <span>${STATUS_META[k].label}</span>
        </span>
        <span class="font-bold text-slate-900">${val} <span class="text-slate-400 font-normal">(${pct}%)</span></span>
      </div>
    `;
  }).join('');

  const ctx = document.getElementById('marchesDoughnutChart').getContext('2d');
  if (doughnutChartInstance) {
    doughnutChartInstance.destroy();
  }

  doughnutChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: keys.map(k => STATUS_META[k].label),
      datasets: [{
        data: total > 0 ? values : [1],
        backgroundColor: total > 0 ? colors : ['#e2e8f0'],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: { display: false },
        tooltip: {
          enabled: total > 0,
          callbacks: {
            label: (context) => ` ${context.label}: ${context.raw} marché(s)`
          }
        }
      }
    }
  });
}

function renderTable() {
  const rows = getFilteredMarches();
  const tbody = document.getElementById('marchesTableBody');
  const rowPadding = state.variant === 'B' ? 'py-2.5 px-3.5' : 'py-3.5 px-4';

  const parts = [];
  if (state.company !== 'ALL') parts.push(getCompanyById(state.company).short);
  if (state.statusFilter !== 'ALL') parts.push('Statut : ' + STATUS_META[state.statusFilter].label);
  if (state.phaseFilter !== 'ALL') parts.push('Phase ' + state.phaseFilter);
  document.getElementById('tableFilterSummary').textContent = parts.length
    ? 'Filtres actifs — ' + parts.join(' · ')
    : 'Affichage de tous les dossiers du périmètre actif';

  if (rows.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="py-8 px-4 text-center text-sm text-slate-500">
          Aucun marché ne correspond aux critères sélectionnés dans ce squelette.
        </td>
      </tr>
    `;
  } else {
    tbody.innerHTML = rows.map(m => {
      const company = getCompanyById(m.companyId);
      const statusMeta = STATUS_META[m.status];
      return `
        <tr class="hover:bg-slate-50/90 transition">
          <td class="${rowPadding} font-bold text-[#0f294a] whitespace-nowrap">${m.numero}</td>
          <td class="${rowPadding} text-slate-800 font-medium min-w-[200px]">${m.objet}</td>
          <td class="${rowPadding} whitespace-nowrap">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
              <span class="w-2 h-2 rounded-full" style="background-color:${company.color}"></span>
              <span>${company.short}</span>
            </span>
          </td>
          <td class="${rowPadding} text-xs font-semibold text-slate-600 whitespace-nowrap">Phase ${m.phase}</td>
          <td class="${rowPadding} whitespace-nowrap">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full border ${statusMeta.badgeClass}">
              ${statusMeta.label}
            </span>
          </td>
          <td class="${rowPadding} text-right font-bold text-slate-900 whitespace-nowrap">${formatMAD(m.amount)}</td>
          <td class="${rowPadding} text-xs text-slate-500 whitespace-nowrap">${m.date}</td>
        </tr>
      `;
    }).join('');
  }

  const totalAmount = rows.reduce((sum, item) => sum + item.amount, 0);
  document.getElementById('tableCountText').textContent = `${rows.length} marché(s) affiché(s)`;
  document.getElementById('tableTotalAmount').textContent = formatMAD(totalAmount);
}

function updateClock() {
  const now = new Date();
  const dateStr = now.toLocaleDateString('fr-FR');
  const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  const dateEl = document.getElementById('liveDate');
  const clockEl = document.getElementById('liveClock');
  if (dateEl) dateEl.textContent = dateStr;
  if (clockEl) clockEl.textContent = timeStr;
}

window.addEventListener('DOMContentLoaded', () => {
  updateClock();
  setInterval(updateClock, 1000);
  renderCompanySelectors();
  setVariant('B');
});
