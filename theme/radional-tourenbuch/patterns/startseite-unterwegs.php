<?php
/**
 * Title: Startseite – Fotostreifen „Unterwegs“
 * Slug: radional/startseite-unterwegs
 * Categories: radional
 * Description: Titelbilder älterer Berichte als Fotostreifen, jedes führt zum Bericht.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"5.5rem","bottom":"2.5rem"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-top:5.5rem;padding-bottom:2.5rem"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Unterwegs</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":14,"query":{"perPage":4,"pages":0,"offset":6,"postType":"post","order":"desc","orderBy":"date","inherit":false,"sticky":"exclude"},"className":"rt-fotos"} -->
<div class="wp-block-query rt-fotos"><!-- wp:post-template {"style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"grid","columnCount":4}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"1"} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->
