<?php
/**
 * Custom Post Types : Événements + Points de vente
 * + Meta boxes pour administration dans WP
 */

// ──────────────────────────────────────────────
// Enregistrement des CPTs
// ──────────────────────────────────────────────
add_action('init', function() {

    register_post_type('oumr_event', [
        'labels' => [
            'name'          => 'Événements',
            'singular_name' => 'Événement',
            'add_new_item'  => 'Ajouter un événement',
            'edit_item'     => 'Modifier l\'événement',
            'all_items'     => 'Tous les événements',
            'not_found'     => 'Aucun événement trouvé.',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'menu_position' => 20,
        'supports'      => ['title', 'thumbnail'],
        'rewrite'       => false,
    ]);

    register_post_type('oumr_shop', [
        'labels' => [
            'name'          => 'Points de vente',
            'singular_name' => 'Point de vente',
            'add_new_item'  => 'Ajouter un point de vente',
            'edit_item'     => 'Modifier',
            'all_items'     => 'Tous les points de vente',
            'not_found'     => 'Aucun point de vente trouvé.',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-store',
        'menu_position' => 21,
        'supports'      => ['title', 'thumbnail'],
        'rewrite'       => false,
    ]);
});

// ──────────────────────────────────────────────
// Meta boxes
// ──────────────────────────────────────────────
add_action('add_meta_boxes', function() {
    add_meta_box(
        'oumr_event_meta',
        'Détails de l\'événement',
        'oumr_event_meta_box_cb',
        'oumr_event',
        'normal',
        'high'
    );
    add_meta_box(
        'oumr_shop_meta',
        'Détails du point de vente',
        'oumr_shop_meta_box_cb',
        'oumr_shop',
        'normal',
        'high'
    );
});

function oumr_event_meta_box_cb($post) {
    wp_nonce_field('oumr_event_save', 'oumr_event_nonce');
    $f = [
        'oumr_jour_num'    => ['Numéro du jour affiché sur la carte',   'text',     '15'],
        'oumr_mois_court'  => ['Mois court affiché (ex : Déc)',          'text',     'Déc'],
        'oumr_date_debut'  => ['Date début (texte libre, ex : 15 déc)', 'text',     '15 décembre 2025'],
        'oumr_date_fin'    => ['Date fin (laisser vide si 1 seul jour)', 'text',     '17 décembre 2025'],
        'oumr_horaires'    => ['Horaires',                               'text',     '10h – 19h'],
        'oumr_lieu'        => ['Lieu court (carte événements)',          'text',     'Place du marché, Meylan'],
        'oumr_adresse'     => ['Adresse complète (bandeau featured)',    'text',     'Place du marché, Meylan (38240)'],
        'oumr_type_tag'    => ['Type — salons OU boutique (filtre)',     'text',     'salons'],
        'oumr_tag_label'   => ['Label du tag (ex : Marché de Noël)',    'text',     'Marché de Noël'],
        'oumr_lien_url'    => ['URL "En savoir plus"',                  'url',      ''],
        'oumr_lat'         => ['Latitude GPS (carte)',                   'text',     '45.210'],
        'oumr_lng'         => ['Longitude GPS (carte)',                  'text',     '5.781'],
    ];
    oumr_render_fields($post, $f);
    // Checkboxes séparées
    $is_featured = get_post_meta($post->ID, 'oumr_is_featured', true);
    $is_past     = get_post_meta($post->ID, 'oumr_is_past', true);
    echo '<p style="margin-top:12px;">';
    echo '<label><input type="checkbox" name="oumr_is_featured" value="1"' . checked($is_featured, '1', false) . '> ';
    echo '⭐ Afficher dans le <strong>bandeau "Prochain rendez-vous"</strong></label><br>';
    echo '<label style="margin-top:6px;display:block;"><input type="checkbox" name="oumr_is_past" value="1"' . checked($is_past, '1', false) . '> ';
    echo '🕐 Marquer comme <strong>événement passé</strong></label>';
    echo '</p>';
}

function oumr_shop_meta_box_cb($post) {
    wp_nonce_field('oumr_shop_save', 'oumr_shop_nonce');
    $f = [
        'oumr_ville'       => ['Ville',                          'text',     'Grenoble'],
        'oumr_cp'          => ['Code postal',                    'text',     '38000'],
        'oumr_description' => ['Description courte',            'textarea', 'Concept-store engagé...'],
        'oumr_horaires'    => ['Horaires',                       'text',     'Mardi – Samedi · 10h-19h'],
        'oumr_lien_url'    => ['URL "Voir l\'article"',          'url',      ''],
        'oumr_itineraire'  => ['URL itinéraire (Google Maps)',   'url',      ''],
        'oumr_lat'         => ['Latitude GPS (carte)',           'text',     '45.190'],
        'oumr_lng'         => ['Longitude GPS (carte)',          'text',     '5.726'],
    ];
    oumr_render_fields($post, $f);
}

// Rendu générique des champs
function oumr_render_fields($post, $fields) {
    echo '<table class="form-table" style="font-size:13px;">';
    foreach ($fields as $key => [$label, $type, $placeholder]) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr><th style="width:260px;padding:8px 10px;"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td style="padding:6px 10px;">';
        if ($type === 'textarea') {
            echo '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" rows="3" style="width:100%;max-width:500px;">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" style="width:100%;max-width:500px;">';
        }
        echo '</td></tr>';
    }
    echo '</table>';
}

// ──────────────────────────────────────────────
// Sauvegarde des meta
// ──────────────────────────────────────────────
add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Événement
    if (isset($_POST['oumr_event_nonce']) && wp_verify_nonce($_POST['oumr_event_nonce'], 'oumr_event_save')) {
        $text_fields = [
            'oumr_jour_num', 'oumr_mois_court', 'oumr_date_debut', 'oumr_date_fin',
            'oumr_horaires', 'oumr_lieu', 'oumr_adresse', 'oumr_type_tag',
            'oumr_tag_label', 'oumr_lat', 'oumr_lng',
        ];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        if (isset($_POST['oumr_lien_url'])) {
            update_post_meta($post_id, 'oumr_lien_url', esc_url_raw($_POST['oumr_lien_url']));
        }
        update_post_meta($post_id, 'oumr_is_featured', isset($_POST['oumr_is_featured']) ? '1' : '');
        update_post_meta($post_id, 'oumr_is_past',     isset($_POST['oumr_is_past'])     ? '1' : '');
    }

    // Point de vente
    if (isset($_POST['oumr_shop_nonce']) && wp_verify_nonce($_POST['oumr_shop_nonce'], 'oumr_shop_save')) {
        $text_fields = ['oumr_ville', 'oumr_cp', 'oumr_horaires', 'oumr_lat', 'oumr_lng'];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        if (isset($_POST['oumr_description'])) {
            update_post_meta($post_id, 'oumr_description', sanitize_textarea_field($_POST['oumr_description']));
        }
        foreach (['oumr_lien_url', 'oumr_itineraire'] as $url_field) {
            if (isset($_POST[$url_field])) {
                update_post_meta($post_id, $url_field, esc_url_raw($_POST[$url_field]));
            }
        }
    }
});
