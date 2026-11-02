# WordPress

distinctUI ships as a WordPress block theme (WordPress 6.6 or later). Download it from https://distinctui.com/distinctui-wordpress.zip, or copy `wordpress/distinctui` from the repository into `wp-content/themes/`.

## What maps to what

| distinctUI | In the theme |
|---|---|
| Black, white, four greys | The only colours in the picker. Custom colours, gradients and duotones are off. |
| Signature colour | `--wp--custom--signature`, set by the style variation. Override in Styles, Additional CSS. |
| Ring palettes | Style variations: Signal (default), Mzansi, Earth, Coast, Jacaranda. Add `ring-1` to `ring-5` as a block class. |
| Bricolage Grotesque | Bundled in `assets/fonts`, registered in `theme.json`. Nothing loads from Google. |
| Hero, h1, h2 with a bold word | Headings are weight 300; select a word and press Bold. Heading style "Hero" for the one centred headline. |
| Lede, eyebrow, meta | Paragraph styles Lede, Eyebrow, Meta. |
| Badge, version chip | Paragraph style Badge. |
| `dui-btn`, `dui-btn-ghost`, text link | Button default, Outline style, Text link style. One black button per group. |
| Card, feature card, panel, note | Group (or Column) styles Card, Feature card, Panel, Note. |
| Signature mark, dashed until live | Image or Avatar styles Mark, "Mark, not live", "Mark, plain". |
| Logo | Site title style "Logo with ring". |
| Questions that open in place | The Details block, styled with hairlines and +. |
| Field error | Paragraph style Error: black, bold, a black "!" disc. |
| Menu shadow | Only on navigation submenus. |

## Patterns

All in the distinctUI category: Hero, Three cards, People in their rings, Plans, Questions, Not live yet, Last call to action, and Landing page (all of them in order). Use the landing page with the "Landing page (no title)" template.

## Rules that still apply

- Don't add colours through Additional CSS. Colour lives only on rings.
- Don't add a dark style variation or a dark cover block. Light only.
- One black button per group; the second is Outline.
- Example people in the patterns are invented and their portraits are AI-made. Replace them with your own.
