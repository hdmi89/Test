<?php
/**
 * Shortcode [resultats_motocross]  — v5 sécurisé (architecture v4 conservée)
 *
 * Corrections de sécurité appliquées sur la base du v4 fonctionnel :
 *  - Toutes les fonctions sont protégées par function_exists() pour éviter
 *    les erreurs "Cannot redeclare" si le code est chargé deux fois
 *  - Validation path traversal sur chaque segment de dossier
 *  - rmx_is_within_base() avec séparateur final (bloque les dossiers adjacents)
 *    et compatible PHP 7.x (substr au lieu de str_starts_with)
 *  - Vérification MIME réelle via finfo en plus de l'extension
 *  - rawurlencode() sur chaque segment de $cat_url
 *  - Validation du paramètre base= du shortcode
 *  - Pas d'AJAX : toutes les données sont embarquées côté serveur comme en v4
 */

if ( ! shortcode_exists( 'resultats_motocross' ) ) {
    add_shortcode( 'resultats_motocross', 'rmx_render_shortcode' );
}

/* ═══════════════════════════════════════════════════════════════
   SÉCURITÉ — helpers
═══════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'rmx_is_safe_segment' ) ) :
/**
 * Valide qu'un segment de chemin ne contient pas de tentative
 * de path traversal (.., slashes, caractères de contrôle).
 */
function rmx_is_safe_segment( $segment ) {
    if ( empty( $segment ) || strlen( $segment ) > 200 ) return false;
    return ! preg_match( '/(\.\.|[\/\\\\]|\p{C})/u', $segment );
}
endif;

if ( ! function_exists( 'rmx_is_within_base' ) ) :
/**
 * Vérifie qu'un chemin absolu est bien SOUS le dossier de base.
 * Résout les symlinks via realpath() et ajoute un séparateur final
 * pour éviter le bypass par dossier adjacent (/uploads/resultats-evil/).
 * Compatible PHP 7.x.
 */
function rmx_is_within_base( $path, $base_dir ) {
    $real_path = realpath( $path );
    $real_base = realpath( $base_dir );
    if ( $real_path === false || $real_base === false ) return false;
    $real_base_with_sep = rtrim( $real_base, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
    return substr( $real_path, 0, strlen( $real_base_with_sep ) ) === $real_base_with_sep;
}
endif;

if ( ! function_exists( 'rmx_is_real_pdf' ) ) :
/**
 * Vérifie qu'un fichier est un PDF via l'extension ET le magic number (finfo).
 */
function rmx_is_real_pdf( $filepath, $filename ) {
    if ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) !== 'pdf' ) return false;
    if ( function_exists( 'finfo_open' ) ) {
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime  = finfo_file( $finfo, $filepath );
        finfo_close( $finfo );
        if ( $mime !== 'application/pdf' ) return false;
    }
    return true;
}
endif;

/* ═══════════════════════════════════════════════════════════════
   SHORTCODE — rendu complet côté serveur (pas d'AJAX)
═══════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'rmx_render_shortcode' ) ) :
function rmx_render_shortcode( $atts = [] ) {

    $atts     = shortcode_atts( [ 'base' => 'uploads/resultats' ], $atts );
    $base_raw = trim( $atts['base'], '/' );

    // Validation du paramètre base : interdit les chemins absolus et les path traversal (..)
    if ( empty( $base_raw ) || preg_match( '/(\.\.|^\/|\p{C})/u', $base_raw ) ) {
        return '<p class="rmx-error">Paramètre <code>base</code> invalide.</p>';
    }

    $base_dir = WP_CONTENT_DIR . '/' . $base_raw . '/';
    $base_url = content_url() . '/' . $base_raw . '/';

    // Vérification que le dossier résolu est bien sous wp-content/
    if ( ! is_dir( $base_dir ) || ! rmx_is_within_base( $base_dir, WP_CONTENT_DIR ) ) {
        return '<p class="rmx-error">Dossier introuvable ou non autorisé.</p>';
    }

    $sort_order = [
        'essais chrono'            => 10,
        'essai chrono'             => 10,
        '1ere manche'              => 20,
        '1ère manche'              => 20,
        'manche 1'                 => 20,
        '2eme manche'              => 30,
        '2ème manche'              => 30,
        'manche 2'                 => 30,
        'repechage'                => 40,
        'repêchage'                => 40,
        'classement journee'       => 50,
        'classement journée'       => 50,
        'classement jour'          => 50,
        'situation au championnat' => 60,
        'situation championnat'    => 60,
        'situation'                => 60,
    ];

    $variant_keywords = [
        'gr a', 'gr b', 'gr c', 'gr d',
        'groupe a', 'groupe b', 'groupe c',
        'or', 'argent', 'bronze',
        'open', 'elite',
    ];

    /* ── Construction de l'arbre avec validations de sécurité ── */
    $tree = [];

    foreach ( rmx_sorted_dirs( $base_dir ) as $disc ) {
        if ( ! rmx_is_safe_segment( $disc ) ) continue;
        $disc_dir   = $base_dir . $disc . '/';
        $disc_label = mb_convert_case( str_replace( [ '-', '_' ], ' ', $disc ), MB_CASE_TITLE, 'UTF-8' );

        $years = array_filter( rmx_sorted_dirs( $disc_dir ), function ( $d ) {
            return preg_match( '/^\d{4}$/', $d );
        } );
        rsort( $years );

        foreach ( $years as $year ) {
            $year_dir = $disc_dir . $year . '/';

            foreach ( rmx_sorted_dirs( $year_dir ) as $ep_folder ) {
                if ( ! rmx_is_safe_segment( $ep_folder ) ) continue;
                $ep_dir  = $year_dir . $ep_folder . '/';
                $ep_name = preg_replace( '/^\d+[-_]\s*/', '', $ep_folder );
                $ep_name = mb_convert_case( str_replace( [ '-', '_' ], ' ', $ep_name ), MB_CASE_TITLE, 'UTF-8' );

                foreach ( rmx_sorted_dirs( $ep_dir ) as $cat_folder ) {
                    if ( ! rmx_is_safe_segment( $cat_folder ) ) continue;
                    $cat_dir = $ep_dir . $cat_folder . '/';

                    if ( ! rmx_is_within_base( $cat_dir, $base_dir ) ) continue;

                    $cat_url = $base_url
                        . rawurlencode( $disc )       . '/'
                        . rawurlencode( $year )       . '/'
                        . rawurlencode( $ep_folder )  . '/'
                        . rawurlencode( $cat_folder ) . '/';

                    $sections = rmx_parse_sections( $cat_dir, $cat_url, $sort_order, $variant_keywords );

                    if ( ! empty( $sections ) ) {
                        $tree[ $disc ]['label']                                               = $disc_label;
                        $tree[ $disc ]['years'][ $year ][ $ep_folder ]['name']                = $ep_name;
                        $tree[ $disc ]['years'][ $year ][ $ep_folder ]['cats'][ $cat_folder ] = [
                            'name'     => $cat_folder,
                            'sections' => $sections,
                        ];
                    }
                }
            }
        }
    }

    if ( empty( $tree ) ) {
        return '<p class="rmx-error">Aucun résultat disponible.</p>';
    }

    $json = wp_json_encode( $tree );

    ob_start();
    ?>
    <div id="rmx-app">

      <div class="rmx-filters">
        <div class="rmx-fg">
          <label for="rmx-disc">Discipline</label>
          <select id="rmx-disc"><option value="">— Choisir —</option></select>
        </div>
        <div class="rmx-fg">
          <label for="rmx-year">Saison</label>
          <select id="rmx-year" disabled><option value="">— Choisir —</option></select>
        </div>
        <div class="rmx-fg">
          <label for="rmx-ep">Épreuve</label>
          <select id="rmx-ep" disabled><option value="">— Choisir —</option></select>
        </div>
      </div>

      <div id="rmx-tabs" class="rmx-tabs" style="display:none;"></div>
      <div id="rmx-results" class="rmx-results" style="display:none;"></div>
      <p id="rmx-ph" class="rmx-ph">Sélectionnez une discipline, une saison et une épreuve.</p>

    </div>

    <style>
    #rmx-app { font-family: inherit; max-width: 860px; }
    .rmx-filters { display:flex; gap:14px; margin-bottom:28px; flex-wrap:wrap; align-items:flex-end; }
    .rmx-fg { display:flex; flex-direction:column; gap:5px; flex:1; min-width:150px; }
    .rmx-fg label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#888; }
    .rmx-fg select {
      padding:10px 36px 10px 12px; border:2px solid #ddd; border-radius:6px;
      font-size:14px; font-weight:600; color:#222; background:#fff;
      cursor:pointer; width:100%;
      -webkit-appearance:none; appearance:none;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 12px center;
      transition:border-color .15s;
    }
    .rmx-fg select:focus    { border-color:#cc0000; outline:none; }
    .rmx-fg select:disabled { opacity:.35; cursor:not-allowed; }
    .rmx-tabs { display:flex; gap:6px; margin-bottom:24px; flex-wrap:wrap; }
    .rmx-tab {
      padding:9px 22px; background:#eee; border:none; border-radius:4px;
      font-weight:800; font-size:13px; text-transform:uppercase;
      letter-spacing:.6px; cursor:pointer; color:#444;
      transition:background .15s, color .15s;
    }
    .rmx-tab:hover  { background:#ddd; }
    .rmx-tab.active { background:#cc0000; color:#fff; }
    .rmx-results { border-top:2px solid #eee; }
    .rmx-row {
      display:flex; align-items:center; padding:15px 0;
      border-bottom:1px solid #eee; gap:16px; flex-wrap:wrap;
    }
    .rmx-row:last-child { border-bottom:none; }
    .rmx-row-label { flex:1; font-weight:700; font-size:15px; color:#222; min-width:160px; }
    .rmx-row-buttons { display:flex; gap:8px; flex-wrap:wrap; }
    .rmx-btn {
      display:inline-flex; align-items:center; gap:6px;
      padding:8px 16px; border-radius:4px; font-size:13px; font-weight:700;
      text-decoration:none !important; white-space:nowrap;
      transition:background .15s; color:#fff !important;
    }
    .rmx-btn svg { width:13px; height:13px; flex-shrink:0; }
    .rmx-btn-primary  { background:#cc0000; }
    .rmx-btn-primary:hover  { background:#aa0000; }
    .rmx-btn-secondary { background:#555; }
    .rmx-btn-secondary:hover { background:#333; }
    .rmx-ph    { color:#aaa; font-style:italic; font-size:14px; }
    .rmx-error { color:#cc0000; font-weight:600; }
    @media (max-width:560px) {
      .rmx-row-label { font-size:14px; }
      .rmx-btn       { font-size:12px; padding:7px 12px; }
    }
    </style>

    <script>
    (function () {
      const DATA     = <?php echo $json; ?>;
      const PDF_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>';

      const selDisc = document.getElementById('rmx-disc');
      const selYear = document.getElementById('rmx-year');
      const selEp   = document.getElementById('rmx-ep');
      const tabsEl  = document.getElementById('rmx-tabs');
      const resEl   = document.getElementById('rmx-results');
      const phEl    = document.getElementById('rmx-ph');

      Object.keys(DATA).forEach(d => selDisc.appendChild(new Option(DATA[d].label, d)));

      function reset(from) {
        if (from <= 1) { selYear.innerHTML = '<option value="">— Choisir —</option>'; selYear.disabled = true; }
        if (from <= 2) { selEp.innerHTML   = '<option value="">— Choisir —</option>'; selEp.disabled   = true; }
        if (from <= 3) { tabsEl.innerHTML  = ''; tabsEl.style.display = 'none'; }
        resEl.style.display = 'none';
        phEl.style.display  = 'block';
      }

      selDisc.addEventListener('change', function () {
        reset(1);
        const d = this.value; if (!d) return;
        Object.keys(DATA[d].years).sort().reverse().forEach(y => selYear.appendChild(new Option(y, y)));
        selYear.disabled = false;
      });

      selYear.addEventListener('change', function () {
        reset(2);
        const d = selDisc.value, y = this.value; if (!d || !y) return;
        Object.entries(DATA[d].years[y]).forEach(([s, ep]) => selEp.appendChild(new Option(ep.name, s)));
        selEp.disabled = false;
      });

      selEp.addEventListener('change', function () {
        reset(3);
        const d = selDisc.value, y = selYear.value, e = this.value; if (!d || !y || !e) return;
        const cats    = DATA[d].years[y][e]?.cats || {};
        const catKeys = Object.keys(cats);
        if (!catKeys.length) return;

        catKeys.forEach((key, i) => {
          const btn = document.createElement('button');
          btn.className   = 'rmx-tab' + (i === 0 ? ' active' : '');
          btn.textContent = cats[key].name;
          btn.addEventListener('click', function () {
            tabsEl.querySelectorAll('.rmx-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            renderSections(d, y, e, key);
          });
          tabsEl.appendChild(btn);
        });

        tabsEl.style.display = 'flex';
        phEl.style.display   = 'none';
        renderSections(d, y, e, catKeys[0]);
      });

      function renderSections(d, y, e, cat) {
        resEl.innerHTML = '';
        const sections = DATA[d].years[y][e]?.cats[cat]?.sections || [];

        sections.forEach(sec => {
          const row  = document.createElement('div');
          row.className = 'rmx-row';
          const lbl  = document.createElement('span');
          lbl.className   = 'rmx-row-label';
          lbl.textContent = sec.name;
          const btns = document.createElement('div');
          btns.className = 'rmx-row-buttons';
          sec.files.forEach((f, i) => {
            const a = document.createElement('a');
            a.className = 'rmx-btn ' + (i === 0 ? 'rmx-btn-primary' : 'rmx-btn-secondary');
            a.href      = f.url;
            a.target    = '_blank';
            a.rel       = 'noopener noreferrer';
            a.innerHTML = PDF_ICON + ' ' + f.label;
            btns.appendChild(a);
          });
          row.appendChild(lbl);
          row.appendChild(btns);
          resEl.appendChild(row);
        });

        resEl.style.display = 'block';
      }
    })();
    </script>
    <?php
    return ob_get_clean();
}
endif;

/* ═══════════════════════════════════════════════════════════════
   FONCTIONS UTILITAIRES
═══════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'rmx_sorted_dirs' ) ) :
function rmx_sorted_dirs( $dir ) {
    if ( ! is_dir( $dir ) ) return [];
    $entries = array_diff( scandir( $dir ), [ '.', '..' ] );
    $dirs    = array_filter( $entries, function ( $d ) use ( $dir ) {
        return is_dir( $dir . $d );
    } );
    usort( $dirs, 'strnatcasecmp' );
    return array_values( $dirs );
}
endif;

if ( ! function_exists( 'rmx_remove_accents' ) ) :
function rmx_remove_accents( $str ) {
    $str = mb_strtolower( $str, 'UTF-8' );
    return strtr( $str, [
        'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','î'=>'i','ï'=>'i','í'=>'i',
        'ò'=>'o','ô'=>'o','ö'=>'o','ó'=>'o','õ'=>'o',
        'ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
        'ç'=>'c','ñ'=>'n',
    ] );
}
endif;

if ( ! function_exists( 'rmx_parse_sections' ) ) :
function rmx_parse_sections( $cat_dir, $cat_url, $sort_order, $variant_keywords ) {

    $pdf_files = array_filter(
        array_diff( scandir( $cat_dir ), [ '.', '..' ] ),
        function ( $f ) use ( $cat_dir ) {
            if ( is_dir( $cat_dir . $f ) ) return false;
            return rmx_is_real_pdf( $cat_dir . $f, $f );
        }
    );
    usort( $pdf_files, 'strnatcasecmp' );

    $sections_map = [];

    foreach ( $pdf_files as $file ) {
        if ( ! rmx_is_within_base( $cat_dir . $file, $cat_dir ) ) continue;

        $stem         = pathinfo( $file, PATHINFO_FILENAME );
        $section_part = $stem;
        if ( preg_match( '/ - (.+)$/u', $stem, $m ) ) {
            $section_part = trim( $m[1] );
        }

        $base_name = $section_part;
        $variant   = null;
        foreach ( $variant_keywords as $kw ) {
            if ( preg_match( '/(.*?)\s+' . preg_quote( $kw, '/' ) . '\s*$/iu', $section_part, $m2 ) ) {
                $base_name = trim( $m2[1] );
                $variant   = mb_convert_case( $kw, MB_CASE_TITLE, 'UTF-8' );
                break;
            }
        }

        $base_key = rmx_remove_accents( $base_name );
        $order    = 999;
        foreach ( $sort_order as $pk => $ord ) {
            if ( strpos( $base_key, $pk ) !== false ) { $order = $ord; break; }
        }

        if ( ! isset( $sections_map[ $base_key ] ) ) {
            $sections_map[ $base_key ] = [ 'name' => $base_name, 'order' => $order, 'files' => [] ];
        }

        $sections_map[ $base_key ]['files'][] = [
            'label' => $variant ?? 'Consulter',
            'url'   => $cat_url . rawurlencode( $file ),
        ];
    }

    uasort( $sections_map, function ( $a, $b ) { return $a['order'] <=> $b['order']; } );

    $sections = [];
    foreach ( $sections_map as $data ) {
        $sections[] = [ 'name' => $data['name'], 'files' => $data['files'] ];
    }

    return $sections;
}
endif;
