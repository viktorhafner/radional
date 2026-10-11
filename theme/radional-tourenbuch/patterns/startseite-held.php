<?php
/**
 * Title: Startseite – neuester Bericht groß
 * Slug: radional/startseite-held
 * Categories: radional
 * Description: Der neueste Bericht mit großem Titelbild und weißer Textkarte.
 */
?>
<!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false,"sticky":"exclude"},"align":"full"} -->
<div class="wp-block-query alignfull"><!-- wp:post-template -->
<!-- wp:group {"className":"rt-held","layout":{"type":"default"}} -->
<div class="wp-block-group rt-held"><!-- wp:post-featured-image {"isLink":true} /-->

<!-- wp:group {"className":"rt-held-karte","style":{"spacing":{"padding":{"top":"2.2rem","bottom":"2.2rem","left":"2.25rem","right":"2.25rem"},"blockGap":"0.9rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group rt-held-karte" style="padding-top:2.2rem;padding-right:2.25rem;padding-bottom:2.2rem;padding-left:2.25rem"><!-- wp:radional/kicker /-->

<!-- wp:post-title {"level":2,"isLink":true,"style":{"typography":{"fontSize":"clamp(2.25rem, 4.6vw, 3.75rem)","lineHeight":"1.02"}}} /-->

<!-- wp:post-excerpt {"excerptLength":24} /-->

<!-- wp:read-more {"content":"Weiterlesen","className":"rt-weiterlesen"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
