=== distinctUI ===
Contributors: distinctui
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.2.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Black, white, grey. Then one mark that's yours.

== Description ==

distinctUI is a block theme for the distinctUI design system (https://distinctui.com). Lots of white space, one typeface, black pill buttons, light headings with one bold word, and one signature colour that lives only on a signature mark.

What you get:

* Black, white and four greys in the colour picker, and nothing else. No custom colours, gradients or duotones.
* Bricolage Grotesque, bundled with the theme, so no font is loaded from Google.
* Templates for the blog, posts, pages, archives, search and 404, plus a "Landing page (no title)" template.
* Patterns: hero, three cards, people in their rings, plans, questions, "not live yet" and a last call to action, and a whole landing page made of them.
* Block styles named after the rules: Card, Feature card, Panel and Note on groups; Lede, Eyebrow, Meta, Badge and Error on paragraphs; Hero on headings; Text link on buttons; Mark, "Mark, not live" and "Mark, plain" on images and avatars; "Logo with ring" on the site title.
* Five ring palettes as style variations: Signal (default), Mzansi, Earth, Coast and Jacaranda.
* "Mark with initials" on groups: a ring with letters inside, for people and projects without a photo.
* The look carried into the big plugins: WooCommerce (shop, product, cart, checkout and its blocks), Contact Form 7, WPForms, Gravity Forms, Yoast and Rank Math breadcrumbs, Jetpack, and the WPML and Polylang language switchers. Pills to press, black bold errors, grey notices, no colour. It loads only when one of them is active.

Light only, by design. There is no dark mode.

== Settings ==

Appearance, distinctUI has four choices, and nothing that can break the look:

* Ring palette: Signal, Mzansi, Earth, Coast or Jacaranda.
* Signature colour: the palette's first colour, or your own.
* Your mark: your initials in the ring, or a picture.
* Room: Airy (the default) or Compact, for pages with a lot on them.

The typeface, greys, corners and sizes are part of the system, so they aren't settings. The Styles screen under Appearance, Editor still works too; the settings page wins where they differ.

== Your signature colour ==

The signature colour draws the ring in the logo and on any image, avatar or group with a Mark style. Set it under Appearance, distinctUI. To set it in CSS instead, add this under Styles, Additional CSS:

:root { --wp--custom--signature: #1f3bff; }

To give a person or project a different ring from the palette, add ring-1 to ring-5 to the block's Additional CSS classes. Colour lives only on rings. Use black, weight or space for everything else that needs to stand out.

== distinctUI+ ==

distinctUI+ is the paid builder for this theme: a full-screen canvas that lays out your content three ways, keeps every page inside the rules, and lets your own Claude build pages over MCP. Pages it makes are ordinary WordPress blocks, so they stay up, unchanged, if you stop paying or remove it.

== Rules ==

The twelve rules are at https://distinctui.com/#rules. The short version: light only; black, white and four greys; colour only on rings; one typeface; light headings with one bold word; black pill main actions; white space before borders, borders before shadows; pills to press, soft corners to read; dashed until live; errors black and bold; hover is a black edge; write plainly.

== Changelog ==

= 0.2.0 =
* Settings under Appearance, distinctUI: palette, signature colour, your mark, Airy or Compact.
* Styling for WooCommerce, the big form plugins, SEO breadcrumbs, Jetpack and language switchers.
* "Mark with initials" group style, and form styles shared with distinctUI+.

= 0.1.0 =
* First version.

== Copyright ==

distinctUI WordPress theme, copyright Venus ARC (Pty) Ltd. MIT licence, which is GPL-compatible.

Bricolage Grotesque by Mathieu Triay, SIL Open Font License 1.1 (assets/fonts/OFL.txt).

The Mzansi palette uses the South African flag colours, credited to Version One.

Example portraits and photos in assets/images are AI-generated people and places that don't exist. Replace them with your own.
