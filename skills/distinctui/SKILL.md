---
name: distinctui
description: Build or restyle web pages and app screens in the distinctUI look (the system behind Version One) - black, white and grey, Bricolage Grotesque, lots of white space, black pill buttons, and one signature colour that lives only on a signature mark. Use when the user asks for distinctUI, the Version One look, or a calm monochrome UI with one accent mark.
---

# distinctUI

A small design system for calm, confident interfaces. Everything is black, white and grey. One typeface. Lots of white space. Black pill buttons. Then one thing that is unmistakably yours: a **signature mark** in a **signature colour**, and that colour appears nowhere else.

It is the system behind [Version One](https://versionone.co.za), where South African builders share what they've shipped.

## Before you build

1. **Ask for the signature.** What is the mark (a ring around a face, a logo dot, a project badge)? What one colour, or which palette if every person gets a ring? If the user has no answer, offer the five palettes in `references/tokens.md` and recommend one for their product, with a reason.
2. **Find the stack.** Plain CSS: link `assets/distinctui.css` and use the `dui-` classes, plus `assets/distinctui.js` for motion and interaction. Tailwind v4: import `assets/tailwind.css`. SwiftUI (iPhone and iPad): add the `DistinctUI` Swift package and follow `references/swiftui.md`. Anything else (React Native, Android, email): copy the tokens from `references/tokens.md` by value.
3. **Load the font** from Google Fonts, `Bricolage Grotesque` with the optical size axis: `https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap`.

## The rules

These are not suggestions. If a request breaks one, say which rule and offer the closest thing that keeps it.

1. **Light only.** White background, black text. No dark mode, no dark sections, no inverted hero.
2. **Black, white and four greys.** `#000`, `#fff`, and greys 50, 100, 200, 400, 600, 800. No blue links, no red errors, no green success, no brand gradients.
3. **Colour lives only on rings.** One signature colour for the product's own mark. If each person or project gets a ring, pick their colours from one palette of five (see `references/tokens.md`: Signal, Mzansi, Earth, Coast, Jacaranda). Never on buttons, links, headings, backgrounds or icons. If something needs to stand out, use black, weight or space.
4. **One typeface.** Bricolage Grotesque for everything, including buttons and numbers. A monospace only for code.
5. **Headings are light with one bold word.** `Just <b>shipped</b>`. Weight 300 for the line, 700 for the word that carries the meaning. Tight tracking (-0.035em to -0.04em).
6. **Main actions are black pills.** One black pill per group. The second action is an outlined pill. Tertiary actions are underlined text links.
7. **White space before borders, borders before shadows.** Separate with space first, a 1px grey-200 hairline second. The only shadow is on floating menus.
8. **Pills for what you press or type one line into; soft corners for what you read.** Cards 22px, feature card 28px, panels 16px, long-form inputs 14px.
9. **Dashed until live.** The mark is dashed while the thing it stands for isn't out yet, solid once it ships, with a small black version label such as `v0.3`.
10. **Errors are black and bold**, with a small black "!" disc, and say which field and how to fix it. Never red.
11. **Hover is a black edge, not a lift.** Cards change border colour to black. No scale, no shadow, no glow. Motion only says what changed (a ring drawing in, a mark going live, a menu opening), runs once, and switches off for reduced motion. See `references/motion.md`.
12. **Write plainly.** See `references/voice.md`.

## Building blocks

All components, their markup and when to use each are in `references/components.md`. The short list:

- Type: hero, h1, h2 (light with a bold word), h3 card headline, lede, body, meta, eyebrow label
- Buttons: `dui-btn`, `dui-btn-ghost`, `dui-btn-sm`, `dui-btn-sm-ghost`, count badge
- Badges: outline status, solid highlight, soft version chip
- Forms: label and hint, pill field, input, select, textarea, counter, checkbox, switch, choice cards, field error, form problems banner
- Cards: card, media card, feature card, note, panel, rows
- Signature mark: empty, initials or photo inside; sizes, dashed/solid, version label, earned dot, plain and group variants, logo
- Page: header with nav, footer, menu, pill tabs, breadcrumbs, side menu, page numbers, steps, progress bar, status notice, empty state, flash
- Content: questions that open in place, table, code block, filter chips, a stack of faces, dialog, loading shapes
- Icons: Lucide outlines from `assets/icons.svg`, black, next to a word
- Motion (`assets/distinctui.js`, optional): press, reveal, ring draws in, goes live, counts up, bars grow, filter tabs, toggle

Photos, icons and charts have their own rules in `references/imagery.md`. Tokens for Figma (Tokens Studio) or Style Dictionary are in `assets/tokens.json`.

For SwiftUI, every one of these is a view or modifier in the `DistinctUI` package (`DUIMark`, `DUITitle`, `.buttonStyle(.duiPrimary)`, `.duiCard()` and so on). The full map, the phone-specific rules and six example screens are in `references/swiftui.md`.

Worked pages are in `examples/`. Open the closest one before building a similar screen:

| Group | Template | Use it for |
|---|---|---|
| Marketing | `landing.html` | A launch or marketing page |
|  | `waitlist.html` | Something not live yet: one field, a dashed mark |
|  | `about.html` | Who you are, why you started, numbers, beliefs |
|  | `team.html` | Everyone in their ring, plus an open seat |
|  | `careers.html` | Open roles with salaries, how you hire |
|  | `contact.html` | A short contact form with the privacy note beside it |
| Stories | `blog.html` | A blog or news index with a lead story and page numbers |
|  | `article.html` | A story, blog post or long read |
|  | `case-study.html` | A portfolio piece: facts, problem, what shipped, results |
|  | `changelog.html` | Versions and what changed |
| Selling | `pricing.html` | Plans and questions |
|  | `product.html` | One product in a shop |
|  | `checkout.html` | Delivery and an order summary |
| Accounts | `signin.html` | Sign in with an email link or passkey |
|  | `onboarding.html` | A first-run flow with steps and a live preview |
|  | `profile.html` | A person or team page |
|  | `settings.html` | Account, emails, your data, delete account |
| Screens | `dashboard.html` | An owner's home: what needs them, numbers, activity |
|  | `list.html` | A directory or feed |
|  | `search.html` | Search results with filters and a no-results state |
|  | `form.html` | Any long form, with error states |
| Help and docs | `help.html` | A help centre: search, topics, questions, a person |
|  | `docs.html` | Documentation with a side menu and code |
|  | `status.html` | Uptime and incidents, without traffic-light colours |
| System pages | `notfound.html` | A 404 page |
|  | `error.html` | A server error, offline and maintenance |
|  | `email.html` | A transactional email (tables and inline styles) |

The example photos in `examples/img/` are AI-generated people, places and products that don't exist. Replace them with your own.

## Layout

- Page width 72rem, reading width 42rem (65 characters).
- Side gutter at least 16px (24px from 640px).
- Sections are 80px apart on phones and 112px on desktop. Inside a section, 28 to 32px.
- Card grids: 1 column on phones, 2 from 640px, 3 or 4 from 1024px. Cards in a row are the same height, with titles clamped to 2 lines and descriptions to 3.
- Centre only the hero. Everything else is left-aligned.

## Before you call it done

Run `references/review.md` against the page. Fix everything it finds, then say in one line what you checked.
