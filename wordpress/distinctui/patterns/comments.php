<?php
/**
 * Title: Comments
 * Slug: distinctui/comments
 * Categories: distinctui
 * Inserter: no
 * Description: Comments with each avatar in a plain black ring, and a calm reply form.
 *
 * @package distinctui
 */
?>
<!-- wp:comments {"layout":{"type":"constrained"}} -->
<div class="wp-block-comments"><!-- wp:comments-title {"level":2,"fontSize":"xx-large"} /-->

<!-- wp:comment-template {"style":{"spacing":{"blockGap":"0"}}} -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"1.25rem","bottom":"1.25rem"}},"border":{"bottom":{"color":"var:preset|color|grey-200","width":"1px"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--grey-200);border-bottom-width:1px;padding-top:1.25rem;padding-bottom:1.25rem"><!-- wp:avatar {"size":40} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.375rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:comment-author-name /-->

<!-- wp:comment-date /--></div>
<!-- /wp:group -->

<!-- wp:comment-content /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"1rem"}},"fontSize":"small","layout":{"type":"flex"}} -->
<div class="wp-block-group has-small-font-size"><!-- wp:comment-reply-link /-->

<!-- wp:comment-edit-link /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:comment-template -->

<!-- wp:comments-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:comments-pagination-previous /-->

<!-- wp:comments-pagination-numbers /-->

<!-- wp:comments-pagination-next /-->
<!-- /wp:comments-pagination -->

<!-- wp:post-comments-form /--></div>
<!-- /wp:comments -->
