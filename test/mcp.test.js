// The MCP endpoint, with ASSETS served from dist/ (npm test builds it first).
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import worker from "../src/worker.js";

const env = {
  ASSETS: {
    async fetch(req) {
      const path = new URL(req.url).pathname;
      try { return new Response(await readFile(new URL(`../dist${path}`, import.meta.url))); }
      catch { return new Response("Not found", { status: 404 }); }
    },
  },
};
const post = (body) => worker.fetch(new Request("https://distinctui.com/mcp", {
  method: "POST",
  headers: { "content-type": "application/json", accept: "application/json, text/event-stream" },
  body: JSON.stringify(body),
}), env);
const call = async (name, args = {}) => (await (await post({ jsonrpc: "2.0", id: 9, method: "tools/call", params: { name, arguments: args } })).json()).result;

test("initialize", async () => {
  const res = await post({ jsonrpc: "2.0", id: 1, method: "initialize", params: { protocolVersion: "2025-06-18", capabilities: {}, clientInfo: { name: "test", version: "1" } } });
  assert.equal(res.status, 200);
  const { result } = await res.json();
  assert.equal(result.protocolVersion, "2025-06-18");
  assert.equal(result.serverInfo.name, "distinctui");
  assert.ok(result.capabilities.tools);
});

test("notifications get 202 and no body", async () => {
  const res = await post({ jsonrpc: "2.0", method: "notifications/initialized" });
  assert.equal(res.status, 202);
});

test("tools/list", async () => {
  const { result } = await (await post({ jsonrpc: "2.0", id: 2, method: "tools/list" })).json();
  const names = result.tools.map((t) => t.name);
  for (const n of ["get_rules", "check_page", "check_spec", "compose_page", "get_template", "get_component", "get_reference", "pick_palette"]) assert.ok(names.includes(n), n);
});

test("unknown methods and bad JSON are errors", async () => {
  const a = await (await post({ jsonrpc: "2.0", id: 3, method: "nope" })).json();
  assert.equal(a.error.code, -32601);
  const res = await worker.fetch(new Request("https://distinctui.com/mcp", { method: "POST", body: "{" }), env);
  assert.equal(res.status, 400);
});

test("GET explains itself", async () => {
  const res = await worker.fetch(new Request("https://distinctui.com/mcp"), env);
  assert.equal(res.status, 405);
  assert.match(await res.text(), /claude mcp add/);
});

test("get_rules", async () => {
  const r = await call("get_rules");
  assert.equal(r.structuredContent.rules.length, 12);
});

test("templates, components and references come through ASSETS", async () => {
  const list = await call("list_templates");
  assert.ok(list.structuredContent.templates.length >= 27);
  const t = await call("get_template", { slug: "landing" });
  assert.match(t.content[0].text, /<!doctype html>/i);
  assert.doesNotMatch(t.content[0].text, /dsite-bar/);
  const c = await call("get_component", { name: "buttons" });
  assert.match(c.content[0].text, /dui-btn/);
  const ref = await call("get_reference", { name: "page-spec" });
  assert.match(ref.content[0].text, /# Page spec/);
  assert.equal((await call("get_template", { slug: "nope" })).isError, true);
});

test("check_page finds a broken page", async () => {
  const r = await call("check_page", { html: `<body style="background:#000"><h1>Hello</h1><button style="background:red">Submit</button></body>` });
  assert.equal(r.structuredContent.ok, false);
  assert.ok(r.structuredContent.errors > 0);
});

test("compose_page returns a page that passes", async () => {
  const r = await call("compose_page", { spec: { name: "Kettle", sections: [{ type: "hero", heading: "Bookings that fill themselves", bold: "fill", primary: { label: "Start free" } }] } });
  assert.ok(!r.isError);
  assert.equal(r.structuredContent.page_check.errors, 0);
  assert.match(r.structuredContent.html, /<b>fill<\/b>/);
});

test("compose_page refuses a spec with errors", async () => {
  const r = await call("compose_page", { spec: { theme: "dark", sections: [{ type: "hero" }] } });
  assert.equal(r.isError, true);
});

test("the rest of the site still goes to ASSETS", async () => {
  const res = await worker.fetch(new Request("https://distinctui.com/skill/index.json"), env);
  assert.equal(res.status, 200);
});
