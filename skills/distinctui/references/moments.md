# Moments

A moment is a small product story: a believable distinctUI screen and one thing happening on it. A ring going live, a follower landing, an ask answered, a field fixed. Use one for a hero, a feature card, a docs page or a launch post. distinctui.com/moments shows four.

There are two ways to make one.

## 1. On a real page: the moves in distinctui.js

When the change really happens in your product, use the moves in `references/motion.md`: the mark going live, `data-roll` and `distinctUI.roll()` for numbers, `data-done` and `distinctUI.done()` for a button that confirms on itself, `distinctUI.add()` and `distinctUI.remove()` for rows. No framework needed.

## 2. As an illustration: Stageshow plus the distinctUI look

For faux product UI (a hero demo with a cursor, a feature card, a walkthrough), use [Stageshow](https://benji.org/stageshow) by Benji Taylor, MIT. It brings the stage, the cursor, framing, and motion parts that hold up frame by frame. distinctUI brings the look and these rules.

```bash
npx skills add benjitaylor/skills --skill stageshow
```

Copy Stageshow's `kit/` into the project, load `assets/distinctui.css` and `assets/stageshow-look.css`, then:

```tsx
<Stage className="dui-look" palette="mono" theme="light" play="once" poster={finished} ...>
```

Follow Stageshow's workflow (medium, look, story, start from a gold example, screen, choreograph, rehearse). Where the two disagree, distinctUI wins:

1. **Light, mono, the distinctUI look.** `className="dui-look"`, `palette="mono"`, `theme="light"`. Never `dark`, `blueprint` or `glass`, and no coloured `ground="orbs"`.
2. **Colour only on rings.** Put real `.dui-mark` elements inside the stage, so rings, dashes and version labels are the real thing. Status is said in words and weight: the look file makes success, danger and warning black. No sparkles, no purple, no letter-in-a-coloured-square logos.
3. **Once, not a loop.** distinctUI motion runs the first time something is seen. Use `play="once"`, offer a small `dui-btn-sm-ghost` "Play again" that remounts the stage, and hide it under reduced motion.
4. **The poster is the payoff.** Reduced motion, thumbnails and screenshots show `poster`. Make it the finished state, never a frame mid-change.
5. **One thing happens.** Three to five beats. Three to seven real strings (the title, the thing acted on, the result); filler bars for the rest.
6. **Every change moves.** Numbers roll with `Count` (only the changed digits), labels change in one footprint with `States`, rows arrive with `Collapse` and carry the rows below. Nothing swaps or pops.
7. **Black pills keep their footprint.** Idle, busy and done labels sit in one cell. A pressed pill sinks to 97%. Hover never lifts.
8. **Dashes fill in.** Going live is the mark's `dui-live-now` class: the solid ring fades in under a fading dashed copy, one pop. Never animate a border's style or a dashed edge's size.
9. **Frame it like a product shot.** The window fills at least two thirds of the stage and runs off one or two edges; only filler is cut, never a label or a control. No plate behind the window on a white page.
10. **Design at the size it's seen.** About 720 × 340 beside copy, 400 × 260 for a card, and a `compact` redesign at 358 wide for phones, not a squeeze.
11. **Invented people only.** Names and products are made up, as in `examples/`.

The four scenes on distinctui.com are in the repository's `moments/` folder, a good starting point to copy.

## Check before you hand it over

- Film it: the finished frame at full width and at 390, and step through every press and change.
- Reduced motion: the poster shows, with nothing to replay.
- `references/review.md` still passes on the page around it.
