// distinctUI composer: turns a page spec into a finished distinctUI page.
// Plain ES module, no dependencies. Runs in a browser, a Worker or Node.
//
//   import { composePage, composeSection } from "./compose.js";
//   composePage(spec)              -> a full HTML page that links distinctui.css from distinctui.com
//   composeSection(section, spec)  -> one section's HTML
//
// The spec format is in references/page-spec.md. Sections take content and a layout, never styling,
// so the result follows the rules by construction. Run checkSpec() first and checkHTML() after.
// MIT · https://distinctui.com

const PALETTES = {
  Signal: ["#1f3bff", "#ff4f1f", "#7a2cff", "#e8a800", "#000000"],
  Mzansi: ["#e03c31", "#001489", "#ffb81c", "#007749", "#000000"],
  Earth: ["#b5452b", "#c8860a", "#6b3e26", "#2e3a87", "#000000"],
  Coast: ["#0067a5", "#ff5a4e", "#00706b", "#d9a400", "#000000"],
  Jacaranda: ["#6f3fc7", "#c2185b", "#2f80ed", "#f26b1d", "#000000"],
};
const STOP = new Set("a an and the of to for in on at by with your our you we it is are be or from as that this".split(" "));
export const LAYOUTS = {
  hero: ["mark-left", "centred"],
  proof: ["faces"],
  features: ["cards", "rows"],
  steps: ["rows"],
  stats: ["row"],
  pricing: ["cards", "one"],
  questions: ["list"],
  cta: ["centred", "split"],
  form: ["contact", "booking", "waitlist"],
  products: ["grid"],
  posts: ["cards", "featured"],
};

const esc = (s) => String(s ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
const slug = (s) => String(s || "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") || "x";

// The word that carries the meaning: the one given, or the longest word that isn't filler.
export function pickBold(heading, bold) {
  if (bold && heading.includes(bold)) return bold;
  const words = String(heading).replace(/[^\p{L}\p{N}\s'-]/gu, "").split(/\s+/).filter((w) => w && !STOP.has(w.toLowerCase()));
  return words.sort((a, b) => b.length - a.length)[0] || "";
}
function heading(text, bold, tag = "h2", cls = "dui-h2", id) {
  const t = String(text || "");
  const b = pickBold(t, bold);
  let html = esc(t);
  if (b) { const e = esc(b); const k = html.indexOf(e); if (k >= 0) html = html.slice(0, k) + "<b>" + e + "</b>" + html.slice(k + e.length); }
  return `<${tag}${id ? ` id="${id}"` : ""} class="${cls}">${html}</${tag}>`;
}
function initials(name) {
  return String(name || "").split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join("") || "+";
}
function mark(spec, { size = "4rem", inner, colour, live } = {}) {
  const isLive = live ?? spec.live ?? false;
  const c = colour ? `;--mark-colour:${esc(colour)}` : "";
  const v = isLive && spec.version ? `<span class="dui-mark__version">${esc(spec.version)}</span>` : "";
  return `<span class="dui-mark"${isLive ? "" : ' data-live="false"'} style="--mark-size:${size}${c}"><span class="dui-mark__inner">${esc(inner ?? spec.signature?.initials ?? initials(spec.name))}</span>${v}</span>`;
}
function actions(s, small = false, centre = false) {
  const p = s.primary, q = s.secondary;
  if (!p && !q) return "";
  const big = small ? "dui-btn-sm" : "dui-btn";
  const out = [];
  if (p) out.push(`<a class="${big}" href="${esc(p.href || "#")}">${esc(p.label || "Get started")}</a>`);
  if (q) out.push(`<a class="${big}-ghost" href="${esc(q.href || "#")}">${esc(q.label)}</a>`);
  return `<div class="dui-actions"${centre ? ' style="justify-content:center"' : ""}>${out.join("")}</div>`;
}
const eyebrow = (s) => (s.eyebrow ? `<span class="dui-eyebrow">${esc(s.eyebrow)}</span>` : "");
const lede = (s) => (s.lede ? `<p class="dui-lede">${esc(s.lede)}</p>` : "");

const SECTIONS = {
  hero(s, spec, i) {
    const id = `s${i}-h`;
    const h = heading(s.heading || spec.name, s.bold, "h1", "dui-hero", id);
    if (s.layout === "centred") {
      return `<section class="dui-section dui-c-hero dui-c-centre" aria-labelledby="${id}">${mark(spec, { size: "5rem" })}${eyebrow(s)}${h}${lede(s)}${actions(s, false, true)}</section>`;
    }
    return `<section class="dui-section dui-c-hero dui-c-split" aria-labelledby="${id}">${mark(spec, { size: "7rem" })}<div class="dui-c-stack">${eyebrow(s)}${h}${lede(s)}${actions(s)}</div></section>`;
  },
  proof(s, spec) {
    const pal = PALETTES[spec.signature?.palette] || null;
    const faces = (s.faces || s.items || []).slice(0, 5).map((f, k) => {
      const name = typeof f === "string" ? f : f.name || f.initials || "";
      return `<span class="dui-mark"${pal ? ` data-ring="${(k % 5) + 1}"` : ""} style="--mark-size:2.5rem"><span class="dui-mark__inner">${esc(typeof f === "object" && f.initials ? f.initials : initials(name))}</span></span>`;
    }).join("");
    const more = s.more ? `<span class="dui-stack__more">${esc(s.more)}</span>` : "";
    return `<section class="dui-section dui-c-proof"><div class="dui-stack">${faces}${more}</div>${s.text ? `<p class="dui-body">${esc(s.text)}</p>` : ""}</section>`;
  },
  features(s, spec, i) {
    const id = `s${i}-h`;
    const items = (s.items || []).slice(0, 6);
    const body = s.layout === "rows"
      ? `<ul class="dui-rows">${items.map((it) => `<li class="dui-row"><div><h3 class="dui-h4">${esc(it.title)}</h3><p class="dui-body">${esc(it.text)}</p></div></li>`).join("")}</ul>`
      : `<div class="dui-grid dui-grid--3">${items.map((it) => `<article class="dui-card"><h3 class="dui-card__title">${esc(it.title)}</h3><p class="dui-card__text">${esc(it.text)}</p></article>`).join("")}</div>`;
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "What it does", s.bold, "h2", "dui-h2", id)}${lede(s)}${body}</section>`;
  },
  steps(s, spec, i) {
    const id = `s${i}-h`;
    const items = (s.items || []).slice(0, 5);
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "How it works", s.bold, "h2", "dui-h2", id)}<ol class="dui-rows dui-c-steps">${items.map((it, k) => `<li class="dui-row"><span class="dui-c-n">${k + 1}</span><div><h3 class="dui-h4">${esc(it.title)}</h3><p class="dui-body">${esc(it.text)}</p></div></li>`).join("")}</ol></section>`;
  },
  stats(s, spec, i) {
    const items = (s.items || []).slice(0, 4);
    return `<section class="dui-section">${s.heading ? heading(s.heading, s.bold, "h2", "dui-h2", `s${i}-h`) : ""}<dl class="dui-grid dui-grid--4 dui-c-stats">${items.map((it) => `<div><dt class="dui-meta">${esc(it.label)}</dt><dd>${esc(it.value)}</dd></div>`).join("")}</dl></section>`;
  },
  pricing(s, spec, i) {
    const id = `s${i}-h`;
    const items = (s.items || []).slice(0, 3);
    let featured = items.findIndex((p) => p.featured);
    if (featured < 0) featured = items.length === 3 ? 1 : 0;
    const card = (p, k) => {
      const top = k === featured;
      const list = (p.features || []).length ? `<ul class="dui-c-list">${p.features.map((f) => `<li>${esc(f)}</li>`).join("")}</ul>` : "";
      const label = p.cta || `Choose ${p.name}`;
      return `<article class="${top && items.length > 1 ? "dui-feature" : "dui-card"} dui-c-plan"><h3 class="dui-card__title">${esc(p.name)}</h3><p class="dui-c-price">${esc(p.price)}${p.period ? ` <span class="dui-meta">${esc(p.period)}</span>` : ""}</p>${p.text ? `<p class="dui-card__text">${esc(p.text)}</p>` : ""}${list}<div class="dui-card__foot"><a class="${top ? "dui-btn-sm" : "dui-btn-sm-ghost"}" href="${esc(p.href || "#")}">${esc(label)}</a></div></article>`;
    };
    const body = s.layout === "one" && items.length
      ? `<div class="dui-c-one">${card(items[featured], featured)}</div>`
      : `<div class="dui-grid dui-grid--3">${items.map(card).join("")}</div>`;
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "Simple prices", s.bold, "h2", "dui-h2", id)}${lede(s)}${body}</section>`;
  },
  questions(s, spec, i) {
    const id = `s${i}-h`;
    const items = s.items || [];
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "Questions people ask", s.bold, "h2", "dui-h2", id)}<div class="dui-accordion">${items.map((q, k) => `<details${k === 0 ? " open" : ""}><summary>${esc(q.q)}</summary><p>${esc(q.a)}</p></details>`).join("")}</div></section>`;
  },
  cta(s, spec, i) {
    const id = `s${i}-h`;
    const h = heading(s.heading || `Try ${spec.name || "it"} today`, s.bold, "h2", "dui-h2", id);
    if (s.layout === "split") return `<section class="dui-section dui-c-split2" aria-labelledby="${id}"><div class="dui-c-stack">${h}${lede(s)}</div>${actions(s)}</section>`;
    return `<section class="dui-section dui-c-centre" aria-labelledby="${id}">${h}${lede(s)}${actions(s, false, true)}</section>`;
  },
  form(s, spec, i) {
    const id = `s${i}-h`;
    const defaults = {
      contact: [{ label: "Your name", name: "name" }, { label: "Email", name: "email", type: "email" }, { label: "Message", name: "message", type: "textarea" }],
      booking: [{ label: "Your name", name: "name" }, { label: "Mobile number", name: "mobile", type: "tel" }, { label: "When suits you", name: "when", optional: true }],
      waitlist: [{ label: "Email", name: "email", type: "email" }],
    };
    const layout = s.layout || "contact";
    const fields = (s.fields && s.fields.length ? s.fields : defaults[layout] || defaults.contact).map((f, k) => {
      const fid = `f${i}-${slug(f.name || f.label || k)}`;
      const opt = f.optional ? ' <span class="dui-optional">(optional)</span>' : "";
      const hint = f.hint ? ` <span class="dui-hint">${esc(f.hint)}</span>` : "";
      const req = f.optional ? "" : " required";
      const control = f.type === "textarea"
        ? `<textarea class="dui-textarea" id="${fid}" name="${esc(f.name || fid)}"${req}></textarea>`
        : f.type === "select"
          ? `<select class="dui-select" id="${fid}" name="${esc(f.name || fid)}"${req}>${(f.options || []).map((o) => `<option>${esc(o)}</option>`).join("")}</select>`
          : `<input class="dui-input" id="${fid}" name="${esc(f.name || fid)}" type="${esc(f.type || "text")}"${f.type === "tel" ? ' inputmode="tel" autocomplete="tel"' : f.type === "email" ? ' autocomplete="email"' : ""}${req}>`;
      return `<label class="dui-label" for="${fid}">${esc(f.label)}${opt}${hint}${control}</label>`;
    }).join("");
    const submit = s.submit || { contact: "Send the message", booking: "Ask for a slot", waitlist: "Join the waitlist" }[layout] || "Send";
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "Get in touch", s.bold, "h2", "dui-h2", id)}${lede(s)}<form class="dui-c-form" action="${esc(s.action || "#")}" method="post" novalidate>${fields}<button class="dui-btn" type="submit">${esc(submit)}</button></form></section>`;
  },
  products(s, spec, i) {
    const id = `s${i}-h`;
    const items = (s.items || []).slice(0, 8);
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "Shop", s.bold, "h2", "dui-h2", id)}<div class="dui-grid dui-grid--3">${items.map((p) => `<article class="dui-c-product"><div class="dui-c-photo">${p.image ? `<img src="${esc(p.image)}" alt="${esc(p.alt ?? p.name)}" loading="lazy">` : ""}</div><div class="dui-c-line"><h3 class="dui-h4">${esc(p.name)}</h3><span class="dui-c-num">${esc(p.price)}</span></div><a class="dui-btn-sm-ghost" href="${esc(p.href || "#")}">${esc(p.cta || "Add to bag")}</a></article>`).join("")}</div></section>`;
  },
  posts(s, spec, i) {
    const id = `s${i}-h`;
    const items = s.layout === "featured" ? (s.items || []).slice(0, 1) : (s.items || []).slice(0, 3);
    return `<section class="dui-section" aria-labelledby="${id}">${eyebrow(s)}${heading(s.heading || "Latest", s.bold, "h2", "dui-h2", id)}<div class="dui-grid${s.layout === "featured" ? "" : " dui-grid--3"}">${items.map((p) => `<article class="dui-c-post"><p class="dui-meta">${esc(p.date || "")}</p><h3 class="${s.layout === "featured" ? "dui-h3" : "dui-h4"}"><a href="${esc(p.href || "#")}">${esc(p.title)}</a></h3>${p.text ? `<p class="dui-body">${esc(p.text)}</p>` : ""}</article>`).join("")}</div></section>`;
  },
};

export function composeSection(section, spec = {}, index = 0) {
  const fn = SECTIONS[section?.type];
  if (!fn) throw new Error(`Unknown section type "${section?.type}". Use one of: ${Object.keys(SECTIONS).join(", ")}.`);
  return fn(section, spec, index);
}

// Layout glue for composed pages, built only from distinctUI tokens.
const GLUE = `
.dui-c-hero{padding-top:var(--dui-space-14)}
.dui-c-split{display:grid;gap:var(--dui-space-10);align-items:center}
@media (min-width:768px){.dui-c-split{grid-template-columns:auto minmax(0,1fr);gap:var(--dui-space-14)}}
.dui-c-centre{display:flex;flex-direction:column;align-items:center;gap:var(--dui-space-6);text-align:center}
.dui-c-centre .dui-lede{max-width:34em}
.dui-c-stack{display:flex;flex-direction:column;gap:var(--dui-space-5);min-width:0}
.dui-c-proof{display:flex;flex-wrap:wrap;align-items:center;gap:var(--dui-space-4)}
.dui-c-split2{display:grid;gap:var(--dui-space-6);align-items:end}
@media (min-width:768px){.dui-c-split2{grid-template-columns:minmax(0,1fr) auto}}
.dui-c-steps .dui-row{align-items:flex-start}
.dui-c-n{font-size:var(--dui-text-3xl);font-weight:300;letter-spacing:var(--dui-tracking-tighter);line-height:1;width:3rem;flex-shrink:0;font-variant-numeric:tabular-nums}
.dui-c-stats dd{margin:0;font-size:var(--dui-text-4xl);font-weight:300;letter-spacing:var(--dui-tracking-tighter);font-variant-numeric:tabular-nums}
.dui-c-price{margin:0;font-size:var(--dui-text-4xl);font-weight:300;letter-spacing:var(--dui-tracking-tighter);font-variant-numeric:tabular-nums}
.dui-c-list{margin:0;padding-left:1.1em;color:var(--dui-grey-800);display:flex;flex-direction:column;gap:var(--dui-space-1)}
.dui-c-plan{display:flex;flex-direction:column;gap:var(--dui-space-3)}
.dui-c-plan .dui-card__foot{margin-top:auto;padding-top:var(--dui-space-4)}
.dui-c-one{max-width:36rem}
.dui-c-form{max-width:36rem;display:flex;flex-direction:column;gap:var(--dui-space-5);align-items:flex-start}
.dui-c-form .dui-label{align-self:stretch}
.dui-c-product{display:flex;flex-direction:column;gap:var(--dui-space-3)}
.dui-c-photo{aspect-ratio:1;background:var(--dui-grey-100);border-radius:var(--dui-radius-card);overflow:hidden}
.dui-c-photo img{width:100%;height:100%;object-fit:cover}
.dui-c-line{display:flex;justify-content:space-between;gap:var(--dui-space-3);align-items:baseline}
.dui-c-num{font-variant-numeric:tabular-nums}
.dui-c-post{display:flex;flex-direction:column;gap:var(--dui-space-2);border-top:1px solid var(--dui-grey-200);padding-top:var(--dui-space-4)}
.dui-c-post a{color:inherit;text-decoration:none}
.dui-c-post a:hover{text-decoration:underline}
.dui-compact .dui-section{padding-block:var(--dui-space-14)}
`;

export function composePage(spec = {}) {
  const name = spec.name || "Your product";
  const sig = spec.signature || {};
  const colour = sig.colour || (PALETTES[sig.palette] || PALETTES.Signal)[0];
  const pal = sig.palette && PALETTES[sig.palette] ? sig.palette.toLowerCase() : "";
  const base = (spec.assets || "https://distinctui.com/assets").replace(/\/$/, "");
  const sections = (spec.sections || []).map((s, i) => composeSection(s, spec, i)).join("\n");
  const nav = (spec.nav || []).map((n) => `<a href="${esc(n.href || "#")}"${n.current ? ' aria-current="page"' : ""}>${esc(n.label)}</a>`).join("");
  const head = spec.header === false ? "" : `<header class="dui-container dui-header">
  <a class="dui-logo" href="/">${esc(name.toLowerCase())}</a>
  <nav class="dui-nav" aria-label="Main">${nav ? `<span class="dui-nav__links">${nav}</span>` : ""}${spec.headerAction ? `<a class="dui-btn-sm" href="${esc(spec.headerAction.href || "#")}">${esc(spec.headerAction.label)}</a>` : ""}</nav>
</header>`;
  const foot = spec.footer === false ? "" : `<footer class="dui-container dui-footer">
  <span><b>${esc(name.toLowerCase())}</b>${spec.domain ? " " + esc(spec.domain) : ""}</span>
  ${spec.tagline ? `<span>${esc(spec.tagline)}</span>` : ""}
</footer>`;
  const desc = spec.description || (spec.sections || []).find((s) => s.lede)?.lede || "";
  return `<!doctype html>
<html lang="${esc(spec.lang || "en")}"${pal ? ` class="dui-palette-${pal}"` : ""}>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>${esc(spec.title || name)}</title>
${desc ? `<meta name="description" content="${esc(desc)}">\n` : ""}<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap">
<link rel="stylesheet" href="${base}/distinctui.css">
<style>
:root { --dui-signature: ${esc(colour)}; }${GLUE}</style>
<script src="${base}/distinctui.js" defer></script>
</head>
<body class="dui-page${spec.density === "compact" ? " dui-compact" : ""}">
<a class="dui-skip" href="#main">Skip to content</a>
${head}
<main id="main" class="dui-container" style="padding-bottom:96px">
${sections}
</main>
${foot}
</body>
</html>
`;
}
