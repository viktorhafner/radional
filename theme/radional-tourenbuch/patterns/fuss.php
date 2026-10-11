<?php
/**
 * Title: Fuß mit Logo
 * Slug: radional/fuss
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 */
$logo = esc_url(get_theme_file_uri('assets/logo/logo-weiss.png'));
$datenschutz = get_privacy_policy_url();
if (!$datenschutz) {
    $datenschutz = home_url('/datenschutz/');
}
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"3rem","bottom":"3rem"}},"elements":{"link":{"color":{"text":"var:preset|color|fusstext"},":hover":{"color":{"text":"var:preset|color|base"}}}}},"backgroundColor":"contrast","textColor":"fusstext","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-fusstext-color has-contrast-background-color has-text-color has-background has-link-color" style="padding-top:3rem;padding-bottom:3rem">
<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide">
<!-- wp:html -->
<a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Radional – Startseite"><img src="<?php echo $logo; ?>" alt="Radional" width="520" height="604" style="display:block;height:120px;width:auto"></a>
<!-- /wp:html -->

<!-- wp:group {"style":{"typography":{"fontSize":"0.8125rem","letterSpacing":"0.08em","textTransform":"uppercase"},"spacing":{"blockGap":"1.5rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group" style="font-size:0.8125rem;letter-spacing:0.08em;text-transform:uppercase">
<!-- wp:paragraph -->
<p><a href="<?php echo esc_url(home_url('/impressum/')); ?>">Impressum</a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><a href="<?php echo esc_url($datenschutz); ?>">Datenschutz</a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><a href="https://www.instagram.com/viktorhafner/">Instagram</a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><a href="https://www.facebook.com/viktor.hafner">Facebook</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
