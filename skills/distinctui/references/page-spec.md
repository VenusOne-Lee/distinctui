# Page spec

A page spec is a page written as content, not styling. `assets/compose.js` turns it into a finished distinctUI page, and `assets/check.js` checks it before and after. The MCP server at `https://distinctui.com/mcp` runs both as `check_spec` and `compose_page`.

A spec has no colours, fonts, shadows or sizes. Each section takes words, items and a layout, so the page follows the rules by construction. The one colour is the signature, and it goes on the mark.

## The page

```json
{
  "name": "Kettle",
  "title": "Kettle, a booking page for small salons",
  "description": "One link for bookings, reminders and a waitlist.",
  "domain": "kettle.example",
  "tagline": "Bookings without the back and forth.",
  "signature": { "colour": "#e03c31", "palette": "Mzansi", "initials": "K" },
  "live": false,
  "version": "v1.0",
  "density": "comfortable",
  "nav": [{ "label": "Prices", "href": "#prices", "current": false }],
  "headerAction": { "label": "Start free", "href": "/start" },
  "assets": "https://distinctui.com/assets",
  "sections": []
}
```

| Field | What it does |
| --- | --- |
| `name` | The product. Shown as the logo, in the footer and in the mark when there are no `initials`. |
| `title`, `description` | The page title and meta description. `description` falls back to the first lede. |
| `domain`, `tagline` | Shown in the footer. |
| `signature.colour` | The one colour, on the mark only. A hex value. Leave out to use the first colour of the palette. |
| `signature.palette` | Signal, Mzansi, Earth, Coast or Jacaranda. Gives each face in a `proof` section its own ring. |
| `signature.initials` | What sits inside the mark. Defaults to the name's initials. |
| `live` | `false` draws the mark dashed (not out yet). `true` draws it solid, with `version` as the small black label. |
| `density` | `comfortable` (the default) or `compact`, which tightens the space between sections. |
| `nav`, `headerAction` | Header links and the one black pill in the header. Set `header: false` to leave the header out. |
| `footer` | Set to `false` to leave the footer out. |
| `assets` | Where `distinctui.css` and `distinctui.js` load from. Defaults to distinctui.com. |

## Sections

Every section has a `type` and, where it has more than one look, a `layout`. Headings take `heading` and `bold`, the word that carries the meaning. Leave `bold` out and the composer bolds the longest word that isn't filler, and `check_spec` warns.

Actions are `{ "label": "...", "href": "..." }`. A section gets one `primary` (the black pill) and, at most, one `secondary` (the outlined pill). Labels say what happens: "Start free", "See prices", never "Click here" or "Submit".

| Type | Layouts | Fields |
| --- | --- | --- |
| `hero` | `mark-left` (default), `centred` | `eyebrow`, `heading`, `bold`, `lede`, `primary`, `secondary` |
| `proof` | `faces` | `faces` (names or `{ name, initials }`, up to 5), `more` ("and 40 salons"), `text` |
| `features` | `cards` (default), `rows` | `eyebrow`, `heading`, `bold`, `lede`, `items: [{ title, text }]` (up to 6) |
| `steps` | `rows` | `heading`, `bold`, `items: [{ title, text }]` (up to 5) |
| `stats` | `row` | `heading` (optional), `items: [{ label, value }]` (up to 4) |
| `pricing` | `cards` (default), `one` | `heading`, `bold`, `lede`, `items: [{ name, price, period, text, features: [], cta, href, featured }]` (up to 3) |
| `questions` | `list` | `heading`, `bold`, `items: [{ q, a }]` |
| `cta` | `centred` (default), `split` | `heading`, `bold`, `lede`, `primary`, `secondary` |
| `form` | `contact` (default), `booking`, `waitlist` | `heading`, `bold`, `lede`, `fields: [{ label, name, type, optional, hint, options }]`, `submit`, `action` |
| `products` | `grid` | `heading`, `bold`, `items: [{ name, price, image, alt, href, cta }]` (up to 8) |
| `posts` | `cards` (default), `featured` | `heading`, `bold`, `items: [{ title, date, text, href }]` |

Notes on the sections:

- **pricing**: one plan is the feature card with the black pill. Mark it with `featured: true`; with three plans and none marked, the middle one is featured. The others get outlined pills.
- **form**: each layout has sensible default fields, so `fields` is optional. Field `type` is `text`, `email`, `tel`, `textarea` or `select` (with `options`). Every field gets a visible label; mark the ones people can skip with `optional: true` instead of starring the required ones.
- **products**: with no `image`, the photo space stays grey. `alt` defaults to the product name.
- **proof**: the faces are initials in rings. Use invented people in examples, never real ones.

## What a spec can't do

These are left out on purpose, and `check_spec` flags them:

- Section types `toast`, `carousel`, `popup`, `modal`, `slider` and `spinner`.
- Styling keys on a section: `colour`, `color`, `background`, `accent`, `shadow`, `gradient`, `font`.
- `theme: "dark"` or `dark: true`.
- More than one hero, or more than one featured plan.

## A whole spec

```json
{
  "name": "Kettle",
  "signature": { "palette": "Mzansi" },
  "live": true,
  "version": "v1.0",
  "nav": [{ "label": "Prices", "href": "#prices" }],
  "headerAction": { "label": "Start free", "href": "/start" },
  "sections": [
    { "type": "hero", "heading": "Bookings that fill themselves", "bold": "fill",
      "lede": "One link for bookings, reminders and a waitlist.",
      "primary": { "label": "Start free", "href": "/start" },
      "secondary": { "label": "See prices", "href": "#prices" } },
    { "type": "proof", "faces": ["Thandi Mokoena", "Ana Ruiz", "Sipho Dlamini"], "more": "and 40 salons" },
    { "type": "features", "heading": "Everything a front desk does", "bold": "front desk",
      "items": [{ "title": "One link", "text": "Share it anywhere." },
                { "title": "Reminders", "text": "A text the day before." },
                { "title": "Waitlist", "text": "Fills gaps when people cancel." }] },
    { "type": "pricing", "heading": "Simple prices", "bold": "prices",
      "items": [{ "name": "Solo", "price": "R99", "period": "a month" },
                { "name": "Salon", "price": "R249", "period": "a month", "featured": true },
                { "name": "Group", "price": "R599", "period": "a month" }] },
    { "type": "questions", "heading": "Questions people ask", "bold": "Questions",
      "items": [{ "q": "Can I cancel?", "a": "Yes, any time, from Settings." }] },
    { "type": "cta", "heading": "Try Kettle today", "bold": "Kettle",
      "primary": { "label": "Start free", "href": "/start" } }
  ]
}
```

## Check, then compose, then check

1. `check_spec` (or `checkSpec(spec)`) and fix every error.
2. `compose_page` (or `composePage(spec)`) for the HTML.
3. `check_page` (or `checkHTML(html)`) on the final page, after any hand edits.

From JavaScript:

```js
import { checkSpec, checkHTML } from "https://distinctui.com/assets/check.js";
import { composePage } from "https://distinctui.com/assets/compose.js";
```
