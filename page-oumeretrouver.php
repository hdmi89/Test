<?php
/**
 * Template Name: Où me retrouver
 * Description: Page administrable via WPBakery — Atelier 2 Jeanne
 */

// Ajouter une classe body pour cibler ce template dans le CSS
add_filter('body_class', function($classes) {
    $classes[] = 'oumr-template';
    return $classes;
});

get_header();
?>

<div id="main-content">
  <div class="oumr-page">
    <?php while (have_posts()): the_post(); ?>
      <?php the_content(); /* WPBakery génère ici tout le contenu */ ?>
    <?php endwhile; ?>
  </div>
</div>

<?php get_footer(); ?>
