# Fix Thumbnails — Plugin WordPress

Corrige l'absence de miniatures (thumbnails) en **back-office** et **front-office** WordPress.

## Problèmes résolus

| Symptôme | Cause probable | Solution apportée |
|---|---|---|
| Pas de miniature en BO ni en FO | `add_theme_support('post-thumbnails')` absent du thème | Activé via le plugin (priorité 99) |
| Images cassées après migration | URLs stockées en base pointent vers l'ancien domaine | Réécriture à la volée via `wp_get_attachment_image_src` |
| Tailles manquantes | Aucun `add_image_size()` déclaré | 3 tailles prédéfinies (300×300, 600×400, 1200×800) |
| Anciennes images sans miniature | Métadonnées non générées lors de l'upload | Page de régénération en masse dans **Outils → Régénérer miniatures** |

## Installation

1. Copier le dossier `fix-thumbnails/` dans `wp-content/plugins/`.
2. Activer le plugin depuis **Extensions → Extensions installées**.
3. Aller dans **Outils → Régénérer miniatures** et cliquer sur **Régénérer toutes les miniatures**.

## Personnalisation des tailles

Dans `fix-thumbnails.php`, modifier la fonction `ft_add_theme_support()` :

```php
add_image_size( 'ft-thumbnail', 300, 300, true );   // largeur, hauteur, crop
add_image_size( 'ft-medium',    600, 400, true );
add_image_size( 'ft-large',    1200, 800, false );  // false = proportionnel
```

## Affichage dans les templates

```php
// Dans single.php, archive.php, etc.
if ( has_post_thumbnail() ) {
    the_post_thumbnail( 'ft-medium' );
}
```

## Diagnostic rapide

Si les miniatures sont toujours absentes après régénération :

1. Vérifier les permissions du dossier `wp-content/uploads/` (755 / 644).
2. Vérifier que la librairie GD ou Imagick est bien installée sur le serveur.
3. Contrôler les logs PHP pour des erreurs de mémoire (`memory_limit`).
4. S'assurer que `UPLOADS` n'est pas redéfini dans `wp-config.php`.
