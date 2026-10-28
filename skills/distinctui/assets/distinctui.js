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
 *                            label to data-pressed-label
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
    }
  });
})();
