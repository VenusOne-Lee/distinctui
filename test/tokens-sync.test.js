// tokens.json is the one source. The Swift tokens are generated from it, and the
// hand-written CSS and Tailwind theme must carry the same values.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { swiftTokens, swiftPath, tokensPath } from "../scripts/swift-tokens.mjs";

const tokens = JSON.parse(readFileSync(tokensPath, "utf8"));
const css = readFileSync("skills/distinctui/assets/distinctui.css", "utf8");
const tailwind = readFileSync("skills/distinctui/assets/tailwind.css", "utf8");

// The first value each custom property gets, which is the :root / @theme one.
const vars = (text) => {
  const out = {};
  for (const [, k, v] of text.matchAll(/(--[\w-]+):\s*([^;]+);/g)) out[k] ??= v.trim();
  return out;
};
const cssVars = vars(css);
const twVars = vars(tailwind);
const toPx = (v) => (v.endsWith("rem") ? `${parseFloat(v) * 16}px` : v);
const bezier = (v) => `cubic-bezier(${v.join(", ")})`;

test("Swift tokens are generated from tokens.json and up to date", () => {
  assert.equal(readFileSync(swiftPath, "utf8"), swiftTokens(tokens), "Run `npm run tokens` and commit the result.");
});

test("distinctui.css matches tokens.json", () => {
  const { color, ring, space, radius, duration, easing } = tokens;
  assert.equal(cssVars["--dui-white"], color.white.$value);
  assert.equal(cssVars["--dui-black"], color.black.$value);
  assert.equal(cssVars["--dui-signature"], color.signature.$value);
  for (const [n, t] of Object.entries(color.grey)) assert.equal(cssVars[`--dui-grey-${n}`], t.$value, `grey-${n}`);
  for (const [n, t] of Object.entries(ring.signal)) assert.equal(cssVars[`--dui-ring-${n}`], t.$value, `ring-${n}`);
  for (const [n, t] of Object.entries(space)) assert.equal(toPx(cssVars[`--dui-space-${n}`]), t.$value, `space-${n}`);
  for (const [k, t] of Object.entries(radius)) assert.equal(cssVars[`--dui-radius-${k}`], t.$value, `radius-${k}`);
  assert.equal(cssVars["--dui-dur-fast"], duration.fast.$value);
  assert.equal(cssVars["--dui-dur"], duration.base.$value);
  assert.equal(cssVars["--dui-dur-slow"], duration.slow.$value);
  assert.equal(cssVars["--dui-dur-live"], duration.live.$value);
  assert.equal(cssVars["--dui-ease"], bezier(easing.settle.$value));
  assert.equal(cssVars["--dui-ease-pop"], bezier(easing.pop.$value));
});

test("every ring palette class in distinctui.css matches tokens.json", () => {
  for (const [name, rings] of Object.entries(tokens.ring)) {
    const rule = css.match(new RegExp(`\\.dui-palette-${name}\\s*\\{([^}]*)\\}`));
    assert.ok(rule, `.dui-palette-${name} is missing`);
    const got = vars(rule[1]);
    for (const [n, t] of Object.entries(rings)) assert.equal(got[`--dui-ring-${n}`], t.$value, `${name} ring-${n}`);
  }
});

test("tailwind.css matches tokens.json", () => {
  const { color, ring, radius, easing } = tokens;
  assert.equal(twVars["--color-white"], color.white.$value);
  assert.equal(twVars["--color-black"], color.black.$value);
  assert.equal(twVars["--color-signature"], color.signature.$value);
  for (const [n, t] of Object.entries(color.grey)) assert.equal(twVars[`--color-grey-${n}`], t.$value, `grey-${n}`);
  for (const [n, t] of Object.entries(ring.signal)) assert.equal(twVars[`--color-ring-${n}`], t.$value, `ring-${n}`);
  for (const [k, t] of Object.entries(radius)) if (k !== "pill") assert.equal(twVars[`--radius-${k}`], t.$value, `radius-${k}`);
  assert.equal(twVars["--ease-dui"], bezier(easing.settle.$value));
  assert.equal(twVars["--ease-pop"], bezier(easing.pop.$value));
});
