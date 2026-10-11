<?php
/**
 * Title: Kopf mit Logo und Menü
 * Slug: radional/kopf
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 */
$r    = esc_url(get_theme_file_uri('assets/logo/r-schwarz.png'));
$wort = esc_url(get_theme_file_uri('assets/logo/wort-schwarz.png'));
$home = esc_url(home_url('/'));
?>
<!-- wp:group {"align":"full","style":{"border":{"bottom":{"color":"var:preset|color|accent-6","width":"1px"}},"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-base-background-color has-background" style="border-bottom-color:var(--wp--preset--color--accent-6);border-bottom-width:1px;padding-top:1rem;padding-bottom:1rem">
<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide">
<!-- wp:html -->
<a class="rt-logo" href="<?php echo $home; ?>" rel="home" aria-label="Radional – Startseite"><img class="rt-logo-r" src="<?php echo $r; ?>" alt="" width="273" height="360"><img class="rt-logo-wort" src="<?php echo $wort; ?>" alt="Radional" width="760" height="172"></a>
<!-- /wp:html -->

<!-- wp:group {"style":{"spacing":{"blockGap":"1.4rem"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group"><!-- wp:search {"label":"Suche","showLabel":false,"placeholder":"Berichte durchsuchen …","buttonText":"Suchen","buttonPosition":"button-only","buttonUseIcon":true,"isSearchFieldHidden":true,"className":"rt-suche"} /-->

<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"},"style":{"spacing":{"blockGap":"1.9rem"}}} -->
<!-- wp:navigation-link {"label":"Berichte","url":"<?php echo esc_url(home_url('/blog/')); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Karte","url":"<?php echo esc_url(home_url('/#karte')); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Bucket List","url":"<?php echo esc_url(home_url('/bucket-list/')); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Meine Bikes","url":"<?php echo esc_url(home_url('/meine-bikes/')); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Fotos","url":"<?php echo esc_url(home_url('/bike-trail-run-fotos/')); ?>","kind":"custom"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
