# distinctUI

**Black, white, grey. Then one mark that's yours.**

distinctUI is a small, open-source design system packaged as a [Claude](https://claude.ai) skill. It gives your app lots of white space, one typeface, black pill buttons, and a single signature colour that lives only on a signature mark.

It's the system behind [Version One](https://versionone.co.za), where South African builders share what they've shipped.

Site and live components: **https://distinctui.com**. A short narrated how-to is at [distinctui.com/#watch](https://distinctui.com/#watch).

## Add the skill

**Claude Code**

```
/plugin marketplace add VenusOne-Lee/distinctui
/plugin install distinctui
```

**Any agent that reads skills**

```
npx skills add VenusOne-Lee/distinctui
```

**By hand:** copy `skills/distinctui/` into your project's `.claude/skills/` folder, or upload it as a skill in Claude.

Then ask Claude for the look: "Build the sign-up page in distinctUI. Our signature colour is #1f3bff."

## distinctUI@work

For apps people use all day, load the work layer after the core files. It adds an app shell, dense tables with selection and a bulk bar, a command menu, approvals, audit trails, reports and settings, all still black, white and grey. Seventeen templates show it in four invented apps: contracts and invoices, hiring, a support desk, and a shift rota with a driver's phone. See [distinctui.com/work.html](https://distinctui.com/work.html) and [references/work.md](skills/distinctui/references/work.md).

```html
<link rel="stylesheet" href="distinctui.css">
<link rel="stylesheet" href="distinctui-work.css">
<script src="distinctui.js" defer></script>
<script src="distinctui-work.js" defer></script>
```

## Use it over MCP

distinctUI also runs as a remote MCP server at `https://distinctui.com/mcp`. Any agent that speaks MCP can read the rules and templates, check a page against the rules, and compose a page from content. Nothing to install, no sign-in, nothing stored.

**Claude Code**

```
claude mcp add --transport http distinctui https://distinctui.com/mcp
```

**Claude, Cursor and others:** add `https://distinctui.com/mcp` as a custom connector or a remote (Streamable HTTP) server.

| Tool | What it does |
| --- | --- |
| `get_rules` | The twelve rules, what's left out on purpose, and the values the checker uses |
| `get_tokens`, `pick_palette` | The tokens, and the five ring palettes with the CSS for each |
| `list_components`, `get_component` | Copy-ready markup for each component group |
| `list_templates`, `get_template` | The 50 page templates |
| `get_reference` | Any reference, such as `page-spec`, `voice` or `review` |
| `check_page` | Checks HTML against the rules and says how to fix each finding |
| `check_spec`, `compose_page` | Checks a [page spec](skills/distinctui/references/page-spec.md) and turns it into a finished page |

The checker and composer are plain JavaScript with no dependencies, so you can run them yourself too:

```js
import { checkHTML } from "@distinctui/css/check.js";
import { composePage } from "@distinctui/css/compose.js";

const { ok, findings } = checkHTML(html);
```

## Use the CSS without Claude

From npm:

```
npm install @distinctui/css
```

```js
import "@distinctui/css";              // distinctui.css
import "@distinctui/css/distinctui.js"; // optional motion and interaction
// also: @distinctui/css/tailwind.css, @distinctui/css/tokens.json, @distinctui/css/icons.svg
```

Or straight from a CDN, no build step:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@distinctui/css@0.2.0/skills/distinctui/assets/distinctui.css">
<script src="https://cdn.jsdelivr.net/npm/@distinctui/css@0.2.0/skills/distinctui/assets/distinctui.js" defer></script>
```

Plain CSS:

```html
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap">
<link rel="stylesheet" href="distinctui.css">
<script src="distinctui.js" defer></script> <!-- optional: motion and interaction -->
<style>:root { --dui-signature: #1f3bff; }</style>
<body class="dui-page">
  <h1 class="dui-hero">Your product, <b>shipped</b>.</h1>
  <a class="dui-btn" href="/start">Start</a>
</body>
```

Tailwind CSS v4:

```css
@import "tailwindcss";
@import "./tailwind.css"; /* from skills/distinctui/assets */
@theme { --color-signature: #1f3bff; }
```

## Use it in WordPress

A block theme with the same look, for WordPress 6.6 or later. Download [distinctui-wordpress.zip](https://distinctui.com/distinctui-wordpress.zip), then in WordPress go to **Appearance → Themes → Add New Theme → Upload Theme**.

- Black, white and four greys in the colour picker, and nothing else. Bricolage Grotesque is bundled.
- Templates for the blog, posts, pages, archives, search and 404, plus a landing page template.
- Patterns for a hero, three cards, people in their rings, plans, questions, "not live yet" and a last call to action.
- Block styles named after the rules: Card, Feature card, Panel, Lede, Eyebrow, Badge, Error, Mark and "Mark, not live".
- The five ring palettes as style variations under **Appearance → Editor → Styles**. The palette's first colour is your signature colour; set your own with `:root { --wp--custom--signature: #1f3bff; }` in Additional CSS.
- Settings under **Appearance → distinctUI**: palette, signature colour, your mark (initials or a picture), and Airy or Compact.
- The same look on WooCommerce, Contact Form 7, WPForms, Gravity Forms, Yoast and Rank Math breadcrumbs, Jetpack, and the WPML and Polylang switchers.

### distinctUI+ (in progress)

[`wordpress/distinctui-plus`](wordpress/distinctui-plus) is the paid builder plugin on top of the free theme, for WordPress 6.9 or later. Type your content once and pick from three designs that all follow the rules; then edit on a full-screen canvas with your phone beside it, swap section layouts, and publish versions. Your own Claude can build pages too, through the WordPress MCP Adapter. Pages are plain core blocks, so they stay live without the plugin. Licensing isn't set up yet, so it isn't on the site for download.

The theme's source is in [`wordpress/distinctui`](wordpress/distinctui). `npm run build` zips it into `dist/` for the site.

## Use it in SwiftUI

The same look for iPhone and iPad apps, as a Swift package (iOS 17+). In Xcode, **File → Add Package Dependencies**, paste `https://github.com/VenusOne-Lee/distinctui` and add `DistinctUI`:

```swift
import DistinctUI

WindowGroup {
    RootView().distinctUI(signature: Color(duiHex: 0x1F3BFF), palette: .signal)
}

DUITitle("Just", bold: "shipped")
DUIMark(photo: Image("naledi"), ring: 1, live: false, version: "v0.4", size: 64)
Button("Try it") { }.buttonStyle(.duiPrimary)
```

Bricolage Grotesque is bundled with the package. The SwiftUI rules and the full component map are in [`references/swiftui.md`](skills/distinctui/references/swiftui.md). Six example screens are in `swift/Sources/DistinctUIExamples`; run them with the demo app in `swift/Demo`.

## What's inside

```
skills/distinctui/
  SKILL.md                 the twelve rules and how Claude applies them
  assets/distinctui.css    every token and component, as plain CSS
  assets/tailwind.css      the same tokens as a Tailwind v4 theme
  assets/distinctui.js     optional motion and interaction, driven by attributes
  assets/tokens.json       the tokens as JSON, the one source for every value; Figma (Tokens Studio) and Style Dictionary read it too
  assets/icons.svg         45 Lucide outline icons as one sprite
  assets/distinctui-work.css  distinctUI@work: app shell, dense tables, command menu, approvals, charts
  assets/distinctui-work.js   distinctUI@work interaction: shortcuts, selection, bulk actions, Undo, signing
  assets/stageshow-look.css  the look for Stageshow moments (faux product UI)
  assets/rules.json        the rules and values in a form tools can read
  assets/check.js          checks a page or a page spec against the rules
  assets/compose.js        turns a page spec into a finished page
  references/tokens.md     colour, type, space, radius, mark, motion
  references/components.md markup for every component
  references/motion.md     how things move, and when they don't
  references/moments.md    product stories with Stageshow, in the distinctUI look
  references/work.md       distinctUI@work: the extra rules for apps people use all day
  references/voice.md      how the words sound
  references/imagery.md    photos, icons and charts
  references/review.md     the checklist Claude runs before it's done
  references/page-spec.md  pages written as content, for compose.js and the MCP server
  references/swiftui.md    the SwiftUI package, what maps to what, phone rules
  references/wordpress.md  the WordPress block theme, what maps to what
  examples/                50 templates in eight groups: marketing, stories, selling,
                           accounts, app screens, at work, help and docs, system pages
wordpress/distinctui/      the WordPress block theme (theme.json, templates, patterns, the font)
wordpress/distinctui-plus/ distinctUI+, the builder plugin (canvas, page specs, abilities for Claude)
site/                      distinctui.com
src/                       the Worker: the site, and the MCP server at /mcp (src/mcp.js)
test/                      tests for the checker, the composer, the MCP server and token sync
moments/                   the Moments page scenes, with a copy of the Stageshow kit (MIT, Benji Taylor)
Package.swift              the Swift package
swift/Sources/DistinctUI   tokens, type, pills, marks, cards and forms in SwiftUI, with the font
                           (Tokens.generated.swift is written from tokens.json: don't edit it)
swift/Sources/DistinctUIExamples  six example screens
swift/Demo                 a demo app that runs them
```

## The twelve rules

1. Light only. No dark mode.
2. Black, white and four greys. Nothing else.
3. Colour lives only on rings, from one palette of five (Signal, Mzansi, Earth, Coast, Jacaranda).
4. One typeface: Bricolage Grotesque.
5. Headings are light, with one bold word.
6. Main actions are black pills.
7. White space before borders, borders before shadows.
8. Pills for what you press, soft corners for what you read.
9. The mark is dashed until live, solid once it ships.
10. Errors are black and bold, never red.
11. Hover is a black edge, not a lift.
12. Write plainly.

## Run the site

```
npm install
npm run dev      # builds dist/ and serves it with wrangler
npm run deploy   # Cloudflare Workers, custom domain distinctui.com
npm test         # builds dist/, then runs the checker, composer, MCP and token sync tests
npm run tokens   # after changing tokens.json: rewrites the Swift tokens
```

`tokens.json` is the one source for token values. The Swift tokens are generated from it, and the build fails if they drift. The CSS and Tailwind theme are written by hand, and a test fails if their values disagree with it.

## Licence

MIT, see [LICENSE](LICENSE). Bricolage Grotesque is by Mathieu Triay and is under the SIL Open Font Licence. On the web it loads from Google Fonts; the Swift package bundles a copy with its licence (`swift/Sources/DistinctUI/Resources/OFL.txt`).

The Version One name and logo aren't part of this licence. The Mzansi palette uses the South African flag colours as Version One does. Every person and product in the examples is invented. The example photos are AI-generated and show people who don't exist.
