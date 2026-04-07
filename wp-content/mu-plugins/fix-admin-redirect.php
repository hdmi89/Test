<?php
/**
 * Plugin Name: Fix Admin Redirect
 * Description: Corrige les redirections wp-admin brisées (mauvais siteurl/home, boucles de redirection, cookies).
 * Version:     1.0.0
 * Author:      hdmi89
 *
 * Ce fichier est un Must-Use plugin (mu-plugins) :
 *  - il se charge AVANT tous les plugins normaux ;
 *  - il ne peut pas être désactivé depuis l'interface ;
 *  - il s'exécute même si WordPress est partiellement cassé.
 */

defined( 'ABSPATH' ) || exit;

// ────────────────────────────────────────────────────────────────────────────
// 1. Forcer siteurl et home à correspondre au domaine de la requête en cours.
//    Résout les redirections brisées après une migration ou un changement de domaine.
// ────────────────────────────────────────────────────────────────────────────
if ( ! defined( 'WP_SITEURL' ) || ! defined( 'WP_HOME' ) ) {

    $far_scheme = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' )
                  || ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
                  || ( ! empty( $_SERVER['SERVER_PORT'] ) && (int) $_SERVER['SERVER_PORT'] === 443 )
        ? 'https'
        : 'http';

    $far_host = isset( $_SERVER['HTTP_HOST'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) )
        : '';

    if ( $far_host ) {
        $far_base = $far_scheme . '://' . $far_host;

        if ( ! defined( 'WP_SITEURL' ) ) {
            define( 'WP_SITEURL', $far_base );
        }
        if ( ! defined( 'WP_HOME' ) ) {
            define( 'WP_HOME', $far_base );
        }
    }
}

// ────────────────────────────────────────────────────────────────────────────
// 2. Synchroniser les options en base si elles diffèrent du domaine actuel.
//    Évite la boucle : wp-admin → mauvais domaine → 404 → wp-admin…
// ────────────────────────────────────────────────────────────────────────────
add_action( 'init', 'far_sync_site_urls', 1 );
function far_sync_site_urls() {
    if ( ! defined( 'WP_SITEURL' ) || ! defined( 'WP_HOME' ) ) {
        return;
    }

    $stored_siteurl = get_option( 'siteurl' );
    $stored_home    = get_option( 'home' );

    if ( $stored_siteurl !== WP_SITEURL ) {
        update_option( 'siteurl', WP_SITEURL );
    }
    if ( $stored_home !== WP_HOME ) {
        update_option( 'home', WP_HOME );
    }
}

// ────────────────────────────────────────────────────────────────────────────
// 3. Corriger l'URL de redirection après connexion.
//    Par défaut WordPress redirige vers admin_url() — si celui-ci est cassé,
//    on force /wp-admin/ relatif au domaine courant.
// ────────────────────────────────────────────────────────────────────────────
add_filter( 'login_redirect', 'far_fix_login_redirect', 999, 3 );
function far_fix_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    if ( is_wp_error( $user ) || ! is_a( $user, 'WP_User' ) ) {
        return $redirect_to;
    }

    // Si la redirection cible un domaine différent du site courant, on force wp-admin.
    if ( defined( 'WP_SITEURL' ) ) {
        $target_host = wp_parse_url( $redirect_to, PHP_URL_HOST );
        $site_host   = wp_parse_url( WP_SITEURL, PHP_URL_HOST );

        if ( $target_host && $site_host && $target_host !== $site_host ) {
            $redirect_to = trailingslashit( WP_SITEURL ) . 'wp-admin/';
        }
    }

    return $redirect_to;
}

// ────────────────────────────────────────────────────────────────────────────
// 4. Corriger le domaine des cookies d'authentification.
//    Un COOKIE_DOMAIN incorrect empêche le navigateur d'envoyer le cookie
//    wp-admin → WordPress considère l'utilisateur comme non connecté → redirect.
// ────────────────────────────────────────────────────────────────────────────
if ( ! defined( 'COOKIE_DOMAIN' ) ) {
    $far_cookie_host = isset( $_SERVER['HTTP_HOST'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) )
        : '';

    // Supprimer le port éventuel (:80, :443, :8080…)
    $far_cookie_host = preg_replace( '/:\d+$/', '', $far_cookie_host );

    if ( $far_cookie_host ) {
        define( 'COOKIE_DOMAIN', $far_cookie_host );
    }
}

// ────────────────────────────────────────────────────────────────────────────
// 5. Détecter et casser les boucles de redirection sur wp-admin.
//    Si le compteur atteint 5 redirections consécutives, on les coupe
//    et on affiche un message de diagnostic.
// ────────────────────────────────────────────────────────────────────────────
add_action( 'init', 'far_detect_redirect_loop' );
function far_detect_redirect_loop() {
    if ( ! is_admin() ) {
        return;
    }

    $key   = 'far_redirect_count_' . md5( $_SERVER['REQUEST_URI'] ?? '' );
    $count = (int) get_transient( $key );

    if ( $count >= 5 ) {
        delete_transient( $key );
        wp_die(
            '<h1>Boucle de redirection détectée</h1>'
            . '<p>WordPress a été redirigé plus de 5 fois vers la même URL (<code>'
            . esc_html( $_SERVER['REQUEST_URI'] ?? '' )
            . '</code>).</p>'
            . '<p><strong>Causes fréquentes :</strong></p>'
            . '<ul>'
            . '<li>Les options <code>siteurl</code> / <code>home</code> en base ne correspondent pas à l\'URL réelle.</li>'
            . '<li>Un fichier <code>.htaccess</code> mal configuré.</li>'
            . '<li>Les constantes <code>WP_SITEURL</code> / <code>WP_HOME</code> dans <code>wp-config.php</code> pointent vers le mauvais domaine.</li>'
            . '<li>Un conflit de plugin forçant une redirection.</li>'
            . '</ul>'
            . '<p><a href="' . esc_url( admin_url() ) . '">Réessayer</a></p>',
            'Boucle de redirection — Fix Admin Redirect',
            array( 'response' => 200 )
        );
    }

    set_transient( $key, $count + 1, 10 ); // expire après 10 secondes
}

// ────────────────────────────────────────────────────────────────────────────
// 6. Page de diagnostic dans Outils → Diagnostic Redirect.
// ────────────────────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'far_admin_menu' );
function far_admin_menu() {
    add_management_page(
        __( 'Diagnostic Redirect', 'fix-admin-redirect' ),
        __( 'Diagnostic Redirect', 'fix-admin-redirect' ),
        'manage_options',
        'fix-admin-redirect',
        'far_diagnostic_page'
    );
}

function far_diagnostic_page() {
    $siteurl        = get_option( 'siteurl' );
    $home           = get_option( 'home' );
    $const_siteurl  = defined( 'WP_SITEURL' ) ? WP_SITEURL : '(non défini)';
    $const_home     = defined( 'WP_HOME' )    ? WP_HOME    : '(non défini)';
    $cookie_domain  = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '(non défini)';
    $cookie_path    = defined( 'COOKIEPATH' )    ? COOKIEPATH    : '(non défini)';
    $admin_url      = admin_url();
    $login_url      = wp_login_url();
    $server_host    = $_SERVER['HTTP_HOST'] ?? '(inconnu)';

    $ok   = '<span style="color:green;font-weight:bold;">&#10003;</span>';
    $warn = '<span style="color:orange;font-weight:bold;">&#9888;</span>';
    $err  = '<span style="color:red;font-weight:bold;">&#10007;</span>';

    $match_siteurl = ( wp_parse_url( $siteurl, PHP_URL_HOST ) === $server_host );
    $match_home    = ( wp_parse_url( $home,    PHP_URL_HOST ) === $server_host );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Diagnostic — Redirections wp-admin', 'fix-admin-redirect' ); ?></h1>
        <table class="widefat striped" style="max-width:900px">
            <thead>
                <tr><th>Paramètre</th><th>Valeur</th><th>Statut</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>HTTP_HOST</code> (requête actuelle)</td>
                    <td><?php echo esc_html( $server_host ); ?></td>
                    <td><?php echo $ok; ?></td>
                </tr>
                <tr>
                    <td><code>siteurl</code> (option BDD)</td>
                    <td><?php echo esc_html( $siteurl ); ?></td>
                    <td><?php echo $match_siteurl ? $ok : $err; ?>
                        <?php if ( ! $match_siteurl ) echo '&nbsp;<em>Ne correspond pas au domaine actuel</em>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>home</code> (option BDD)</td>
                    <td><?php echo esc_html( $home ); ?></td>
                    <td><?php echo $match_home ? $ok : $err; ?>
                        <?php if ( ! $match_home ) echo '&nbsp;<em>Ne correspond pas au domaine actuel</em>'; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>WP_SITEURL</code> (constante)</td>
                    <td><?php echo esc_html( $const_siteurl ); ?></td>
                    <td><?php echo ( $const_siteurl !== '(non défini)' ) ? $ok : $warn; ?></td>
                </tr>
                <tr>
                    <td><code>WP_HOME</code> (constante)</td>
                    <td><?php echo esc_html( $const_home ); ?></td>
                    <td><?php echo ( $const_home !== '(non défini)' ) ? $ok : $warn; ?></td>
                </tr>
                <tr>
                    <td><code>COOKIE_DOMAIN</code></td>
                    <td><?php echo esc_html( $cookie_domain ); ?></td>
                    <td><?php echo ( $cookie_domain !== '(non défini)' ) ? $ok : $warn; ?></td>
                </tr>
                <tr>
                    <td><code>COOKIEPATH</code></td>
                    <td><?php echo esc_html( $cookie_path ); ?></td>
                    <td><?php echo $ok; ?></td>
                </tr>
                <tr>
                    <td><code>admin_url()</code></td>
                    <td><a href="<?php echo esc_url( $admin_url ); ?>"><?php echo esc_html( $admin_url ); ?></a></td>
                    <td><?php echo $ok; ?></td>
                </tr>
                <tr>
                    <td><code>wp_login_url()</code></td>
                    <td><a href="<?php echo esc_url( $login_url ); ?>"><?php echo esc_html( $login_url ); ?></a></td>
                    <td><?php echo $ok; ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ( ! $match_siteurl || ! $match_home ) : ?>
        <div class="notice notice-error" style="max-width:900px;margin-top:20px">
            <p><strong><?php esc_html_e( 'Problème détecté :', 'fix-admin-redirect' ); ?></strong>
            <?php esc_html_e( 'Les URLs en base de données ne correspondent pas au domaine actuel. Ce plugin les a déjà corrigées automatiquement. Si le problème persiste, videz le cache de votre navigateur et de votre hébergeur.', 'fix-admin-redirect' ); ?>
            </p>
        </div>
        <?php else : ?>
        <div class="notice notice-success" style="max-width:900px;margin-top:20px">
            <p><?php esc_html_e( 'Toutes les URLs correspondent au domaine actuel.', 'fix-admin-redirect' ); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
