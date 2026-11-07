// Writes the SwiftUI tokens from skills/distinctui/assets/tokens.json, so the
// web and Swift sides share one source file.
//
//   node scripts/swift-tokens.mjs           write swift/Sources/DistinctUI/Tokens.generated.swift
//   node scripts/swift-tokens.mjs --check   fail if that file is out of date (the build runs this)
import { readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";

const root = fileURLToPath(new URL("..", import.meta.url));
export const tokensPath = `${root}skills/distinctui/assets/tokens.json`;
export const swiftPath = `${root}swift/Sources/DistinctUI/Tokens.generated.swift`;

const hex = (v) => {
  if (!/^#[0-9a-f]{6}$/i.test(v)) throw new Error(`tokens.json: expected a #rrggbb colour, got ${v}`);
  return `0x${v.slice(1).toUpperCase()}`;
};
const px = (v) => {
  const m = /^(\d+(?:\.\d+)?)px$/.exec(v);
  if (!m) throw new Error(`tokens.json: expected a px value, got ${v}`);
  return m[1];
};
const ms = (v) => {
  const m = /^(\d+)ms$/.exec(v);
  if (!m) throw new Error(`tokens.json: expected a ms duration, got ${v}`);
  return String(Number(m[1]) / 1000);
};
const doc = (t, indent) => (t.$description ? `${indent}/// ${t.$description.replace(/\.?$/, ".")}\n` : "");
const byNumber = (o) => Object.keys(o).filter((k) => !k.startsWith("$")).sort((a, b) => a - b);

export function swiftTokens(tokens = JSON.parse(readFileSync(tokensPath, "utf8"))) {
  const { color, ring, radius, space, border, duration, easing } = tokens;
  const out = [];
  out.push(`// Generated from skills/distinctui/assets/tokens.json by scripts/swift-tokens.mjs.
// Don't edit by hand: change tokens.json, then run \`npm run tokens\`.
// The build fails if this file and tokens.json disagree.

import CoreGraphics
import SwiftUI
`);

  // Black, white and the greys.
  out.push("public extension Color {");
  const colours = [
    ["duiWhite", color.white],
    ["duiBlack", color.black],
    ...byNumber(color.grey).map((n) => [`duiGrey${n}`, color.grey[n]]),
  ];
  for (const [name, t] of colours) out.push(`${doc(t, "    ")}    static let ${name} = Color(duiHex: ${hex(t.$value)})`);
  out.push("}\n");
  out.push("public extension ShapeStyle where Self == Color {");
  for (const [name] of colours) out.push(`    static var ${name}: Color { .${name} }`);
  out.push("}\n");

  // Ring palettes.
  const palettes = Object.keys(ring);
  out.push(`/// Five ring colours for when each person or project gets its own mark. The fifth is always black.
/// Pick one palette per product and keep it. These colours go on marks and earned dots, nowhere else.
public enum DUIPalette: String, CaseIterable, Sendable {
    case ${palettes.join(", ")}

    /// Rings 1 to 5, in order.
    public var hex: [UInt32] {
        switch self {`);
  for (const p of palettes) out.push(`        case .${p}: [${byNumber(ring[p]).map((n) => hex(ring[p][n].$value)).join(", ")}]`);
  out.push("        }\n    }\n}\n");

  // Corner radii, without the pill: SwiftUI draws pills with Capsule().
  out.push("/// Corner radii. Pills for what you press or type one line into, soft corners for what you read.\n/// Pills use `Capsule()`.\npublic enum DUIRadius {");
  for (const [k, t] of Object.entries(radius)) {
    if (k === "pill") continue;
    out.push(`${doc(t, "    ")}    public static let ${k}: CGFloat = ${px(t.$value)}`);
  }
  out.push("}\n");

  out.push("/// The 4pt spacing scale.\npublic enum DUISpace {");
  for (const n of byNumber(space)) out.push(`    public static let x${n}: CGFloat = ${px(space[n].$value)}`);
  out.push("}\n");

  out.push("/// Hairlines and the one black edge.\npublic enum DUIBorder {");
  for (const [k, t] of Object.entries(border)) out.push(`${doc(t, "    ")}    public static let ${k}: CGFloat = ${px(t.$value)}`);
  out.push("}\n");

  out.push("/// Motion durations in seconds.\npublic enum DUIDuration {");
  for (const [k, t] of Object.entries(duration)) out.push(`${doc(t, "    ")}    public static let ${k}: Double = ${ms(t.$value)}`);
  out.push("}\n");

  out.push("/// Easing curves as cubic-bezier control points, the same as the CSS.\npublic enum DUIEasing {");
  for (const [k, t] of Object.entries(easing)) {
    const [x1, y1, x2, y2] = t.$value;
    out.push(`${doc(t, "    ")}    public static let ${k}: (x1: Double, y1: Double, x2: Double, y2: Double) = (${x1}, ${y1}, ${x2}, ${y2})`);
  }
  out.push("}");

  return out.join("\n") + "\n";
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  const want = swiftTokens();
  if (process.argv.includes("--check")) {
    let have = "";
    try { have = readFileSync(swiftPath, "utf8"); } catch {}
    if (have !== want) {
      console.error("swift/Sources/DistinctUI/Tokens.generated.swift is out of date with tokens.json. Run `npm run tokens` and commit it.");
      process.exit(1);
    }
    console.log("Swift tokens match tokens.json.");
  } else {
    writeFileSync(swiftPath, want);
    console.log(`Wrote ${swiftPath.slice(root.length)}`);
  }
}
