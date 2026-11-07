/*
 * distinctUI@work: motion and interaction for app screens. Optional, no dependencies.
 * Load it after distinctui.js. https://distinctui.com · MIT licence
 *
 * <script src="distinctui.js" defer></script>
 * <script src="distinctui-work.js" defer></script>
 *
 * By attribute:
 *   [data-rail-toggle]               opens and closes the rail on phones
 *   dialog.dui-cmd                   the command menu: ⌘K or Ctrl+K, or [data-cmd-open]
 *     .dui-cmd__opt[data-href|data-click="#id"|data-words]  what each command does
 *   [data-key="a"] / [data-key="g c"] a shortcut that presses this element. Never the only
 *                                    way in. [data-keys-toggle] (a switch) turns single keys
 *                                    off for everyone who needs that (WCAG 2.1.4)
 *   table.dui-data[data-select]      rows with [data-row-check] and a [data-check-all];
 *                                    the nearest .dui-swapbar swaps views for the bulk bar
 *   [data-bulk="stage"] / [data-bulk="archive"]  bulk actions on the selected rows, each
 *                                    with an Undo in a notice that stays
 *   button.dui-sort                  sorts its column; rows slide to their new places
 *   input[data-filter-rows="#table"] filters rows as you type; [data-no-results] shows at none
 *   input[name][data-density-set]    Compact, Default or Airy for the nearest [data-density]
 *   input[data-col="vendor"]         shows or hides cells marked data-c="vendor"
 *   .dui-split-view .dui-item[data-open-item="id"]  opens [data-detail="id"] beside the list
 *   [data-sign="#step"]              signs: the ring's dashes fill in, the link goes solid,
 *                                    the count rolls, a row joins the audit trail
 *   .dui-draw-lines                  chart lines draw in once, when seen
 *   input[data-view-as-set]          chart or table for the nearest [data-view-as]
 *   [data-dialog-open="#id"]         opens a dui-dialog; [data-dialog-close] closes it
 *   input[data-confirm-match="name"] enables [data-confirm-btn] once the name is typed
 *   select[data-viewer-set]          who's looking: admin and auditor see .dui-private cells
 *   [data-mark-read]                 marks every update as read
 *   [data-reload="#id"]              shows loading shapes, then the content again
 *
 * From your own code:
 *   distinctUI.notice("Sent 2 contracts", { undo: () => … })  a notice that stays until closed
 *
 * Reduced motion: everything still works, it just lands without moving.
 */
(() => {
  const ui = window.distinctUI || {};
  const root = document.documentElement;
  const still = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const store = {
    get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch {} },
  };
  let say;
  const announce = (text) => {
    if (!say) { say = document.createElement("span"); say.className = "dui-sr-only"; say.setAttribute("aria-live", "polite"); document.body.append(say); }
    say.textContent = ""; setTimeout(() => { say.textContent = text; }, 50);
  };
  const add = (list, item, opts) => (ui.add ? ui.add(list, item, opts) : list.prepend(item));
  const remove = (item) => (ui.remove ? ui.remove(item) : item.remove());
  const roll = (el, n) => (ui.roll ? ui.roll(el, n) : (el.textContent = n));
  const typing = (el) => el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
  const changed = (el) => { el.classList.remove("dui-changed"); void el.offsetWidth; el.classList.add("dui-changed"); };

  /* ---------- Notices that stay (no toasts) ---------- */
  function notice(text, { undo, where } = {}) {
    const box = where || $("[data-notices]");
    if (!box) { announce(text); return null; }
    const n = document.createElement("div");
    n.className = "dui-status";
    n.setAttribute("role", "status");
    const words = document.createElement("span");
    words.textContent = text;
    const end = document.createElement("span");
    end.className = "dui-status__end";
    if (undo) {
      const u = document.createElement("button");
      u.type = "button"; u.className = "dui-link"; u.textContent = "Undo";
      u.addEventListener("click", () => { undo(); remove(n); announce("Undone"); });
      end.append(u);
    }
    const x = document.createElement("button");
    x.type = "button"; x.className = "dui-link"; x.textContent = "Close";
    x.addEventListener("click", () => remove(n));
    end.append(x);
    n.append(words, end);
    add(box, n);
    announce(text);
    return n;
  }

  /* ---------- Rail on phones ---------- */
  const app = $(".dui-app");
  const closeRail = () => app && app.removeAttribute("data-rail");
  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-rail-toggle]");
    if (t && app) { const open = app.dataset.rail !== "open"; open ? (app.dataset.rail = "open") : closeRail(); t.setAttribute("aria-expanded", String(open)); if (open) $(".dui-rail a, .dui-rail button", app)?.focus(); return; }
    if (app && app.dataset.rail === "open" && e.target === app) closeRail();
  });

  /* ---------- Shortcuts, which can be switched off ---------- */
  const keysOn = () => root.dataset.keys !== "off";
  root.dataset.keys = store.get("dui-keys") || "on";
  $$("[data-keys-toggle]").forEach((t) => { t.checked = keysOn(); });
  document.addEventListener("change", (e) => {
    if (e.target.matches("[data-keys-toggle]")) {
      root.dataset.keys = e.target.checked ? "on" : "off";
      store.set("dui-keys", root.dataset.keys);
      announce(e.target.checked ? "Single-key shortcuts on" : "Single-key shortcuts off");
    }
  });
  let pending = "", pendingAt = 0;

  /* ---------- Command menu ---------- */
  const cmd = $("dialog.dui-cmd");
  let cmdReturn = null;
  const opts = () => (cmd ? $$(".dui-cmd__opt", cmd).filter((o) => !o.hidden) : []);
  function pick(o) {
    if (!cmd) return;
    $$(".dui-cmd__opt", cmd).forEach((x) => x.setAttribute("aria-selected", String(x === o)));
    if (o) { o.scrollIntoView({ block: "nearest" }); $(".dui-cmd__q input", cmd)?.setAttribute("aria-activedescendant", o.id); }
  }
  function filterCmd(q) {
    q = q.trim().toLowerCase();
    // Every word typed has to appear somewhere, in any order: "assign nal" finds "Assign to Naledi".
    const terms = q.split(/\s+/).filter(Boolean);
    $$(".dui-cmd__opt", cmd).forEach((o) => { const hay = (o.textContent + " " + (o.dataset.words || "")).toLowerCase(); o.hidden = !terms.every((t) => hay.includes(t)); });
    $$(".dui-cmd__group", cmd).forEach((g) => {
      let n = g.nextElementSibling, any = false;
      while (n && !n.classList.contains("dui-cmd__group")) { if (n.classList.contains("dui-cmd__opt") && !n.hidden) any = true; n = n.nextElementSibling; }
      g.hidden = !any;
    });
    const empty = $(".dui-cmd__empty", cmd);
    if (empty) empty.hidden = opts().length > 0;
    pick(opts()[0]);
  }
  function openCmd() {
    if (!cmd || cmd.open) return;
    cmdReturn = document.activeElement;
    const input = $(".dui-cmd__q input", cmd);
    if (input) input.value = "";
    filterCmd("");
    cmd.showModal();
    input?.focus();
  }
  function run(o) {
    if (!o) return;
    cmd.close();
    if (o.dataset.href) { location.href = o.dataset.href; return; }
    const target = o.dataset.click && $(o.dataset.click);
    if (target) target.click();
    document.dispatchEvent(new CustomEvent("dui:command", { detail: { id: o.id, el: o } }));
    if (o.dataset.done) notice(o.dataset.done);
  }
  if (cmd) {
    $$(".dui-cmd__opt", cmd).forEach((o, i) => { o.id ||= "dui-cmd-" + i; o.setAttribute("role", "option"); });
    cmd.addEventListener("input", (e) => { if (e.target.matches(".dui-cmd__q input")) filterCmd(e.target.value); });
    cmd.addEventListener("keydown", (e) => {
      const list = opts(), at = list.findIndex((o) => o.getAttribute("aria-selected") === "true");
      if (e.key === "ArrowDown" || e.key === "ArrowUp") { e.preventDefault(); pick(list[(at + (e.key === "ArrowDown" ? 1 : -1) + list.length) % list.length]); }
      if (e.key === "Enter") { e.preventDefault(); run(list[at]); }
    });
    cmd.addEventListener("click", (e) => { const o = e.target.closest(".dui-cmd__opt"); if (o) run(o); else if (e.target === cmd) cmd.close(); });
    cmd.addEventListener("pointermove", (e) => { const o = e.target.closest(".dui-cmd__opt"); if (o && o.getAttribute("aria-selected") !== "true") pick(o); });
    cmd.addEventListener("close", () => cmdReturn?.focus?.());
  }

  document.addEventListener("keydown", (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k" && cmd) { e.preventDefault(); cmd.open ? cmd.close() : openCmd(); return; }
    if (e.key === "Escape" && app?.dataset.rail === "open") { closeRail(); return; }
    if (e.metaKey || e.ctrlKey || e.altKey || typing(e.target) || document.querySelector("dialog[open]")) return;
    // Single keys: only when they're switched on.
    if (!keysOn() || e.key.length !== 1) return;
    const k = e.key.toLowerCase();
    const now = performance.now();
    const seq = pending && now - pendingAt < 900 ? pending + " " + k : k;
    const hit = $$("[data-key]").find((el) => el.dataset.key === seq && !el.closest("[hidden]"));
    if (hit) { e.preventDefault(); pending = ""; hit.focus?.(); hit.click(); return; }
    if ($$("[data-key]").some((el) => el.dataset.key.startsWith(k + " "))) { pending = k; pendingAt = now; } else pending = "";
  });
  document.addEventListener("click", (e) => { if (e.target.closest("[data-cmd-open]")) openCmd(); });

  /* ---------- Row selection and the bulk bar ---------- */
  function selection(table) {
    const rows = $$("tbody tr", table).filter((r) => !r.hidden && $("[data-row-check]", r));
    const on = rows.filter((r) => $("[data-row-check]", r).checked);
    rows.forEach((r) => r.setAttribute("aria-selected", String($("[data-row-check]", r).checked)));
    const all = $("[data-check-all]", table);
    if (all) { all.checked = on.length > 0 && on.length === rows.length; all.indeterminate = on.length > 0 && on.length < rows.length; }
    const bar = table.dataset.select ? $(table.dataset.select) : $(".dui-swapbar");
    if (bar) {
      const was = bar.hasAttribute("data-selecting");
      bar.toggleAttribute("data-selecting", on.length > 0);
      const count = $("[data-selected-count]", bar);
      if (count) roll(count, on.length);
      if (!was && on.length) $(".dui-bulk button", bar)?.setAttribute("tabindex", "0");
    }
    return on;
  }
  document.addEventListener("change", (e) => {
    const t = e.target;
    const table = t.closest && t.closest("table.dui-data[data-select]");
    if (!table) return;
    if (t.matches("[data-check-all]")) $$("tbody tr:not([hidden]) [data-row-check]", table).forEach((c) => { c.checked = t.checked; });
    if (t.matches("[data-check-all], [data-row-check]")) {
      const n = selection(table).length;
      announce(n ? `${n} selected` : "Nothing selected");
    }
  });
  const tableFor = (el) => { const bar = el.closest(".dui-swapbar"); return (bar && bar.dataset.for && $(bar.dataset.for)) || $("table.dui-data[data-select]"); };
  function clearSelection(table) { $$("[data-row-check]", table).forEach((c) => { c.checked = false; }); selection(table); }

  document.addEventListener("click", (e) => {
    const clear = e.target.closest("[data-clear-selection]");
    if (clear) { const t = tableFor(clear); if (t) { clearSelection(t); announce("Nothing selected"); } return; }
    const b = e.target.closest("[data-bulk]");
    if (!b) return;
    const table = tableFor(b);
    if (!table) return;
    const rows = selection(table);
    if (!rows.length) return;
    const n = rows.length, noun = n === 1 ? (b.dataset.one || "row") : (b.dataset.many || "rows");
    if (b.dataset.bulk === "archive") {
      const places = rows.map((r) => ({ r, next: r.nextElementSibling, body: r.parentElement }));
      rows.forEach((r) => remove(r));
      setTimeout(() => selection(table), 450);
      notice(`Archived ${n} ${noun}.`, { undo: () => places.reverse().forEach(({ r, next, body }) => { $("[data-row-check]", r).checked = false; r.removeAttribute("aria-selected"); next && next.isConnected ? body.insertBefore(r, next) : body.append(r); changed(r); }) });
    } else {
      const before = rows.map((r) => { const c = $("[data-cell='stage']", r); return { r, c, html: c?.innerHTML }; });
      before.forEach(({ r, c }) => { if (c) { c.innerHTML = `<span class="dui-st" data-st="${b.dataset.st || "review"}">${b.dataset.label || "In review"}</span>`; } changed(r); });
      clearSelection(table);
      notice(`${b.dataset.doneWords || "Done"}: ${n} ${noun}.`, { undo: () => before.forEach(({ r, c, html }) => { if (c) c.innerHTML = html; changed(r); }) });
    }
  });

  // Rows by keyboard: up and down move, space selects, enter opens.
  document.addEventListener("keydown", (e) => {
    const tr = e.target.closest && e.target.closest("table.dui-data tbody tr[tabindex]");
    if (!tr || e.target !== tr) return;
    if (e.key === "ArrowDown" || e.key === "ArrowUp") {
      e.preventDefault();
      let n = e.key === "ArrowDown" ? tr.nextElementSibling : tr.previousElementSibling;
      while (n && n.hidden) n = e.key === "ArrowDown" ? n.nextElementSibling : n.previousElementSibling;
      n?.focus();
    } else if (e.key === " " || e.key === "x") {
      const c = $("[data-row-check]", tr);
      if (c) { e.preventDefault(); c.checked = !c.checked; c.dispatchEvent(new Event("change", { bubbles: true })); }
    } else if (e.key === "Enter") {
      $(".dui-row-link", tr)?.click();
    }
  });

  /* ---------- Sorting ---------- */
  const val = (td) => {
    if (!td) return "";
    const v = td.dataset.v ?? td.textContent.trim();
    const n = parseFloat(String(v).replace(/[^\d.-]/g, ""));
    return /^[\sR$€£]*-?[\d\s,.]+%?$/.test(v) || td.dataset.v != null && !isNaN(n) ? n : v.toLowerCase();
  };
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("th .dui-sort");
    if (!btn) return;
    const th = btn.closest("th"), table = th.closest("table"), body = table.tBodies[0];
    const i = [...th.parentElement.children].indexOf(th);
    const dir = th.getAttribute("aria-sort") === "descending" ? "ascending" : "descending";
    $$("th[aria-sort]", table).forEach((h) => h.removeAttribute("aria-sort"));
    th.setAttribute("aria-sort", dir);
    const rows = [...body.rows];
    const first = new Map(rows.map((r) => [r, r.getBoundingClientRect().top]));
    rows.sort((a, b) => {
      const x = val(a.cells[i]), y = val(b.cells[i]);
      const c = typeof x === "number" && typeof y === "number" ? x - y : String(x).localeCompare(String(y));
      return dir === "ascending" ? c : -c;
    }).forEach((r) => body.append(r));
    if (!still) rows.forEach((r) => {
      const d = first.get(r) - r.getBoundingClientRect().top;
      if (!d) return;
      r.style.transition = "none"; r.style.transform = `translateY(${d}px)`;
      requestAnimationFrame(() => { r.style.transition = ""; r.style.transform = ""; });
    });
    announce(`Sorted by ${btn.textContent.trim()}, ${dir === "descending" ? "highest" : "lowest"} first`);
  });

  /* ---------- Filtering rows ---------- */
  document.addEventListener("input", (e) => {
    const f = e.target.closest && e.target.closest("input[data-filter-rows]");
    if (!f) return;
    const table = $(f.dataset.filterRows);
    if (!table) return;
    const q = f.value.trim().toLowerCase();
    let shown = 0;
    $$("tbody tr", table).forEach((r) => { const show = !q || r.textContent.toLowerCase().includes(q); r.hidden = !show; if (show) shown++; });
    const none = $(f.dataset.noResults || "[data-no-results]");
    if (none) { none.hidden = shown > 0; const w = $("[data-query]", none); if (w) w.textContent = f.value.trim(); }
    table.closest(".dui-data-wrap")?.toggleAttribute("hidden", shown === 0);
    $$("[data-shown-count]").forEach((c) => (c.textContent = shown));
    if (table.matches("[data-select]")) selection(table);
  });

  /* ---------- Density and columns ---------- */
  const densityScope = (el) => el.closest("[data-density]") || $("[data-density]");
  const savedDensity = store.get("dui-density");
  if (savedDensity) {
    $$("[data-density]").forEach((s) => (s.dataset.density = savedDensity));
    $$(`input[data-density-set][value="${savedDensity}"]`).forEach((i) => (i.checked = true));
  }
  document.addEventListener("change", (e) => {
    const t = e.target;
    if (t.matches("input[data-density-set]") && t.checked) {
      const s = densityScope(t);
      if (s) s.dataset.density = t.value;
      store.set("dui-density", t.value);
      announce(`${t.value[0].toUpperCase() + t.value.slice(1)} rows`);
    }
    if (t.matches("input[data-col]")) {
      $$(`[data-c="${t.dataset.col}"]`).forEach((c) => (c.hidden = !t.checked));
    }
    if (t.matches("input[data-view-as-set]") && t.checked) {
      const w = t.closest("[data-view-as]") || $(t.dataset.viewAsSet);
      if (w) w.dataset.viewAs = t.value;
    }
    if (t.matches("select[data-viewer-set]")) {
      const s = t.closest("[data-viewer]") || document.body;
      s.dataset.viewer = t.value;
      announce(`Showing what ${t.options[t.selectedIndex].text} sees`);
    }
    if (t.matches("select.dui-role-select:not([data-viewer-set])")) {
      const row = t.closest("tr, li");
      if (row) changed(row);
      notice(`${t.dataset.who || "Their"} role is now ${t.value}.`);
    }
  });

  /* ---------- A list beside the open item ---------- */
  function openItem(item, focusDetail) {
    const view = item.closest(".dui-split-view");
    $$(".dui-item[data-open-item]", view).forEach((i) => i.setAttribute("aria-current", String(i === item)));
    $$("[data-detail]", view).forEach((d) => (d.hidden = d.dataset.detail !== item.dataset.openItem));
    view.dataset.open = item.dataset.openItem;
    const d = $(`[data-detail="${item.dataset.openItem}"]`, view);
    if (d && !still) { d.style.opacity = 0; requestAnimationFrame(() => { d.style.transition = "opacity 200ms cubic-bezier(.2,0,0,1)"; d.style.opacity = 1; }); }
    if (focusDetail) $("h1, h2", d)?.focus();
  }
  document.addEventListener("click", (e) => {
    const item = e.target.closest(".dui-split-view .dui-item[data-open-item]");
    if (item) { e.preventDefault(); openItem(item, matchMedia("(max-width: 899px)").matches); return; }
    const back = e.target.closest("[data-split-back]");
    if (back) { const v = back.closest(".dui-split-view"); v.removeAttribute("data-open"); $(".dui-item[aria-current='true']", v)?.focus(); }
  });
  document.addEventListener("keydown", (e) => {
    const item = e.target.closest && e.target.closest(".dui-split-view .dui-item[data-open-item]");
    if (!item || !/^(ArrowDown|ArrowUp)$/.test(e.key)) return;
    e.preventDefault();
    const items = $$(".dui-item[data-open-item]", item.closest(".dui-split-view")).filter((i) => !i.hidden);
    const n = items[items.indexOf(item) + (e.key === "ArrowDown" ? 1 : -1)];
    if (n) { n.focus(); openItem(n, false); }
  });
  // j and k move through the list, as single keys (off with the switch).
  document.addEventListener("dui:move", (e) => {
    const view = $(".dui-split-view");
    if (!view) return;
    const items = $$(".dui-item[data-open-item]", view);
    const at = items.findIndex((i) => i.getAttribute("aria-current") === "true");
    const n = items[Math.max(0, Math.min(items.length - 1, at + e.detail))];
    if (n) { n.focus(); openItem(n, false); }
  });
  document.addEventListener("click", (e) => { const m = e.target.closest("[data-move]"); if (m) document.dispatchEvent(new CustomEvent("dui:move", { detail: +m.dataset.move })); });

  /* ---------- Signing ---------- */
  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-sign]");
    if (!b || b.getAttribute("aria-disabled") === "true") return;
    const step = $(b.dataset.sign);
    if (!step) return;
    const mark = $(".dui-mark", step);
    if (mark) { mark.dataset.live = "true"; mark.classList.remove("dui-live-now"); void mark.offsetWidth; mark.classList.add("dui-live-now"); }
    const link = step.previousElementSibling;
    if (link && link.classList.contains("dui-chain__link")) link.dataset.done = "true";
    const when = $("[data-when]", step);
    if (when) when.textContent = when.dataset.when;
    const count = $("[data-sign-count]");
    if (count) roll(count, (parseInt(count.textContent, 10) || 0) + 1);
    $$("[data-sign-state]").forEach((s) => { s.dataset.st = s.dataset.signState; if (s.dataset.signLabel) s.textContent = s.dataset.signLabel; });
    const tpl = $("template[data-sign-row]"), list = tpl && $(tpl.dataset.signRow);
    if (tpl && list) { const row = tpl.content.firstElementChild.cloneNode(true); add(list, row); changed(row); }
    if (ui.done) ui.done(b, b.dataset.doneWords || "Signed", 2400);
    b.setAttribute("aria-disabled", "true");
    $$("[data-sign-hide]").forEach((h) => setTimeout(() => remove(h), 700));
    setTimeout(() => notice(b.dataset.notice || "Signed."), still ? 0 : 700);
  });

  /* ---------- Lines that draw in ---------- */
  $$(".dui-draw-lines .dui-chart-line").forEach((l) => l.setAttribute("pathLength", "1"));
  const lines = $$(".dui-draw-lines");
  if ("IntersectionObserver" in window && !still) {
    const io = new IntersectionObserver((es) => es.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("is-in"); io.unobserve(en.target); } }), { rootMargin: "0px 0px -10% 0px" });
    lines.forEach((l) => io.observe(l));
  } else lines.forEach((l) => l.classList.add("is-in"));

  /* ---------- Dialogs and typed confirmation ---------- */
  document.addEventListener("click", (e) => {
    const o = e.target.closest("[data-dialog-open]");
    if (o) { const d = $(o.dataset.dialogOpen); if (d && d.showModal) { d.showModal(); $("input", d)?.focus(); } return; }
    const c = e.target.closest("[data-dialog-close]");
    if (c) c.closest("dialog")?.close();
  });
  document.addEventListener("input", (e) => {
    const i = e.target.closest && e.target.closest("input[data-confirm-match]");
    if (!i) return;
    const scope = i.closest("form, dialog") || document;
    const ok = i.value.trim() === i.dataset.confirmMatch;
    $$("[data-confirm-btn]", scope).forEach((b) => { b.disabled = !ok; b.setAttribute("aria-disabled", String(!ok)); });
  });

  /* ---------- Updates ---------- */
  document.addEventListener("click", (e) => {
    const r = e.target.closest("[data-mark-read]");
    if (!r) return;
    const panel = r.closest(".dui-updates") || document;
    $$("[data-unread]", panel).forEach((li) => li.removeAttribute("data-unread"));
    const c = $(".dui-count", panel);
    if (c) { roll(c, 0); setTimeout(() => (c.hidden = true), 500); }
    r.hidden = true;
    announce("All updates read");
  });

  /* ---------- Loading, then the content ---------- */
  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-reload]");
    if (!b) return;
    const t = $(b.dataset.reload), sk = $(b.dataset.skeleton || "[data-skeleton]");
    if (!t || !sk) return;
    t.hidden = true; sk.hidden = false; announce("Loading");
    setTimeout(() => { sk.hidden = true; t.hidden = false; $$("tr, .dui-item", t).forEach((r, i) => { r.style.setProperty("--dui-i", i); changed(r); }); announce("Loaded"); }, 1100);
  });

  /* ---------- A gentle check for builders ---------- */
  $$(".dui-mark").forEach((m) => {
    const s = m.getBoundingClientRect().width, inner = $(".dui-mark__inner", m);
    if (s && s < 28 && inner && !$("img, svg", inner) && inner.textContent.trim() && getComputedStyle(inner).fontSize !== "0px")
      console.warn("distinctUI@work: initials in a mark under 28px can't be read. Use a photo or .dui-mark--sm.", m);
  });

  window.distinctUI = Object.assign(window.distinctUI || {}, { notice, openCommand: openCmd });
})();
