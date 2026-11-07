// The checker passes every shipped template and catches pages that break the rules.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync, readdirSync } from "node:fs";
import { checkHTML, checkSpec } from "../skills/distinctui/assets/check.js";

const dir = new URL("../skills/distinctui/examples/", import.meta.url);
const examples = readdirSync(dir).filter((f) => f.endsWith(".html"));

test("every template passes with no errors", () => {
  assert.ok(examples.length >= 27);
  for (const f of examples) {
    const r = checkHTML(readFileSync(new URL(f, dir), "utf8"));
    assert.equal(r.errors, 0, `${f}: ${r.findings.filter((x) => x.severity === "error").map((x) => x.message).join(" / ")}`);
  }
});

const ids = (html) => new Set(checkHTML(html).findings.map((f) => f.id));

test("catches dark mode", () => {
  assert.equal(checkHTML(`<style>@media (prefers-color-scheme: dark){body{background:#000}}</style><p>x</p>`).ok, false);
  assert.equal(checkHTML(`<section style="background:#111;padding:80px"><p>Night</p></section>`).ok, false);
});

test("catches colour off the mark and red errors", () => {
  assert.equal(checkHTML(`<a style="color:#1f3bff" href="#">Read more about it</a>`).ok, false);
  assert.equal(checkHTML(`<p class="error" style="color:#e00">Add an email address.</p>`).ok, false);
  assert.equal(checkHTML(`<span class="dui-mark" style="--mark-colour:#1f3bff"><span class="dui-mark__inner">K</span></span>`).errors, 0);
});

test("catches other fonts, shadows and hover lifts", () => {
  assert.equal(checkHTML(`<style>body{font-family:Inter,sans-serif}</style>`).ok, false);
  assert.ok(ids(`<style>.card{box-shadow:0 4px 12px rgba(0,0,0,.2)}</style><div class="card">A card</div>`).has("shadow"));
  assert.ok(checkHTML(`<style>.dui-card:hover{transform:translateY(-4px)}</style>`).findings.length > 0);
});

test("catches two black pills in one group", () => {
  assert.equal(checkHTML(`<div class="dui-actions"><a class="dui-btn" href="#">Start free</a><a class="dui-btn" href="#">Book a demo</a></div>`).ok, false);
  assert.equal(checkHTML(`<div class="dui-actions"><a class="dui-btn" href="#">Start free</a><a class="dui-btn-ghost" href="#">Book a demo</a></div>`).errors, 0);
});

test("flags headings without one bold word, vague buttons and missing labels", () => {
  assert.ok(ids(`<h1 class="dui-hero">Bookings that fill themselves</h1>`).size > 0);
  assert.ok(ids(`<button class="dui-btn">Submit</button>`).size > 0);
  assert.ok(ids(`<input class="dui-input" name="email">`).size > 0);
  assert.ok(ids(`<img src="a.jpg">`).size > 0);
});

test("checkSpec catches styling, dark mode and left-out sections", () => {
  assert.equal(checkSpec({ sections: [{ type: "hero", heading: "Hi there", bold: "Hi", background: "#000" }] }).ok, false);
  assert.equal(checkSpec({ theme: "dark", sections: [{ type: "hero" }] }).ok, false);
  assert.equal(checkSpec({ sections: [{ type: "carousel" }] }).ok, false);
  assert.equal(checkSpec({ signature: { palette: "Neon" }, sections: [] }).ok, false);
});

test("distinctUI@work: initials in small marks, two rings for one person, single keys with no off switch", () => {
  assert.ok(ids(`<span class="dui-mark dui-mark--sm"><span class="dui-mark__inner">AK</span></span>`).has("small-initials"));
  assert.ok(!ids(`<span class="dui-mark dui-mark--sm"><span class="dui-mark__inner"><img src="a.jpg" alt=""></span></span>`).has("small-initials"));
  assert.ok(!ids(`<span class="dui-mark" style="--mark-size:2rem"><span class="dui-mark__inner">AK</span></span>`).has("small-initials"));
  assert.ok(ids(`<span class="dui-mark" data-ring="1"><span class="dui-mark__inner"><img src="p.jpg" alt=""></span></span><span class="dui-mark" data-ring="3"><span class="dui-mark__inner"><img src="p.jpg" alt=""></span></span>`).has("two-rings"));
  assert.ok(ids(`<button data-key="a">Approve</button>`).has("keys-no-switch"));
  assert.ok(!ids(`<button data-key="a">Approve</button><input type="checkbox" role="switch" data-keys-toggle aria-label="Single-key shortcuts">`).has("keys-no-switch"));
  assert.ok(!ids(`<a href="x" data-key="g c">Contracts</a>`).has("keys-no-switch"));
});

test("every @work template is in the examples and has no warnings", () => {
  const work = examples.filter((f) => f.startsWith("work-"));
  assert.equal(work.length, 9);
  for (const f of work) {
    const r = checkHTML(readFileSync(new URL(f, dir), "utf8"));
    assert.equal(r.warnings, 0, `${f}: ${r.findings.map((x) => x.message).join(" / ")}`);
  }
});
