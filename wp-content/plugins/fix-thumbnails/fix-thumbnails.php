<?php
/**
 * Plugin Name: Fix Thumbnails
 * Plugin URI:  https://github.com/hdmi89/test
 * Description: Corrige l'affichage des miniatures (thumbnails) en back-office et front-office WordPress.
 * Version:     1.0.0
 * Author:      hdmi89
 * License:     GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

/**
 * 1. Activer le support des miniatures pour tous les types de posts.
 */
add_action( 'after_setup_theme', 'ft_add_theme_support', 99 );
function ft_add_theme_support() {
    add_theme_support( 'post-thumbnails' );

    // Tailles d'image standard — ajustez selon vos besoins.
    add_image_size( 'ft-thumbnail',  300, 300, true );
    add_image_size( 'ft-medium',     600, 400, true );
    add_image_size( 'ft-large',     1200, 800, false );
}

/**
 * 2. Exposer les tailles personnalisées dans le sélecteur de l'éditeur de blocs.
 */
add_filter( 'image_size_names_choose', 'ft_custom_image_sizes' );
function ft_custom_image_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'ft-thumbnail' => __( 'Miniature (300×300)', 'fix-thumbnails' ),
        'ft-medium'    => __( 'Moyenne (600×400)',   'fix-thumbnails' ),
        'ft-large'     => __( 'Grande (1200×800)',   'fix-thumbnails' ),
    ) );
}

/**
 * 3. Corriger les URLs de miniatures brisées dues à un déplacement de site / changement de domaine.
 *    Si l'URL stockée ne correspond pas à l'URL actuelle du site, on la réécrit à la volée.
 */
add_filter( 'wp_get_attachment_image_src', 'ft_fix_attachment_url', 10, 4 );
function ft_fix_attachment_url( $image, $attachment_id, $size, $icon ) {
    if ( ! $image ) {
        return $image;
    }

    $stored_url = get_post_meta( $attachment_id, '_wp_attached_file', true );
    if ( ! $stored_url ) {
        return $image;
    }

    $upload_dir  = wp_upload_dir();
    $correct_url = trailingslashit( $upload_dir['baseurl'] ) . ltrim( $stored_url, '/' );

    // Si l'URL renvoyée ne correspond pas, on corrige.
    if ( strpos( $image[0], $upload_dir['baseurl'] ) === false ) {
        $image[0] = $correct_url;
    }

    return $image;
}

/**
 * 4. Régénérer les métadonnées d'une pièce jointe spécifique via AJAX (admin).
 *    Appeler via : wp_ajax_ft_regenerate_thumbnail
 */
add_action( 'wp_ajax_ft_regenerate_thumbnail', 'ft_regenerate_thumbnail_ajax' );
function ft_regenerate_thumbnail_ajax() {
    check_ajax_referer( 'ft_regenerate_nonce', 'nonce' );

    if ( ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( __( 'Permission refusée.', 'fix-thumbnails' ) );
    }

    $attachment_id = intval( $_POST['attachment_id'] ?? 0 );
    if ( ! $attachment_id ) {
        wp_send_json_error( __( 'ID de pièce jointe invalide.', 'fix-thumbnails' ) );
    }

    $result = ft_regenerate_attachment( $attachment_id );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( __( 'Miniature régénérée avec succès.', 'fix-thumbnails' ) );
}

function ft_regenerate_attachment( $attachment_id ) {
    $file = get_attached_file( $attachment_id );
    if ( ! $file || ! file_exists( $file ) ) {
        return new WP_Error( 'file_not_found', sprintf(
            __( 'Fichier introuvable : %s', 'fix-thumbnails' ),
            $file
        ) );
    }

    $metadata = wp_generate_attachment_metadata( $attachment_id, $file );
    if ( empty( $metadata ) ) {
        return new WP_Error( 'metadata_error', __( 'Impossible de générer les métadonnées.', 'fix-thumbnails' ) );
    }

    wp_update_attachment_metadata( $attachment_id, $metadata );
    return true;
}

/**
 * 5. Page d'administration : régénération en masse des miniatures.
 */
add_action( 'admin_menu', 'ft_admin_menu' );
function ft_admin_menu() {
    add_management_page(
        __( 'Régénérer les miniatures', 'fix-thumbnails' ),
        __( 'Régénérer miniatures', 'fix-thumbnails' ),
        'upload_files',
        'fix-thumbnails',
        'ft_admin_page'
    );
}

function ft_admin_page() {
    $processed = 0;
    $errors    = array();

    if ( isset( $_POST['ft_regenerate_all'] ) && check_admin_referer( 'ft_bulk_regenerate' ) ) {
        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );

        foreach ( $attachments as $id ) {
            $result = ft_regenerate_attachment( $id );
            if ( is_wp_error( $result ) ) {
                $errors[] = sprintf( 'ID %d : %s', $id, $result->get_error_message() );
            } else {
                $processed++;
            }
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Régénérer les miniatures', 'fix-thumbnails' ); ?></h1>

        <?php if ( $processed > 0 ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php printf(
                    esc_html__( '%d miniature(s) régénérée(s) avec succès.', 'fix-thumbnails' ),
                    $processed
                ); ?></p>
            </div>
        <?php endif; ?>

        <?php foreach ( $errors as $err ) : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $err ); ?></p></div>
        <?php endforeach; ?>

        <p><?php esc_html_e(
            'Cliquez sur le bouton ci-dessous pour régénérer toutes les miniatures. Cette opération peut prendre plusieurs minutes selon le nombre d\'images.',
            'fix-thumbnails'
        ); ?></p>

        <form method="post">
            <?php wp_nonce_field( 'ft_bulk_regenerate' ); ?>
            <input type="hidden" name="ft_regenerate_all" value="1">
            <?php submit_button( __( 'Régénérer toutes les miniatures', 'fix-thumbnails' ) ); ?>
        </form>
    </div>
    <?php
}
