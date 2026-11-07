// The distinctUI MCP server, at https://distinctui.com/mcp.
// Streamable HTTP, stateless, JSON responses only (no SSE, no sessions). Read-only:
// every tool reads the skill or runs the checker and composer; nothing is stored.
//
//   claude mcp add --transport http distinctui https://distinctui.com/mcp
//
// The skill's markdown, templates and an index come from dist/skill/ through the ASSETS
// binding (scripts/build-site.mjs writes them). The rules, checker and composer are bundled.
import rules from "../skills/distinctui/assets/rules.json" with { type: "json" };
import tokens from "../skills/distinctui/assets/tokens.json" with { type: "json" };
import { checkHTML, checkSpec } from "../skills/distinctui/assets/check.js";
import { composePage, LAYOUTS } from "../skills/distinctui/assets/compose.js";

export const SERVER = { name: "distinctui", title: "distinctUI", version: rules.version };
const PROTOCOLS = ["2025-06-18", "2025-03-26", "2024-11-05"];
const MAX_BODY = 1_000_000;

const INSTRUCTIONS = `distinctUI is a light-only design system: black, white and four greys, Bricolage Grotesque, black pill buttons, light headings with one bold word, and colour only on the signature mark.
To build a page: call get_rules once, then either write a page spec (get_reference "page-spec") and call compose_page, or adapt a template from list_templates and get_template. Always run check_page on the HTML you hand over and fix every error.
Links to the CSS: https://distinctui.com/assets/distinctui.css and https://distinctui.com/assets/distinctui.js.`;

const str = (description, extra = {}) => ({ type: "string", description, ...extra });
const ro = { readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false };

export const TOOLS = [
  {
    name: "get_rules",
    title: "Get the distinctUI rules",
    description: "The 12 distinctUI rules, what the system leaves out (toasts, carousels, popups, spinners, dark mode) and the colours, radii and motion values the checker uses. Call this before you design anything. Set full to true for the whole SKILL.md.",
    inputSchema: { type: "object", properties: { full: { type: "boolean", description: "Also return the full SKILL.md text." } } },
    annotations: ro,
  },
  {
    name: "get_tokens",
    title: "Get the design tokens",
    description: "distinctUI tokens in the W3C Design Tokens format: colour, ring palettes, type, space, radius, motion.",
    inputSchema: { type: "object", properties: {} },
    annotations: ro,
  },
  {
    name: "pick_palette",
    title: "Pick a signature colour or ring palette",
    description: "Lists the five ring palettes (Signal, Mzansi, Earth, Coast, Jacaranda), or gives one palette's colours and the CSS to use it. A product gets one signature colour for its mark; a page of people gets a palette of five, one ring each.",
    inputSchema: { type: "object", properties: { palette: str("A palette name, such as Mzansi. Leave out to list them all.") } },
    annotations: ro,
  },
  {
    name: "list_components",
    title: "List components",
    description: "The component groups in the distinctUI reference (type, buttons, form fields, cards, signature mark, header and footer, notices and more).",
    inputSchema: { type: "object", properties: {} },
    annotations: ro,
  },
  {
    name: "get_component",
    title: "Get a component's markup",
    description: "The copy-ready HTML and notes for one component group, such as Buttons or Form fields.",
    inputSchema: { type: "object", properties: { name: str("The group name from list_components, such as \"Buttons\".") }, required: ["name"] },
    annotations: ro,
  },
  {
    name: "list_templates",
    title: "List page templates",
    description: "The 50 page templates in 8 groups, with what each one shows.",
    inputSchema: { type: "object", properties: {} },
    annotations: ro,
  },
  {
    name: "get_template",
    title: "Get a page template",
    description: "One template's full HTML. The people and products in it are invented; swap in the real ones.",
    inputSchema: { type: "object", properties: { slug: str("The template slug from list_templates, such as \"landing\".") }, required: ["slug"] },
    annotations: ro,
  },
  {
    name: "get_reference",
    title: "Get a reference",
    description: "One reference from the skill: page-spec, components, tokens, voice, imagery, motion, moments, work, review, swiftui or wordpress.",
    inputSchema: { type: "object", properties: { name: str("The reference name, such as \"page-spec\" or \"voice\".") }, required: ["name"] },
    annotations: ro,
  },
  {
    name: "check_page",
    title: "Check a page against the rules",
    description: "Checks HTML (with its inline and <style> CSS) against the distinctUI rules: dark mode, colour off the mark, other fonts, shadows, radii, hover lifts, headings without one bold word, more than one black pill in a group, red errors, vague buttons, missing labels and alt text. Returns findings with the rule, the place and the fix. Fix every error before you hand the page over.",
    inputSchema: {
      type: "object",
      properties: {
        html: str("The page or fragment to check."),
        allow_colours: { type: "array", items: { type: "string" }, description: "Extra hex colours allowed on the mark, such as a custom signature colour." },
      },
      required: ["html"],
    },
    annotations: ro,
  },
  {
    name: "check_spec",
    title: "Check a page spec",
    description: "Checks a page spec before it's composed: section types, one primary action, a bold word per heading, no styling keys, labels on fields, alt text on products.",
    inputSchema: { type: "object", properties: { spec: { type: "object", description: "The page spec. See get_reference \"page-spec\"." } }, required: ["spec"] },
    annotations: ro,
  },
  {
    name: "compose_page",
    title: "Compose a page from a spec",
    description: `Turns a page spec (content and layouts, no styling) into a finished distinctUI HTML page that links the CSS from distinctui.com, and checks it. Section types and layouts: ${Object.entries(LAYOUTS).map(([k, v]) => `${k} (${v.join(", ")})`).join("; ")}. See get_reference "page-spec" for every field.`,
    inputSchema: { type: "object", properties: { spec: { type: "object", description: "The page spec." } }, required: ["spec"] },
    annotations: ro,
  },
];

// ---- reading the skill through ASSETS ----

async function asset(env, origin, path) {
  const res = await env.ASSETS.fetch(new Request(origin + path));
  if (!res.ok) return null;
  return res.text();
}
async function skillIndex(env, origin) {
  const t = await asset(env, origin, "/skill/index.json");
  return t ? JSON.parse(t) : { templates: [], references: [] };
}
function sections(md) {
  const out = [];
  let cur = null;
  for (const line of md.split("\n")) {
    const m = /^## (.+)$/.exec(line);
    if (m) { cur = { name: m[1].trim(), body: [] }; out.push(cur); continue; }
    if (cur) cur.body.push(line);
  }
  return out.map((s) => ({ name: s.name, body: s.body.join("\n").trim() }));
}
const norm = (s) => String(s || "").toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();

// ---- tool results ----

const text = (t) => ({ content: [{ type: "text", text: t }] });
const json = (v, summary) => ({
  content: [{ type: "text", text: (summary ? summary + "\n\n" : "") + JSON.stringify(v, null, 2) }],
  structuredContent: v,
});
const fail = (t) => ({ content: [{ type: "text", text: t }], isError: true });

function summarise(r, what) {
  if (r.ok && !r.warnings) return `The ${what} follows the distinctUI rules.`;
  const lines = r.findings.slice(0, 30).map((f) => `- ${f.severity === "error" ? "Error" : "Warning"}, rule ${f.rule} (${f.title}): ${f.message} Fix: ${f.fix}${f.where ? ` [${f.where}]` : ""}`);
  return `The ${what} has ${r.errors} error${r.errors === 1 ? "" : "s"} and ${r.warnings} warning${r.warnings === 1 ? "" : "s"}.\n${lines.join("\n")}${r.findings.length > 30 ? `\n...and ${r.findings.length - 30} more.` : ""}`;
}

export async function callTool(name, args = {}, env, origin) {
  switch (name) {
    case "get_rules": {
      const out = {
        version: rules.version,
        rules: rules.rules,
        left_out: rules.left_out,
        work: rules.work,
        colours: rules.colours,
        radius: rules.radius,
        motion: rules.motion,
        font: rules.font,
        css: "https://distinctui.com/assets/distinctui.css",
        js: "https://distinctui.com/assets/distinctui.js",
        work_css: "https://distinctui.com/assets/distinctui-work.css",
        work_js: "https://distinctui.com/assets/distinctui-work.js",
      };
      const head = rules.rules.map((r) => `${r.n}. ${r.title}. ${r.text}`).join("\n");
      if (args.full) {
        const md = await asset(env, origin, "/skill/SKILL.md");
        return text(md || head);
      }
      return json(out, `distinctUI ${rules.version}\n${head}`);
    }
    case "get_tokens":
      return json(tokens);
    case "pick_palette": {
      const names = Object.keys(rules.palettes);
      if (!args.palette) {
        const list = names.map((n) => ({ name: n, colours: rules.palettes[n], credit: rules.palette_credits[n] || undefined }));
        return json({ palettes: list, signature: "Pick one colour for the product's own mark, usually the first of a palette, and set --dui-signature to it." },
          "Five ring palettes. A product uses one signature colour on its mark only. A page of people gives each face a ring from one palette, with data-ring 1 to 5.");
      }
      const n = names.find((k) => k.toLowerCase() === String(args.palette).toLowerCase());
      if (!n) return fail(`There's no palette called "${args.palette}". Pick one of ${names.join(", ")}.`);
      const c = rules.palettes[n];
      return json({
        name: n,
        colours: c,
        credit: rules.palette_credits[n] || undefined,
        css: `<html class="dui-palette-${n.toLowerCase()}">  <!-- rings on .dui-mark[data-ring="1".."5"] -->\n:root { --dui-signature: ${c[0]}; }`,
      }, `${n}: ${c.join(", ")}. Colour goes on rings only, never on buttons, links, headings or backgrounds.`);
    }
    case "list_components": {
      const md = await asset(env, origin, "/skill/references/components.md");
      if (!md) return fail("The components reference isn't available right now.");
      return json({ components: sections(md).map((s) => s.name) });
    }
    case "get_component": {
      const md = await asset(env, origin, "/skill/references/components.md");
      if (!md) return fail("The components reference isn't available right now.");
      const all = sections(md);
      const want = norm(args.name);
      const hit = all.find((s) => norm(s.name) === want) || all.find((s) => norm(s.name).includes(want) || (want && want.includes(norm(s.name))));
      if (!hit) return fail(`There's no component group called "${args.name}". Use one of: ${all.map((s) => s.name).join(", ")}.`);
      return text(`## ${hit.name}\n\n${hit.body}`);
    }
    case "list_templates": {
      const idx = await skillIndex(env, origin);
      return json({ templates: idx.templates }, `${idx.templates.length} templates. Get one with get_template and its slug.`);
    }
    case "get_template": {
      const slug = String(args.slug || "").toLowerCase().replace(/\.html$/, "");
      const idx = await skillIndex(env, origin);
      const t = idx.templates.find((x) => x.slug === slug || norm(x.name) === norm(args.slug));
      if (!t) return fail(`There's no template called "${args.slug}". Use one of: ${idx.templates.map((x) => x.slug).join(", ")}.`);
      const html = await asset(env, origin, `/skill/examples/${t.slug}.html`);
      if (!html) return fail(`The ${t.slug} template isn't available right now.`);
      return text(`<!-- distinctUI template: ${t.name} (${t.group}). Photos referenced as img/... live at https://distinctui.com/examples/img/. -->\n${html}`);
    }
    case "get_reference": {
      const idx = await skillIndex(env, origin);
      const name = String(args.name || "").toLowerCase().replace(/\.md$/, "");
      if (!idx.references.includes(name)) return fail(`There's no reference called "${args.name}". Use one of: ${idx.references.join(", ")}.`);
      const md = await asset(env, origin, `/skill/references/${name}.md`);
      return md ? text(md) : fail(`The ${name} reference isn't available right now.`);
    }
    case "check_page": {
      if (typeof args.html !== "string" || !args.html.trim()) return fail("Pass the page as html.");
      if (args.html.length > MAX_BODY) return fail("That page is too big to check here. Check it in parts, or run check.js from the npm package.");
      const r = checkHTML(args.html, { allowColours: Array.isArray(args.allow_colours) ? args.allow_colours : [] });
      return json(r, summarise(r, "page"));
    }
    case "check_spec": {
      const r = checkSpec(args.spec);
      return json(r, summarise(r, "spec"));
    }
    case "compose_page": {
      const spec = args.spec;
      const s = checkSpec(spec);
      if (!s.ok) return { ...json({ spec_check: s }, summarise(s, "spec") + "\nFix the spec and compose again."), isError: true };
      let html;
      try { html = composePage(spec); } catch (e) { return fail(String(e.message || e)); }
      const h = checkHTML(html, { allowColours: spec.signature?.colour ? [spec.signature.colour] : [] });
      return {
        content: [
          { type: "text", text: `${summarise(h, "composed page")}${s.warnings ? `\n${summarise(s, "spec")}` : ""}\n\nThe page links the CSS from distinctui.com. Save it as an .html file.` },
          { type: "text", text: html },
        ],
        structuredContent: { html, spec_check: s, page_check: h },
      };
    }
    default:
      return null;
  }
}

// ---- JSON-RPC over HTTP ----

const CORS = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "POST, GET, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Accept, Mcp-Protocol-Version, Mcp-Session-Id, Authorization",
  "Access-Control-Expose-Headers": "Mcp-Protocol-Version",
};
const reply = (body, status = 200, extra = {}) =>
  new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { ...CORS, ...(body == null ? {} : { "Content-Type": "application/json" }), ...extra },
  });
const rpcError = (id, code, message) => ({ jsonrpc: "2.0", id: id ?? null, error: { code, message } });

async function handleMessage(msg, env, origin) {
  if (!msg || typeof msg !== "object" || msg.jsonrpc !== "2.0" || typeof msg.method !== "string") {
    // A response or something unreadable: nothing to answer.
    if (msg && (msg.result !== undefined || msg.error !== undefined)) return null;
    return rpcError(msg?.id, -32600, "Not a JSON-RPC 2.0 request.");
  }
  const { id, method, params = {} } = msg;
  const isNote = id === undefined;
  if (isNote) return null; // notifications/initialized, notifications/cancelled and the like

  switch (method) {
    case "initialize": {
      const asked = params.protocolVersion;
      return {
        jsonrpc: "2.0",
        id,
        result: {
          protocolVersion: PROTOCOLS.includes(asked) ? asked : PROTOCOLS[0],
          capabilities: { tools: { listChanged: false } },
          serverInfo: { ...SERVER, websiteUrl: "https://distinctui.com" },
          instructions: INSTRUCTIONS,
        },
      };
    }
    case "ping":
      return { jsonrpc: "2.0", id, result: {} };
    case "tools/list":
      return { jsonrpc: "2.0", id, result: { tools: TOOLS } };
    case "tools/call": {
      const name = params.name;
      if (!TOOLS.some((t) => t.name === name)) return rpcError(id, -32602, `Unknown tool "${name}".`);
      try {
        const result = await callTool(name, params.arguments || {}, env, origin);
        return { jsonrpc: "2.0", id, result };
      } catch (e) {
        return { jsonrpc: "2.0", id, result: fail(`The tool failed: ${e?.message || e}`) };
      }
    }
    case "resources/list":
      return { jsonrpc: "2.0", id, result: { resources: [] } };
    case "prompts/list":
      return { jsonrpc: "2.0", id, result: { prompts: [] } };
    default:
      return rpcError(id, -32601, `Method "${method}" isn't supported.`);
  }
}

export async function handleMCP(request, env) {
  const origin = new URL(request.url).origin;
  if (request.method === "OPTIONS") return new Response(null, { status: 204, headers: { ...CORS, "Access-Control-Max-Age": "86400" } });
  if (request.method !== "POST") {
    return new Response(
      "This is the distinctUI MCP server. It speaks MCP over HTTP POST.\nAdd it to Claude Code with:\n  claude mcp add --transport http distinctui https://distinctui.com/mcp\nhttps://distinctui.com/#mcp\n",
      { status: 405, headers: { ...CORS, Allow: "POST, OPTIONS", "Content-Type": "text/plain; charset=utf-8" } },
    );
  }
  const raw = await request.text();
  if (raw.length > MAX_BODY) return reply(rpcError(null, -32600, "The request is too big."), 413);
  let body;
  try { body = JSON.parse(raw); } catch { return reply(rpcError(null, -32700, "The request isn't valid JSON."), 400); }

  if (Array.isArray(body)) {
    if (!body.length) return reply(rpcError(null, -32600, "Empty batch."), 400);
    const out = (await Promise.all(body.map((m) => handleMessage(m, env, origin)))).filter(Boolean);
    return out.length ? reply(out) : reply(null, 202);
  }
  const out = await handleMessage(body, env, origin);
  return out ? reply(out) : reply(null, 202);
}
