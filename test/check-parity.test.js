// The WordPress plugin's PHP checker (wordpress/distinctui-plus/includes/class-check.php)
// finds the same things as the shared checkSpec in check.js. Skipped when PHP isn't installed.
import { test } from "node:test";
import assert from "node:assert/strict";
import { spawnSync } from "node:child_process";
import { checkSpec } from "../skills/distinctui/assets/check.js";

const hasPHP = spawnSync("php", ["-v"]).status === 0;

const hero = { type: "hero", heading: "Bookings that fill themselves", bold: "fill", primary: { label: "Start free" } };
const specs = [
  { name: "Clean", sections: [hero, { type: "pricing", heading: "Simple prices", bold: "prices", items: [{ name: "Solo", price: "R99" }] }] },
  { name: "Empty", sections: [] },
  { name: "Dark", theme: "dark", sections: [hero] },
  { name: "Bad colours", signature: { colour: "blue", palette: "Neon" }, sections: [hero] },
  { name: "No type", sections: [hero, { heading: "Hi" }] },
  { name: "Bold", sections: [{ ...hero, bold: "" }, { type: "features", heading: "What it does", bold: "flies", primary: { label: "Go" } }] },
  { name: "Shout", sections: [{ ...hero, heading: "Book now!", bold: "Book" }] },
  { name: "Styling", sections: [{ ...hero, colour: "#f00", font: "Inter", shadow: "lg", gradient: "x" }] },
  { name: "Second only", sections: [{ type: "cta", heading: "Try it", bold: "Try", secondary: { label: "Later" } }] },
  { name: "Two pills", sections: [{ ...hero, actions: [{ kind: "primary" }, { kind: "primary" }] }] },
  { name: "Vague", sections: [{ ...hero, primary: { label: "Submit" } }, { type: "form", layout: "contact", heading: "Write", bold: "Write", submit: "Send", fields: [{ label: "Name" }, { type: "email" }] }] },
  { name: "Featured", sections: [hero, { type: "pricing", heading: "Prices", bold: "Prices", items: [{ name: "A", featured: true }, { name: "B", featured: true }] }] },
  { name: "Alt", sections: [hero, { type: "products", heading: "Shop", bold: "Shop", items: [{ name: "Mug", image: "m.jpg" }] }] },
  { name: "Left out", sections: [hero, { type: "carousel" }, { type: "toast" }] },
  { name: "Heroes", sections: [{ type: "features", heading: "Things", bold: "Things" }, hero, hero] },
];

test("PHP and JS spec checks agree", { skip: !hasPHP && "php not installed" }, () => {
  const r = spawnSync("php", ["wordpress/distinctui-plus/tests/check-cli.php"], { input: JSON.stringify(specs), encoding: "utf8" });
  assert.equal(r.status, 0, r.stderr);
  const php = JSON.parse(r.stdout);
  specs.forEach((spec, i) => {
    const js = checkSpec(spec);
    const key = (f) => `${f.id}|${f.severity}|${f.where}`;
    // unknown-type is PHP only: the plugin also names types it can't draw.
    const p = php[i].findings.filter((f) => f.id !== "unknown-type").map(key).sort();
    assert.deepEqual(p, js.findings.map(key).sort(), spec.name);
    assert.equal(php[i].ok, js.ok, `${spec.name}: ok`);
  });
});
