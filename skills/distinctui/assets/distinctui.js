/*
 * distinctUI motion and interaction. Optional, small, no dependencies.
 * https://distinctui.com · MIT licence
 *
 * <script src="distinctui.js" defer></script>
 *
 * What it does, all by attribute:
 *   .dui-reveal              fades up when scrolled into view
 *   .dui-mark.dui-draw       draws its ring in when seen (wait for it with .dui-reveal-draw)
 *   .dui-grow                bars grow from the baseline when seen
 *   [data-count="128"]       counts up to the number when seen
 *   [data-go-live="#id"]     a button that turns that mark from dashed to solid
 *   [data-filter="x"]        a tab that shows only [data-tags~="x"] items in the
 *                            same [data-filter-scope]; "all" shows everything
 *   [data-replay="#id"]      a button that draws the rings inside #id again
 *   [aria-pressed]           a toggle button: flips true/false and swaps its
 *                            label to data-pressed-label; data-count-target="#id"
 *                            rolls that number up or down by one
 *   [data-done="Copied"]     a button that confirms on itself, then settles back
 *   [data-copy="#id"]        copies that element's text (or data-copy-text), then
 *                            confirms on itself
 *   .dui-stepper [data-step] minus and plus around a number input, within min/max
 *   [data-calendar]          draws a month grid (data-value, data-min="today",
 *                            data-max, data-closed="1" for Mondays, data-for="#input")
 *   .dui-date[data-datepicker]  a date field whose button opens the month grid
 *   [data-dismiss]           closes its [data-dismiss-key] box for good and remembers
 *                            the answer (data-dismiss="needed"); fires "dui:dismiss"
 *   table[data-hours="#now"] marks today's row and says "Open now" or when it opens
 *
 * And from your own code, for things only your app knows happened:
 *   distinctUI.roll(el, 128)       roll a number to a new value
 *   distinctUI.done(button, "Saved")  confirm on the button that was pressed
 *   distinctUI.add(list, item)     a new row opens its room, carrying the rows below
 *   distinctUI.remove(item)        a row fades, then its room closes
 *   distinctUI.dismissed("cookies")  what someone answered, or null
 * Reduced motion: everything still works, it just lands without moving.
 */
(() => {
  const root = document.documentElement;
  root.classList.add("dui-js");
  const still = matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Seen-once observer for reveals, rings, bars and counters.
  const seen = (el) => {
    el.classList.add("is-in");
    if (el.matches(".dui-reveal-draw")) el.querySelectorAll(".dui-mark").forEach((m, i) => {
      m.style.setProperty("--dui-i", i);
      m.classList.add("dui-draw");
    });
    if (el.dataset.count != null) countUp(el);
  };
  const watch = ".dui-reveal, .dui-reveal-draw, .dui-grow, [data-count]";
  if ("IntersectionObserver" in window && !still) {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      if (e.isIntersecting) { seen(e.target); io.unobserve(e.target); }
    }), { rootMargin: "0px 0px -8% 0px" });
    document.querySelectorAll(watch).forEach((el) => io.observe(el));
  } else {
    document.querySelectorAll(watch).forEach(seen);
  }

  function countUp(el) {
    const to = parseFloat(el.dataset.count);
    const decimals = (el.dataset.count.split(".")[1] || "").length;
    const fmt = (n) => n.toLocaleString("en-ZA", { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    if (still || !isFinite(to)) { el.textContent = fmt(to); return; }
    const start = performance.now(), dur = 900;
    const tick = (now) => {
      const t = Math.min(1, (now - start) / dur);
      el.textContent = fmt(to * (1 - Math.pow(1 - t, 3)));
      if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  }

  const fmtNum = (n) => n.toLocaleString("en-ZA");
  const num = (text) => parseFloat(String(text).replace(/[^\d.-]/g, "")) || 0;

  // One polite live region, so a change said by motion is also said in words.
  let say;
  const announce = (text) => {
    if (!say) {
      say = document.createElement("span");
      say.className = "dui-sr-only";
      say.setAttribute("aria-live", "polite");
      document.body.append(say);
    }
    say.textContent = "";
    setTimeout(() => { say.textContent = text; }, 50);
  };

  // Roll: only the digits that changed move, last digit first.
  function roll(el, to) {
    const next = typeof to === "number" ? fmtNum(to) : String(to);
    const prev = el.dataset.rollValue ?? el.textContent.trim();
    el.dataset.rollValue = next;
    if (still || prev === next) { el.textContent = next; return; }
    const dir = num(next) < num(prev) ? "down" : "up";
    const len = Math.max(prev.length, next.length);
    const a = prev.padStart(len, " "), b = next.padStart(len, " ");
    const shown = document.createElement("span");
    shown.className = "dui-roll";
    shown.setAttribute("aria-hidden", "true");
    let order = 0;
    for (let i = len - 1; i >= 0; i--) {
      const d = document.createElement("span");
      if (a[i] === b[i]) { d.textContent = b[i]; shown.prepend(d); continue; }
      d.className = "dui-roll__digit";
      d.dataset.dir = dir;
      d.style.setProperty("--dui-i", order++);
      d.innerHTML = `<span class="dui-roll__old">${a[i] === " " ? "" : a[i]}</span><span class="dui-roll__new">${b[i] === " " ? "" : b[i]}</span>`;
      shown.prepend(d);
    }
    const label = document.createElement("span");
    label.className = "dui-sr-only";
    label.textContent = next;
    el.replaceChildren(shown, label);
    // Once it has landed, it's plain text again.
    setTimeout(() => { if (el.dataset.rollValue === next) el.textContent = next; }, 480 + order * 60 + 40);
  }

  // Confirm on itself: the label crossfades to a tick and the done words in
  // the same footprint, holds, then settles back.
  const tick = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7"/></svg>';
  function done(btn, words = btn.dataset.done || "Done", hold = 1800) {
    if (!btn.querySelector(":scope > .dui-swap")) {
      const swap = document.createElement("span");
      swap.className = "dui-swap";
      const idle = document.createElement("span");
      idle.className = "dui-swap__idle";
      idle.append(...btn.childNodes);
      const fin = document.createElement("span");
      fin.className = "dui-swap__done";
      fin.setAttribute("aria-hidden", "true");
      swap.append(idle, fin);
      btn.append(swap);
    }
    btn.querySelector(".dui-swap__done").innerHTML = `${tick}<span>${words.replace(/</g, "&lt;")}</span>`;
    btn.classList.add("is-done");
    announce(words);
    clearTimeout(btn._duiDone);
    btn._duiDone = setTimeout(() => btn.classList.remove("is-done"), hold);
  }

  // A new row opens its own room, so the rows below move with it; its words
  // come in once the room is mostly open.
  function add(list, item, { at = "start" } = {}) {
    at === "start" ? list.prepend(item) : list.append(item);
    if (still || !item.animate) return;
    const cs = getComputedStyle(item);
    const h = item.getBoundingClientRect().height;
    item.style.overflow = "hidden";
    item.animate([
      { height: "0px", paddingTop: "0px", paddingBottom: "0px", opacity: 0 },
      { opacity: 0, offset: 0.45 },
      { height: `${h}px`, paddingTop: cs.paddingTop, paddingBottom: cs.paddingBottom, opacity: 1 },
    ], { duration: 480, easing: "cubic-bezier(0.2, 0, 0, 1)" }).finished.then(() => { item.style.overflow = ""; });
  }

  // Leaving: the words fade first, then the room closes. Never an empty box.
  function remove(item) {
    if (still || !item.animate) { item.remove(); return; }
    const cs = getComputedStyle(item);
    const h = item.getBoundingClientRect().height;
    item.style.overflow = "hidden";
    item.animate([
      { height: `${h}px`, paddingTop: cs.paddingTop, paddingBottom: cs.paddingBottom, opacity: 1 },
      { opacity: 0, offset: 0.35 },
      { height: "0px", paddingTop: "0px", paddingBottom: "0px", opacity: 0 },
    ], { duration: 420, easing: "cubic-bezier(0.2, 0, 0, 1)" }).finished.then(() => item.remove());
  }

  // Remembering small choices (a closed notice, a cookie answer). Storage can
  // be blocked, so every read and write is allowed to fail quietly.
  const store = {
    get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch {} },
  };
  const dismissed = (key) => store.get("dui-dismissed:" + key);
  document.querySelectorAll("[data-dismiss-key]").forEach((el) => { if (dismissed(el.dataset.dismissKey) != null) el.hidden = true; });

  // Copy: the text of data-copy="#id" (or data-copy-text), then the button
  // confirms on itself. If the clipboard is blocked, the text gets selected.
  async function copy(btn) {
    const sel = btn.dataset.copy;
    let target = null;
    try { target = sel ? document.getElementById(sel.replace(/^#/, "")) || document.querySelector(sel) : null; } catch {}
    const text = btn.dataset.copyText ?? (target ? (target.matches("input, textarea") ? target.value : target.textContent) : "");
    try {
      await navigator.clipboard.writeText(text.trim());
      done(btn, btn.dataset.done || "Copied");
    } catch {
      if (target) target.matches("input, textarea") ? target.select() : getSelection().selectAllChildren(target);
      done(btn, "Selected");
    }
  }

  // Quantity: minus and plus step the number inside, within its min and max.
  function stepLimits(stepper) {
    const input = stepper.querySelector("input");
    if (!input) return;
    const v = parseFloat(input.value) || 0;
    stepper.querySelectorAll("[data-step]").forEach((b) => {
      const by = parseFloat(b.dataset.step);
      const stop = by < 0 ? input.min !== "" && v <= +input.min : input.max !== "" && v >= +input.max;
      b.setAttribute("aria-disabled", String(stop));
    });
  }
  function step(stepper, by) {
    const input = stepper.querySelector("input");
    const lo = input.min === "" ? -Infinity : +input.min, hi = input.max === "" ? Infinity : +input.max;
    const was = parseFloat(input.value) || 0;
    const next = Math.min(hi, Math.max(lo, was + by * (parseFloat(input.step) || 1)));
    if (next !== was) {
      input.value = next;
      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.dispatchEvent(new Event("change", { bubbles: true }));
      announce(String(next));
    }
    stepLimits(stepper);
  }
  document.querySelectorAll(".dui-stepper").forEach(stepLimits);
  document.addEventListener("change", (event) => {
    const stepper = event.target.closest && event.target.closest(".dui-stepper");
    if (!stepper || event.target.tagName !== "INPUT") return;
    const input = event.target, v = parseFloat(input.value);
    if (isNaN(v) || (input.min !== "" && v < +input.min)) input.value = input.min || 0;
    else if (input.max !== "" && v > +input.max) input.value = input.max;
    stepLimits(stepper);
  });

  // Dates: a month grid, Monday first. Drawn into <div class="dui-calendar"
  // data-calendar>, or opened under a .dui-date[data-datepicker] field.
  const lang = document.documentElement.lang || "en-ZA";
  const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
  const midnight = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; };
  const parseDate = (s) => {
    if (s === "today") return midnight();
    const m = /^(\d{4})-(\d\d)-(\d\d)$/.exec(s || "");
    return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
  };
  const chevron = (d) => `<svg class="dui-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="${d}"/></svg>`;
  function calendar(el, { value, min, max, closed = [], onPick } = {}) {
    const today = midnight(), lo = parseDate(min), hi = parseDate(max);
    let sel = parseDate(value);
    let focus = sel || (lo && lo > today ? lo : today);
    let shown = new Date(focus.getFullYear(), focus.getMonth(), 1);
    const blocked = (d) => (lo && d < lo) || (hi && d > hi) || closed.includes(d.getDay());
    const days = [...Array(7)].map((_, i) => new Date(2024, 0, 1 + i)); // 1 Jan 2024 was a Monday
    const draw = (moveFocus) => {
      const y = shown.getFullYear(), m = shown.getMonth();
      const lead = (new Date(y, m, 1).getDay() + 6) % 7, count = new Date(y, m + 1, 0).getDate();
      if (focus.getMonth() !== m || focus.getFullYear() !== y) focus = new Date(y, m, 1);
      let rows = "", row = "<td></td>".repeat(lead);
      for (let n = 1; n <= count; n++) {
        const d = new Date(y, m, n);
        const label = d.toLocaleDateString(lang, { weekday: "long", day: "numeric", month: "long" });
        row += `<td><button type="button" data-date="${iso(d)}" aria-label="${label}" aria-pressed="${!!sel && +d === +sel}"${+d === +today ? ' aria-current="date"' : ""}${blocked(d) ? " disabled" : ""} tabindex="-1">${n}</button></td>`;
        if ((lead + n) % 7 === 0) { rows += `<tr>${row}</tr>`; row = ""; }
      }
      if (row) rows += `<tr>${row}${"<td></td>".repeat(7 - ((lead + count) % 7))}</tr>`;
      const before = lo && new Date(y, m, 1) <= lo, after = hi && new Date(y, m + 1, 1) > hi;
      el.innerHTML = `<div class="dui-calendar__head">
<button class="dui-icon-btn" type="button" data-month="-1" aria-label="Previous month"${before ? " disabled" : ""}>${chevron("M15 18l-6-6 6-6")}</button>
<b aria-live="polite">${shown.toLocaleDateString(lang, { month: "long", year: "numeric" })}</b>
<button class="dui-icon-btn" type="button" data-month="1" aria-label="Next month"${after ? " disabled" : ""}>${chevron("M9 18l6-6-6-6")}</button></div>
<table role="presentation"><thead><tr>${days.map((d) => `<th scope="col" abbr="${d.toLocaleDateString(lang, { weekday: "long" })}">${d.toLocaleDateString(lang, { weekday: "narrow" })}</th>`).join("")}</tr></thead><tbody>${rows}</tbody></table>`;
      const target = el.querySelector(`[data-date="${iso(focus)}"]:not(:disabled)`) || el.querySelector("[data-date]:not(:disabled)");
      if (target) { target.tabIndex = 0; if (moveFocus) target.focus(); }
    };
    el.addEventListener("click", (e) => {
      const month = e.target.closest("[data-month]");
      if (month) { shown = new Date(shown.getFullYear(), shown.getMonth() + +month.dataset.month, 1); draw(false); el.querySelector(`[data-month="${month.dataset.month}"]:not(:disabled)`)?.focus(); return; }
      const day = e.target.closest("[data-date]");
      if (!day || day.disabled) return;
      sel = focus = parseDate(day.dataset.date);
      draw(true);
      announce(day.getAttribute("aria-label"));
      onPick && onPick(day.dataset.date);
    });
    el.addEventListener("keydown", (e) => {
      const day = e.target.closest("[data-date]");
      const by = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
      if (!day || (by == null && e.key !== "PageUp" && e.key !== "PageDown")) return;
      e.preventDefault();
      const d = parseDate(day.dataset.date);
      focus = by != null ? new Date(d.getFullYear(), d.getMonth(), d.getDate() + by) : new Date(d.getFullYear(), d.getMonth() + (e.key === "PageUp" ? -1 : 1), Math.min(d.getDate(), 28));
      shown = new Date(focus.getFullYear(), focus.getMonth(), 1);
      draw(true);
    });
    draw(false);
    return { set(v) { sel = parseDate(v); if (sel) { focus = sel; shown = new Date(sel.getFullYear(), sel.getMonth(), 1); } draw(false); } };
  }
  const closedDays = (s) => (s || "").split(/[\s,]+/).filter(Boolean).map(Number);
  document.querySelectorAll("[data-calendar]").forEach((el) => calendar(el, {
    value: el.dataset.value, min: el.dataset.min, max: el.dataset.max, closed: closedDays(el.dataset.closed),
    onPick: (date) => {
      el.dataset.value = date;
      const input = el.dataset.for && document.querySelector(el.dataset.for);
      if (input) { input.value = date; input.dispatchEvent(new Event("change", { bubbles: true })); }
      el.dispatchEvent(new CustomEvent("dui:pick", { bubbles: true, detail: { date } }));
    },
  }));

  // A date field that opens the month grid under itself.
  let openPicker = null;
  function closePicker(returnFocus) {
    if (!openPicker) return;
    const { panel, button } = openPicker;
    panel.remove();
    button.setAttribute("aria-expanded", "false");
    if (returnFocus) button.focus();
    openPicker = null;
  }
  function togglePicker(button) {
    const wrap = button.closest(".dui-date");
    if (openPicker && openPicker.button === button) { closePicker(true); return; }
    closePicker(false);
    const input = wrap.querySelector("input");
    const panel = document.createElement("div");
    panel.className = "dui-date__panel";
    panel.setAttribute("role", "dialog");
    panel.setAttribute("aria-label", "Choose a date");
    const grid = document.createElement("div");
    grid.className = "dui-calendar";
    panel.append(grid);
    wrap.append(panel);
    calendar(grid, {
      value: input.value, min: input.min || wrap.dataset.min, max: input.max || wrap.dataset.max, closed: closedDays(wrap.dataset.closed),
      onPick: (date) => {
        input.value = date;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
        closePicker(true);
      },
    });
    button.setAttribute("aria-expanded", "true");
    openPicker = { panel, button };
    (grid.querySelector('[data-date][tabindex="0"]') || grid.querySelector("button")).focus();
  }
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && openPicker) closePicker(true); });
  document.addEventListener("pointerdown", (e) => { if (openPicker && !openPicker.panel.parentElement.contains(e.target)) closePicker(false); });

  // Opening hours: mark today's row and say whether it's open now, by the
  // visitor's clock. <table class="dui-hours" data-hours="#now"> with rows
  // <tr data-day="1" data-open="09:00" data-close="17:00"> (0 is Sunday).
  function hours(table) {
    const now = new Date(), day = now.getDay(), mins = now.getHours() * 60 + now.getMinutes();
    const at = (s) => { const [h, m] = String(s).split(":").map(Number); return h * 60 + (m || 0); };
    const rows = [...table.querySelectorAll("tr[data-day]")];
    const row = (d) => rows.find((r) => +r.dataset.day === d);
    rows.forEach((r) => (+r.dataset.day === day ? r.setAttribute("aria-current", "date") : r.removeAttribute("aria-current")));
    const line = table.dataset.hours && document.querySelector(table.dataset.hours);
    if (!line) return;
    const t = row(day);
    if (t && t.dataset.open && mins >= at(t.dataset.open) && mins < at(t.dataset.close)) {
      line.innerHTML = `<span class="dui-badge-solid">Open now</span> <span>until ${t.dataset.close}</span>`;
      return;
    }
    for (let i = 0; i < 8; i++) {
      const r = row((day + i) % 7);
      if (r && r.dataset.open && (i > 0 || mins < at(r.dataset.open))) {
        const when = i === 0 ? "today" : i === 1 ? "tomorrow" : (r.querySelector("th")?.textContent || "").trim();
        line.innerHTML = `<span class="dui-badge">Closed now</span> <span>Opens ${when} at ${r.dataset.open}</span>`;
        return;
      }
    }
  }
  document.querySelectorAll("table[data-hours]").forEach(hours);

  window.distinctUI = Object.assign(window.distinctUI || {}, { roll, done, add, remove, copy, calendar, dismissed });

  document.addEventListener("click", (event) => {
    // Go live: dashed to solid, with a pop.
    const live = event.target.closest("[data-go-live]");
    if (live) {
      const mark = document.querySelector(live.dataset.goLive);
      if (mark) {
        const goingLive = mark.dataset.live === "false";
        mark.dataset.live = goingLive ? "true" : "false";
        mark.classList.remove("dui-live-now");
        if (goingLive) { void mark.offsetWidth; mark.classList.add("dui-live-now"); }
        if (live.dataset.liveLabel) {
          const other = live.dataset.liveLabel;
          live.dataset.liveLabel = live.textContent;
          live.textContent = other;
        }
      }
    }

    // Draw the rings again.
    const replay = event.target.closest("[data-replay]");
    if (replay) document.querySelectorAll(replay.dataset.replay + " .dui-mark").forEach((m, i) => {
      m.style.setProperty("--dui-i", i);
      m.classList.remove("dui-draw"); void m.offsetWidth; m.classList.add("dui-draw");
    });

    // Filter tabs.
    const tab = event.target.closest("[data-filter]");
    if (tab) {
      const scope = tab.closest("[data-filter-scope]") || document;
      scope.querySelectorAll("[data-filter]").forEach((t) => t.setAttribute("aria-selected", String(t === tab)));
      const want = tab.dataset.filter;
      let shown = 0;
      scope.querySelectorAll("[data-tags]").forEach((item) => {
        const show = want === "all" || item.dataset.tags.split(" ").includes(want);
        item.hidden = !show;
        item.classList.remove("is-shown");
        if (show) { shown++; void item.offsetWidth; item.classList.add("is-shown"); }
      });
      const empty = scope.querySelector("[data-filter-empty]");
      if (empty) empty.hidden = shown > 0;
    }

    // Toggle buttons (Follow / Following).
    const toggle = event.target.closest("button[aria-pressed]");
    if (toggle) {
      const on = toggle.getAttribute("aria-pressed") !== "true";
      toggle.setAttribute("aria-pressed", String(on));
      if (toggle.dataset.pressedLabel) {
        const other = toggle.dataset.pressedLabel;
        toggle.dataset.pressedLabel = toggle.textContent;
        toggle.textContent = other;
      }
      const counter = toggle.dataset.countTarget && document.querySelector(toggle.dataset.countTarget);
      if (counter) roll(counter, num(counter.dataset.rollValue ?? counter.textContent) + (on ? 1 : -1));
    }

    // Copy, then confirm on itself.
    const copier = event.target.closest("[data-copy], [data-copy-text]");
    if (copier) copy(copier);

    // Confirm on itself (Save → Saved).
    const confirm = event.target.closest("[data-done]");
    if (confirm && !confirm.matches("[type=submit], [data-copy], [data-copy-text]")) done(confirm);

    // Quantity steppers.
    const stepBtn = event.target.closest(".dui-stepper [data-step]");
    if (stepBtn && stepBtn.getAttribute("aria-disabled") !== "true") step(stepBtn.closest(".dui-stepper"), parseFloat(stepBtn.dataset.step));

    // A date field's calendar button.
    const opener = event.target.closest(".dui-date__open");
    if (opener) togglePicker(opener);

    // Close for good: an announcement line, or a cookie answer.
    const dismiss = event.target.closest("[data-dismiss]");
    if (dismiss) {
      const box = dismiss.closest("[data-dismiss-key]") || dismiss.closest(".dui-announce, .dui-cookies");
      if (box) {
        const key = box.dataset.dismissKey, value = dismiss.dataset.dismiss || "closed";
        if (key) store.set("dui-dismissed:" + key, value);
        document.dispatchEvent(new CustomEvent("dui:dismiss", { detail: { key, value } }));
        remove(box);
      }
    }
  });
})();
