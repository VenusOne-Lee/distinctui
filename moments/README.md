# Moments

The scenes on distinctui.com/moments: faux product UI in the distinctUI look, built with [Stageshow](https://benji.org/stageshow).

- `src/` the four scenes and the page mount. Each plays once when seen, with Play again; reduced motion shows the poster.
- `kit/` a copy of Stageshow's React kit from [benjitaylor/skills](https://github.com/benjitaylor/skills/tree/main/stageshow) (commit 8020574), MIT, copyright Benji Taylor; see `STAGESHOW-LICENSE`. Its brand registry is left out, since distinctUI moments never show other companies' logos. Everything else is unchanged.

`npm run build` bundles them into `dist/moments/` with esbuild. The look lives in `skills/distinctui/assets/stageshow-look.css`; the rules in `skills/distinctui/references/moments.md`. None of this ships in the npm package.
