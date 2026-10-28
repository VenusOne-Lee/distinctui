# Motion

Motion says what just changed. It never decorates. Add `assets/distinctui.js` (optional, no dependencies) and everything below works from attributes. Without it, the CSS classes still work if you add them yourself.

```html
<script src="distinctui.js" defer></script>
```

## Rules

1. **Only on a change.** Something appeared, opened, shipped or was filtered. Nothing loops, pulses or moves to get attention.
2. **Quick and quiet.** 120 to 480ms with `--dui-ease`. The one exception is going live: 700ms with one small pop.
3. **No lift.** Hover is still a black edge. Cards never rise, scale or cast a shadow. A pressed pill sinks to 97%.
4. **Once.** Reveals, ring draws and counters run the first time something is seen, not every time you scroll past.
5. **Off when asked.** With `prefers-reduced-motion: reduce`, everything lands in its final state and still works.

## The moves

| Move | Markup | What happens |
|---|---|---|
| Press | any `dui-btn*` or `dui-tab` | sinks to 97% while held |
| Reveal | `class="dui-reveal"`, stagger with `style="--dui-i:2"` | fades up 12px when seen |
| Ring draws in | `class="dui-mark dui-draw"`, or `dui-reveal-draw` on a parent | the ring sweeps round once, then the inside fades up |
| Goes live | `<button data-go-live="#mark-id">` on a `data-live="false"` mark | dashed turns solid, one pop, a ripple of the ring colour, the version label rises |
| Counts up | `<b data-count="128">128</b>` | counts from 0 when seen (en-ZA number format) |
| Bars grow | `class="dui-grow"` on the bar container, `--dui-i` on each bar | bars rise from the baseline |
| Filter | `data-filter-scope` on a wrapper, `data-filter="live"` on tabs, `data-tags="live beta"` on items, optional `data-filter-empty` | only matching items show and settle in, the empty state shows when nothing matches |
| Toggle | `<button aria-pressed="false" data-pressed-label="Following">Follow</button>` | flips state and label, the black pill turns outlined |
| Draw again | `<button data-replay="#group-id">` | redraws the rings inside the group |
| Menu | `details.dui-menu` | the panel fades and drops 4px |

## Don't

- Don't animate colour onto anything. Colour still only lives on rings.
- Don't use parallax, autoplaying carousels, marquee text, typewriter effects or spinning logos.
- Don't animate layout size (height, width) on content people are reading.
- Don't delay content behind motion: a reveal never hides something that's already on screen when the page loads, and nothing waits longer than half a second.
