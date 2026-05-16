<?php
/**
 * Salient Child Theme — functions.php
 * Atelier 2 Jeanne
 */

// Charger le CSS du thème parent Salient
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('salient-parent', get_template_directory_uri() . '/style.css');
}, 10);

// Assets spécifiques à la page "Où me retrouver" — chargés uniquement sur ce template
add_action('wp_enqueue_scripts', function() {
    if (!is_page_template('page-oumeretrouver.php')) return;

    wp_enqueue_style(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        [],
        '1.9.4'
    );
    wp_enqueue_style(
        'oumr-page',
        get_stylesheet_directory_uri() . '/oumeretrouver.css',
        ['leaflet'],
        '1.1'
    );
    wp_enqueue_script(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        '1.9.4',
        true
    );
}, 20);

// Inclure les modules
require_once get_stylesheet_directory() . '/inc/oumr-cpt.php';
require_once get_stylesheet_directory() . '/inc/oumr-shortcodes.php';

// WPBakery — enregistrement des éléments custom
add_action('vc_before_init', function() {
    require_once get_stylesheet_directory() . '/inc/oumr-vc.php';
});
