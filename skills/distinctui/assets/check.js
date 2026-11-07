// distinctUI checker: tests a page against the 12 rules.
// Plain ES module, no dependencies. Runs in a browser, a Worker or Node.
//
//   import { checkHTML, checkSpec } from "./check.js";
//   checkHTML(html)  -> rendered checks on any HTML (colours, dark, fonts, shadows, headings, pills, alt text...)
//   checkSpec(spec)  -> structural checks on a page spec (see references/page-spec.md)
//
// Both return { ok, errors, warnings, findings: [{ id, rule, title, severity, message, fix, where }] }.
// rule is 1 to 12, "access", "work" or "left-out". ok is true when there are no errors.
// MIT · https://distinctui.com

const GREYS = ["#000000", "#ffffff", "#fafafa", "#f2f2f2", "#e5e5e5", "#a3a3a3", "#6b6b6b", "#262626"];
const PALETTES = {
  Signal: ["#1f3bff", "#ff4f1f", "#7a2cff", "#e8a800", "#000000"],
  Mzansi: ["#e03c31", "#001489", "#ffb81c", "#007749", "#000000"],
  Earth: ["#b5452b", "#c8860a", "#6b3e26", "#2e3a87", "#000000"],
  Coast: ["#0067a5", "#ff5a4e", "#00706b", "#d9a400", "#000000"],
  Jacaranda: ["#6f3fc7", "#c2185b", "#2f80ed", "#f26b1d", "#000000"],
};
const TITLES = {
  1: "Light only", 2: "Black, white and four greys", 3: "Colour lives only on rings", 4: "One typeface",
  5: "Headings are light with one bold word", 6: "Main actions are black pills",
  7: "White space before borders, borders before shadows", 8: "Pills for what you press, soft corners for what you read",
  9: "Dashed until live", 10: "Errors are black and bold", 11: "Hover is a black edge, not a lift", 12: "Write plainly",
  access: "Everyone can use it", "left-out": "Left out on purpose", work: "distinctUI@work",
};
const NAMED = {
  red: "#ff0000", green: "#008000", blue: "#0000ff", yellow: "#ffff00", orange: "#ffa500", purple: "#800080",
  pink: "#ffc0cb", teal: "#008080", navy: "#000080", lime: "#00ff00", cyan: "#00ffff", magenta: "#ff00ff",
  gold: "#ffd700", crimson: "#dc143c", tomato: "#ff6347", coral: "#ff7f50", indigo: "#4b0082", violet: "#ee82ee",
  maroon: "#800000", olive: "#808000", aqua: "#00ffff", fuchsia: "#ff00ff", salmon: "#fa8072", royalblue: "#4169e1",
  dodgerblue: "#1e90ff", tan: "#d2b48c", brown: "#a52a2a", silver: "#c0c0c0", gray: "#808080", grey: "#808080",
  black: "#000000", white: "#ffffff",
};
const TW_HUES = "red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose";
const RADII = new Set([0, 12, 14, 16, 22, 24, 28]);
const VOID = new Set(["area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr"]);
const MARKISH = /(^|[\s.#\-_])(dui-mark|mark|ring|logo|dot|signature|swatch|avatar|face)/i;
const FLOATING = /(menu|dialog|popover|dropdown|panel__float|tooltip|dui-menu)/i;

// ---------- small helpers ----------
function norm(hex) {
  let h = hex.toLowerCase().replace("#", "");
  if (h.length === 3 || h.length === 4) h = h.slice(0, 3).split("").map((c) => c + c).join("");
  if (h.length === 8) h = h.slice(0, 6);
  return "#" + h;
}
function rgbToHex(r, g, b) {
  return "#" + [r, g, b].map((v) => Math.max(0, Math.min(255, Math.round(+v))).toString(16).padStart(2, "0")).join("");
}
function lum(hex) {
  const n = parseInt(norm(hex).slice(1), 16);
  const c = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; });
  return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
}
function isNeutral(hex) {
  const n = parseInt(norm(hex).slice(1), 16);
  const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
  return Math.max(r, g, b) - Math.min(r, g, b) <= 6;
}
// Every colour written in a CSS value, as { hex, alpha, raw }.
function coloursIn(value) {
  const out = [];
  const v = value.replace(/url\([^)]*\)/gi, "");
  for (const m of v.matchAll(/#([0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{3,4})\b/gi)) {
    const a = m[1].length === 8 ? parseInt(m[1].slice(6), 16) / 255 : m[1].length === 4 ? parseInt(m[1][3] + m[1][3], 16) / 255 : 1;
    out.push({ hex: norm(m[0]), alpha: a, raw: m[0] });
  }
  for (const m of v.matchAll(/rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)(?:[\s,/]+([\d.]+%?))?\s*\)/gi)) {
    let a = m[4] == null ? 1 : m[4].endsWith("%") ? parseFloat(m[4]) / 100 : parseFloat(m[4]);
    out.push({ hex: rgbToHex(m[1], m[2], m[3]), alpha: a, raw: m[0] });
  }
  for (const m of v.matchAll(/hsla?\(\s*([\d.]+)(?:deg)?[\s,]+([\d.]+)%[\s,]+([\d.]+)%(?:[\s,/]+([\d.]+%?))?\s*\)/gi)) {
    const h = +m[1] / 360, s = +m[2] / 100, l = +m[3] / 100;
    const f = (n) => { const k = (n + h * 12) % 12; return l - s * Math.min(l, 1 - l) * Math.max(-1, Math.min(k - 3, 9 - k, 1)); };
    let a = m[4] == null ? 1 : m[4].endsWith("%") ? parseFloat(m[4]) / 100 : parseFloat(m[4]);
    out.push({ hex: rgbToHex(f(0) * 255, f(8) * 255, f(4) * 255), alpha: a, raw: m[0] });
  }
  for (const m of v.matchAll(/(?:^|[\s,:(])([a-z]+)(?=$|[\s,;)!])/gi)) {
    const n = m[1].toLowerCase();
    if (NAMED[n] && n !== "black" && n !== "white") out.push({ hex: NAMED[n], alpha: 1, raw: m[1] });
  }
  return out;
}
function snippet(s, n = 90) { s = String(s).replace(/\s+/g, " ").trim(); return s.length > n ? s.slice(0, n - 1) + "…" : s; }

// ---------- a forgiving HTML reader ----------
function parseAttrs(s) {
  const a = {};
  for (const m of s.matchAll(/([^\s=/"'>]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'>]+)))?/g)) a[m[1].toLowerCase()] = m[2] ?? m[3] ?? m[4] ?? "";
  return a;
}
export function parseHTML(html) {
  const root = { tag: "#root", attrs: {}, children: [], parent: null, text: "" };
  const styles = [];
  let cur = root, i = 0;
  const re = /<!--[\s\S]*?-->|<(script|style)\b([^>]*)>([\s\S]*?)<\/\1\s*>|<\/([a-zA-Z][\w-]*)\s*>|<([a-zA-Z][\w-]*)((?:[^>"']|"[^"]*"|'[^']*')*)>|<!doctype[^>]*>/gi;
  let m;
  while ((m = re.exec(html))) {
    if (m.index > i) { const t = html.slice(i, m.index); cur.children.push({ tag: "#text", text: t, parent: cur }); }
    i = re.lastIndex;
    if (m[1]) {
      const tag = m[1].toLowerCase();
      const node = { tag, attrs: parseAttrs(m[2] || ""), children: [], parent: cur, text: m[3] };
      cur.children.push(node);
      if (tag === "style") styles.push(m[3]);
    } else if (m[4]) {
      const tag = m[4].toLowerCase();
      let n = cur;
      while (n && n.tag !== tag) n = n.parent;
      if (n && n.parent) cur = n.parent;
    } else if (m[5]) {
      const tag = m[5].toLowerCase();
      const raw = m[6] || "";
      const node = { tag, attrs: parseAttrs(raw.replace(/\/\s*$/, "")), children: [], parent: cur };
      cur.children.push(node);
      if (!VOID.has(tag) && !/\/\s*$/.test(raw)) cur = node;
    }
  }
  if (i < html.length) cur.children.push({ tag: "#text", text: html.slice(i), parent: cur });
  return { root, styles };
}
function walk(node, fn) { for (const c of node.children || []) { fn(c); walk(c, fn); } }
function textOf(node) {
  if (node.tag === "#text") return node.text.replace(/&nbsp;/g, " ").replace(/&amp;/g, "&").replace(/&#39;|&rsquo;/g, "'");
  if (node.tag === "script" || node.tag === "style" || node.tag === "title") return "";
  return (node.children || []).map(textOf).join("");
}
function classes(node) { return (node.attrs?.class || "").split(/\s+/).filter(Boolean); }
function ancestorMatches(node, test) { for (let n = node; n; n = n.parent) if (n.attrs && test(n)) return true; return false; }
// A mark: a dui-mark (or ring, logo, dot) class, a --mark-colour, or a round thing drawn with a border (emails can't use classes).
function inMark(node) { return ancestorMatches(node, (n) => MARKISH.test(n.attrs.class || "") || /--mark-colour|--dui-signature|border-radius:\s*(50%|9{3,}px)/.test(n.attrs.style || "")); }
function describe(node) {
  const c = classes(node).slice(0, 3).join(".");
  const t = snippet(textOf(node), 50);
  return `<${node.tag}${c ? " ." + c : ""}>${t ? " " + t : ""}`;
}

// ---------- CSS reading ----------
function cssRules(css) {
  const out = [];
  const src = css.replace(/\/\*[\s\S]*?\*\//g, "");
  for (const m of src.matchAll(/([^{}]*)\{([^{}]*)\}/g)) out.push({ selector: m[1].trim(), body: m[2] });
  return out;
}
function decls(body) {
  const out = [];
  for (const part of body.split(";")) {
    const k = part.indexOf(":");
    if (k < 0) continue;
    out.push({ prop: part.slice(0, k).trim().toLowerCase(), value: part.slice(k + 1).trim() });
  }
  return out;
}

// ---------- the checks ----------
function makeReport() {
  const findings = [];
  const seen = new Set();
  const add = (id, rule, severity, message, fix, where) => {
    const key = id + "|" + message + "|" + where;
    if (seen.has(key)) return;
    seen.add(key);
    findings.push({ id, rule, title: TITLES[rule], severity, message, fix, where: where ?? "" });
  };
  const done = () => {
    findings.sort((a, b) => (a.severity === b.severity ? 0 : a.severity === "error" ? -1 : 1));
    const errors = findings.filter((f) => f.severity === "error").length;
    return { ok: errors === 0, errors, warnings: findings.length - errors, findings };
  };
  return { add, done };
}

function allowedColour(c, markContext, extra) {
  if (c.alpha === 0) return true;
  if (GREYS.includes(c.hex)) return true;
  // Black or white at any opacity (veils, hairlines, menu shadows) and pure neutrals near the greys.
  if (c.hex === "#000000" || c.hex === "#ffffff") return true;
  if (markContext) return true;
  if (extra && extra.has(c.hex)) return true;
  return false;
}

function checkDecl(prop, value, ctx, add, where) {
  const markContext = ctx.mark || /^--(mark|dui-signature|dui-ring|ring|signature)/.test(prop);
  // Rule 1: dark
  if (prop === "color-scheme" && /dark/.test(value) && !/light/.test(value)) add("dark-scheme", 1, "error", "The page asks for a dark colour scheme.", "Set color-scheme: light. distinctUI is light only.", where);
  if (/^(background|background-color)$/.test(prop) && ctx.large) {
    for (const c of coloursIn(value)) if (c.alpha > 0.5 && lum(c.hex) < 0.2) add("dark-section", 1, "error", "A large block has a dark background.", "Use white or grey-50 for sections. Keep black for pills and small marks.", where);
  }
  // Rules 2, 3 and 10: colour outside the greys
  if (!prop.startsWith("--") || /^--(dui-)?(accent|brand|primary|link|error|danger|success|warning|info)/.test(prop)) {
    if (/(color|background|border|outline|fill|stroke|shadow|decoration|caret|accent|^--)/.test(prop)) {
      for (const c of coloursIn(value)) {
        if (allowedColour(c, markContext, ctx.extra)) continue;
        if (isNeutral(c.hex)) { add("off-grey", 2, "warning", `A grey that isn't one of the six: ${c.raw}.`, "Use grey 50, 100, 200, 400, 600 or 800.", where); continue; }
        const red = /^#(?:[c-f][0-9a-f])(?:[0-4][0-9a-f]){2}$/.test(c.hex);
        if (ctx.error || (red && /error|danger|invalid|alert/.test(where))) add("red-error", 10, "error", `An error is shown in colour (${c.raw}).`, "Errors are black and bold, with the small black ! disc.", where);
        else add("colour-off-ring", 3, "error", `Colour ${c.raw} is used outside the signature mark.`, "Move the colour onto the mark (--mark-colour or --dui-signature) and make this black, white or grey.", where);
      }
    }
  }
  if (/gradient\(/i.test(value) && !markContext) {
    const cols = coloursIn(value).filter((c) => !allowedColour(c, false, ctx.extra));
    if (cols.length) add("gradient", 2, "error", "A colour gradient.", "Use flat white or grey. Colour goes only on the mark.", where);
  }
  // Rule 4: one typeface
  if (prop === "font-family" || prop === "font") {
    const fam = prop === "font" ? value.replace(/^.*?(\d+(\.\d+)?(px|rem|em|%)(\/[\d.]+\w*)?\s+)/, "") : value;
    if (prop === "font-family" || /,|"|'/.test(fam)) {
      const first = fam.split(",")[0].trim().replace(/["']/g, "").toLowerCase();
      const ok = !first || /bricolage|inherit|initial|unset|var\(|^ui-monospace|monospace|menlo|consolas|sf mono|courier|^ui-sans-serif|system-ui|-apple-system|^sans-serif|^font|\d/.test(first);
      if (!ok) add("other-font", 4, "error", `Another typeface is used: ${snippet(first, 40)}.`, "Use Bricolage Grotesque everywhere. A monospace is fine for code.", where);
    }
  }
  // Rule 7: shadows
  if ((prop === "box-shadow" || (prop === "filter" && /drop-shadow/.test(value))) && !/^none$|^0\s+0\s+0/.test(value) && !/inset/.test(value) && !ctx.floating) {
    add("shadow", 7, ctx.hover ? "error" : "warning", "A shadow on something that doesn't float.", "Use white space, then a 1px grey-200 hairline. Shadows are only for floating menus.", where);
  }
  if (prop === "text-shadow" && value !== "none") add("text-shadow", 7, "error", "A text shadow.", "Remove it. Weight and size carry emphasis.", where);
  // Rule 8: corners
  if (prop === "border-radius" && !ctx.mark) {
    for (const m of value.matchAll(/(\d+(?:\.\d+)?)px/g)) {
      const n = Math.round(+m[1]);
      if (n > 8 && n < 99 && !RADII.has(n)) add("radius", 8, "warning", `A ${n}px corner.`, "Use 12 (thumbnails, notes), 14 (inputs), 16 (panels), 22 (cards), 24 (menus), 28 (feature) or a pill.", where);
    }
  }
  // Rule 11: hover lifts
  if (ctx.hover && /^(transform|translate|scale)$/.test(prop) && /scale|translate|lift|-\d/.test(value) && !/scale\(\s*0?\.9[5-9]/.test(value)) {
    add("hover-lift", 11, "error", "Something lifts or grows on hover.", "On hover, change the border to black or the opacity. Never scale, lift or glow.", where);
  }
  // Motion that loops
  if (/^animation(-iteration-count)?$/.test(prop) && /infinite/.test(value) && !ctx.loading) add("loop", 11, "warning", "An animation loops forever.", "Motion runs once to say what changed. Only loading shapes may pulse.", where);
}

function checkCSS(css, add, extra) {
  if (/prefers-color-scheme\s*:\s*dark/i.test(css)) add("dark-mode", 1, "error", "There's a dark mode block (prefers-color-scheme: dark).", "Remove it. distinctUI is light only.", "@media (prefers-color-scheme: dark)");
  for (const r of cssRules(css)) {
    const sel = r.selector;
    if (/^@/.test(sel) || /^(from|to|\d+%)/.test(sel)) continue;
    if (/\[data-theme=["']?dark|\.dark\b|\.theme-dark/.test(sel)) { add("dark-theme", 1, "error", "There's a dark theme selector.", "Remove it. distinctUI is light only.", snippet(sel, 60)); continue; }
    const ctx = {
      mark: MARKISH.test(sel),
      floating: FLOATING.test(sel),
      hover: /:hover/.test(sel),
      large: /(^|[\s,>])(body|html|main|section|header|footer|:root)\b|\.(hero|section|band|page|dui-section|dui-page|site)\b/.test(sel) && !/(btn|button|pill|badge|chip|mark|tag|kbd|code|count)/.test(sel),
      error: /error|invalid|danger|problem/.test(sel),
      loading: /skeleton|loading|pulse|progress/.test(sel),
      extra,
    };
    for (const d of decls(r.body)) checkDecl(d.prop, d.value, ctx, add, snippet(sel, 60));
  }
}

const TW = [
  [new RegExp(`(^|:)(bg|text|border|ring|from|via|to|fill|stroke|outline|decoration|accent|caret|divide|shadow)-(${TW_HUES})-\\d{2,3}`), "colour-off-ring", 3, "error", "A Tailwind colour class", "Use black, white or the greys (gray-50 to gray-800). Colour goes only on the mark."],
  [/^dark:/, "dark-mode", 1, "error", "A dark: Tailwind class", "Remove it. distinctUI is light only."],
  [/^(hover:|group-hover:)?shadow(-(sm|md|lg|xl|2xl|inner))?$/, "shadow", 7, "warning", "A shadow class", "Use white space, then a 1px grey-200 hairline. Shadows are only for floating menus."],
  [/^(hover|group-hover|focus):(scale-|-?translate-|shadow|drop-shadow)/, "hover-lift", 11, "error", "A hover that lifts or grows", "On hover, change the border to black. Never scale, lift or glow."],
  [/^bg-gradient-to-|^bg-linear-/, "gradient", 2, "error", "A gradient class", "Use flat white or grey."],
  [/^font-(serif)$/, "other-font", 4, "error", "A serif font class", "Use Bricolage Grotesque everywhere."],
  [/^animate-(spin|bounce|ping)$/, "loop", 11, "warning", "A looping animation class", "Motion runs once. Use loading shapes for waits."],
];

export function checkHTML(html, options = {}) {
  const { add, done } = makeReport();
  const extra = new Set((options.allowColours || []).map(norm));
  const { root, styles } = parseHTML(String(html || ""));
  for (const css of styles) checkCSS(css, add, extra);

  let h1Count = 0, featureCount = 0;
  walk(root, (n) => {
    if (n.tag === "#text" || n.tag === "script" || n.tag === "style") return;
    const cls = classes(n);
    const where = describe(n);
    const large = /^(body|html|main|section|header|footer|article|aside)$/.test(n.tag) || cls.some((c) => /(^|-)(hero|section|band|page|feature)$/.test(c));
    const mark = inMark(n);
    // Inline styles
    if (n.attrs.style) {
      for (const d of decls(n.attrs.style)) checkDecl(d.prop, d.value, { mark, large, floating: FLOATING.test(n.attrs.class || ""), error: cls.some((c) => /error|invalid|problem/.test(c)), extra }, add, where);
    }
    // SVG fills and strokes
    for (const a of ["fill", "stroke", "color", "bgcolor"]) {
      if (n.attrs[a]) for (const c of coloursIn(n.attrs[a])) if (!allowedColour(c, mark, extra) && !isNeutral(c.hex)) add("colour-off-ring", 3, "error", `Colour ${c.raw} is used outside the signature mark.`, "Draw icons and shapes in black or grey (currentColor).", where);
    }
    if (n.attrs["data-theme"] === "dark" || cls.includes("dark")) add("dark-theme", 1, "error", "Part of the page is set to dark.", "Remove it. distinctUI is light only.", where);
    // Tailwind classes
    for (const c of cls) for (const [re, id, rule, sev, what, fix] of TW) if (re.test(c) && !(id === "colour-off-ring" && mark) && !(id === "shadow" && FLOATING.test(n.attrs.class))) add(id, rule, sev, `${what}: ${c}.`, fix, where);
    if (large && cls.some((c) => /^bg-(black|(gray|neutral|zinc|slate|stone)-(7|8|9)\d{2})$/.test(c))) add("dark-section", 1, "error", "A large block has a dark background.", "Use white or grey-50 for sections.", where);
    // Left out on purpose
    if (cls.some((c) => /toast|snackbar/i.test(c))) add("toast", "left-out", "error", "A toast.", "No toasts. Show a dui-status notice where the person is looking, and leave it there.", where);
    if (cls.some((c) => /carousel|swiper|slick|splide|glide/i.test(c))) add("carousel", "left-out", "warning", "A carousel.", "Use a grid, rows or a gallery so nothing is hidden.", where);
    // Headings
    if (n.tag === "h1") h1Count++;
    if (cls.includes("dui-feature")) featureCount++;
    // Page and section headings. Small card headings (dui-h3, dui-h4) and headings inside navigation don't need a bold word.
    const sized = cls.find((c) => /^dui-(hero|h1|h2|h3|h4)$/.test(c));
    const isHeading = (sized ? /^dui-(hero|h1|h2)$/.test(sized) : n.tag === "h1" || n.tag === "h2") && !ancestorMatches(n.parent, (a) => a.tag === "nav" || /sidenav|menu|footer/.test(a.attrs.class || ""));
    if (isHeading && !cls.includes("dui-sr-only")) {
      const bolds = [];
      walk(n, (k) => { if (k.tag === "b" || k.tag === "strong") bolds.push(k); });
      const t = textOf(n).trim();
      if (t && !bolds.length && t.split(/\s+/).length > 1) add("no-bold-word", 5, "warning", `A heading has no bold word: "${snippet(t, 50)}".`, "Make the word that carries the meaning bold with <b>. The rest stays light.", where);
      if (bolds.length > 1) add("many-bold", 5, "warning", `A heading has ${bolds.length} bold parts: "${snippet(t, 50)}".`, "Keep one bold word or phrase per heading.", where);
      if (/!\s*$/.test(t)) add("exclaim", 12, "warning", `A heading ends with an exclamation mark: "${snippet(t, 50)}".`, "Say it plainly, without the exclamation mark.", where);
    }
    // One black pill per group
    const blacks = (n.children || []).filter((k) => k.attrs && classes(k).some((c) => c === "dui-btn" || c === "dui-btn-sm" || c === "btn-primary"));
    if (blacks.length > 1) add("two-black-pills", 6, "error", `${blacks.length} black pills in one group (${blacks.map((b) => snippet(textOf(b), 20)).join(", ")}).`, "Keep one black pill for the main action. Make the others outlined (dui-btn-ghost) or a text link.", where);
    // Access
    if (n.tag === "img" && !("alt" in n.attrs)) add("img-alt", "access", "error", "An image has no alt text.", 'Say what\'s in it, or use alt="" when nearby words already say it.', where);
    if (/^(input|select|textarea)$/.test(n.tag)) {
      const type = (n.attrs.type || "").toLowerCase();
      if (!/^(hidden|submit|button|reset|image)$/.test(type)) {
        const id = n.attrs.id;
        const labelled = n.attrs["aria-label"] || n.attrs["aria-labelledby"] || n.attrs.title || ancestorMatches(n, (a) => a.tag === "label") || (id && new RegExp(`for=["']?${id.replace(/[^\w-]/g, "")}["'\\s>]`).test(html));
        if (!labelled) add("label", "access", "warning", `A ${n.tag} has no label.`, "Put a label above it (label for=id), or add aria-label for a lone search field.", where);
      }
    }
    // Words
    if (n.tag === "button" || n.tag === "a" || (n.tag === "input" && /submit|button/.test(n.attrs.type || ""))) {
      const t = (n.tag === "input" ? n.attrs.value || "" : textOf(n)).trim();
      if (/^(submit|click here|click|ok|go)$/i.test(t)) add("vague-button", 12, "warning", `A button says "${t}".`, "Say what happens: \"Send for review\", \"Book the slot\", \"Save changes\".", where);
    }
  });
  const text = textOf(root);
  for (const [re, msg] of [[/\b(oops|whoops|uh[- ]oh)\b/i, "a joke in place of an error"], [/something went wrong/i, "\"Something went wrong\" without saying what"], [/an error (has )?occurred/i, "\"An error occurred\" without saying what"]]) {
    const m = text.match(re);
    if (m) add("vague-error", 12, "warning", `The words include ${msg}.`, "Name what failed and how to fix it, in one plain sentence.", snippet(m[0]));
  }
  checkWork(root, add);
  if (h1Count > 1) add("many-h1", "access", "warning", `There are ${h1Count} h1 headings.`, "Use one h1 per page, then h2 for sections.", "");
  if (featureCount > 1) add("many-features", 7, "warning", `There are ${featureCount} feature cards.`, "Use one feature card per page, for the one thing that matters most.", "");
  return done();
}

// ---------- distinctUI@work ----------
// Initials in a small mark, a person with two ring colours, single keys with no off switch.
function markPx(n) {
  const cls = classes(n);
  if (cls.includes("dui-mark--xs")) return 20;
  if (cls.includes("dui-mark--sm")) return 24;
  const m = /--mark-size:\s*([\d.]+)(rem|px)/.exec(n.attrs.style || "");
  return m ? (m[2] === "rem" ? parseFloat(m[1]) * 16 : parseFloat(m[1])) : null;
}
function checkWork(root, add) {
  const rings = new Map();
  let singleKeys = 0, toggle = false;
  walk(root, (n) => {
    if (!n.attrs) return;
    if ("data-keys-toggle" in n.attrs) toggle = true;
    const k = n.attrs["data-key"];
    if (k && /^\S$/.test(k.trim())) singleKeys++;
    if (!classes(n).includes("dui-mark")) return;
    let img = null, inner = null;
    walk(n, (c) => { if (c.tag === "img" && !img) img = c; if (classes(c).includes("dui-mark__inner") && !inner) inner = c; });
    const px = markPx(n);
    const initials = inner && !img && !(inner.children || []).some((c) => c.tag === "svg") ? textOf(inner).trim() : "";
    if (px && px < 28 && initials && initials !== "+") add("small-initials", "work", "warning", `Initials "${snippet(initials, 6)}" in a ${px}px mark.`, "Below 28px initials can't be read. Use a photo, or a bigger mark.", describe(n));
    const who = img?.attrs.src || (initials.length <= 3 && /^[A-Z]{1,3}$/.test(initials) ? initials : "");
    const ring = n.attrs["data-ring"];
    if (who && ring) { if (!rings.has(who)) rings.set(who, new Set()); rings.get(who).add(ring); }
  });
  for (const [who, set] of rings) if (set.size > 1) add("two-rings", "work", "warning", `The same person (${snippet(who, 40)}) has ${set.size} ring colours: ${[...set].join(", ")}.`, "Give each person one ring colour and keep it everywhere.", "");
  if (singleKeys && !toggle) add("keys-no-switch", "access", "warning", `${singleKeys} single-key shortcut${singleKeys === 1 ? "" : "s"} with no way to turn them off.`, "Add a switch with data-keys-toggle (WCAG 2.1.4), or use keys with Ctrl or Cmd.", "");
}

// ---------- page specs ----------
const PRIMARY_TYPES = new Set(["hero", "cta", "form", "pricing"]);
export function checkSpec(spec) {
  const { add, done } = makeReport();
  if (!spec || typeof spec !== "object") { add("spec", 12, "error", "There's no page spec.", "Pass an object with name and sections.", ""); return done(); }
  const sig = spec.signature || {};
  if (sig.colour && !/^#[0-9a-f]{3,8}$/i.test(sig.colour)) add("sig-colour", 3, "error", `The signature colour "${sig.colour}" isn't a hex colour.`, "Use one hex colour, such as #1f3bff, or pick a palette.", "signature");
  if (sig.palette && !PALETTES[sig.palette]) add("sig-palette", 3, "error", `There's no palette called "${sig.palette}".`, `Pick one of ${Object.keys(PALETTES).join(", ")}.`, "signature");
  if (spec.theme === "dark" || spec.dark) add("dark-mode", 1, "error", "The spec asks for dark mode.", "Remove it. distinctUI is light only.", "theme");
  const sections = Array.isArray(spec.sections) ? spec.sections : [];
  if (!sections.length) add("empty", 12, "warning", "The page has no sections.", "Start with a hero, then add what the page is for.", "sections");
  let heroes = 0;
  sections.forEach((s, i) => {
    const where = `sections[${i}] (${s.type || "?"})`;
    if (!s.type) { add("type", 12, "error", "A section has no type.", "Set type to hero, features, pricing, questions, cta, form, products, posts, proof, steps or stats.", where); return; }
    if (s.type === "hero") heroes++;
    if (s.heading) {
      const words = s.heading.trim().split(/\s+/);
      if (!s.bold && words.length > 1) add("no-bold-word", 5, "warning", `The heading "${snippet(s.heading, 50)}" has no bold word set, so the composer will pick the longest word.`, "Set bold to the word that carries the meaning.", where);
      else if (s.bold && !s.heading.includes(s.bold)) add("bold-missing", 5, "warning", `The bold word "${s.bold}" isn't in the heading.`, "Pick a word that's in the heading.", where);
      if (/!\s*$/.test(s.heading)) add("exclaim", 12, "warning", "The heading ends with an exclamation mark.", "Say it plainly, without the exclamation mark.", where);
    }
    for (const k of ["colour", "color", "background", "accent", "shadow", "gradient", "font"]) {
      if (s[k] != null) add("styling", k === "font" ? 4 : k === "shadow" ? 7 : 3, "error", `The section sets its own ${k}.`, "Sections don't take styling. Choose a layout instead; colour goes only on the mark.", where);
    }
    const b = [s.primary, s.secondary].filter(Boolean);
    if (s.secondary && !s.primary) add("secondary-only", 6, "warning", "There's a second action but no main one.", "Make the main action primary. The second one is outlined.", where);
    if (Array.isArray(s.actions) && s.actions.filter((a) => a.kind === "primary").length > 1) add("two-black-pills", 6, "error", "More than one main action in a section.", "Keep one primary action. Make the others secondary.", where);
    for (const a of b) if (a.label && /^(submit|click here|click|ok|go)$/i.test(a.label.trim())) add("vague-button", 12, "warning", `A button says "${a.label}".`, "Say what happens when it's pressed.", where);
    if (s.type === "pricing" && Array.isArray(s.items) && s.items.filter((p) => p.featured).length > 1) add("two-featured", 6, "error", "More than one plan is featured.", "Feature one plan. Its button is the black pill, the others are outlined.", where);
    if (s.type === "products" && Array.isArray(s.items)) s.items.forEach((p, k) => { if (p.image && p.alt == null) add("img-alt", "access", "warning", `Product ${k + 1} has a photo with no alt text, so the composer will use its name.`, "Say what's in the photo, such as the product on a grey background.", where); });
    if (s.type === "form" && Array.isArray(s.fields)) s.fields.forEach((f, k) => { if (!f.label) add("label", "access", "error", `Field ${k + 1} has no label.`, "Every field gets a label above it.", where); });
    if (s.type === "form" && s.submit && /^(submit|send|ok)$/i.test(s.submit.trim())) add("vague-button", 12, "warning", `The form button says "${s.submit}".`, "Say what happens: \"Ask for a slot\", \"Join the waitlist\".", where);
    if (/toast|popup|carousel|slider|modal|spinner/.test(s.type)) add("left-out", "left-out", "error", `A ${s.type} section.`, "Left out on purpose. Use a form, rows or a status notice instead.", where);
    if (PRIMARY_TYPES.has(s.type) && s.type !== "form" && s.type !== "pricing" && !s.primary) add("no-action", 6, "warning", "This section has no main action.", "Add one primary action with a label that says what happens.", where);
  });
  if (heroes > 1) add("many-heroes", 5, "warning", `There are ${heroes} heroes.`, "Use one hero at the top. Other sections use section headings.", "sections");
  if (sections.length && sections[0].type !== "hero") add("hero-first", 5, "warning", "The page doesn't start with a hero.", "Start with a hero that says what this is, in a few words.", "sections[0]");
  return done();
}

export const palettes = PALETTES;
export const greys = GREYS;
