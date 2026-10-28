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
- Photos sit inside the ring on grey-100. Initials when there is no photo.
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
