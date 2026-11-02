// Builds the static site into dist/: the pages in site/, plus the skill's CSS
// and examples, so the site always shows exactly what the skill ships.
// On the site only, each example gets a thin distinctUI bar on top (back to
// Templates, home, previous and next), so nobody gets stuck inside a fake app.
// The skill's own example files stay clean.
import { cpSync, rmSync, mkdirSync, readFileSync, writeFileSync, readdirSync } from "node:fs";
import { zipDir } from "./zip.mjs";

rmSync("dist", { recursive: true, force: true });
mkdirSync("dist");
cpSync("site", "dist", { recursive: true });
cpSync("skills/distinctui/assets", "dist/assets", { recursive: true });
cpSync("skills/distinctui/examples", "dist/examples", { recursive: true });

// Template order and names come from the Templates page, so new templates join the bar on their own.
const templates = [...readFileSync("site/templates.html", "utf8").matchAll(
  /href="\/examples\/([\w-]+)\.html"[\s\S]*?<h[23][^>]*>([^<]+)<\/h[23]>/g,
)].map(([, slug, name]) => ({ slug, name }));

const esc = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/"/g, "&quot;");

const style = `<style>
.dsite-bar{box-sizing:border-box;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px;min-height:52px;padding:8px 16px;background:#fff;border-bottom:1px solid #e5e5e5;font:400 14px/1.4 "Bricolage Grotesque",ui-sans-serif,system-ui,sans-serif;color:#000;position:relative;z-index:50}
.dsite-bar a{color:#000;text-decoration:none}
.dsite-bar a:focus-visible{outline:2px solid #000;outline-offset:2px;border-radius:999px}
.dsite-back{justify-self:start;font-weight:600;padding:6px 0}
.dsite-back:hover,.dsite-code:hover{text-decoration:underline}
.dsite-logo{font-weight:700;letter-spacing:-0.02em;font-size:16px}
.dsite-end{justify-self:end;display:flex;align-items:center;gap:8px}
.dsite-name{color:#6b6b6b;white-space:nowrap}
.dsite-code{color:#6b6b6b!important;margin-right:8px}
.dsite-step{display:inline-grid;place-items:center;width:34px;height:34px;border:1px solid #e5e5e5;border-radius:999px;font-size:16px;transition:border-color 120ms cubic-bezier(.2,0,0,1)}
.dsite-step:hover{border-color:#000}
@media (max-width:639px){.dsite-bar{grid-template-columns:1fr auto}.dsite-logo,.dsite-code,.dsite-label{display:none}}
.dsite-step svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
@media print{.dsite-bar{display:none}}
</style>`;

templates.forEach(({ slug, name }, i) => {
  const prev = templates[(i - 1 + templates.length) % templates.length];
  const next = templates[(i + 1) % templates.length];
  const bar = `${style}
<nav class="dsite-bar" aria-label="distinctUI">
  <a class="dsite-back" href="/templates.html">&larr; Templates</a>
  <a class="dsite-logo" href="/">distinctUI</a>
  <span class="dsite-end">
    <a class="dsite-code" href="https://github.com/VenusOne-Lee/distinctui/blob/main/skills/distinctui/examples/${slug}.html">View code</a>
    <span class="dsite-name"><span class="dsite-label">${esc(name)} · </span>${i + 1} of ${templates.length}</span>
    <a class="dsite-step" href="/examples/${prev.slug}.html" aria-label="Previous: ${esc(prev.name)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></a>
    <a class="dsite-step" href="/examples/${next.slug}.html" aria-label="Next: ${esc(next.name)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></a>
  </span>
</nav>`;
  const file = `dist/examples/${slug}.html`;
  const html = readFileSync(file, "utf8");
  if (!/<body[^>]*>/.test(html)) throw new Error(`No <body> in ${file}`);
  writeFileSync(file, html.replace(/<body[^>]*>/, (m) => `${m}\n${bar}`));
});

// Moments: faux product UI built with the Stageshow kit (moments/kit, MIT,
// Benji Taylor) in the distinctUI look, bundled for the Moments page.
const { build } = await import("esbuild");
await build({
  entryPoints: { moments: "moments/src/main.tsx" },
  bundle: true,
  minify: true,
  format: "esm",
  target: "es2020",
  jsx: "automatic",
  outdir: "dist/moments",
  define: { "process.env.NODE_ENV": '"production"' },
  legalComments: "eof",
  logLevel: "warning",
});

// The skill itself, for the MCP server at /mcp (src/mcp.js reads these through ASSETS):
// SKILL.md, the references, the clean examples (without the site bar) and an index.
mkdirSync("dist/skill/examples", { recursive: true });
cpSync("skills/distinctui/SKILL.md", "dist/skill/SKILL.md");
cpSync("skills/distinctui/references", "dist/skill/references", { recursive: true });
for (const { slug } of templates) cpSync(`skills/distinctui/examples/${slug}.html`, `dist/skill/examples/${slug}.html`);
const groups = [];
for (const part of readFileSync("site/templates.html", "utf8").split(/(?=<h2 id="[\w-]+-h")/).slice(1)) {
  const title = (part.match(/<h2[^>]*>([\s\S]*?)<\/h2>/)?.[1] || "").replace(/<[^>]+>/g, "").trim();
  for (const [, slug, name, about] of part.matchAll(/href="\/examples\/([\w-]+)\.html"[\s\S]*?<h[34][^>]*>([^<]+)<\/h[34]>(?:<p class="dui-meta">([^<]*)<\/p>)?/g))
    groups.push({ slug, name, about: about || "", group: title });
}
const references = readdirSync("skills/distinctui/references").filter((f) => f.endsWith(".md")).map((f) => f.replace(/\.md$/, ""));
writeFileSync("dist/skill/index.json", JSON.stringify({ templates: groups, references }, null, 1));

// The WordPress theme, as the zip WordPress asks for under Themes, Add New, Upload.
const themeFiles = zipDir("wordpress/distinctui", "dist/distinctui-wordpress.zip", "distinctui");

// distinctUI+ (the paid builder plugin) gets its own copy of the shared rules and checker,
// so the canvas and the PHP checker work offline. Its zip goes to .build/, not the site:
// it isn't for download until licensing is set up.
mkdirSync("wordpress/distinctui-plus/assets/vendor", { recursive: true });
for (const f of ["rules.json", "check.js"]) cpSync(`skills/distinctui/assets/${f}`, `wordpress/distinctui-plus/assets/vendor/${f}`);
mkdirSync(".build", { recursive: true });
zipDir("wordpress/distinctui-plus", ".build/distinctui-plus.zip", "distinctui-plus");

console.log(`Built dist/ (${templates.length} templates with the site bar, moments bundle, WordPress theme zip with ${themeFiles} files)`);
