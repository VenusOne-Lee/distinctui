# distinctUI@work

The same look for apps people use all day: admin tools, back offices, approvals, reports. The twelve rules still hold. This file adds what an app needs, and the markup for each piece.

Load the work layer after the core files:

```html
<link rel="stylesheet" href="distinctui.css">
<link rel="stylesheet" href="distinctui-work.css">
<script src="distinctui.js" defer></script>
<script src="distinctui-work.js" defer></script>
```

The worked example is Brightwater Logistics, an invented company, and four invented apps it uses, in seventeen templates. Quarry, for contracts and money: `work-home`, `work-table`, `work-console`, `work-approvals`, `work-reports`, `work-audit`, `work-admin`, `work-states`, `work-trust`, `work-signin`, `work-request`, `work-invoice` and `work-people`. Intake, for hiring: `work-board`. Kettle, for support: `work-desk`. Roster, for shifts: `work-rota` and `work-field` (a phone app). Open the closest one before building.

The same people appear in every app with the same ring. Only Brightwater's workspace mark carries its colour; a second customer's workspace (`work-signin`) carries its own.

## The nine extra rules

They're numbered W1 to W9, so they don't mix with the twelve core rules.

- **W1. Dense, then roomy if you like.** Three row heights: Compact 34px, Default 44px, Airy 56px. Each person chooses, and it's remembered (`input[data-density-set]`). Text never goes below 12px (`--dui-text-floor`).
- **W2. States are drawn.** Hover is grey-50. A selected row (`aria-selected="true"`) and a focused row get a black edge. Status is `.dui-st` with `data-st`: `signed` and `done` solid, `review` half, `draft` dashed, `off` grey, `blocked` a black ! and bold words. Always with words.
- **W3. One ring colour per person.** Give each person a ring from one palette and keep it everywhere: the table, the inbox, the chain, the updates. Marks under 28px (`.dui-mark--sm` 24px, `--xs` 20px) show a photo, never initials; the script warns in the console if it finds initials.
- **W4. Your colour stays on your mark.** A customer's brand colour is `--dui-signature`, and it shows on their workspace mark (`.dui-ws`) only. Nothing else in the app changes colour. To let an admin pick it, put `input[data-brand-set]` radios inside a `[data-brand]` scope.
- **W5. Who asked is not who signs.** In `.dui-chain`, the requester sits in `.dui-chain__from` with a plain mark, set apart by a hairline. Signers follow in order. A link is solid once signed, dashed (`data-done="false"`) before.
- **W6. Personal details are need-to-know.** IP addresses, places and devices are personal information under POPIA. Mark those cells `.dui-private`; they show only when a parent has `data-viewer="admin"` or `"auditor"`. Add a `.dui-private-note` that says who can see them. Dates are full, with the time zone: `5 Nov 2026, 09:14 SAST`, in a `<time datetime>`.
- **W7. Shortcuts you can see, and switch off.** Single-key shortcuts (`data-key="a"`, `"g c"`) show as `.dui-kbd` key caps with `data-single`. A switch (`input[data-keys-toggle]`) turns them all off, for speech input and switch users (WCAG 2.1.4); the caps hide with them. The command menu on ⌘K or Ctrl K always works, and every shortcut is also a button or a menu item.
- **W8. Undo instead of are you sure.** A bulk action happens at once, then a notice with Undo stays in the page (`distinctUI.notice(text, { undo })`, into `[data-notices]`). No toasts. Only what can't be undone opens a dialog that asks for the typed name (`input[data-confirm-match]`).
- **W9. Compare in black.** Series are black small multiples on one shared scale (`.dui-multiples`), never a colour per series. Say the scale in words. Every chart can turn into its table (`[data-view-as]`). A heat map has three steps you can tell apart, with the count printed in each cell.

## Pieces

| Piece | Classes | Notes |
|---|---|---|
| App shell | `.dui-app`, `.dui-rail`, `.dui-main`, `.dui-topbar`, `.dui-content` | The rail is a sheet on phones (`[data-rail-toggle]`). Current page: `aria-current="page"`, a black edge. |
| Workspace switcher | `.dui-ws` in a `details.dui-menu` | The customer's mark and name. |
| Search and commands | `dialog.dui-cmd`, `.dui-cmd__opt[data-href|data-click|data-done]` | Opens on ⌘K or `[data-cmd-open]`. Filters on text and `data-words`. A bottom sheet on phones. |
| Updates | `.dui-updates`, `li[data-unread]`, `[data-mark-read]` | A bell with a count, in a menu. |
| Data table | `.dui-data-wrap`, `table.dui-data`, `.dui-pin`, `.dui-num`, `.dui-sort`, `.dui-row-more` | Sticky header and first column. Sort keeps rows moving to their new places. |
| Selection and bulk bar | `table[data-select]`, `[data-row-check]`, `[data-check-all]`, `.dui-swapbar`, `.dui-bulk` | The views row and the black bulk bar share one place and swap. `[data-bulk="stage"]` and `[data-bulk="archive"]` come with Undo. |
| Saved views and filter | `.dui-tabs` with `data-filter`, `input[data-filter-rows]`, `[data-no-results]` | Wrap the tabs and rows in one `[data-filter-scope]`. |
| Density and columns | `.dui-seg` with `input[data-density-set]`, `input[data-col]` | |
| List and detail | `.dui-split-view`, `.dui-item[data-open-item]`, `[data-detail]`, `[data-split-back]` | Arrow keys move, J and K as single keys. One pane at a time on phones. |
| Properties and activity | `.dui-props`, `.dui-props--2`, `.dui-feed` | |
| Approval chain | `.dui-chain`, `__from`, `__steps`, `__step`, `__link` | `[data-sign="#step"]` signs: the ring goes live, the link goes solid, `[data-sign-count]` rolls, a `template[data-sign-row]` row joins the trail. |
| Roles and permissions | `.dui-role`, `.dui-role-select`, `.dui-perm` with `.dui-yes` / `.dui-no` | Each yes and no has screen-reader words. |
| Disabled, with a reason | `.dui-lock` | Say why and who can change it, and give one way to ask. |
| Numbers | `.dui-tiles`, `.dui-tile`, `__label`, `__num`, `__means`, `.dui-spark` | The number is light with the part that matters bold. `__means` says what it means. |
| Charts | `.dui-multiples`, `.dui-multiple`, `.dui-chart-*`, `.dui-draw-lines`, `.dui-heat`, `.dui-heat-key` | Lines draw in once when seen. |
| Settings | `.dui-settings`, `.dui-setting`, `.dui-secret`, `.dui-danger`, `.dui-confirm-name` | Secrets show once, then masked with Copy. |
| Notices | `.dui-status` in `[data-notices]`, `data-tone="alert"` for something to look at | Stay until closed or undone. |
| Loading again | `[data-reload="#id"]`, `[data-skeleton]` | Grey shapes, after 300ms. |
| Workspace colour | `[data-brand]`, `input[data-brand-set][value="#0b6e4f"]` | Sets `--dui-signature` for that scope, so only the workspace mark changes. |
| Several pieces on one page | `[data-scope]` | Keeps a piece's notices, sign count and states to itself. Without it, they go to the first on the page. |

## Motion

Same tokens as the core: 120, 200, 480 and 700ms on `cubic-bezier(0.2, 0, 0, 1)`. Things move only to say what changed: the bulk bar swaps in, sorted rows slide, a signed ring goes live with the one pop, numbers roll, chart lines draw once. Reduced motion lands everything without moving.

The four @work moments on distinctui.com/work.html (sign, bulk send, command menu, report) are Stageshow scenes in `moments/src/work-*.tsx`, following `references/moments.md`.

## Before you call it done

- Every list or table has its loading, empty, no-results, error, no-access and read-only state drawn (`work-states.html`).
- Long and translated text (Afrikaans runs about 30% longer) wraps to two lines and stops.
- Tab through the page: the rail, the rows, the bulk bar and the dialogs all work by keyboard, with a visible focus edge.
- Run `check.js`. It also warns about initials in small marks, a person with two ring colours, and single-key shortcuts without an off switch.
