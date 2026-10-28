// Builds the static site into dist/: the pages in site/, plus the skill's CSS
// and examples, so the site always shows exactly what the skill ships.
import { cpSync, rmSync, mkdirSync } from "node:fs";

rmSync("dist", { recursive: true, force: true });
mkdirSync("dist");
cpSync("site", "dist", { recursive: true });
cpSync("skills/distinctui/assets", "dist/assets", { recursive: true });
cpSync("skills/distinctui/examples", "dist/examples", { recursive: true });
console.log("Built dist/");
