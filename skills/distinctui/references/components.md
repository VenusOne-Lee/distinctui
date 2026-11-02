# Components

Markup uses the classes in `assets/distinctui.css`. Put `class="dui-page"` on `<body>` (or `class="dui"` on any wrapper). With Tailwind, the same names without the `dui-` prefix exist for buttons, fields, cards and the mark; build the rest from the tokens.

---

## Type

```html
<h1 class="dui-hero">Mzansi <b>builders</b>, before everyone else finds them.</h1>
<p class="dui-lede">Meet them. Try what they shipped. Be user number one.</p>

<h2 class="dui-h2">Just <b>shipped</b></h2>
<h3 class="dui-h3">Lift Club</h3>
<p class="dui-body">Body text in grey-800 inside cards, black on the page.</p>
<p class="dui-meta">Thandi Mokoena · v0.3</p>
<span class="dui-eyebrow">Builder of the week</span>
<a class="dui-link" href="#">How it works</a>
```

- One bold word per heading. If nothing deserves bold, the heading is too vague.
- Hero is the only centred block on a page.

## Buttons

```html
<div class="dui-actions">
  <a class="dui-btn" href="#">Share a launch</a>
  <a class="dui-btn-ghost" href="#">See how it works</a>
</div>

<a class="dui-btn-sm" href="#">Try it</a>
<a class="dui-btn-sm-ghost" href="#">Details</a>

<button class="dui-btn-sm" type="button">
  <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2.5v11l9-5.5z"/></svg> Play
</button>

<a class="dui-btn-sm-ghost dui-has-count" href="#">You <span class="dui-count">3<span class="dui-sr-only"> things need you</span></span></a>
```

- One black pill per group. Big pills (`dui-btn`) for page-level actions, small pills in cards and headers.
- The count badge is only for things waiting on this person to act. Never for "new" or totals.
- Disabled is 40% opacity. Prefer explaining why over disabling.

## Badges

```html
<span class="dui-badge">Live</span>             <!-- status -->
<span class="dui-badge-solid">Featured</span>   <!-- highlight, at most one per card -->
<span class="dui-badge-soft">v0.3</span>        <!-- version -->
```

## Form fields

```html
<form class="dui-form" novalidate>
  <div class="dui-problems" role="alert">
    <p>Two things to fix before this can go out.</p>
    <ul>
      <li><a href="#name">Project name</a>: add a name, up to 40 characters.</li>
      <li><a href="#link">Link</a>: add a link that starts with https://.</li>
    </ul>
  </div>

  <label class="dui-label" for="name">Project name
    <input class="dui-input" id="name" maxlength="40" aria-invalid="true" aria-describedby="name-error">
  </label>
  <p class="dui-field-error" id="name-error">Add a name, up to 40 characters.</p>

  <label class="dui-label" for="pitch">One line <span class="dui-hint">What it does, for whom.</span>
    <textarea class="dui-textarea" id="pitch" maxlength="140"></textarea>
  </label>
  <span class="dui-counter">42/140</span>

  <label class="dui-label" for="city">City <span class="dui-optional">(optional)</span>
    <select class="dui-select" id="city"><option>Johannesburg</option></select>
  </label>

  <label class="dui-check"><input type="checkbox" name="updates"> Email me when people I follow ship.</label>

  <label class="dui-choice"><input type="radio" name="stage" checked>
    <span><b>Live</b><br><span class="dui-hint">People can use it today.</span></span></label>

  <button class="dui-btn" type="submit">Send for review</button>
</form>

<!-- A lone field, such as sign-in or search: the pill version -->
<input class="dui-field" type="email" placeholder="you@example.com" aria-label="Email">
```

- Labels sit above fields. Hints sit in the label, in grey.
- Mark optional fields, not required ones.
- Counter turns black and bold in the last 10% (`data-near="true"`).
- After a failed submit, show the problems banner at the top, each item linking to its field, plus the error under each field.
- Consent ticks are unticked by default, one per purpose.

## Cards

```html
<!-- A card you open -->
<a class="dui-card" href="#">
  <div style="display:flex;align-items:center;gap:10px">
    <span class="dui-mark" style="--mark-size:2.25rem"><span class="dui-mark__inner">TM</span></span>
    <div style="flex:1;min-width:0"><b>Thandi Mokoena</b><br><span class="dui-meta">Durban</span></div>
    <span class="dui-badge-soft">v0.3</span>
  </div>
  <h3 class="dui-card__title">Lift Club</h3>
  <p class="dui-card__text">Shared school lifts for parents in the same suburb.</p>
  <div class="dui-card__foot"><span class="dui-btn-sm">Try it</span></div>
</a>

<!-- A card with a screenshot on top -->
<a class="dui-card dui-card--flush" href="#">
  <div class="dui-card__media"><img src="shot.png" alt=""></div>
  <div class="dui-card__body">
    <h3 class="dui-h4">Lift Club</h3>
    <p class="dui-card__text">For parents in the same suburb.</p>
    <p class="dui-note"><b>Needs:</b> testers in Durban</p>
  </div>
</a>

<!-- The one thing that matters most on the page -->
<section class="dui-feature">…</section>

<!-- Supporting content -->
<div class="dui-panel">…</div>

<ul class="dui-rows"><li class="dui-row">…</li></ul>
```

- Grids: `dui-grid`, plus `dui-grid--3` or `dui-grid--4` on wide screens.
- No screenshot? Put the mark large in the media area on grey-50 instead of an empty box.
- One feature card per page at most.

## Signature mark

```html
<!-- Empty: nobody here yet, or no photo. A grey disc, dashed ring -->
<span class="dui-mark dui-mark--plain" data-live="false" style="--mark-size:4rem"><span class="dui-mark__inner"></span></span>

<!-- Not live yet: dashed -->
<span class="dui-mark" data-live="false" style="--mark-size:4rem">
  <span class="dui-mark__inner">TM</span>
  <span class="dui-mark__version">v0.1</span>
</span>

<!-- Live: solid, with a photo -->
<span class="dui-mark" style="--mark-size:7.5rem">
  <span class="dui-mark__inner"><img src="face.jpg" alt=""></span>
  <span class="dui-mark__version">v1.0</span>
</span>

<!-- An earned dot in someone else's colour -->
<span class="dui-mark" style="--mark-size:4rem">
  <span class="dui-mark__inner">SN</span>
  <span class="dui-mark__dot" style="--mark-colour:#ff4f1f"></span>
</span>

<span class="dui-mark dui-mark--plain">…</span>   <!-- no signature of their own -->
<span class="dui-mark dui-mark--group">…</span>   <!-- a group -->

<a class="dui-logo" href="/">your name</a>
```

- Set `--dui-signature` once on `:root`. If each person in your product has their own colour, set `--mark-colour` per mark, and keep that colour set small (five or fewer), saturated and distinct.
- Three fillings, in order of preference: a photo (face centred, cropped square), initials, or empty grey for a spot nobody holds yet.
- Photos sit inside the ring on grey-100.
- The logo is a small ring in the signature colour and the name in lower case.

## Header, footer and menu

```html
<header class="dui-container dui-header">
  <a class="dui-logo" href="/">your name</a>
  <nav class="dui-nav" aria-label="Main">
    <span class="dui-nav__links"><a href="/shipped" aria-current="page">Shipped</a><a href="/people">People</a></span>
    <a class="dui-btn-sm" href="/share">Share</a>
    <details class="dui-menu">
      <summary class="dui-btn-sm-ghost">You</summary>
      <div class="dui-menu__panel">
        <a class="dui-menu__item" href="/me">Your page</a>
        <a class="dui-menu__item" href="/me/settings">Settings</a>
        <div class="dui-menu__sep"></div>
        <button class="dui-menu__item" type="submit">Sign out</button>
      </div>
    </details>
  </nav>
</header>
<!-- On phones the text links move to a scrolling row under the header -->
<nav class="dui-container dui-subnav" aria-label="Sections"><a href="/shipped" aria-current="page">Shipped</a><a href="/people">People</a></nav>

<footer class="dui-container dui-footer">
  <span><b>your name</b> yourdomain.com</span>
  <span>One line about why you exist.</span>
  <nav aria-label="More"><a href="/privacy">Privacy</a><a href="/help">Help</a></nav>
</footer>
```

- Current page in the nav is bold, not coloured or underlined.
- Close menus on Escape and on a click outside.

## Tabs

```html
<div class="dui-tabs" role="tablist">
  <button class="dui-tab" role="tab" aria-selected="true">Read it</button>
  <button class="dui-tab" role="tab" aria-selected="false">Edit the words</button>
</div>
```

## Notices

```html
<p class="dui-status" role="status">You're signed in as <b>naledi@example.com</b>.</p>

<div class="dui-empty">
  <span class="dui-mark" data-live="false" style="--mark-size:4rem"><span class="dui-mark__inner">+</span></span>
  <p>Your launches show up here once they're live.</p>
  <a class="dui-btn-sm" href="/share">Share your first one</a>
</div>
```

- No toasts that vanish before they're read. Show the status where the person is looking, once.
- Add `dui-flash` to the element a link lands on, to outline it for a few seconds.

## Page furniture

```html
<!-- Where you are. The last item is the page itself. -->
<ol class="dui-crumbs"><li><a href="/docs">Docs</a></li><li><a href="/docs/start">Start</a></li><li aria-current="page">Import</li></ol>

<!-- More pages of results -->
<nav aria-label="More results">
  <ul class="dui-pager">
    <li><span aria-current="page">1</span></li><li><a href="?p=2">2</a></li>
    <li><span class="dui-pager__gap">…</span></li><li><a href="?p=8">8</a></li>
    <li><a href="?p=2">Next</a></li>
  </ul>
</nav>

<!-- Steps through a flow: done, current, still to come -->
<ol class="dui-steps" aria-label="Checkout">
  <li data-done="true"><span>Bag</span></li>
  <li aria-current="step"><span>Delivery</span></li>
  <li><span>Pay</span></li>
</ol>

<!-- How far along -->
<label class="dui-label">Profile 60% done <progress class="dui-progress" max="100" value="60">60%</progress></label>

<!-- A side menu for docs and help -->
<nav class="dui-sidenav" aria-label="Docs">
  <div><h2>Start</h2><ul><li><a href="#">Install</a></li><li><a href="#" aria-current="page">Import</a></li></ul></div>
</nav>
```

- Breadcrumbs only when pages nest three deep or more. The current page isn't a link.
- Page numbers for lists people come back to (search, an archive). For a feed, a "Show more" outlined pill is kinder.
- Steps: three to five, short names. On phones only the current step's name shows.

## Questions, tables and code

```html
<div class="dui-accordion">
  <details open><summary>Does it work offline?</summary><p>Yes. Counts save on the phone and sync later.</p></details>
  <details><summary>Can I pay in cash?</summary><p>Not yet.</p></details>
</div>

<div class="dui-table-wrap">
  <table class="dui-table">
    <thead><tr><th>Item</th><th class="dui-num">Price</th></tr></thead>
    <tbody><tr><td>Speckled mug</td><td class="dui-num">R320</td></tr></tbody>
    <tfoot><tr><td>Total</td><td class="dui-num">R320</td></tr></tfoot>
  </table>
</div>

<pre class="dui-code"><code>npm install @distinctui/css</code></pre>
<p>Inline <code>code</code> gets a grey-100 pill.</p>
```

- Open the first question when it's the one most people ask. Never open them all.
- Tables: a black line under the header, grey hairlines between rows, no stripes, numbers right-aligned with `dui-num`. Wrap in `dui-table-wrap` so a wide table scrolls on phones instead of the page.

## Switches, chips and faces

```html
<!-- Saves straight away. For choices that wait for a Save button, use a checkbox. -->
<label class="dui-switch">Remind me on Sundays <input type="checkbox" role="switch" checked></label>

<!-- A filter that's on. Press to remove. -->
<button class="dui-chip" type="button" aria-label="Remove filter: Help">Help</button>

<!-- Who else is here: overlapping marks, each in its own ring -->
<div class="dui-stack">
  <span class="dui-mark" data-ring="1" style="--mark-size:2.5rem"><span class="dui-mark__inner"><img src="a.jpg" alt=""></span></span>
  <span class="dui-mark" data-ring="2" style="--mark-size:2.5rem"><span class="dui-mark__inner">SN</span></span>
  <span class="dui-stack__more">and 12 more</span>
</div>
```

- A switch is on (black) or off (grey). Never use it for consent; consent is an unticked checkbox, one per purpose.
- Stack at most five faces, then say how many more in words.

## Dialogs and loading

```html
<dialog class="dui-dialog" id="delete">
  <h2 class="dui-h4">Delete Lift Club?</h2>
  <p class="dui-body">Its page and its 14 versions go for good. People who tried it keep their notes.</p>
  <form method="dialog" class="dui-actions">
    <button class="dui-btn" value="delete">Delete it</button>
    <button class="dui-btn-ghost" value="cancel">Keep it</button>
  </form>
</dialog>

<span class="dui-skeleton" style="width:60%"></span>
<span class="dui-skeleton" style="width:40%;margin-top:8px"></span>
```

- A dialog is for a question that has to be answered before going on: deleting, leaving with unsaved work. Not for news or tips. Use `showModal()` so focus stays inside and Escape closes it.
- The veil behind is white and blurred, never a dark overlay.
- Show loading shapes only if a wait passes 300ms, in the shape of what's coming. No spinners on whole pages.

## Icons

```html
<a class="dui-btn-sm-ghost" href="/bag"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#bag"/></svg> Bag</a>
<button class="dui-icon-btn" type="button" aria-label="Close"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#x"/></svg></button>
```

- Lucide outlines from `assets/icons.svg`, in the text colour, next to a word. Rules in `references/imagery.md`.

## Quotes and testimonials

```html
<!-- A pull quote: light, one bold word, who said it under it -->
<figure class="dui-quote">
  <blockquote><p>"Saturdays used to be forty WhatsApps before nine. Now the chair is <b>booked</b> by Thursday."</p></blockquote>
  <figcaption class="dui-quote__by">
    <span class="dui-mark dui-mark--plain" style="--mark-size:2.75rem"><span class="dui-mark__inner"><img src="face.jpg" alt=""></span></span>
    <span><b>Sizwe Ndlovu</b><br>Barber, Melville</span>
  </figcaption>
</figure>

<!-- A quote inside running text -->
<blockquote class="dui-blockquote"><p>We delete your bookings 30 days after you close your account.</p><cite>From the privacy page</cite></blockquote>

<!-- Testimonials: two or three of these in a dui-grid dui-grid--3 -->
<figure class="dui-testimonial">
  <blockquote><p>"Reminders cut our no-shows from six a week to <b>one</b>."</p></blockquote>
  <figcaption class="dui-quote__by">…</figcaption>
</figure>
```

- One pull quote per section at most. No giant quotation marks, no coloured bars.
- Customers wear the plain black ring (`dui-mark--plain`): the colour belongs to your mark. Initials when there's no photo.
- No star ratings in gold and no customer logos in colour. If you show stars, they're black outlines filled black.

## Dates and amounts

```html
<!-- A date field. With distinctui.js the button opens a month grid under it; without, the native picker works. -->
<div class="dui-date" data-datepicker data-closed="1">
  <label class="dui-label" for="day">Day <span class="dui-hint">Closed on Mondays.</span></label>
  <span class="dui-date__field">
    <input class="dui-input" id="day" type="date" min="2026-11-02">
    <button class="dui-date__open" type="button" aria-label="Choose from a calendar" aria-expanded="false"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#calendar"/></svg></button>
  </span>
</div>

<input class="dui-input" type="time" step="900" aria-label="Time">

<!-- A month grid on its own, for bookings. Fires "dui:pick" with the date. -->
<div class="dui-calendar" data-calendar data-min="today" data-closed="1 0" data-for="#day" aria-label="Pick a day"></div>

<!-- How many -->
<div class="dui-stepper" role="group" aria-label="How many mugs">
  <button type="button" data-step="-1" aria-label="One fewer"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#minus"/></svg></button>
  <input type="number" inputmode="numeric" value="1" min="1" max="6" aria-label="Quantity">
  <button type="button" data-step="1" aria-label="One more"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#plus"/></svg></button>
</div>
```

- Keep the native date input underneath, so phones get their own wheel and typing still works.
- Weeks start on Monday. Today has a grey edge; the picked day is black. Days you can't pick are grey and crossed out, and the hint says why (`data-closed` takes days of the week, 0 for Sunday).
- The stepper's buttons fade at the limits. In a cart, going to zero takes the item out; add `dui-stepper--sm` inside rows.

## Copying

```html
<div class="dui-copy-wrap">
  <pre class="dui-code"><code id="install">npm install @distinctui/css</code></pre>
  <button class="dui-btn-sm-ghost dui-copy" type="button" data-copy="#install">Copy</button>
</div>

<div class="dui-copy-line"><code id="order-no">CH-20418</code><button class="dui-btn-sm-ghost" type="button" data-copy="#order-no">Copy</button></div>
```

- The button confirms on itself ("Copied", with a tick) and settles back. Never a toast. If the clipboard is blocked, the text is selected instead.
- `data-copy-text="…"` copies text that isn't on the page, such as a share link.

## Announcements and cookies

```html
<!-- First thing in <body>. Closes for good once data-dismiss-key is set. -->
<div class="dui-announce" data-dismiss-key="kiln-nov">
  <div class="dui-container"><p><b>New from the kiln:</b> the terracotta set is back. <a href="/shop">See it</a></p>
  <button type="button" data-dismiss aria-label="Close this notice"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#x"/></svg></button></div>
</div>

<!-- Last thing in <body>. A small panel in the corner. -->
<aside class="dui-cookies" aria-label="Cookies" data-dismiss-key="cookies">
  <p><b>Cookies.</b> One keeps you signed in. With your OK, another counts visits. <a href="/privacy#cookies">What we use</a></p>
  <div class="dui-actions">
    <button class="dui-btn-sm" type="button" data-dismiss="needed">Only what's needed</button>
    <button class="dui-btn-sm-ghost" type="button" data-dismiss="all">Allow counting</button>
  </div>
</aside>
```

- One announcement line at a time, one sentence, one link. Not for anything urgent: that's a status notice where the person is looking.
- The cookie notice never covers the page, has no veil, and doesn't block scrolling. The black pill is the choice that shares least. Load anything optional only after `distinctUI.dismissed("cookies") === "all"`, or on the `dui:dismiss` event. Add `dui-cookies--inline` to put it in the flow instead.
- No pop-up on arrival, no newsletter wall, no floating chat bubble.

## Sections

```html
<!-- Words beside a photo. Stack them in dui-splits and flip every second one. -->
<div class="dui-split" style="--split-ratio:1/1">
  <div class="dui-split__media"><img src="mug.jpg" alt="A hand-thrown mug in a speckled oat glaze"></div>
  <div class="dui-split__body"><span class="dui-eyebrow">Made by hand</span><h2 class="dui-h2">Thrown in <b>Woodstock</b></h2><p class="dui-body">…</p></div>
</div>
<div class="dui-split dui-split--flip">…</div>

<!-- How it works: three or four steps -->
<ol class="dui-how">
  <li><h3>Pick a time</h3><p>See the open chairs for the week.</p></li>
  <li><h3>Get a reminder</h3><p>An SMS the evening before.</p></li>
  <li><h3>Walk in</h3><p>Pay at the chair.</p></li>
</ol>

<!-- Plan comparison, under the plan cards -->
<div class="dui-table-wrap"><table class="dui-table dui-compare">
  <thead><tr><th scope="col"><span class="dui-sr-only">What you get</span></th><th scope="col">Solo<span>R99 a month</span></th><th scope="col" data-pick><span class="dui-badge-solid">Most pick</span>Shop<span>R249 a month</span></th></tr></thead>
  <tbody>
    <tr class="dui-compare__group"><th scope="colgroup" colspan="3">Bookings</th></tr>
    <tr><th scope="row">Deposits</th><td><span class="dui-no"><span class="dui-sr-only">Not included</span></span></td><td><span class="dui-yes"><span class="dui-sr-only">Included</span></span></td></tr>
  </tbody>
</table></div>

<!-- Newsletter: say how often and what you'll get -->
<div class="dui-signup">
  <div><h2 class="dui-h2">Hear when the kiln <b>opens</b></h2><p class="dui-body">One email a month. Nothing else.</p></div>
  <form>
    <div class="dui-inline"><label class="dui-sr-only" for="nl">Email</label><input class="dui-field" id="nl" type="email" placeholder="you@example.com"><button class="dui-btn" type="submit">Sign me up</button></div>
    <p class="dui-meta">Leave any time with the link in each email.</p>
  </form>
</div>

<!-- Opening hours and location -->
<p class="dui-hours__now" id="now"></p>
<table class="dui-hours" data-hours="#now"><tbody>
  <tr data-day="1"><th scope="row">Monday</th><td>Closed</td></tr>
  <tr data-day="2" data-open="09:00" data-close="18:00"><th scope="row">Tuesday</th><td>09:00 to 18:00</td></tr>
  …
</tbody></table>
<address class="dui-place"><svg class="dui-icon" aria-hidden="true"><use href="icons.svg#map-pin"/></svg><span><b>Tilde barbers</b><br>12 Jacaranda Lane, Melville</span></address>
<a class="dui-btn-sm-ghost" href="https://maps.example/…">Get directions</a>
```

- Photos sit beside the words, never under them. Fixed shapes: `--split-ratio` 3/2 for scenes, 1/1 for products.
- Comparison: ticks in black, "no" as a grey dash, words where a number helps ("500 a month"). The plan most people pick gets `data-pick` (a black line on top) and the page's one solid badge.
- Hours: write every day, closed ones too, as 24-hour times. `distinctui.js` bolds today and fills the "Open now" line from the visitor's clock, so leave the line out if your visitors are mostly in another time zone. Link out for directions instead of a coloured map.
