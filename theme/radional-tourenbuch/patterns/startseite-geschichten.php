<?php
/**
 * Title: Startseite – neueste Geschichten
 * Slug: radional/startseite-geschichten
 * Categories: radional
 * Description: Der zweitneueste Bericht groß, die vier davor als Liste daneben.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"5.5rem","bottom":"2.5rem"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-top:5.5rem;padding-bottom:2.5rem"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Neueste Geschichten</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%"><!-- wp:query {"queryId":12,"query":{"perPage":1,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date","inherit":false,"sticky":"exclude"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->

<!-- wp:radional/kicker /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"2.4rem","lineHeight":"1.08"}}} /-->

<!-- wp:post-excerpt {"excerptLength":32} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:query {"queryId":13,"query":{"perPage":4,"pages":0,"offset":2,"postType":"post","order":"desc","orderBy":"date","inherit":false,"sticky":"exclude"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:group {"className":"rt-klein","style":{"spacing":{"blockGap":"1.1rem"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group rt-klein"><!-- wp:post-featured-image {"isLink":true} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.35rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:radional/kicker /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"1.45rem","lineHeight":"1.15"}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
