<?php
/**
 * Shortcodes administrables via WPBakery
 *
 * [oumr_hero]
 * [oumr_featured_event]
 * [oumr_events_grid]
 * [oumr_shops_grid]
 * [oumr_map]
 * [oumr_cta]
 */

// ──────────────────────────────────────────────
// [oumr_hero] — Section d'introduction
// ──────────────────────────────────────────────
add_shortcode('oumr_hero', function($atts) {
    $a = shortcode_atts([
        'label'  => 'Salons &amp; points de vente',
        'title'  => 'Où me retrouver.',
        'accent' => 'retrouver.',
        'lead'   => 'Mes créations sont visibles dans plusieurs boutiques partenaires de Grenoble et de l\'Isère, et je participe régulièrement aux salons de créateurs et marchés artisanaux de la région. Voici les prochains rendez-vous.',
        'chips'  => 'Salons & événements,Points de vente partenaires,Atelier sur RDV',
    ], $atts);

    $chips_html = '';
    foreach (array_map('trim', explode(',', $a['chips'])) as $chip) {
        $chips_html .= '<span class="oumr-chip">' . esc_html($chip) . '</span>';
    }

    // Remplacer la partie "accent" dans le titre
    $title_safe   = esc_html($a['title']);
    $accent_safe  = esc_html($a['accent']);
    $title_html   = str_replace($accent_safe, '<span class="oumr-accent">' . $accent_safe . '</span>', $title_safe);

    ob_start(); ?>
    <section class="oumr-hero">
      <span class="oumr-section-label"><?php echo wp_kses_post($a['label']); ?></span>
      <h1 class="oumr-hero__title"><?php echo $title_html; ?></h1>
      <?php if ($a['lead']): ?>
      <p class="oumr-hero__lead"><?php echo esc_html($a['lead']); ?></p>
      <?php endif; ?>
      <div class="oumr-chips"><?php echo $chips_html; ?></div>
    </section>
    <?php return ob_get_clean();
});

// ──────────────────────────────────────────────
// [oumr_featured_event] — Bandeau événement mis en avant
// Auto-détecte l'événement coché "mis en avant", ou utilise id=""
// ──────────────────────────────────────────────
add_shortcode('oumr_featured_event', function($atts) {
    $a = shortcode_atts(['id' => 0], $atts);

    if ($a['id']) {
        $event = get_post(intval($a['id']));
    } else {
        $results = get_posts([
            'post_type'      => 'oumr_event',
            'posts_per_page' => 1,
            'meta_query'     => [['key' => 'oumr_is_featured', 'value' => '1']],
        ]);
        $event = $results ? $results[0] : null;
    }

    if (!$event) return '';

    $jour    = get_post_meta($event->ID, 'oumr_jour_num',   true);
    $mois    = get_post_meta($event->ID, 'oumr_mois_court', true);
    $debut   = get_post_meta($event->ID, 'oumr_date_debut', true);
    $fin     = get_post_meta($event->ID, 'oumr_date_fin',   true);
    $horaire = get_post_meta($event->ID, 'oumr_horaires',   true);
    $adresse = get_post_meta($event->ID, 'oumr_adresse',    true);
    $lien    = get_post_meta($event->ID, 'oumr_lien_url',   true);
    $img     = get_the_post_thumbnail_url($event->ID, 'large');
    $date_txt = $fin ? 'Du ' . $debut . ' au ' . $fin : $debut;

    ob_start(); ?>
    <section class="oumr-featured">
      <div class="oumr-featured__inner">
        <div class="oumr-featured__visual">
          <div class="oumr-featured__date-badge">
            <span class="oumr-day"><?php echo esc_html($jour); ?></span>
            <span class="oumr-month"><?php echo esc_html($mois); ?></span>
          </div>
          <?php if ($img): ?>
          <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($event->post_title); ?>" loading="eager">
          <?php endif; ?>
        </div>
        <div class="oumr-featured__content">
          <span class="oumr-featured__tag">Prochain rendez-vous</span>
          <h2><?php echo esc_html($event->post_title); ?></h2>
          <ul class="oumr-featured__details">
            <?php if ($date_txt): ?><li><span class="oumr-icon">📅</span> <?php echo esc_html($date_txt); ?></li><?php endif; ?>
            <?php if ($adresse):  ?><li><span class="oumr-icon">📍</span> <?php echo esc_html($adresse); ?></li><?php endif; ?>
            <?php if ($horaire):  ?><li><span class="oumr-icon">🕐</span> <?php echo esc_html($horaire); ?></li><?php endif; ?>
          </ul>
          <?php if ($lien): ?>
          <a href="<?php echo esc_url($lien); ?>" class="oumr-btn-dark">En savoir plus</a>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php return ob_get_clean();
});

// ──────────────────────────────────────────────
// [oumr_events_grid] — Grille des événements avec filtres
// ──────────────────────────────────────────────
add_shortcode('oumr_events_grid', function($atts) {
    $a = shortcode_atts([
        'count'          => 12,
        'show_filters'   => 'yes',
    ], $atts);

    $events = get_posts([
        'post_type'      => 'oumr_event',
        'posts_per_page' => intval($a['count']),
        'orderby'        => 'menu_order date',
        'order'          => 'ASC',
    ]);

    if (empty($events)) return '';

    $upcoming = array_filter($events, fn($e) => !get_post_meta($e->ID, 'oumr_is_past', true));
    $past     = array_filter($events, fn($e) =>  get_post_meta($e->ID, 'oumr_is_past', true));
    $count_str = count($upcoming) . ' événement' . (count($upcoming) > 1 ? 's' : '') . ' à venir';
    if (count($past)) {
        $count_str .= ' · ' . count($past) . ' passé' . (count($past) > 1 ? 's' : '');
    }

    ob_start();

    if ($a['show_filters'] === 'yes'): ?>
    <div class="oumr-filters">
      <button class="oumr-filter active" data-filter="all">Tout afficher</button>
      <button class="oumr-filter" data-filter="salons">Salons &amp; événements</button>
      <button class="oumr-filter" data-filter="boutique">Points de vente</button>
    </div>
    <?php endif; ?>

    <section class="oumr-events">
      <div class="oumr-events__head">
        <h2><span class="oumr-accent">Salons</span> &amp; événements à venir</h2>
        <span class="oumr-events__count"><?php echo esc_html($count_str); ?></span>
      </div>
      <div class="oumr-events-grid">
        <?php foreach ($events as $event):
            $is_past  = get_post_meta($event->ID, 'oumr_is_past',    true);
            $jour     = get_post_meta($event->ID, 'oumr_jour_num',   true);
            $mois     = get_post_meta($event->ID, 'oumr_mois_court', true);
            $type     = get_post_meta($event->ID, 'oumr_type_tag',   true) ?: 'salons';
            $tag_lbl  = get_post_meta($event->ID, 'oumr_tag_label',  true);
            $lieu     = get_post_meta($event->ID, 'oumr_lieu',       true);
            $horaire  = get_post_meta($event->ID, 'oumr_horaires',   true);
            $lien     = get_post_meta($event->ID, 'oumr_lien_url',   true) ?: '#';
            $img      = get_the_post_thumbnail_url($event->ID, 'medium_large');
        ?>
        <article class="oumr-card">
          <div class="oumr-card__visual">
            <span class="oumr-card__date<?php echo $is_past ? ' past' : ''; ?>">
              <span class="oumr-day"><?php echo esc_html($jour); ?></span>
              <span class="oumr-month"><?php echo esc_html($mois); ?></span>
            </span>
            <span class="oumr-card__tag <?php echo esc_attr($type); ?>"><?php echo esc_html($tag_lbl); ?></span>
            <?php if ($img): ?>
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($event->post_title); ?>" loading="lazy">
            <?php endif; ?>
          </div>
          <div class="oumr-card__body">
            <h3><a href="<?php echo esc_url($lien); ?>"><?php echo esc_html($event->post_title); ?></a></h3>
            <ul class="oumr-card__meta">
              <?php if ($lieu):    ?><li><span class="oumr-icon">📍</span> <?php echo esc_html($lieu); ?></li><?php endif; ?>
              <?php if ($horaire): ?><li><span class="oumr-icon">🕐</span> <?php echo esc_html($horaire); ?></li><?php endif; ?>
            </ul>
            <div class="oumr-card__footer">
              <a href="<?php echo esc_url($lien); ?>" class="oumr-card__link">En savoir plus</a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <script>
    (function() {
      document.querySelectorAll('.oumr-filter').forEach(function(btn) {
        btn.addEventListener('click', function() {
          document.querySelectorAll('.oumr-filter').forEach(function(b) { b.classList.remove('active'); });
          btn.classList.add('active');
          var filter = btn.dataset.filter;
          document.querySelectorAll('.oumr-card').forEach(function(card) {
            var tag = card.querySelector('.oumr-card__tag');
            card.style.display = (filter === 'all' || tag.classList.contains(filter)) ? 'flex' : 'none';
          });
        });
      });
    })();
    </script>
    <?php return ob_get_clean();
});

// ──────────────────────────────────────────────
// [oumr_shops_grid] — Grille des points de vente
// ──────────────────────────────────────────────
add_shortcode('oumr_shops_grid', function($atts) {
    $a = shortcode_atts([
        'title' => 'Mes boutiques partenaires',
        'intro' => 'Mes créations sont disponibles à l\'année dans ces boutiques partenaires de Grenoble et de l\'agglomération.',
    ], $atts);

    $shops = get_posts([
        'post_type'      => 'oumr_shop',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ]);

    if (empty($shops)) return '';

    ob_start(); ?>
    <section class="oumr-shops">
      <div class="oumr-shops__inner">
        <div class="oumr-shops__head">
          <div>
            <span class="oumr-section-label" style="color:#C97B5C;">Points de vente</span>
            <h2><span class="oumr-accent-terra"><?php echo esc_html($a['title']); ?></span></h2>
            <?php if ($a['intro']): ?><p><?php echo esc_html($a['intro']); ?></p><?php endif; ?>
          </div>
        </div>
        <div class="oumr-shops-grid">
          <?php foreach ($shops as $shop):
              $ville    = get_post_meta($shop->ID, 'oumr_ville',       true);
              $cp       = get_post_meta($shop->ID, 'oumr_cp',          true);
              $desc     = get_post_meta($shop->ID, 'oumr_description', true);
              $horaires = get_post_meta($shop->ID, 'oumr_horaires',    true);
              $lien     = get_post_meta($shop->ID, 'oumr_lien_url',    true);
              $itin     = get_post_meta($shop->ID, 'oumr_itineraire',  true);
              $img      = get_the_post_thumbnail_url($shop->ID, 'medium');
          ?>
          <article class="oumr-shop">
            <div class="oumr-shop__visual">
              <?php if ($img): ?>
              <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($shop->post_title); ?>" loading="lazy">
              <?php endif; ?>
            </div>
            <div class="oumr-shop__body">
              <h3><?php echo esc_html($shop->post_title); ?></h3>
              <?php if ($ville): ?>
              <div class="oumr-shop__city">
                <?php echo esc_html($ville); ?><?php if ($cp) echo ' · ' . esc_html($cp); ?>
              </div>
              <?php endif; ?>
              <?php if ($desc): ?><p class="oumr-shop__address"><?php echo esc_html($desc); ?></p><?php endif; ?>
              <?php if ($horaires): ?>
              <p class="oumr-shop__hours"><strong>Horaires :</strong> <?php echo esc_html($horaires); ?></p>
              <?php endif; ?>
              <div class="oumr-shop__actions">
                <?php if ($lien): ?><a href="<?php echo esc_url($lien); ?>">Voir l'article</a><?php endif; ?>
                <?php if ($itin): ?><a href="<?php echo esc_url($itin); ?>" target="_blank" rel="noopener">Itinéraire</a><?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php return ob_get_clean();
});

// ──────────────────────────────────────────────
// [oumr_map] — Carte Leaflet dynamique
// Les marqueurs sont générés depuis les CPTs
// ──────────────────────────────────────────────
add_shortcode('oumr_map', function($atts) {
    $a = shortcode_atts([
        'atelier_lat' => '45.207',
        'atelier_lng' => '5.785',
        'atelier_nom' => 'Atelier de Jeanne',
        'atelier_cp'  => 'Meylan, 38240',
        'center_lat'  => '45.205',
        'center_lng'  => '5.78',
        'zoom'        => '11',
    ], $atts);

    $markers = [];

    // Atelier (fixe, configurable via paramètres du shortcode)
    $markers[] = [
        'lat'   => (float) $a['atelier_lat'],
        'lng'   => (float) $a['atelier_lng'],
        'type'  => 'atelier',
        'popup' => '<strong>' . esc_html($a['atelier_nom']) . '</strong><br>' . esc_html($a['atelier_cp']) . '<br><em>Sur rendez-vous</em>',
    ];

    // Événements à venir
    $events = get_posts(['post_type' => 'oumr_event', 'posts_per_page' => -1]);
    foreach ($events as $event) {
        if (get_post_meta($event->ID, 'oumr_is_past', true)) continue;
        $lat = (float) get_post_meta($event->ID, 'oumr_lat', true);
        $lng = (float) get_post_meta($event->ID, 'oumr_lng', true);
        if (!$lat || !$lng) continue;
        $jour = get_post_meta($event->ID, 'oumr_jour_num',   true);
        $mois = get_post_meta($event->ID, 'oumr_mois_court', true);
        $markers[] = [
            'lat'   => $lat,
            'lng'   => $lng,
            'type'  => 'event',
            'popup' => '<strong>' . esc_html($event->post_title) . '</strong><br>' . esc_html(trim("$jour $mois")),
        ];
    }

    // Points de vente
    $shops = get_posts(['post_type' => 'oumr_shop', 'posts_per_page' => -1]);
    foreach ($shops as $shop) {
        $lat = (float) get_post_meta($shop->ID, 'oumr_lat', true);
        $lng = (float) get_post_meta($shop->ID, 'oumr_lng', true);
        if (!$lat || !$lng) continue;
        $horaires = get_post_meta($shop->ID, 'oumr_horaires', true);
        $markers[] = [
            'lat'   => $lat,
            'lng'   => $lng,
            'type'  => 'shop',
            'popup' => '<strong>' . esc_html($shop->post_title) . '</strong><br>' . esc_html($horaires),
        ];
    }

    $json = wp_json_encode($markers);

    ob_start(); ?>
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
    <script>
    (function() {
      var oumrData = <?php echo $json; ?>;
      var oumrCenter = [<?php echo (float)$a['center_lat']; ?>, <?php echo (float)$a['center_lng']; ?>];
      var oumrZoom = <?php echo intval($a['zoom']); ?>;
      function initOumrMap() {
        if (typeof L === 'undefined') { setTimeout(initOumrMap, 150); return; }
        var map = L.map('oumr-map', { center: oumrCenter, zoom: oumrZoom, scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        function mkIcon(t) {
          return L.divIcon({ className: 'oumr-pin-wrapper', html: '<div class="oumr-pin-dot ' + t + '"></div>', iconSize: [32,32], iconAnchor: [16,16] });
        }
        oumrData.forEach(function(m) {
          L.marker([m.lat, m.lng], { icon: mkIcon(m.type) }).addTo(map).bindPopup(m.popup);
        });
      }
      document.addEventListener('DOMContentLoaded', initOumrMap);
    })();
    </script>
    <?php return ob_get_clean();
});

// ──────────────────────────────────────────────
// [oumr_cta] — Bloc d'appel à l'action final
// ──────────────────────────────────────────────
add_shortcode('oumr_cta', function($atts) {
    $a = shortcode_atts([
        'title'        => 'Vous tenez une boutique éco-responsable ?',
        'title_accent' => 'éco-responsable ?',
        'text'         => 'Vous êtes commerçante et vous voulez exposer mes créations dans votre boutique ? Vous organisez un salon de créateurs ? Tissons le contact.',
        'btn_text'     => 'Tissons le contact',
        'btn_url'      => '',
    ], $atts);

    $btn_url    = $a['btn_url'] ?: get_permalink(get_page_by_path('contact'));
    $title_safe = esc_html($a['title']);
    $accent_safe = esc_html($a['title_accent']);
    $title_html = str_replace($accent_safe, '<span class="oumr-accent">' . $accent_safe . '</span>', $title_safe);

    ob_start(); ?>
    <section class="oumr-cta">
      <h2><?php echo $title_html; ?></h2>
      <p><?php echo esc_html($a['text']); ?></p>
      <a href="<?php echo esc_url($btn_url); ?>" class="oumr-btn"><?php echo esc_html($a['btn_text']); ?></a>
    </section>
    <?php return ob_get_clean();
});
