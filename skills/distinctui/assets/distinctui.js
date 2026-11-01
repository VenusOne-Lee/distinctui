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
 *
 * And from your own code, for things only your app knows happened:
 *   distinctUI.roll(el, 128)       roll a number to a new value
 *   distinctUI.done(button, "Saved")  confirm on the button that was pressed
 *   distinctUI.add(list, item)     a new row opens its room, carrying the rows below
 *   distinctUI.remove(item)        a row fades, then its room closes
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

  window.distinctUI = Object.assign(window.distinctUI || {}, { roll, done, add, remove });

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

    // Confirm on itself (Copy → Copied).
    const confirm = event.target.closest("[data-done]");
    if (confirm && !confirm.matches("[type=submit]")) done(confirm);
  });
})();
