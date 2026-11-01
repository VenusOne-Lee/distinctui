// The "every element" demos on /moments: each one is a distinctui.js move
// (roll, done, add, remove, data-done, aria-pressed, data-go-live,
// data-filter) on a different distinctUI element. Invented data only.
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const ui = () => window.distinctUI;
  const rand = (n) => `R${n.toLocaleString("en-ZA").replace(/\s/g, ",")}`;
  const row = (html) => { const li = document.createElement("li"); li.className = "dui-row"; li.innerHTML = html; return li; };

  // Install command copy
  $$("[data-copy]").forEach((btn) => btn.addEventListener("click", () => {
    navigator.clipboard?.writeText(document.getElementById(btn.dataset.copy).textContent).catch(() => {});
  }));

  // Badges: the version rolls
  let minor = 3;
  $("#g-ship")?.addEventListener("click", () => { minor = minor >= 9 ? 3 : minor + 1; ui().roll($("#g-ver"), `v0.${minor}`); });

  // Inputs: the error leaves once the value is fixed, then Save confirms
  const site = $("#g-site");
  const check = () => {
    const ok = /^https:\/\/\S+\.\S+/.test(site.value.trim());
    site.setAttribute("aria-invalid", String(!ok));
    $("#g-site-e").classList.toggle("is-gone", ok);
    return ok;
  };
  site?.addEventListener("input", check);
  $("#g-form")?.addEventListener("submit", (e) => {
    e.preventDefault();
    if (check()) ui().done(e.submitter || $("#g-form button"), "Saved");
    else { $("#g-site-e").classList.remove("is-gone"); site.focus(); }
  });

  // Switch: a notice opens under it and stays; off takes it away
  $("#g-switch")?.addEventListener("change", (e) => {
    const box = $("#g-notify");
    const old = $(".dui-status", box);
    if (e.target.checked && !old) {
      const p = document.createElement("p");
      p.className = "dui-status";
      p.setAttribute("role", "status");
      p.textContent = "Saved. The first one goes out on Monday.";
      ui().add(box, p, { at: "end" });
    } else if (!e.target.checked && old) ui().remove(old);
  });

  // Lists: add and remove rows
  const names = ["Budget", "Launch plan", "Survey answers", "Photos", "Pitch deck"];
  let ni = 0;
  $("#g-add")?.addEventListener("click", () => {
    ui().add($("#g-list"), row(`<b>${names[ni++ % names.length]}</b><button class="dui-btn-sm-ghost g-rm" type="button" style="margin-left:auto">Remove</button>`));
  });
  $("#g-list")?.addEventListener("click", (e) => { const b = e.target.closest(".g-rm"); if (b) ui().remove(b.closest("li")); });

  // Tables: quantity, line and total roll together
  let qty = 2;
  $$("[data-step]").forEach((b) => b.addEventListener("click", () => {
    qty = Math.max(1, Math.min(9, qty + Number(b.dataset.step)));
    ui().roll($("#g-n"), qty);
    ui().roll($("#g-line"), rand(qty * 320));
    ui().roll($("#g-total"), rand(qty * 320 + 60));
  }));

  // Steps and progress
  const still = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const steps = () => $$("#g-steps li");
  let at = 1;
  const fill = (to) => {
    const bar = $("#g-prog"), from = Number(bar.value);
    ui().roll($("#g-pct"), `${to}%`);
    if (still) { bar.value = to; return; }
    const t0 = performance.now();
    const tick = (now) => {
      const t = Math.min(1, (now - t0) / 480), e = 1 - Math.pow(1 - t, 3);
      bar.value = from + (to - from) * e;
      if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  $("#g-next")?.addEventListener("click", (e) => {
    at = at >= 3 ? 1 : at + 1;
    steps().forEach((li, i) => {
      li.toggleAttribute("aria-current", false);
      if (i < at || at === 3) li.dataset.done = "true"; else delete li.dataset.done;
      if (i === at && at < 3) { li.setAttribute("aria-current", "step"); delete li.dataset.done; }
    });
    if (at === 1) { steps()[0].dataset.done = "true"; steps()[1].setAttribute("aria-current", "step"); }
    fill(at === 3 ? 100 : at === 2 ? 66 : 33);
    e.target.textContent = at === 3 ? "Start again" : "Continue";
    if (at === 3) ui().done(e.target, "All done");
  });

  // Dialog: ask first, then the row closes up and the count rolls
  const dlg = $("#g-dlg");
  let pending;
  $("#g-filelist")?.addEventListener("click", (e) => {
    const b = e.target.closest("[data-ask]");
    if (!b || !dlg?.showModal) return;
    pending = b.closest("li");
    $("#g-dlg-name").textContent = b.dataset.ask;
    dlg.showModal();
  });
  dlg?.addEventListener("close", () => {
    if (dlg.returnValue === "remove" && pending) {
      ui().remove(pending);
      ui().roll($("#g-files"), Number($("#g-files").dataset.rollValue ?? $("#g-files").textContent) - 1);
    }
    pending = null;
  });

  // Count on a menu: Done closes the row and the count rolls down
  $("#g-inbox")?.addEventListener("click", (e) => {
    const b = e.target.closest(".g-done");
    if (!b) return;
    ui().remove(b.closest("li"));
    const c = $("#g-count"), left = Number(c.dataset.rollValue ?? c.textContent) - 1;
    ui().roll(c, left);
    if (left === 0) setTimeout(() => c.classList.add("is-gone"), 500);
  });

  // Chips: the filter leaves, the results roll up
  let results = 8;
  $("#g-chips")?.addEventListener("click", (e) => {
    const chip = e.target.closest(".dui-chip");
    if (!chip) return;
    results = results === 8 ? 23 : 61;
    ui().remove(chip);
    ui().roll($("#g-results"), results);
  });
})();
