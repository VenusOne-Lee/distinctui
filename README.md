# distinctUI

**Black, white, grey. Then one mark that's yours.**

distinctUI is a small, open-source design system packaged as a [Claude](https://claude.ai) skill. It gives your app lots of white space, one typeface, black pill buttons, and a single signature colour that lives only on a signature mark.

It's the system behind [Version One](https://versionone.co.za), where South African builders share what they've shipped.

Site and live components: **https://distinctui.com**

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

## Use the CSS without Claude

Plain CSS:

```html
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap">
<link rel="stylesheet" href="distinctui.css">
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

## What's inside

```
skills/distinctui/
  SKILL.md                 the twelve rules and how Claude applies them
  assets/distinctui.css    every token and component, as plain CSS
  assets/tailwind.css      the same tokens as a Tailwind v4 theme
  references/tokens.md     colour, type, space, radius, mark, motion
  references/components.md markup for every component
  references/voice.md      how the words sound
  references/review.md     the checklist Claude runs before it's done
  examples/                eleven templates: landing, profile, directory, dashboard,
                           article, pricing, sign-in, settings, changelog, form, email
site/                      distinctui.com
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
```

## Licence

MIT, see [LICENSE](LICENSE). Bricolage Grotesque is by Mathieu Triay and is under the SIL Open Font Licence; it loads from Google Fonts and isn't included here.

The Version One name and logo aren't part of this licence. The Mzansi palette uses the South African flag colours as Version One does. Every person and product in the examples is invented. The example photos are AI-generated and show people who don't exist.
