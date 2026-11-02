<?php
/**
 * Title: Posts as cards
 * Slug: distinctui/posts-grid
 * Categories: distinctui, posts
 * Block Types: core/query
 * Inserter: yes
 * Description: Posts as same-height cards, one column on phones and three on desktop, with page numbers.
 *
 * @package distinctui
 */
?>
<!-- wp:query {"queryId":1,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"style":{"spacing":{"blockGap":"1.5rem"}},"layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
<!-- wp:group {"className":"is-style-card","style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
<div class="wp-block-group is-style-card"><!-- wp:post-featured-image {"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"0.5rem"}}}} /-->

<!-- wp:post-date {"fontSize":"small"} /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-excerpt {"excerptLength":24} /-->

<!-- wp:post-terms {"term":"category","fontSize":"x-small"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:group {"className":"is-style-panel","layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group is-style-panel"><!-- wp:paragraph -->
<p><strong>Nothing here yet.</strong> When there are posts, they'll show up here as cards.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:query-no-results -->

<!-- wp:query-pagination {"paginationArrow":"arrow","style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}},"layout":{"type":"flex","justifyContent":"left"}} -->
<!-- wp:query-pagination-previous {"label":"Newer"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"label":"Older"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:query -->
