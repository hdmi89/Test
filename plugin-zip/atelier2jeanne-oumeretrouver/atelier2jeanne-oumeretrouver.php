<?php
/**
 * Plugin Name:  Atelier 2 Jeanne — Où me retrouver
 * Description:  CPTs Événements & Points de vente + éléments WPBakery pour la page "Où me retrouver".
 * Version:      1.0.0
 * Author:       Atelier 2 Jeanne
 * Text Domain:  a2j-oumr
 */

if (!defined('ABSPATH')) exit;

// ══════════════════════════════════════════════════════════════
//  ASSETS — Leaflet + CSS (uniquement sur les pages concernées)
// ══════════════════════════════════════════════════════════════
add_action('wp_enqueue_scripts', function () {
    global $post;
    if (!is_a($post, 'WP_Post')) return;

    $shortcodes = ['oumr_hero','oumr_featured_event','oumr_events_grid',
                   'oumr_shops_grid','oumr_map','oumr_cta'];
    $found = false;
    foreach ($shortcodes as $sc) {
        if (has_shortcode($post->post_content, $sc)) { $found = true; break; }
    }
    if (!$found) return;

    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

    // CSS complet en inline (pas besoin de fichier externe)
    wp_register_style('a2j-oumr', false);
    wp_enqueue_style('a2j-oumr');
    wp_add_inline_style('a2j-oumr', a2j_oumr_css());
});

// ══════════════════════════════════════════════════════════════
//  CUSTOM POST TYPES
// ══════════════════════════════════════════════════════════════
add_action('init', function () {

    register_post_type('oumr_event', [
        'labels'        => [
            'name'          => 'Événements',
            'singular_name' => 'Événement',
            'add_new_item'  => 'Ajouter un événement',
            'edit_item'     => 'Modifier l\'événement',
            'all_items'     => 'Tous les événements',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'menu_position' => 20,
        'supports'      => ['title', 'thumbnail'],
    ]);

    register_post_type('oumr_shop', [
        'labels'        => [
            'name'          => 'Points de vente',
            'singular_name' => 'Point de vente',
            'add_new_item'  => 'Ajouter un point de vente',
            'edit_item'     => 'Modifier',
            'all_items'     => 'Tous les points de vente',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-store',
        'menu_position' => 21,
        'supports'      => ['title', 'thumbnail'],
    ]);
});

// ══════════════════════════════════════════════════════════════
//  META BOXES
// ══════════════════════════════════════════════════════════════
add_action('add_meta_boxes', function () {
    add_meta_box('oumr_event_meta', 'Détails de l\'événement',    'a2j_event_meta_cb', 'oumr_event', 'normal', 'high');
    add_meta_box('oumr_shop_meta',  'Détails du point de vente',  'a2j_shop_meta_cb',  'oumr_shop',  'normal', 'high');
});

function a2j_event_meta_cb($post) {
    wp_nonce_field('a2j_event_save', 'a2j_event_nonce');
    $fields = [
        'oumr_jour_num'   => ['Numéro du jour affiché',              'text',     '15'],
        'oumr_mois_court' => ['Mois court (ex : Déc)',               'text',     'Déc'],
        'oumr_date_debut' => ['Date début (texte libre)',            'text',     '15 décembre 2025'],
        'oumr_date_fin'   => ['Date fin (vide = 1 seul jour)',       'text',     '17 décembre 2025'],
        'oumr_horaires'   => ['Horaires',                            'text',     '10h – 19h'],
        'oumr_lieu'       => ['Lieu court (carte événements)',       'text',     'Place du marché, Meylan'],
        'oumr_adresse'    => ['Adresse complète (bandeau featured)', 'text',     'Place du marché, Meylan (38240)'],
        'oumr_type_tag'   => ['Type — <code>salons</code> ou <code>boutique</code>', 'text', 'salons'],
        'oumr_tag_label'  => ['Label du tag (ex : Marché de Noël)', 'text',     'Marché de Noël'],
        'oumr_lien_url'   => ['URL "En savoir plus"',               'url',      ''],
        'oumr_lat'        => ['Latitude GPS',                        'text',     '45.210'],
        'oumr_lng'        => ['Longitude GPS',                       'text',     '5.781'],
    ];
    a2j_render_fields($post, $fields);
    $feat = get_post_meta($post->ID, 'oumr_is_featured', true);
    $past = get_post_meta($post->ID, 'oumr_is_past', true);
    echo '<p style="margin:14px 0 4px;">';
    echo '<label><input type="checkbox" name="oumr_is_featured" value="1"' . checked($feat,'1',false) . '> ';
    echo '⭐ <strong>Afficher dans le bandeau "Prochain rendez-vous"</strong></label><br>';
    echo '<label style="display:block;margin-top:8px;"><input type="checkbox" name="oumr_is_past" value="1"' . checked($past,'1',false) . '> ';
    echo '🕐 Marquer comme <strong>événement passé</strong></label></p>';
}

function a2j_shop_meta_cb($post) {
    wp_nonce_field('a2j_shop_save', 'a2j_shop_nonce');
    $fields = [
        'oumr_ville'       => ['Ville',                         'text',     'Grenoble'],
        'oumr_cp'          => ['Code postal',                   'text',     '38000'],
        'oumr_description' => ['Description courte',           'textarea', 'Concept-store engagé…'],
        'oumr_horaires'    => ['Horaires',                      'text',     'Mardi – Samedi · 10h-19h'],
        'oumr_lien_url'    => ['URL "Voir l\'article"',         'url',      ''],
        'oumr_itineraire'  => ['URL itinéraire (Google Maps)',  'url',      ''],
        'oumr_lat'         => ['Latitude GPS',                  'text',     '45.190'],
        'oumr_lng'         => ['Longitude GPS',                 'text',     '5.726'],
    ];
    a2j_render_fields($post, $fields);
}

function a2j_render_fields($post, $fields) {
    echo '<table class="form-table" style="font-size:13px;">';
    foreach ($fields as $key => [$label, $type, $ph]) {
        $val = get_post_meta($post->ID, $key, true);
        echo '<tr><th style="width:280px;padding:8px 10px;"><label for="' . esc_attr($key) . '">' . wp_kses_post($label) . '</label></th><td style="padding:6px 10px;">';
        if ($type === 'textarea') {
            echo '<textarea id="'.esc_attr($key).'" name="'.esc_attr($key).'" rows="3" style="width:100%;max-width:480px;">'.esc_textarea($val).'</textarea>';
        } else {
            echo '<input type="'.esc_attr($type).'" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr($val).'" placeholder="'.esc_attr($ph).'" style="width:100%;max-width:480px;">';
        }
        echo '</td></tr>';
    }
    echo '</table>';
}

// ── Sauvegarde ────────────────────────────────
add_action('save_post', function ($id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $id)) return;

    // FIX : vérifier le post_type avant de sauvegarder pour éviter
    // qu'un nonce valide sur un autre formulaire écrive dans le mauvais post.
    $post_type = get_post_type($id);

    if ($post_type === 'oumr_event'
        && isset($_POST['a2j_event_nonce'])
        && wp_verify_nonce($_POST['a2j_event_nonce'], 'a2j_event_save')
    ) {
        foreach (['oumr_jour_num','oumr_mois_court','oumr_date_debut','oumr_date_fin',
                  'oumr_horaires','oumr_lieu','oumr_adresse','oumr_type_tag','oumr_tag_label','oumr_lat','oumr_lng'] as $f) {
            if (isset($_POST[$f])) update_post_meta($id, $f, sanitize_text_field($_POST[$f]));
        }
        if (isset($_POST['oumr_lien_url'])) update_post_meta($id, 'oumr_lien_url', esc_url_raw($_POST['oumr_lien_url']));
        update_post_meta($id, 'oumr_is_featured', isset($_POST['oumr_is_featured']) ? '1' : '');
        update_post_meta($id, 'oumr_is_past',     isset($_POST['oumr_is_past'])     ? '1' : '');
    }

    if ($post_type === 'oumr_shop'
        && isset($_POST['a2j_shop_nonce'])
        && wp_verify_nonce($_POST['a2j_shop_nonce'], 'a2j_shop_save')
    ) {
        foreach (['oumr_ville','oumr_cp','oumr_horaires','oumr_lat','oumr_lng'] as $f) {
            if (isset($_POST[$f])) update_post_meta($id, $f, sanitize_text_field($_POST[$f]));
        }
        if (isset($_POST['oumr_description'])) update_post_meta($id, 'oumr_description', sanitize_textarea_field($_POST['oumr_description']));
        foreach (['oumr_lien_url','oumr_itineraire'] as $f) {
            if (isset($_POST[$f])) update_post_meta($id, $f, esc_url_raw($_POST[$f]));
        }
    }
});

// ══════════════════════════════════════════════════════════════
//  SHORTCODES
// ══════════════════════════════════════════════════════════════

// ── [oumr_hero] ───────────────────────────────
add_shortcode('oumr_hero', function ($atts) {
    $a = shortcode_atts([
        'label'  => 'Salons &amp; points de vente',
        'title'  => 'Où me retrouver.',
        'accent' => 'retrouver.',
        'lead'   => 'Mes créations sont visibles dans plusieurs boutiques partenaires de Grenoble et de l\'Isère, et je participe régulièrement aux salons de créateurs et marchés artisanaux de la région. Voici les prochains rendez-vous.',
        'chips'  => 'Salons & événements,Points de vente partenaires,Atelier sur RDV',
    ], $atts);

    $chips_html = '';
    foreach (array_map('trim', explode(',', $a['chips'])) as $c) {
        $chips_html .= '<span class="oumr-chip">' . esc_html($c) . '</span>';
    }
    $t = esc_html($a['title']);
    $ac = esc_html($a['accent']);
    $title_html = str_replace($ac, '<span class="oumr-accent">' . $ac . '</span>', $t);

    ob_start(); ?>
    <section class="oumr-hero">
      <span class="oumr-section-label"><?php echo esc_html($a['label']); ?></span>
      <h1 class="oumr-hero__title"><?php echo $title_html; ?></h1>
      <?php if ($a['lead']): ?><p class="oumr-hero__lead"><?php echo esc_html($a['lead']); ?></p><?php endif; ?>
      <div class="oumr-chips"><?php echo $chips_html; ?></div>
    </section>
    <?php return ob_get_clean();
});

// ── [oumr_featured_event] ─────────────────────
add_shortcode('oumr_featured_event', function ($atts) {
    $a = shortcode_atts(['id' => 0], $atts);

    if ($a['id']) {
        $event = get_post(intval($a['id']));
        // FIX : s'assurer que l'ID fourni pointe bien sur notre CPT,
        // pas sur un article privé ou un autre type de contenu.
        if (!$event || $event->post_type !== 'oumr_event') return '';
    } else {
        $r = get_posts(['post_type' => 'oumr_event', 'posts_per_page' => 1,
                        'meta_query' => [['key' => 'oumr_is_featured', 'value' => '1']]]);
        $event = $r ? $r[0] : null;
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
    $date    = $fin ? 'Du ' . $debut . ' au ' . $fin : $debut;

    ob_start(); ?>
    <section class="oumr-featured">
      <div class="oumr-featured__inner">
        <div class="oumr-featured__visual">
          <div class="oumr-featured__date-badge">
            <span class="oumr-day"><?php echo esc_html($jour); ?></span>
            <span class="oumr-month"><?php echo esc_html($mois); ?></span>
          </div>
          <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($event->post_title); ?>" loading="eager"><?php endif; ?>
        </div>
        <div class="oumr-featured__content">
          <span class="oumr-featured__tag">Prochain rendez-vous</span>
          <h2><?php echo esc_html($event->post_title); ?></h2>
          <ul class="oumr-featured__details">
            <?php if ($date):    ?><li><span class="oumr-icon">📅</span> <?php echo esc_html($date); ?></li><?php endif; ?>
            <?php if ($adresse): ?><li><span class="oumr-icon">📍</span> <?php echo esc_html($adresse); ?></li><?php endif; ?>
            <?php if ($horaire): ?><li><span class="oumr-icon">🕐</span> <?php echo esc_html($horaire); ?></li><?php endif; ?>
          </ul>
          <?php if ($lien): ?><a href="<?php echo esc_url($lien); ?>" class="oumr-btn-dark">En savoir plus</a><?php endif; ?>
        </div>
      </div>
    </section>
    <?php return ob_get_clean();
});

// ── [oumr_events_grid] ────────────────────────
add_shortcode('oumr_events_grid', function ($atts) {
    $a = shortcode_atts(['count' => 12, 'show_filters' => 'yes'], $atts);

    $events = get_posts(['post_type' => 'oumr_event', 'posts_per_page' => intval($a['count']),
                         'orderby' => 'menu_order date', 'order' => 'ASC']);
    if (empty($events)) return '';

    $nb_up   = count(array_filter($events, fn($e) => !get_post_meta($e->ID,'oumr_is_past',true)));
    $nb_past = count($events) - $nb_up;
    $count_str = $nb_up . ' événement' . ($nb_up > 1 ? 's' : '') . ' à venir';
    if ($nb_past) $count_str .= ' · ' . $nb_past . ' passé' . ($nb_past > 1 ? 's' : '');

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
        <?php foreach ($events as $ev):
            $past    = get_post_meta($ev->ID, 'oumr_is_past',    true);
            $jour    = get_post_meta($ev->ID, 'oumr_jour_num',   true);
            $mois    = get_post_meta($ev->ID, 'oumr_mois_court', true);
            $type    = get_post_meta($ev->ID, 'oumr_type_tag',   true) ?: 'salons';
            $tag_lbl = get_post_meta($ev->ID, 'oumr_tag_label',  true);
            $lieu    = get_post_meta($ev->ID, 'oumr_lieu',       true);
            $horaire = get_post_meta($ev->ID, 'oumr_horaires',   true);
            $lien    = get_post_meta($ev->ID, 'oumr_lien_url',   true) ?: '#';
            $img     = get_the_post_thumbnail_url($ev->ID, 'medium_large');
        ?>
        <article class="oumr-card">
          <div class="oumr-card__visual">
            <span class="oumr-card__date<?php echo $past ? ' past' : ''; ?>">
              <span class="oumr-day"><?php echo esc_html($jour); ?></span>
              <span class="oumr-month"><?php echo esc_html($mois); ?></span>
            </span>
            <span class="oumr-card__tag <?php echo esc_attr($type); ?>"><?php echo esc_html($tag_lbl); ?></span>
            <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($ev->post_title); ?>" loading="lazy"><?php endif; ?>
          </div>
          <div class="oumr-card__body">
            <h3><a href="<?php echo esc_url($lien); ?>"><?php echo esc_html($ev->post_title); ?></a></h3>
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
    (function(){
      document.querySelectorAll('.oumr-filter').forEach(function(btn){
        btn.addEventListener('click',function(){
          document.querySelectorAll('.oumr-filter').forEach(function(b){b.classList.remove('active');});
          btn.classList.add('active');
          var f=btn.dataset.filter;
          document.querySelectorAll('.oumr-card').forEach(function(c){
            var t=c.querySelector('.oumr-card__tag');
            c.style.display=(f==='all'||t.classList.contains(f))?'flex':'none';
          });
        });
      });
    })();
    </script>
    <?php return ob_get_clean();
});

// ── [oumr_shops_grid] ─────────────────────────
add_shortcode('oumr_shops_grid', function ($atts) {
    $a = shortcode_atts([
        'title' => 'Mes boutiques partenaires',
        'intro' => 'Mes créations sont disponibles à l\'année dans ces boutiques partenaires de Grenoble et de l\'agglomération.',
    ], $atts);

    $shops = get_posts(['post_type' => 'oumr_shop', 'posts_per_page' => -1,
                        'orderby' => 'menu_order title', 'order' => 'ASC']);
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
          <?php foreach ($shops as $sh):
              $ville = get_post_meta($sh->ID, 'oumr_ville',       true);
              $cp    = get_post_meta($sh->ID, 'oumr_cp',          true);
              $desc  = get_post_meta($sh->ID, 'oumr_description', true);
              $hor   = get_post_meta($sh->ID, 'oumr_horaires',    true);
              $lien  = get_post_meta($sh->ID, 'oumr_lien_url',    true);
              $itin  = get_post_meta($sh->ID, 'oumr_itineraire',  true);
              $img   = get_the_post_thumbnail_url($sh->ID, 'medium');
          ?>
          <article class="oumr-shop">
            <div class="oumr-shop__visual">
              <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($sh->post_title); ?>" loading="lazy"><?php endif; ?>
            </div>
            <div class="oumr-shop__body">
              <h3><?php echo esc_html($sh->post_title); ?></h3>
              <?php if ($ville): ?><div class="oumr-shop__city"><?php echo esc_html($ville . ($cp ? ' · ' . $cp : '')); ?></div><?php endif; ?>
              <?php if ($desc):  ?><p class="oumr-shop__address"><?php echo esc_html($desc); ?></p><?php endif; ?>
              <?php if ($hor):   ?><p class="oumr-shop__hours"><strong>Horaires :</strong> <?php echo esc_html($hor); ?></p><?php endif; ?>
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

// ── [oumr_map] ────────────────────────────────
add_shortcode('oumr_map', function ($atts) {
    $a = shortcode_atts([
        'atelier_lat' => '45.207', 'atelier_lng' => '5.785',
        'atelier_nom' => 'Atelier de Jeanne', 'atelier_cp' => 'Meylan, 38240',
        'center_lat'  => '45.205', 'center_lng'  => '5.78', 'zoom' => '11',
    ], $atts);

    $markers = [[
        'lat'   => (float)$a['atelier_lat'],
        'lng'   => (float)$a['atelier_lng'],
        'type'  => 'atelier',
        'popup' => '<strong>' . esc_html($a['atelier_nom']) . '</strong><br>' . esc_html($a['atelier_cp']) . '<br><em>Sur rendez-vous</em>',
    ]];

    foreach (get_posts(['post_type' => 'oumr_event', 'posts_per_page' => -1]) as $ev) {
        if (get_post_meta($ev->ID, 'oumr_is_past', true)) continue;
        $lat = (float)get_post_meta($ev->ID, 'oumr_lat', true);
        $lng = (float)get_post_meta($ev->ID, 'oumr_lng', true);
        if (!$lat || !$lng) continue;
        $markers[] = ['lat' => $lat, 'lng' => $lng, 'type' => 'event',
            'popup' => '<strong>' . esc_html($ev->post_title) . '</strong><br>' . esc_html(get_post_meta($ev->ID,'oumr_jour_num',true) . ' ' . get_post_meta($ev->ID,'oumr_mois_court',true))];
    }
    foreach (get_posts(['post_type' => 'oumr_shop', 'posts_per_page' => -1]) as $sh) {
        $lat = (float)get_post_meta($sh->ID, 'oumr_lat', true);
        $lng = (float)get_post_meta($sh->ID, 'oumr_lng', true);
        if (!$lat || !$lng) continue;
        $markers[] = ['lat' => $lat, 'lng' => $lng, 'type' => 'shop',
            'popup' => '<strong>' . esc_html($sh->post_title) . '</strong><br>' . esc_html(get_post_meta($sh->ID,'oumr_horaires',true))];
    }

    // FIX : JSON_HEX_TAG échappe < et > en </>,
    // garantit qu'un titre contenant </script> ne peut pas fermer la balise.
    $json = wp_json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP);
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
    (function(){
      var D=<?php echo $json; ?>,C=[<?php echo (float)$a['center_lat'];?>,<?php echo (float)$a['center_lng'];?>],Z=<?php echo intval($a['zoom']);?>;
      function initMap(){
        if(typeof L==='undefined'){setTimeout(initMap,150);return;}
        var m=L.map('oumr-map',{center:C,zoom:Z,scrollWheelZoom:false});
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(m);
        function ic(t){return L.divIcon({className:'oumr-pin-wrapper',html:'<div class="oumr-pin-dot '+t+'"></div>',iconSize:[32,32],iconAnchor:[16,16]});}
        D.forEach(function(p){L.marker([p.lat,p.lng],{icon:ic(p.type)}).addTo(m).bindPopup(p.popup);});
      }
      document.addEventListener('DOMContentLoaded',initMap);
    })();
    </script>
    <?php return ob_get_clean();
});

// ── [oumr_cta] ────────────────────────────────
add_shortcode('oumr_cta', function ($atts) {
    $a = shortcode_atts([
        'title'        => 'Vous tenez une boutique éco-responsable ?',
        'title_accent' => 'éco-responsable ?',
        'text'         => 'Vous êtes commerçante et vous voulez exposer mes créations dans votre boutique ? Vous organisez un salon de créateurs ? Tissons le contact.',
        'btn_text'     => 'Tissons le contact',
        'btn_url'      => '',
    ], $atts);

    $url = $a['btn_url'] ?: get_permalink(get_page_by_path('contact'));
    $t   = esc_html($a['title']);
    $ac  = esc_html($a['title_accent']);
    $th  = str_replace($ac, '<span class="oumr-accent">'.$ac.'</span>', $t);

    ob_start(); ?>
    <section class="oumr-cta">
      <h2><?php echo $th; ?></h2>
      <p><?php echo esc_html($a['text']); ?></p>
      <a href="<?php echo esc_url($url); ?>" class="oumr-btn"><?php echo esc_html($a['btn_text']); ?></a>
    </section>
    <?php return ob_get_clean();
});

// ══════════════════════════════════════════════════════════════
//  WPBAKERY — Enregistrement des éléments custom
//  (catégorie "Atelier 2 Jeanne" dans le panel WPBakery)
// ══════════════════════════════════════════════════════════════
add_action('vc_before_init', function () {

    vc_map(['name' => '🌿 Hero — Intro page', 'base' => 'oumr_hero',
        'category' => 'Atelier 2 Jeanne', 'icon' => 'dashicons-location',
        'params' => [
            ['type'=>'textfield','heading'=>'Label','param_name'=>'label','value'=>'Salons &amp; points de vente'],
            ['type'=>'textfield','heading'=>'Titre','param_name'=>'title','value'=>'Où me retrouver.'],
            ['type'=>'textfield','heading'=>'Mot en couleur (accent — doit être dans le titre)','param_name'=>'accent','value'=>'retrouver.'],
            ['type'=>'textarea','heading'=>'Texte d\'intro','param_name'=>'lead','value'=>''],
            ['type'=>'textfield','heading'=>'Chips (séparés par virgules)','param_name'=>'chips','value'=>'Salons & événements,Points de vente partenaires,Atelier sur RDV'],
        ],
    ]);

    vc_map(['name' => '🌿 Événement mis en avant', 'base' => 'oumr_featured_event',
        'category' => 'Atelier 2 Jeanne', 'icon' => 'dashicons-star-filled',
        'description' => 'Bandeau noir — prend l\'événement coché "Mis en avant" dans le CPT.',
        'params' => [
            ['type'=>'textfield','heading'=>'ID événement (vide = automatique)','param_name'=>'id','value'=>''],
        ],
    ]);

    vc_map(['name' => '🌿 Grille d\'événements', 'base' => 'oumr_events_grid',
        'category' => 'Atelier 2 Jeanne', 'icon' => 'dashicons-grid-view',
        'description' => 'Cards + filtres. Alimentée par le CPT "Événements".',
        'params' => [
            ['type'=>'textfield','heading'=>'Nombre max','param_name'=>'count','value'=>'12'],
            ['type'=>'dropdown','heading'=>'Afficher les filtres','param_name'=>'show_filters','value'=>['Oui'=>'yes','Non'=>'no']],
        ],
    ]);

    vc_map(['name' => '🌿 Points de vente', 'base' => 'oumr_shops_grid',
        'category' => 'Atelier 2 Jeanne', 'icon' => 'dashicons-store',
        'description' => 'Grille boutiques. Alimentée par le CPT "Points de vente".',
        'params' => [
            ['type'=>'textfield','heading'=>'Titre section','param_name'=>'title','value'=>'Mes boutiques partenaires'],
            ['type'=>'textarea','heading'=>'Intro','param_name'=>'intro','value'=>''],
        ],
    ]);

    vc_map(['name' => '🌿 Carte interactive', 'base' => 'oumr_map',
        'category' => 'Atelier 2 Jeanne', 'icon' => 'dashicons-location-alt',
        'description' => 'Carte OpenStreetMap auto (atelier + CPTs).',
        'params' => [
            ['type'=>'textfield','heading'=>'Latitude atelier','param_name'=>'atelier_lat','value'=>'45.207'],
            ['type'=>'textfield','heading'=>'Longitude atelier','param_name'=>'atelier_lng','value'=>'5.785'],
            ['type'=>'textfield','heading'=>'Nom atelier (popup)','param_name'=>'atelier_nom','value'=>'Atelier de Jeanne'],
            ['type'=>'textfield','heading'=>'Ville / CP atelier','param_name'=>'atelier_cp','value'=>'Meylan, 38240'],
            ['type'=>'textfield','heading'=>'Latitude centre carte','param_name'=>'center_lat','value'=>'45.205'],
            ['type'=>'textfield','heading'=>'Longitude centre carte','param_name'=>'center_lng','value'=>'5.78'],
            ['type'=>'textfield','heading'=>'Zoom initial','param_name'=>'zoom','value'=>'11'],
        ],
    ]);

    vc_map(['name' => '🌿 CTA final', 'base' => 'oumr_cta',
        'category' => 'Atelier 2 Jeanne', 'icon' => 'dashicons-megaphone',
        'params' => [
            ['type'=>'textfield','heading'=>'Titre','param_name'=>'title','value'=>'Vous tenez une boutique éco-responsable ?'],
            ['type'=>'textfield','heading'=>'Partie du titre en couleur','param_name'=>'title_accent','value'=>'éco-responsable ?'],
            ['type'=>'textarea','heading'=>'Texte','param_name'=>'text','value'=>''],
            ['type'=>'textfield','heading'=>'Texte bouton','param_name'=>'btn_text','value'=>'Tissons le contact'],
            ['type'=>'textfield','heading'=>'URL bouton (vide = page Contact)','param_name'=>'btn_url','value'=>''],
        ],
    ]);
});

// ══════════════════════════════════════════════════════════════
//  CSS complet (retourné comme chaîne pour wp_add_inline_style)
// ══════════════════════════════════════════════════════════════
function a2j_oumr_css() { return '
@import url("https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700&display=swap");

/* ─── Variables globales utilisées par tous les shortcodes ─── */
:root {
  --oumr-sage:#95B69E; --oumr-sage-light:#D1E6DF; --oumr-sage-btn:#9EC0AF;
  --oumr-sage-dark:#6F8E78; --oumr-beige:#F3F0E2; --oumr-cream:#FAF7F2;
  --oumr-noir:#1E1E1E; --oumr-blanc:#FFFFFF; --oumr-gris:#6B6B6B;
  --oumr-gris-light:#D8D2C0; --oumr-gris-bord:#E8E5D8; --oumr-terra:#C97B5C;
  --oumr-serif:"Playfair Display",Georgia,serif;
  --oumr-sans:"Lato",system-ui,sans-serif;
}

/* ─── Compat WPBakery : neutralise padding rows/colonnes ─── */
.vc_row:has(.oumr-hero),.vc_row:has(.oumr-featured),.vc_row:has(.oumr-events),
.vc_row:has(.oumr-filters),.vc_row:has(.oumr-shops),.vc_row:has(.oumr-map-section),.vc_row:has(.oumr-cta) {
  margin-left:0!important; margin-right:0!important;
  padding-left:0!important; padding-right:0!important; max-width:none!important;
}
.vc_row:has(.oumr-hero) .vc_column-inner,
.vc_row:has(.oumr-featured) .vc_column-inner,
.vc_row:has(.oumr-events) .vc_column-inner,
.vc_row:has(.oumr-filters) .vc_column-inner,
.vc_row:has(.oumr-shops) .vc_column-inner,
.vc_row:has(.oumr-map-section) .vc_column-inner,
.vc_row:has(.oumr-cta) .vc_column-inner {
  padding:0!important;
}

/* ─── Utilitaires ─── */
.oumr-accent{color:var(--oumr-sage-dark);}
.oumr-accent-terra{color:var(--oumr-terra);}
.oumr-section-label{font-family:var(--oumr-sans);font-weight:600;font-size:12px;letter-spacing:.15em;text-transform:uppercase;color:var(--oumr-sage-dark);margin-bottom:14px;display:block;}
.oumr-icon{flex-shrink:0;font-size:16px;}

/* ─── Hero ─── */
.oumr-hero{padding:60px 60px 50px;text-align:center;max-width:800px;margin:0 auto;background:var(--oumr-beige);}
.oumr-hero__title{font-family:var(--oumr-serif);font-size:clamp(38px,5.5vw,70px);line-height:1;font-weight:500;margin-bottom:24px;letter-spacing:-.02em;color:var(--oumr-noir);}
.oumr-hero__lead{font-size:18px;line-height:1.65;color:var(--oumr-noir);max-width:60ch;margin:0 auto 32px;}
.oumr-chips{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;}
.oumr-chip{font-family:var(--oumr-sans);font-size:12px;letter-spacing:.03em;padding:8px 16px;background:var(--oumr-sage-light);color:var(--oumr-sage-dark);border-radius:999px;font-weight:600;}

/* ─── Featured event ─── */
.oumr-featured{padding:50px 60px 80px;background:var(--oumr-beige);}
.oumr-featured__inner{background:var(--oumr-noir);color:var(--oumr-beige);display:grid;grid-template-columns:repeat(12,1fr);gap:40px;padding:50px;align-items:center;position:relative;overflow:hidden;max-width:1240px;margin:0 auto;}
.oumr-featured__visual{grid-column:1/6;position:relative;z-index:1;}
.oumr-featured__visual img{width:100%;aspect-ratio:4/3;object-fit:cover;}
.oumr-featured__date-badge{position:absolute;top:-18px;left:-18px;background:var(--oumr-sage);color:var(--oumr-blanc);padding:14px 18px;text-align:center;z-index:2;}
.oumr-featured__date-badge .oumr-day{display:block;font-family:var(--oumr-serif);font-size:32px;line-height:1;font-weight:600;}
.oumr-featured__date-badge .oumr-month{display:block;font-size:11px;letter-spacing:.15em;text-transform:uppercase;margin-top:4px;font-weight:600;}
.oumr-featured__content{grid-column:6/13;position:relative;z-index:1;}
.oumr-featured__tag{display:inline-block;background:var(--oumr-sage);color:var(--oumr-blanc);padding:6px 14px;font-size:11px;letter-spacing:.15em;text-transform:uppercase;border-radius:999px;font-weight:600;margin-bottom:20px;}
.oumr-featured__inner h2{color:var(--oumr-beige);font-family:var(--oumr-serif);font-size:clamp(28px,3.5vw,44px);margin-bottom:14px;font-weight:500;}
.oumr-featured__details{list-style:none;padding:0;margin:0 0 26px;}
.oumr-featured__details li{font-size:15px;padding:8px 0;display:flex;align-items:center;gap:12px;color:rgba(243,240,226,.9);}
.oumr-btn-dark{font-family:var(--oumr-sans);font-size:13px;padding:14px 28px;background:var(--oumr-sage-btn);color:var(--oumr-blanc);border-radius:999px;display:inline-flex;align-items:center;gap:10px;font-weight:600;transition:all .3s ease;border:none;cursor:pointer;text-decoration:none;}
.oumr-btn-dark::after{content:"→";}
.oumr-btn-dark:hover{background:var(--oumr-sage);color:var(--oumr-blanc);transform:translateY(-2px);}

/* ─── Filtres ─── */
.oumr-filters{padding:0 60px 40px;display:flex;justify-content:center;gap:12px;flex-wrap:wrap;background:var(--oumr-beige);}
.oumr-filter{font-family:var(--oumr-sans);font-size:13px;font-weight:600;padding:12px 24px;background:var(--oumr-blanc);color:var(--oumr-noir);border:1px solid var(--oumr-gris-bord);border-radius:999px;cursor:pointer;transition:all .3s ease;box-shadow:none;outline:none;line-height:1;}
.oumr-filter:hover{border-color:var(--oumr-sage);color:var(--oumr-sage-dark);background:var(--oumr-blanc);transform:none;}
.oumr-filter.active{background:var(--oumr-sage);color:var(--oumr-blanc);border-color:var(--oumr-sage);}

/* ─── Section événements ─── */
.oumr-events{padding:0 60px 80px;max-width:1240px;margin:0 auto;background:var(--oumr-beige);}
.oumr-events__head{margin-bottom:30px;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:20px;}
.oumr-events__head h2{margin:0;font-family:var(--oumr-serif);}
.oumr-events__count{font-family:var(--oumr-sans);font-size:13px;color:var(--oumr-gris);}
.oumr-events-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}

/* ─── Event card ─── */
.oumr-card{background:var(--oumr-blanc);border:1px solid var(--oumr-gris-bord);overflow:hidden;transition:transform .3s ease,border-color .3s ease;display:flex;flex-direction:column;}
.oumr-card:hover{transform:translateY(-4px);border-color:var(--oumr-sage);}
.oumr-card__visual{position:relative;aspect-ratio:4/3;overflow:hidden;}
.oumr-card__visual img{width:100%;height:100%;object-fit:cover;transition:transform .6s ease;}
.oumr-card:hover .oumr-card__visual img{transform:scale(1.05);}
.oumr-card__date{position:absolute;top:16px;left:16px;background:var(--oumr-blanc);padding:10px 12px;text-align:center;min-width:60px;box-shadow:0 2px 8px rgba(0,0,0,.1);z-index:1;}
.oumr-card__date .oumr-day{display:block;font-family:var(--oumr-serif);font-size:22px;line-height:1;color:var(--oumr-noir);font-weight:600;}
.oumr-card__date .oumr-month{display:block;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--oumr-sage-dark);margin-top:4px;font-weight:600;}
.oumr-card__date.past{background:var(--oumr-gris-light);opacity:.7;}
.oumr-card__date.past .oumr-month{color:var(--oumr-gris);}
.oumr-card__tag{position:absolute;top:16px;right:16px;background:rgba(30,30,30,.85);color:var(--oumr-blanc);padding:5px 11px;font-size:10px;letter-spacing:.12em;text-transform:uppercase;border-radius:999px;font-weight:600;z-index:1;font-family:var(--oumr-sans);}
.oumr-card__tag.salons{background:var(--oumr-sage);}
.oumr-card__tag.boutique{background:var(--oumr-terra);}
.oumr-card__body{padding:24px 24px 26px;flex:1;display:flex;flex-direction:column;}
.oumr-card h3{font-family:var(--oumr-serif);font-size:22px;margin-bottom:10px;line-height:1.3;font-weight:600;}
.oumr-card h3 a{color:inherit;text-decoration:none;}
.oumr-card h3 a:hover{color:var(--oumr-sage-dark);}
.oumr-card__meta{list-style:none;padding:0;margin:0 0 16px;}
.oumr-card__meta li{font-size:13px;color:var(--oumr-gris);padding:4px 0;display:flex;align-items:center;gap:8px;}
.oumr-card__footer{margin-top:auto;padding-top:16px;border-top:1px solid var(--oumr-gris-bord);}
.oumr-card__link{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--oumr-sage-dark);font-weight:600;display:inline-flex;align-items:center;gap:6px;font-family:var(--oumr-sans);text-decoration:none;}
.oumr-card__link::after{content:"→";transition:transform .3s;}
.oumr-card__link:hover{color:var(--oumr-sage);}
.oumr-card__link:hover::after{transform:translateX(3px);}

/* ─── Points de vente ─── */
.oumr-shops{background:var(--oumr-cream);padding:80px 60px;}
.oumr-shops__inner{max-width:1240px;margin:0 auto;}
.oumr-shops__head{margin-bottom:50px;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:20px;}
.oumr-shops__head h2{font-family:var(--oumr-serif);margin-bottom:0;}
.oumr-shops__head p{max-width:50ch;font-size:16px;color:var(--oumr-gris);margin-top:12px;}
.oumr-shops-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px;}
.oumr-shop{background:var(--oumr-blanc);border:1px solid var(--oumr-gris-bord);display:grid;grid-template-columns:200px 1fr;overflow:hidden;transition:transform .3s ease,border-color .3s ease;}
.oumr-shop:hover{transform:translateY(-4px);border-color:var(--oumr-terra);}
.oumr-shop__visual{overflow:hidden;}
.oumr-shop__visual img{width:100%;height:100%;object-fit:cover;}
.oumr-shop__body{padding:24px 26px;}
.oumr-shop h3{font-family:var(--oumr-serif);font-size:22px;margin-bottom:6px;font-weight:600;}
.oumr-shop__city{font-size:13px;color:var(--oumr-terra);font-weight:600;letter-spacing:.03em;margin-bottom:14px;font-family:var(--oumr-sans);}
.oumr-shop__address{font-size:14px;color:var(--oumr-noir);line-height:1.55;margin-bottom:12px;}
.oumr-shop__hours{font-size:13px;color:var(--oumr-gris);line-height:1.5;margin-bottom:16px;padding-top:12px;border-top:1px solid var(--oumr-gris-bord);}
.oumr-shop__hours strong{color:var(--oumr-noir);}
.oumr-shop__actions{display:flex;gap:14px;flex-wrap:wrap;}
.oumr-shop__actions a{font-size:12px;letter-spacing:.05em;font-weight:600;color:var(--oumr-sage-dark);border-bottom:1px solid var(--oumr-sage);padding-bottom:2px;transition:color .25s,border-color .25s;font-family:var(--oumr-sans);text-decoration:none;}
.oumr-shop__actions a:hover{color:var(--oumr-terra);border-color:var(--oumr-terra);}

/* ─── Carte ─── */
.oumr-map-section{padding:80px 60px;background:var(--oumr-beige);}
.oumr-map-section__inner{max-width:1240px;margin:0 auto;}
.oumr-map-section__head{text-align:center;margin-bottom:40px;}
.oumr-map-section__head h2{font-family:var(--oumr-serif);margin-bottom:0;}
.oumr-map-section__head p{max-width:56ch;margin:14px auto 0;font-size:16px;color:var(--oumr-gris);}
#oumr-map{width:100%;height:500px;border:1px solid var(--oumr-gris-bord);z-index:1;}
.oumr-map-legend{display:flex;justify-content:center;gap:24px;margin-top:20px;flex-wrap:wrap;}
.oumr-legend-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--oumr-gris);font-family:var(--oumr-sans);}
.oumr-pin{width:14px;height:14px;border-radius:50%;flex-shrink:0;display:inline-block;}
.oumr-pin.event{background:var(--oumr-sage);} .oumr-pin.shop{background:var(--oumr-terra);} .oumr-pin.atelier{background:var(--oumr-noir);}
.oumr-pin-wrapper{background:none;border:none;}
.oumr-pin-dot{width:32px;height:32px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);}
.oumr-pin-dot.event{background:#95B69E;} .oumr-pin-dot.shop{background:#C97B5C;} .oumr-pin-dot.atelier{background:#1E1E1E;}

/* ─── CTA final ─── */
.oumr-cta{background:#D1E6DF;padding:80px 60px;text-align:center;}
.oumr-cta h2{font-family:var(--oumr-serif);margin:0 auto 18px;max-width:22ch;font-size:clamp(26px,3.5vw,42px);}
.oumr-cta p{font-size:17px;color:var(--oumr-noir);max-width:50ch;margin:0 auto 30px;line-height:1.65;}
.oumr-btn{font-family:var(--oumr-sans);font-size:13px;letter-spacing:.03em;padding:14px 28px;background:var(--oumr-sage-btn);color:#fff;border-radius:999px;transition:all .3s ease;display:inline-flex;align-items:center;gap:10px;border:none;cursor:pointer;font-weight:600;text-decoration:none;}
.oumr-btn::after{content:"→";transition:transform .3s;}
.oumr-btn:hover{background:var(--oumr-sage);color:#fff;transform:translateY(-2px);}
.oumr-btn:hover::after{transform:translateX(4px);}

/* ─── Responsive ─── */
@media(max-width:1024px){
  .oumr-hero,.oumr-featured,.oumr-filters,.oumr-events,.oumr-shops,.oumr-map-section,.oumr-cta{padding-left:30px;padding-right:30px;}
  .oumr-featured__inner{grid-template-columns:1fr;padding:30px;gap:30px;}
  .oumr-featured__visual,.oumr-featured__content{grid-column:1;}
  .oumr-events-grid{grid-template-columns:repeat(2,1fr);}
  .oumr-shops-grid{grid-template-columns:1fr;}
}
@media(max-width:690px){
  .oumr-events-grid{grid-template-columns:1fr;}
  .oumr-shop{grid-template-columns:1fr;}
  .oumr-shop__visual{height:200px;}
  #oumr-map{height:350px;}
  .oumr-hero__title{font-size:clamp(32px,8vw,52px);}
}
'; }
