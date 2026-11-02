<?php
/**
 * Title: Not live yet
 * Slug: distinctui/waitlist
 * Categories: distinctui, call-to-action
 * Keywords: waitlist, coming soon, launch, dashed
 * Viewport Width: 1280
 * Description: For something that isn't out yet: a dashed mark, a plain promise and one black pill. Switch the mark to the solid style when it ships.
 *
 * @package distinctui
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|80"},"blockGap":"1.5rem"}},"layout":{"type":"constrained","justifyContent":"left","contentSize":"42rem"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--80)"><!-- wp:image {"width":"88px","height":"88px","scale":"cover","sizeSlug":"full","linkDestination":"none","className":"is-style-mark-dashed"} -->
<figure class="wp-block-image size-full is-resized is-style-mark-dashed"><img src="<?php echo esc_url( get_theme_file_uri( "assets/images/scene-desk.jpg" ) ); ?>" alt="" style="object-fit:cover;width:88px;height:88px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"is-style-badge"} -->
<p class="is-style-badge">Not live yet</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Coming in <strong>March</strong></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">We'll email you once, on the day it opens. No newsletter, and you can leave the list with one click.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#join">Tell me when it opens</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
