<?php
/**
 * Template Name: Où me retrouver
 * Description: Page salons, événements et points de vente - Atelier 2 Jeanne
 */

// Enqueue Leaflet + page styles/scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'leaflet-css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        [],
        '1.9.4'
    );
    wp_enqueue_style(
        'oumr-page',
        get_stylesheet_directory_uri() . '/oumeretrouver.css',
        ['leaflet-css'],
        '1.0'
    );
    wp_enqueue_script(
        'leaflet-js',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        '1.9.4',
        true
    );
}, 10);

get_header();
?>

<div class="oumr-page">

  <!-- BREADCRUMB -->
  <nav class="oumr-breadcrumb">
    <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
    <span class="oumr-sep">»</span>
    Où me retrouver
  </nav>

  <!-- HERO -->
  <section class="oumr-hero">
    <span class="oumr-section-label">Salons &amp; points de vente</span>
    <h1 class="oumr-hero__title">
      Où me <span class="oumr-accent">retrouver.</span>
    </h1>
    <p class="oumr-hero__lead">
      Mes créations sont visibles dans plusieurs boutiques partenaires de Grenoble et de l'Isère, et je participe régulièrement aux salons de créateurs et marchés artisanaux de la région. Voici les prochains rendez-vous.
    </p>
    <div class="oumr-chips">
      <span class="oumr-chip">Salons &amp; événements</span>
      <span class="oumr-chip">Points de vente partenaires</span>
      <span class="oumr-chip">Atelier sur RDV</span>
    </div>
  </section>

  <!-- PROCHAIN ÉVÉNEMENT FEATURED -->
  <section class="oumr-featured">
    <div class="oumr-featured__inner">
      <div class="oumr-featured__visual">
        <div class="oumr-featured__date-badge">
          <span class="oumr-day">15</span>
          <span class="oumr-month">Déc</span>
        </div>
        <img
          src="https://atelier2jeanne.fr/wp-content/uploads/2025/11/marche_artisanal_meylan-rotated.jpg"
          alt="Marché de Noël Meylan - Atelier 2 Jeanne"
          loading="eager"
        >
      </div>
      <div class="oumr-featured__content">
        <span class="oumr-featured__tag">Prochain rendez-vous</span>
        <h2>Marchés de Noël 2025 — Meylan</h2>
        <ul class="oumr-featured__details">
          <li><span class="oumr-icon">📅</span> Du 15 au 17 décembre 2025</li>
          <li><span class="oumr-icon">📍</span> Place du marché, Meylan (38240)</li>
          <li><span class="oumr-icon">🕐</span> 10h - 19h tous les jours</li>
          <li><span class="oumr-icon">🎁</span> Idéal pour vos cadeaux de fin d'année</li>
        </ul>
        <a href="#" class="oumr-btn-dark">En savoir plus</a>
      </div>
    </div>
  </section>

  <!-- FILTRES -->
  <div class="oumr-filters">
    <button class="oumr-filter active" data-filter="all">Tout afficher</button>
    <button class="oumr-filter" data-filter="salons">Salons &amp; événements</button>
    <button class="oumr-filter" data-filter="boutique">Points de vente</button>
  </div>

  <!-- ÉVÉNEMENTS -->
  <section class="oumr-events">
    <div class="oumr-events__head">
      <h2><span class="oumr-accent">Salons</span> &amp; événements à venir</h2>
      <span class="oumr-events__count">3 événements à venir · 1 passé</span>
    </div>

    <div class="oumr-events-grid">

      <!-- Event 1 -->
      <article class="oumr-card">
        <div class="oumr-card__visual">
          <span class="oumr-card__date">
            <span class="oumr-day">15</span>
            <span class="oumr-month">Déc</span>
          </span>
          <span class="oumr-card__tag salons">Marché de Noël</span>
          <img src="https://atelier2jeanne.fr/wp-content/uploads/2025/11/marche_artisanal_meylan-rotated.jpg" alt="Marché de Noël Meylan" loading="lazy">
        </div>
        <div class="oumr-card__body">
          <h3><a href="#">Marchés de Noël 2025 — Meylan</a></h3>
          <ul class="oumr-card__meta">
            <li><span class="oumr-icon">📍</span> Place du marché, Meylan</li>
            <li><span class="oumr-icon">🕐</span> 15-17 déc · 10h-19h</li>
          </ul>
          <div class="oumr-card__footer">
            <a href="#" class="oumr-card__link">En savoir plus</a>
          </div>
        </div>
      </article>

      <!-- Event 2 -->
      <article class="oumr-card">
        <div class="oumr-card__visual">
          <span class="oumr-card__date">
            <span class="oumr-day">22</span>
            <span class="oumr-month">Mar</span>
          </span>
          <span class="oumr-card__tag salons">Salon créateurs</span>
          <img src="https://images.unsplash.com/photo-1556905200-279565513a2d?w=900&q=80" alt="Salon des créateurs" loading="lazy">
        </div>
        <div class="oumr-card__body">
          <h3><a href="#">Salon des créateurs de Grenoble</a></h3>
          <ul class="oumr-card__meta">
            <li><span class="oumr-icon">📍</span> Halle Clémenceau, Grenoble</li>
            <li><span class="oumr-icon">🕐</span> 22-23 mars · 10h-18h</li>
          </ul>
          <div class="oumr-card__footer">
            <a href="#" class="oumr-card__link">En savoir plus</a>
          </div>
        </div>
      </article>

      <!-- Event 3 -->
      <article class="oumr-card">
        <div class="oumr-card__visual">
          <span class="oumr-card__date">
            <span class="oumr-day">12</span>
            <span class="oumr-month">Avr</span>
          </span>
          <span class="oumr-card__tag salons">Marché artisanal</span>
          <img src="https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=900&q=80" alt="Marché artisanal printemps" loading="lazy">
        </div>
        <div class="oumr-card__body">
          <h3><a href="#">Marché artisanal du printemps</a></h3>
          <ul class="oumr-card__meta">
            <li><span class="oumr-icon">📍</span> Saint-Martin-d'Hères</li>
            <li><span class="oumr-icon">🕐</span> 12 avril · 9h-17h</li>
          </ul>
          <div class="oumr-card__footer">
            <a href="#" class="oumr-card__link">En savoir plus</a>
          </div>
        </div>
      </article>

    </div>
  </section>

  <!-- POINTS DE VENTE -->
  <section class="oumr-shops">
    <div class="oumr-shops__inner">
      <div class="oumr-shops__head">
        <div>
          <span class="oumr-section-label" style="color:#C97B5C;">Points de vente</span>
          <h2><span class="oumr-accent-terra">Mes boutiques</span> partenaires</h2>
          <p>Mes créations sont disponibles à l'année dans ces boutiques partenaires de Grenoble et de l'agglomération.</p>
        </div>
      </div>

      <div class="oumr-shops-grid">

        <!-- Shop 1 -->
        <article class="oumr-shop">
          <div class="oumr-shop__visual">
            <img src="https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?w=400&q=80" alt="Rebelles Grenoble" loading="lazy">
          </div>
          <div class="oumr-shop__body">
            <h3>Rebelles Grenoble</h3>
            <div class="oumr-shop__city">Grenoble · 38000</div>
            <p class="oumr-shop__address">
              Concept-store engagé dédié aux créateurs locaux et à la mode responsable.
            </p>
            <p class="oumr-shop__hours">
              <strong>Horaires :</strong> Mardi - Samedi · 10h-19h
            </p>
            <div class="oumr-shop__actions">
              <a href="#">Voir l'article</a>
              <a href="#">Itinéraire</a>
            </div>
          </div>
        </article>

        <!-- Shop 2 -->
        <article class="oumr-shop">
          <div class="oumr-shop__visual">
            <img src="https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=400&q=80" alt="Atelier de Meylan" loading="lazy">
          </div>
          <div class="oumr-shop__body">
            <h3>Atelier de Meylan</h3>
            <div class="oumr-shop__city">Meylan · 38240</div>
            <p class="oumr-shop__address">
              Mon atelier personnel, ouvert sur rendez-vous pour découvrir les créations.
            </p>
            <p class="oumr-shop__hours">
              <strong>Horaires :</strong> Sur RDV · Mardi - Samedi
            </p>
            <div class="oumr-shop__actions">
              <a href="#">Prendre RDV</a>
              <a href="#">Itinéraire</a>
            </div>
          </div>
        </article>

      </div>
    </div>
  </section>

  <!-- CARTE -->
  <section class="oumr-map-section">
    <div class="oumr-map-section__inner">
      <div class="oumr-map-section__head">
        <span class="oumr-section-label">Sur la carte</span>
        <h2>Tous mes <span class="oumr-accent">rendez-vous</span> en un coup d'œil</h2>
        <p>Salons à venir, points de vente partenaires et atelier de Meylan, géolocalisés sur la carte.</p>
      </div>
      <div id="oumr-map"></div>
      <div class="oumr-map-legend">
        <div class="oumr-legend-item"><span class="oumr-pin atelier"></span> Atelier de Meylan</div>
        <div class="oumr-legend-item"><span class="oumr-pin event"></span> Salons à venir</div>
        <div class="oumr-legend-item"><span class="oumr-pin shop"></span> Points de vente</div>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="oumr-cta">
    <h2>Vous tenez une boutique <span class="oumr-accent">éco-responsable ?</span></h2>
    <p>
      Vous êtes commerçante et vous voulez exposer mes créations dans votre boutique ? Vous organisez un salon de créateurs ? Tissons le contact.
    </p>
    <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="oumr-btn">Tissons le contact</a>
  </section>

</div><!-- .oumr-page -->

<script>
(function() {
  // Filtres événements
  document.querySelectorAll('.oumr-filter').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.oumr-filter').forEach(function(b) {
        b.classList.remove('active');
      });
      btn.classList.add('active');

      var filter = btn.dataset.filter;
      document.querySelectorAll('.oumr-card').forEach(function(card) {
        var tag = card.querySelector('.oumr-card__tag');
        var isType = tag.classList.contains(filter);
        card.style.display = (filter === 'all' || isType) ? 'flex' : 'none';
      });
    });
  });

  // Carte Leaflet — attend que la lib soit chargée
  function initMap() {
    if (typeof L === 'undefined') {
      setTimeout(initMap, 100);
      return;
    }

    var map = L.map('oumr-map', {
      center: [45.205, 5.78],
      zoom: 11,
      scrollWheelZoom: false
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    function createIcon(type) {
      return L.divIcon({
        className: 'oumr-pin-wrapper',
        html: '<div class="oumr-pin-dot ' + type + '"></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
      });
    }

    L.marker([45.207, 5.785], { icon: createIcon('atelier') })
      .addTo(map)
      .bindPopup('<strong>Atelier de Jeanne</strong><br>Meylan, 38240<br><em>Sur rendez-vous</em>');

    L.marker([45.210, 5.781], { icon: createIcon('event') })
      .addTo(map)
      .bindPopup('<strong>Marché de Noël</strong><br>15-17 décembre<br>Meylan');

    L.marker([45.188, 5.724], { icon: createIcon('event') })
      .addTo(map)
      .bindPopup('<strong>Salon des créateurs</strong><br>22-23 mars<br>Halle Clémenceau');

    L.marker([45.190, 5.770], { icon: createIcon('event') })
      .addTo(map)
      .bindPopup('<strong>Marché artisanal</strong><br>12 avril<br>Saint-Martin-d\'Hères');

    L.marker([45.190, 5.726], { icon: createIcon('shop') })
      .addTo(map)
      .bindPopup('<strong>Rebelles Grenoble</strong><br>Concept-store local<br>Mardi-Samedi 10h-19h');
  }

  document.addEventListener('DOMContentLoaded', initMap);
})();
</script>

<?php get_footer(); ?>
