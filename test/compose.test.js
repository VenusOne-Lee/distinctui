// Composed pages follow the rules by construction.
import { test } from "node:test";
import assert from "node:assert/strict";
import { composePage, composeSection, pickBold, LAYOUTS } from "../skills/distinctui/assets/compose.js";
import { checkHTML, checkSpec } from "../skills/distinctui/assets/check.js";

const spec = {
  name: "Kettle",
  domain: "kettle.example",
  signature: { palette: "Mzansi" },
  live: true,
  version: "v1.0",
  nav: [{ label: "Prices", href: "#prices" }],
  headerAction: { label: "Start free", href: "/start" },
  sections: [
    { type: "hero", heading: "Bookings that fill themselves", bold: "fill", lede: "One link for bookings.", primary: { label: "Start free" }, secondary: { label: "See prices" } },
    { type: "proof", faces: ["Thandi Mokoena", "Ana Ruiz"], more: "and 40 salons" },
    { type: "features", heading: "Everything a front desk does", bold: "front desk", items: [{ title: "One link", text: "Share it anywhere." }] },
    { type: "steps", heading: "How it works", bold: "works", items: [{ title: "Add services", text: "Names and prices." }] },
    { type: "stats", items: [{ label: "Setup", value: "10 min" }] },
    { type: "pricing", heading: "Simple prices", bold: "prices", items: [{ name: "Solo", price: "R99" }, { name: "Salon", price: "R249" }, { name: "Group", price: "R599" }] },
    { type: "questions", heading: "Questions people ask", bold: "Questions", items: [{ q: "Can I cancel?", a: "Yes." }] },
    { type: "products", heading: "Gift cards", bold: "Gift", items: [{ name: "R200 card", price: "R200", image: "card.jpg", alt: "A white gift card on grey" }] },
    { type: "posts", heading: "Latest notes", bold: "notes", items: [{ title: "Cutting no-shows", date: "1 Nov 2026" }] },
    { type: "form", layout: "booking", heading: "Book a chair", bold: "chair" },
    { type: "cta", layout: "split", heading: "Try Kettle today", bold: "Kettle", primary: { label: "Start free" } },
  ],
};

test("the sample spec is clean", () => {
  const r = checkSpec(spec);
  assert.equal(r.errors, 0);
  assert.equal(r.warnings, 0, r.findings.map((f) => f.message).join(" / "));
});

test("a composed page passes the checker", () => {
  const r = checkHTML(composePage(spec));
  assert.equal(r.errors, 0, r.findings.map((f) => f.message).join(" / "));
  assert.equal(r.warnings, 0, r.findings.map((f) => f.message).join(" / "));
});

test("every section type and layout composes and passes", () => {
  for (const [type, layouts] of Object.entries(LAYOUTS)) {
    for (const layout of layouts) {
      const base = spec.sections.find((s) => s.type === type);
      const html = composePage({ ...spec, sections: [spec.sections[0], { ...base, layout }].slice(type === "hero" ? 1 : 0) });
      assert.equal(checkHTML(html).errors, 0, `${type}/${layout}`);
    }
  }
});

test("the mark is dashed until live, with the version once live", () => {
  assert.match(composePage({ ...spec, live: false }), /data-live="false"/);
  assert.match(composePage(spec), /dui-mark__version">v1\.0/);
});

test("one featured plan with the only black pill", () => {
  const html = composeSection(spec.sections[5], spec, 5);
  assert.equal((html.match(/dui-feature/g) || []).length, 1);
  assert.equal((html.match(/class="dui-btn-sm"/g) || []).length, 1);
});

test("text is escaped", () => {
  const html = composeSection({ type: "questions", heading: "<script>x</script> tips", items: [{ q: "<b>", a: "&" }] }, {}, 0);
  assert.doesNotMatch(html, /<script>/);
  assert.match(html, /&lt;b&gt;/);
});

test("pickBold uses the given word, else the longest real word", () => {
  assert.equal(pickBold("Bookings that fill themselves", "fill"), "fill");
  assert.equal(pickBold("The people behind it"), "people");
});

test("unknown section types throw", () => {
  assert.throws(() => composeSection({ type: "carousel" }, {}, 0));
});
