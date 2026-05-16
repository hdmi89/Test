<?php
/**
 * Enregistrement des éléments WPBakery (Visual Composer)
 * Catégorie "Atelier 2 Jeanne" dans le panneau WPBakery
 *
 * Chargé uniquement via add_action('vc_before_init', ...)
 */

// ── Hero ──────────────────────────────────────
vc_map([
    'name'        => '🌿 Hero — Intro page',
    'base'        => 'oumr_hero',
    'category'    => 'Atelier 2 Jeanne',
    'icon'        => 'dashicons-location',
    'description' => 'Section d\'introduction avec titre, texte et chips.',
    'params'      => [
        [
            'type'        => 'textfield',
            'heading'     => 'Label (petite ligne au-dessus du titre)',
            'param_name'  => 'label',
            'value'       => 'Salons &amp; points de vente',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Titre',
            'param_name'  => 'title',
            'value'       => 'Où me retrouver.',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Partie du titre en couleur sage (accent)',
            'param_name'  => 'accent',
            'value'       => 'retrouver.',
            'description' => 'Doit être une sous-chaîne exacte du titre.',
        ],
        [
            'type'        => 'textarea',
            'heading'     => 'Texte d\'introduction',
            'param_name'  => 'lead',
            'value'       => '',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Chips (séparés par des virgules)',
            'param_name'  => 'chips',
            'value'       => 'Salons & événements,Points de vente partenaires,Atelier sur RDV',
            'description' => 'Ex : Salons & événements,Atelier sur RDV',
        ],
    ],
]);

// ── Bandeau événement mis en avant ────────────
vc_map([
    'name'        => '🌿 Événement mis en avant',
    'base'        => 'oumr_featured_event',
    'category'    => 'Atelier 2 Jeanne',
    'icon'        => 'dashicons-star-filled',
    'description' => 'Bandeau noir avec l\'événement coché "mis en avant" dans le CPT.',
    'params'      => [
        [
            'type'        => 'textfield',
            'heading'     => 'ID de l\'événement (laisser vide = automatique)',
            'param_name'  => 'id',
            'value'       => '',
            'description' => 'Vide = prend l\'événement coché "Mis en avant" dans Événements > modifier.',
        ],
    ],
]);

// ── Grille d'événements ───────────────────────
vc_map([
    'name'        => '🌿 Grille d\'événements',
    'base'        => 'oumr_events_grid',
    'category'    => 'Atelier 2 Jeanne',
    'icon'        => 'dashicons-grid-view',
    'description' => 'Grille des événements avec filtres. Alimentée par le CPT Événements.',
    'params'      => [
        [
            'type'        => 'textfield',
            'heading'     => 'Nombre maximum d\'événements affichés',
            'param_name'  => 'count',
            'value'       => '12',
        ],
        [
            'type'        => 'dropdown',
            'heading'     => 'Afficher les boutons de filtre',
            'param_name'  => 'show_filters',
            'value'       => ['Oui' => 'yes', 'Non' => 'no'],
        ],
    ],
]);

// ── Grille points de vente ────────────────────
vc_map([
    'name'        => '🌿 Points de vente',
    'base'        => 'oumr_shops_grid',
    'category'    => 'Atelier 2 Jeanne',
    'icon'        => 'dashicons-store',
    'description' => 'Grille des boutiques partenaires. Alimentée par le CPT Points de vente.',
    'params'      => [
        [
            'type'        => 'textfield',
            'heading'     => 'Titre de la section',
            'param_name'  => 'title',
            'value'       => 'Mes boutiques partenaires',
        ],
        [
            'type'        => 'textarea',
            'heading'     => 'Texte d\'introduction',
            'param_name'  => 'intro',
            'value'       => 'Mes créations sont disponibles à l\'année dans ces boutiques partenaires de Grenoble et de l\'agglomération.',
        ],
    ],
]);

// ── Carte Leaflet ─────────────────────────────
vc_map([
    'name'        => '🌿 Carte interactive',
    'base'        => 'oumr_map',
    'category'    => 'Atelier 2 Jeanne',
    'icon'        => 'dashicons-location-alt',
    'description' => 'Carte OpenStreetMap avec tous les marqueurs (atelier + CPTs).',
    'params'      => [
        [
            'type'        => 'textfield',
            'heading'     => 'Latitude de l\'atelier',
            'param_name'  => 'atelier_lat',
            'value'       => '45.207',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Longitude de l\'atelier',
            'param_name'  => 'atelier_lng',
            'value'       => '5.785',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Nom de l\'atelier (popup)',
            'param_name'  => 'atelier_nom',
            'value'       => 'Atelier de Jeanne',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Ville / CP de l\'atelier',
            'param_name'  => 'atelier_cp',
            'value'       => 'Meylan, 38240',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Latitude centre carte',
            'param_name'  => 'center_lat',
            'value'       => '45.205',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Longitude centre carte',
            'param_name'  => 'center_lng',
            'value'       => '5.78',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Niveau de zoom initial',
            'param_name'  => 'zoom',
            'value'       => '11',
        ],
    ],
]);

// ── CTA final ─────────────────────────────────
vc_map([
    'name'        => '🌿 CTA final',
    'base'        => 'oumr_cta',
    'category'    => 'Atelier 2 Jeanne',
    'icon'        => 'dashicons-megaphone',
    'description' => 'Bloc d\'appel à l\'action avec fond vert sage.',
    'params'      => [
        [
            'type'        => 'textfield',
            'heading'     => 'Titre',
            'param_name'  => 'title',
            'value'       => 'Vous tenez une boutique éco-responsable ?',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Partie du titre en couleur (accent)',
            'param_name'  => 'title_accent',
            'value'       => 'éco-responsable ?',
            'description' => 'Doit être une sous-chaîne exacte du titre.',
        ],
        [
            'type'        => 'textarea',
            'heading'     => 'Texte',
            'param_name'  => 'text',
            'value'       => '',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'Texte du bouton',
            'param_name'  => 'btn_text',
            'value'       => 'Tissons le contact',
        ],
        [
            'type'        => 'textfield',
            'heading'     => 'URL du bouton (vide = page Contact)',
            'param_name'  => 'btn_url',
            'value'       => '',
        ],
    ],
]);
