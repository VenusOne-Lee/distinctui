---
name: distinctui
description: Build or restyle web pages and app screens in the distinctUI look (the system behind Version One) - black, white and grey, Bricolage Grotesque, lots of white space, black pill buttons, and one signature colour that lives only on a signature mark. Use when the user asks for distinctUI, the Version One look, or a calm monochrome UI with one accent mark.
---

# distinctUI

A small design system for calm, confident interfaces. Everything is black, white and grey. One typeface. Lots of white space. Black pill buttons. Then one thing that is unmistakably yours: a **signature mark** in a **signature colour**, and that colour appears nowhere else.

It is the system behind [Version One](https://versionone.co.za), where South African builders share what they've shipped.

## Before you build

1. **Ask for the signature.** What is the mark (a ring around a face, a logo dot, a project badge)? What one colour, or which palette if every person gets a ring? If the user has no answer, offer the five palettes in `references/tokens.md` and recommend one for their product, with a reason.
2. **Find the stack.** Plain CSS: link `assets/distinctui.css` and use the `dui-` classes, plus `assets/distinctui.js` for motion and interaction. Tailwind v4: import `assets/tailwind.css`. SwiftUI (iPhone and iPad): add the `DistinctUI` Swift package and follow `references/swiftui.md`. WordPress: use the distinctUI block theme and follow `references/wordpress.md`. Anything else (React Native, Android, email): copy the tokens from `references/tokens.md` by value.
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
- Forms: label and hint, pill field, input, select, textarea, counter, checkbox, switch, choice cards, date and time fields, date picker and month grid, quantity stepper, field error, form problems banner
- Cards: card, media card, feature card, note, panel, rows, testimonial card
- Signature mark: empty, initials or photo inside; sizes, dashed/solid, version label, earned dot, plain and group variants, logo
- Page: header with nav, footer, menu, pill tabs, breadcrumbs, side menu, page numbers, steps, progress bar, status notice, empty state, flash, announcement line, cookie notice
- Content: pull quote and blockquote, questions that open in place, table, code block with a copy button, a line to copy, filter chips, a stack of faces, dialog, loading shapes
- Sections: feature with a photo, how it works, testimonials, plan comparison, newsletter signup, opening hours and location
- Icons: Lucide outlines from `assets/icons.svg`, black, next to a word
- Motion and interaction (`assets/distinctui.js`, optional): press, reveal, ring draws in, goes live, counts up, rolls, confirms on itself, copy, rows arrive and leave, bars grow, filter tabs, toggle, steppers, the month grid, closing a notice for good, open-now hours
- At work (`assets/distinctui-work.css` and `assets/distinctui-work.js`, for apps people use all day): app shell with a rail and top bar, dense data table with saved views, sort, selection and a bulk bar, three row densities, command menu on ⌘K, shortcuts with an off switch, list beside the open item, approval chain, roles and permissions, audit trail, number tiles, small multiples, heat map, settings, notices with Undo. Rules in `references/work.md`
- Moments: small product stories (a hero demo, a feature card with a cursor) built with Stageshow and `assets/stageshow-look.css`. Rules in `references/moments.md`

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
|  | `shop.html` | A small shop: filter tabs, product cards, the studio, hours, newsletter |
|  | `cart.html` | The bag: steppers, a summary, an empty bag |
|  | `checkout.html` | Delivery and an order summary |
|  | `order.html` | Order placed: the order goes live, what happens next |
| Accounts | `signin.html` | Sign in with an email link or passkey |
|  | `signup.html` | Sign up with a password, errors showing |
|  | `reset-password.html` | Ask for a reset link, choose a new password |
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
|  | `terms.html` | Terms and privacy people can read: short version first |
| At work | `work-home.html` | A work app's home: what needs you, three tiles, what changed |
|  | `work-table.html` | A dense table: saved views, filter, sort, select, bulk bar, Undo, densities |
|  | `work-console.html` | An inbox: a list beside the open item, J and K, ⌘K, shortcuts switch |
|  | `work-approvals.html` | One approval: requester apart from signers, sign, decline, delegate, audit trail |
|  | `work-reports.html` | Reports: tiles, black small multiples on one scale, chart or table, heat map |
|  | `work-audit.html` | The audit log: full dates, filters, export, personal details for Admin only |
|  | `work-admin.html` | Workspace settings: roles, SSO, SCIM, API keys, billing, typed-name delete |
|  | `work-states.html` | Loading, empty, no results, error, no access, read only, long text |
|  | `work-trust.html` | The trust page a buyer's security team reads: reports, plans, questions |
| System pages | `notfound.html` | A 404 page |
|  | `error.html` | A server error, offline and maintenance |
|  | `email.html` | A transactional email (tables and inline styles) |

The example photos in `examples/img/` are AI-generated people, places and products that don't exist. Replace them with your own.

## At work

For apps people use all day (admin tools, dashboards, back offices), add `assets/distinctui-work.css` and `assets/distinctui-work.js` after the core files. The twelve rules still hold. Read `references/work.md` first; the short version:

- Three row heights (Compact 34px, Default 44px, Airy 56px), chosen by each person and remembered.
- Rows have drawn states: hover grey-50, selected and focused get a black edge. Status is a shape plus words, never a colour.
- One ring colour per person, everywhere in the product. Photos only in marks under 28px, never initials.
- A customer's brand colour goes on their workspace mark only.
- In an approval, the person who asked sits apart from the people who sign. Dashed means still to sign.
- IP addresses and places show to Admin and Auditor only (POPIA). Dates are full, with the time zone.
- Single-key shortcuts show as key caps and have an off switch (WCAG 2.1.4). ⌘K always works.
- Bulk actions happen, then a notice with Undo stays in the page. Only what can't be undone asks for the typed name.
- Compare series with black small multiples on one scale, never a colour per series. Every chart can become its table.

## Layout

- Page width 72rem, reading width 42rem (65 characters).
- Side gutter at least 16px (24px from 640px).
- Sections are 80px apart on phones and 112px on desktop. Inside a section, 28 to 32px.
- Card grids: 1 column on phones, 2 from 640px, 3 or 4 from 1024px. Cards in a row are the same height, with titles clamped to 2 lines and descriptions to 3.
- Centre only the hero. Everything else is left-aligned.

## Before you call it done

Run `references/review.md` against the page. If you can run JavaScript, also run `assets/check.js` on it (`checkHTML(html)`), or call `check_page` on the distinctUI MCP server at `https://distinctui.com/mcp`. Fix everything either finds, then say in one line what you checked.

To build a page from content instead of by hand, write a page spec (`references/page-spec.md`) and compose it with `assets/compose.js` or the MCP server's `compose_page`.
