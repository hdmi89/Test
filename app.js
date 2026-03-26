/* =====================================================================
   TP Location — Application principale
   ===================================================================== */

'use strict';

/* ─── SVG ILLUSTRATIONS ────────────────────────────────────────────── */
const SVG = {
  miniPelle: (color = '#e67e22') => `
    <svg viewBox="0 0 200 160" xmlns="http://www.w3.org/2000/svg">
      <rect x="20" y="120" width="160" height="20" rx="10" fill="#2d3748"/>
      <rect x="30" y="115" width="140" height="12" rx="6" fill="#4a5568"/>
      <rect x="50" y="80" width="100" height="45" rx="8" fill="${color}"/>
      <rect x="70" y="55" width="60" height="30" rx="6" fill="${color}"/>
      <rect x="75" y="58" width="25" height="18" rx="3" fill="#85c1e9"/>
      <line x1="138" y1="68" x2="170" y2="30" stroke="${color === '#e67e22' ? '#d35400' : '#b7770d'}" stroke-width="8" stroke-linecap="round"/>
      <line x1="170" y1="30" x2="185" y2="70" stroke="#c0392b" stroke-width="6" stroke-linecap="round"/>
      <path d="M183 70 L195 80 L185 88 L175 80 Z" fill="#c0392b"/>
      <rect x="62" y="45" width="5" height="15" rx="2" fill="#4a5568"/>
      <rect x="58" y="90" width="50" height="12" rx="3" fill="${color === '#e67e22' ? '#d35400' : '#b7770d'}"/>
      <text x="83" y="100" font-size="7" fill="white" text-anchor="middle" font-weight="bold">LOVOL</text>
    </svg>`,

  chargeuse: (color = '#f39c12') => `
    <svg viewBox="0 0 200 160" xmlns="http://www.w3.org/2000/svg">
      <rect x="10" y="120" width="180" height="22" rx="11" fill="#2d3748"/>
      <rect x="20" y="114" width="160" height="14" rx="7" fill="#4a5568"/>
      <rect x="30" y="75" width="130" height="50" rx="8" fill="${color}"/>
      <rect x="60" y="50" width="70" height="30" rx="6" fill="${color}"/>
      <rect x="65" y="53" width="30" height="20" rx="3" fill="#85c1e9"/>
      <rect x="10" y="90" width="40" height="10" rx="3" fill="#95a5a6" transform="rotate(-10 10 90)"/>
      <path d="M10 85 L15 105 L50 105 L50 85 Z" fill="#7f8c8d"/>
      <rect x="30" y="83" width="50" height="12" rx="3" fill="${color === '#f39c12' ? '#e67e22' : '#d4870d'}"/>
      <text x="55" y="93" font-size="7" fill="white" text-anchor="middle" font-weight="bold">LOVOL</text>
    </svg>`,

  compacteur: (color = '#8e44ad') => `
    <svg viewBox="0 0 200 160" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="70" cy="130" rx="50" ry="18" fill="#2d3748"/>
      <ellipse cx="70" cy="128" rx="46" ry="15" fill="#4a5568"/>
      <rect x="30" y="80" width="80" height="55" rx="6" fill="${color}"/>
      <rect x="110" y="95" width="60" height="12" rx="6" fill="#2d3748"/>
      <rect x="160" y="70" width="12" height="40" rx="4" fill="#7f8c8d"/>
      <rect x="50" y="60" width="40" height="25" rx="5" fill="${color}"/>
      <rect x="53" y="63" width="20" height="15" rx="3" fill="#85c1e9"/>
      <rect x="35" y="88" width="55" height="12" rx="3" fill="rgba(0,0,0,.2)"/>
      <text x="62" y="98" font-size="7" fill="white" text-anchor="middle" font-weight="bold">BOMAG</text>
    </svg>`,
};

/* ─── EQUIPMENT DATA ────────────────────────────────────────────────── */
const EQUIPMENT = [
  {
    id: 1, category: 'mini-pelle', brand: 'Lovol', model: 'FR65E2',
    name: 'Mini Pelle Lovol FR65E2',
    subtitle: 'Classe 6,5 tonnes — Idéale chantier urbain',
    price: 290, available: true,
    specs: { Poids: '6 500 kg', Puissance: '46,5 kW', 'Profondeur de fouille': '3 960 mm', 'Capacité godet': '0,23 m³' },
    description: 'La Lovol FR65E2 est une mini pelle compacte et puissante, parfaite pour les travaux en milieu urbain. Faible empreinte au sol, accès limité. Cabine ergonomique avec climatisation. Idéale pour démolition légère, terrassement et pose de réseaux.',
    color: '#e67e22', svgKey: 'miniPelle',
  },
  {
    id: 2, category: 'mini-pelle', brand: 'Lovol', model: 'FR80E2',
    name: 'Mini Pelle Lovol FR80E2',
    subtitle: 'Classe 8 tonnes — Polyvalente et robuste',
    price: 340, available: true,
    specs: { Poids: '8 200 kg', Puissance: '55,4 kW', 'Profondeur de fouille': '4 430 mm', 'Capacité godet': '0,30 m³' },
    description: 'Avec ses 8 tonnes, la Lovol FR80E2 offre une polyvalence remarquable. Bras long pour accès difficile, système hydraulique haute performance. Parfaite pour terrassement, démolition et travaux de voirie.',
    color: '#e67e22', svgKey: 'miniPelle',
  },
  {
    id: 3, category: 'mini-pelle', brand: 'Lovol', model: 'FR130E2',
    name: 'Mini Pelle Lovol FR130E2',
    subtitle: 'Classe 13 tonnes — Grande capacité',
    price: 420, available: false,
    specs: { Poids: '13 000 kg', Puissance: '71 kW', 'Profondeur de fouille': '5 570 mm', 'Capacité godet': '0,52 m³' },
    description: 'La FR130E2 est adaptée aux chantiers de moyenne envergure. Sa puissance et sa profondeur de fouille en font un outil de choix pour les terrassements profonds et les travaux de fondation.',
    color: '#e67e22', svgKey: 'miniPelle',
  },
  {
    id: 4, category: 'mini-pelle', brand: 'Lovol', model: 'FR220E2',
    name: 'Pelle Lovol FR220E2',
    subtitle: 'Classe 22 tonnes — Chantiers lourds',
    price: 590, available: true,
    specs: { Poids: '22 000 kg', Puissance: '122 kW', 'Profondeur de fouille': '6 650 mm', 'Capacité godet': '0,91 m³' },
    description: "Destinée aux chantiers d'envergure, la FR220E2 allie puissance et finesse de contrôle. Système de gestion hydraulique intelligent pour réduire la consommation de carburant.",
    color: '#c0392b', svgKey: 'miniPelle',
  },
  {
    id: 5, category: 'mini-pelle', brand: 'Lovol', model: 'FR360E2',
    name: 'Grande Pelle Lovol FR360E2',
    subtitle: 'Classe 36 tonnes — Très gros chantiers',
    price: 820, available: true,
    specs: { Poids: '36 000 kg', Puissance: '206 kW', 'Profondeur de fouille': '7 540 mm', 'Capacité godet': '1,60 m³' },
    description: 'La FR360E2 est le fleuron de la gamme Lovol pour les grands travaux. Sa cabine pressurisée, ses écrans LCD et son système de surveillance à distance en font un engin haute technologie.',
    color: '#922b21', svgKey: 'miniPelle',
  },
  {
    id: 6, category: 'chargeuse', brand: 'Lovol', model: 'FL936H',
    name: 'Chargeuse Lovol FL936H',
    subtitle: 'Chargeur sur pneus 3,5 T',
    price: 310, available: true,
    specs: { Poids: '10 500 kg', Puissance: '92 kW', 'Capacité godet': '1,8 m³', 'Force d\'arrachement': '90 kN' },
    description: 'La chargeuse FL936H est idéale pour le chargement de matériaux, le déblaiement et le transport sur courte distance. Transmission hydrostatique, direction articulée et cabine confortable.',
    color: '#f39c12', svgKey: 'chargeuse',
  },
  {
    id: 7, category: 'chargeuse', brand: 'Lovol', model: 'FL956H',
    name: 'Chargeuse Lovol FL956H',
    subtitle: 'Chargeur sur pneus 5 T',
    price: 390, available: true,
    specs: { Poids: '16 500 kg', Puissance: '147 kW', 'Capacité godet': '2,7 m³', 'Force d\'arrachement': '145 kN' },
    description: 'Chargeuse robuste pour carrières, décharges et grands chantiers. Transmission à 4 vitesses, différentiel à glissement limité, cabine ROPS/FOPS.',
    color: '#e67e22', svgKey: 'chargeuse',
  },
  {
    id: 8, category: 'compacteur', brand: 'Bomag', model: 'BW 161 D',
    name: 'Compacteur Bomag BW 161 D',
    subtitle: 'Rouleau monocylindre 10 T',
    price: 260, available: true,
    specs: { Poids: '10 200 kg', Puissance: '93 kW', 'Largeur de travail': '2 130 mm', Fréquence: '30/35 Hz' },
    description: 'Rouleau vibrant monocylindre pour compactage de sols, remblais et sous-couches. Système ECONOMIZER pour compactage optimal sans surcompactage. Excellente visibilité depuis la cabine.',
    color: '#8e44ad', svgKey: 'compacteur',
  },
  {
    id: 9, category: 'compacteur', brand: 'Bomag', model: 'BW 219 DH',
    name: 'Compacteur Bomag BW 219 DH',
    subtitle: 'Rouleau tandem 19 T',
    price: 340, available: false,
    specs: { Poids: '19 000 kg', Puissance: '155 kW', 'Largeur de travail': '2 130 mm', Fréquence: '28/33 Hz' },
    description: 'Rouleau tandem haute performance pour compactage de couches de grave et enrobés. Aspersion automatique pour éviter le collage. Idéal pour voirie et aéroports.',
    color: '#6c3483', svgKey: 'compacteur',
  },
];

/* ─── STATE ─────────────────────────────────────────────────────────── */
let state = {
  filter: 'all',
  search: '',
  selectedEquipment: null,
};

/* ─── RENDER ─────────────────────────────────────────────────────────── */
function getFilteredEquipment() {
  return EQUIPMENT.filter(eq => {
    const matchFilter = state.filter === 'all' || eq.category === state.filter;
    const q = state.search.toLowerCase().trim();
    const matchSearch = !q || [eq.name, eq.brand, eq.model, eq.subtitle, eq.category]
      .some(s => s.toLowerCase().includes(q));
    return matchFilter && matchSearch;
  });
}

function renderEquipment() {
  const grid = document.getElementById('equipmentGrid');
  const list = getFilteredEquipment();

  if (!list.length) {
    grid.innerHTML = `
      <div class="no-results">
        <span class="no-results-icon">&#128269;</span>
        <p>Aucun engin ne correspond à votre recherche.</p>
      </div>`;
    return;
  }

  grid.innerHTML = list.map(eq => {
    const svgFn = SVG[eq.svgKey];
    const svgHtml = svgFn ? svgFn(eq.color) : '';
    const badgeClass = eq.available ? 'available' : 'rented';
    const badgeText  = eq.available ? 'Disponible' : 'Loué';

    return `
      <article class="equipment-card" data-id="${eq.id}">
        <div class="card-image">
          ${svgHtml}
          <span class="card-badge ${badgeClass}">${badgeText}</span>
        </div>
        <div class="card-body">
          <div class="card-category">${categoryLabel(eq.category)}</div>
          <h3 class="card-title">${eq.name}</h3>
          <p class="card-subtitle">${eq.subtitle}</p>
          <div class="card-specs">
            ${Object.entries(eq.specs).slice(0, 4).map(([k, v]) => `
              <div class="spec-item">
                <span class="spec-label">${k}</span>
                <span class="spec-value">${v}</span>
              </div>`).join('')}
          </div>
          <div class="card-footer">
            <div class="card-price">${eq.price}€ <span>/ jour</span></div>
            <div class="card-actions">
              <button class="btn btn-outline btn-sm" style="color:var(--dark);border-color:#e2e8f0"
                onclick="openDetailModal(${eq.id})">Détails</button>
              <button class="btn btn-primary btn-sm" ${eq.available ? '' : 'disabled style="opacity:.5;cursor:not-allowed"'}
                onclick="${eq.available ? `openBookingModal(${eq.id})` : ''}">
                ${eq.available ? 'Réserver' : 'Indisponible'}
              </button>
            </div>
          </div>
        </div>
      </article>`;
  }).join('');
}

function categoryLabel(cat) {
  return { 'mini-pelle': 'Mini Pelle / Pelle', chargeuse: 'Chargeuse', compacteur: 'Compacteur' }[cat] || cat;
}

/* ─── FILTERS & SEARCH ────────────────────────────────────────────── */
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.filter = btn.dataset.filter;
    renderEquipment();
  });
});

document.getElementById('searchInput').addEventListener('input', e => {
  state.search = e.target.value;
  renderEquipment();
});

/* ─── DETAIL MODAL ───────────────────────────────────────────────── */
function openDetailModal(id) {
  const eq = EQUIPMENT.find(e => e.id === id);
  if (!eq) return;
  state.selectedEquipment = eq;

  const svgFn = SVG[eq.svgKey];
  const svgHtml = svgFn ? svgFn(eq.color) : '';

  document.getElementById('detailContent').innerHTML = `
    <div class="detail-header">
      <div class="detail-image">${svgHtml}</div>
      <div class="detail-info">
        <div class="card-category">${categoryLabel(eq.category)}</div>
        <h2>${eq.name}</h2>
        <p class="detail-sub">${eq.subtitle}</p>
        <span class="price-badge" style="font-size:.95rem">${eq.price}€ / jour HT</span>
        <p style="margin-top:.75rem;font-size:.85rem;color:${eq.available ? 'var(--success)' : '#e74c3c'};font-weight:700">
          ${eq.available ? '&#9679; Disponible immédiatement' : '&#9679; Actuellement loué'}
        </p>
      </div>
    </div>
    <h3 style="margin-bottom:.75rem;font-size:1rem">Caractéristiques techniques</h3>
    <div class="detail-specs-grid">
      ${Object.entries(eq.specs).map(([k, v]) => `
        <div class="detail-spec">
          <div class="detail-spec-label">${k}</div>
          <div class="detail-spec-value">${v}</div>
        </div>`).join('')}
    </div>
    <p class="detail-description">${eq.description}</p>
    <div class="detail-actions">
      ${eq.available
        ? `<button class="btn btn-primary btn-lg" onclick="closeDetailModal();openBookingModal(${eq.id})">Réserver cet engin</button>`
        : `<button class="btn btn-outline" style="color:var(--dark);border-color:#e2e8f0" disabled>Indisponible</button>`}
      <button class="btn btn-outline" style="color:var(--dark);border-color:#e2e8f0" onclick="closeDetailModal()">Fermer</button>
    </div>`;

  document.getElementById('detailModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDetailModal() {
  document.getElementById('detailModal').classList.remove('open');
  document.body.style.overflow = '';
}

/* ─── BOOKING MODAL ─────────────────────────────────────────────── */
function openBookingModal(id) {
  const eq = EQUIPMENT.find(e => e.id === id);
  if (!eq) return;
  state.selectedEquipment = eq;

  document.getElementById('modalSubtitle').textContent = `${eq.name} — ${eq.price}€ / jour HT`;
  document.getElementById('bookingForm').reset();
  updatePriceSummary();

  // Set min date to today
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('startDate').min = today;
  document.getElementById('endDate').min = today;

  document.getElementById('bookingModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('bookingModal').classList.remove('open');
  document.body.style.overflow = '';
}

/* Price calculation */
['startDate', 'endDate'].forEach(id => {
  document.getElementById(id).addEventListener('change', updatePriceSummary);
});

function updatePriceSummary() {
  const eq = state.selectedEquipment;
  const start = document.getElementById('startDate').value;
  const end   = document.getElementById('endDate').value;

  if (!eq || !start || !end) {
    ['durationDisplay', 'dailyRateDisplay', 'totalDisplay'].forEach(id => {
      document.getElementById(id).textContent = '—';
    });
    return;
  }

  const days = Math.max(1, Math.ceil((new Date(end) - new Date(start)) / 86400000) + 1);
  const total = days * eq.price;

  document.getElementById('durationDisplay').textContent = `${days} jour${days > 1 ? 's' : ''}`;
  document.getElementById('dailyRateDisplay').textContent = `${eq.price}€`;
  document.getElementById('totalDisplay').textContent = `${total}€ HT`;

  // Ensure end >= start
  if (end < start) {
    document.getElementById('endDate').value = start;
    updatePriceSummary();
  }
}

/* Booking submit */
function submitBooking(e) {
  e.preventDefault();
  const eq = state.selectedEquipment;
  const start = document.getElementById('startDate').value;
  const end   = document.getElementById('endDate').value;

  if (!start || !end || end < start) {
    alert('Veuillez sélectionner des dates valides (fin ≥ début).');
    return;
  }

  const ref = 'TPL-' + Date.now().toString(36).toUpperCase();
  closeModal();
  document.getElementById('successRef').textContent = `Référence de demande : ${ref}`;
  document.getElementById('successModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
  document.getElementById('successModal').classList.remove('open');
  document.body.style.overflow = '';
}

/* ─── CONTACT FORM ────────────────────────────────────────────────── */
function submitContactForm(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type=submit]');
  const orig = btn.textContent;
  btn.textContent = 'Message envoyé !';
  btn.disabled = true;
  btn.style.background = 'var(--success)';
  e.target.reset();
  setTimeout(() => {
    btn.textContent = orig;
    btn.disabled = false;
    btn.style.background = '';
  }, 3500);
}

/* ─── CLOSE MODALS ON OVERLAY CLICK ──────────────────────────────── */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
});

/* ─── CLOSE ON ESCAPE ────────────────────────────────────────────── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
});

/* ─── MOBILE MENU ────────────────────────────────────────────────── */
document.getElementById('hamburger').addEventListener('click', () => {
  document.getElementById('mobileMenu').classList.toggle('open');
});

function closeMobileMenu() {
  document.getElementById('mobileMenu').classList.remove('open');
}

/* ─── SMOOTH SCROLL HELPER ───────────────────────────────────────── */
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}

/* ─── INIT ───────────────────────────────────────────────────────── */
renderEquipment();
