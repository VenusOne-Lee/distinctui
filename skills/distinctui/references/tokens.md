# Tokens

Copy these by value when you can't use `assets/distinctui.css` (native apps, email, canvas).

## Colour

| Token | Value | Use |
|---|---|---|
| white | `#ffffff` | Page background, text on black |
| black | `#000000` | Text, primary buttons, focus ring, borders that mean "this one" |
| grey-50 | `#fafafa` | Notes and panels inside white |
| grey-100 | `#f2f2f2` | Image placeholders, hover fills, form problems banner, soft badges |
| grey-200 | `#e5e5e5` | Hairlines, card and input borders |
| grey-400 | `#a3a3a3` | Placeholders, outline badge border, empty-state dashed border |
| grey-600 | `#6b6b6b` | Secondary text: meta, hints, lede, footer |
| grey-800 | `#262626` | Long body text inside cards |
| signature | your colour | The signature mark, and nothing else |

Contrast: grey-600 on white is 5.3:1, so it is fine for text. grey-400 is 2.5:1, so only use it for placeholders and decoration, never for words someone must read.

## Type

Family: Bricolage Grotesque (variable, optical size 12 to 96, weight 200 to 800). Fallback: `ui-sans-serif, system-ui, sans-serif`.

| Role | Size | Weight | Tracking | Line height |
|---|---|---|---|---|
| Hero | 40 / 60 / 76px (phone / tablet / desktop) | 300, key word 700 | -0.04em | 1.02 |
| h1 | 36 / 54px | 300, key word 700 | -0.04em | 1.04 |
| h2 section | 36 / 48px | 300, key word 700 | -0.035em | 1.08 |
| h3 card headline | 26px | 700 | -0.03em | 1.15 |
| h4 card title | 20px | 700 | -0.02em | 1.3 |
| Lede | 18px | 400, grey-600 | 0 | 1.55 |
| Body | 16px | 400 | 0 | 1.55 |
| Menu, compact rows | 15px | 400 | 0 | 1.45 |
| Meta, hint, button small | 14px | 400 (600 for buttons) | 0 | 1.45 |
| Eyebrow | 13px uppercase | 500, grey-600 | 0.08em | 1.4 |
| Badge, counter | 12px | 500 | 0 | 1.5 |

Use `font-variant-numeric: tabular-nums` where numbers line up.

## Space

4px base: 4, 8, 12, 16, 20, 24, 32, 40, 56, 80, 112.

- Inside a card: 20 to 24px padding, 16px between parts.
- Between cards: 16px on phones, 24px from 640px.
- Between sections: 80px on phones, 112px from 640px.
- Page side gutter: 16px, 24px from 640px.

## Radius

| Token | Value | Use |
|---|---|---|
| pill | 9999px | Buttons, tabs, badges, one-line fields, menu items, the mark |
| input | 14px | Inputs, selects, textareas on longer forms |
| note | 12px | The grey note inside a card |
| panel | 16px | Grey panels, choice cards, form problems banner |
| card | 22px | Cards |
| menu | 24px | Floating menu, status notice |
| feature | 28px | The one feature card on a page |

## Borders and shadow

- Hairline: 1px grey-200.
- Selected or important: 1.5px black (feature card, status notice, invalid field).
- Float shadow, for menus only: `0 8px 30px rgba(0,0,0,0.08)`.

## Signature mark

| Part | Value |
|---|---|
| Ring width | 6% of the mark's size |
| Gap between ring and face | 6% of the size |
| Not live | dashed |
| Live | solid |
| Version label | black pill, white 3px border, 12% of the size (10 to 20px), sits over the bottom edge |
| Earned dot | 20% of the size (at least 10px), bottom right, white border |
| Plain (no signature of their own) | black, ring width 3.5% |
| Group | black double ring, 8% (at least 4px) |

Common sizes: 20px in the logo, 36px in a card header, 64px in a list, 108px on the home row, 120px+ on a profile.

## Motion

- Hover and state changes: 150ms, opacity or colour only.
- Link landing flash: 3s outline fade.
- Respect `prefers-reduced-motion`: turn both off.
