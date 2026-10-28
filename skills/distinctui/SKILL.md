---
name: distinctui
description: Build or restyle web pages and app screens in the distinctUI look (the system behind Version One) - black, white and grey, Bricolage Grotesque, lots of white space, black pill buttons, and one signature colour that lives only on a signature mark. Use when the user asks for distinctUI, the Version One look, or a calm monochrome UI with one accent mark.
---

# distinctUI

A small design system for calm, confident interfaces. Everything is black, white and grey. One typeface. Lots of white space. Black pill buttons. Then one thing that is unmistakably yours: a **signature mark** in a **signature colour**, and that colour appears nowhere else.

It is the system behind [Version One](https://versionone.co.za), where South African builders share what they've shipped.

## Before you build

1. **Ask for the signature.** What is the mark (a ring around a face, a logo dot, a project badge)? What one colour? If the user has no answer, suggest a strong, saturated colour that is not already a well-known brand's, and say why. Never default to green.
2. **Find the stack.** Plain CSS: link `assets/distinctui.css` and use the `dui-` classes. Tailwind v4: import `assets/tailwind.css`. Anything else (React Native, SwiftUI, email): copy the tokens from `references/tokens.md` by value.
3. **Load the font** from Google Fonts, `Bricolage Grotesque` with the optical size axis: `https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap`.

## The rules

These are not suggestions. If a request breaks one, say which rule and offer the closest thing that keeps it.

1. **Light only.** White background, black text. No dark mode, no dark sections, no inverted hero.
2. **Black, white and four greys.** `#000`, `#fff`, and greys 50, 100, 200, 400, 600, 800. No blue links, no red errors, no green success, no brand gradients.
3. **One signature colour, only on the signature mark.** Not on buttons, links, headings, backgrounds or icons. If something needs to stand out, use black, weight or space.
4. **One typeface.** Bricolage Grotesque for everything, including buttons and numbers. A monospace only for code.
5. **Headings are light with one bold word.** `Just <b>shipped</b>`. Weight 300 for the line, 700 for the word that carries the meaning. Tight tracking (-0.035em to -0.04em).
6. **Main actions are black pills.** One black pill per group. The second action is an outlined pill. Tertiary actions are underlined text links.
7. **White space before borders, borders before shadows.** Separate with space first, a 1px grey-200 hairline second. The only shadow is on floating menus.
8. **Pills for what you press or type one line into; soft corners for what you read.** Cards 22px, feature card 28px, panels 16px, long-form inputs 14px.
9. **Dashed until live.** The mark is dashed while the thing it stands for isn't out yet, solid once it ships, with a small black version label such as `v0.3`.
10. **Errors are black and bold**, with a small black "!" disc, and say which field and how to fix it. Never red.
11. **Hover is a black edge, not a lift.** Cards change border colour to black. No scale, no shadow, no glow.
12. **Write plainly.** See `references/voice.md`.

## Building blocks

All components, their markup and when to use each are in `references/components.md`. The short list:

- Type: hero, h1, h2 (light with a bold word), h3 card headline, lede, body, meta, eyebrow label
- Buttons: `dui-btn`, `dui-btn-ghost`, `dui-btn-sm`, `dui-btn-sm-ghost`, count badge
- Badges: outline status, solid highlight, soft version chip
- Forms: label and hint, pill field, input, select, textarea, counter, checkbox, choice cards, field error, form problems banner
- Cards: card, media card, feature card, note, panel, rows
- Signature mark: sizes, dashed/solid, version label, earned dot, plain and group variants, logo
- Page: header with nav, footer, menu, pill tabs, status notice, empty state, flash

Worked pages are in `examples/`: `landing.html`, `profile.html`, `form.html` and `list.html`. Open one before building a similar screen.

## Layout

- Page width 72rem, reading width 42rem (65 characters).
- Side gutter at least 16px (24px from 640px).
- Sections are 80px apart on phones and 112px on desktop. Inside a section, 28 to 32px.
- Card grids: 1 column on phones, 2 from 640px, 3 or 4 from 1024px. Cards in a row are the same height, with titles clamped to 2 lines and descriptions to 3.
- Centre only the hero. Everything else is left-aligned.

## Before you call it done

Run `references/review.md` against the page. Fix everything it finds, then say in one line what you checked.
