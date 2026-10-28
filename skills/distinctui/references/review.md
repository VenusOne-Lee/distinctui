# Review before you call it done

Go through every line. Fix what fails, then tell the user in one line what you checked.

## Colour
- [ ] Only black, white and the six greys appear, apart from the signature mark.
- [ ] The signature colour appears only on the mark (and the logo ring, which is a mark).
- [ ] No coloured links, errors, success states, gradients or tinted backgrounds.
- [ ] No dark mode block, no `prefers-color-scheme: dark`, no dark section.
- [ ] Every word someone must read is grey-600 or darker.

## Type
- [ ] Bricolage Grotesque is loaded and used everywhere, including buttons and inputs (`font: inherit`).
- [ ] Section headings are weight 300 with one bold word, tracking around -0.035em.
- [ ] Body text stays under about 65 characters wide.
- [ ] Sentence case throughout.

## Shape
- [ ] Buttons, tabs, badges and one-line fields are pills.
- [ ] Cards are 22px, the feature card 28px, long-form inputs 14px.
- [ ] One black pill per group of actions; the rest are outlined or text links.
- [ ] No shadows except on a floating menu.
- [ ] Hover changes a border or opacity, never lifts or scales.

## Mark
- [ ] The mark is dashed for anything not live yet, solid once live.
- [ ] Version labels read like `v0.3` or `v1.0`.
- [ ] A person without a signature of their own gets the plain black ring.

## Space and layout
- [ ] Sections have real breathing room (80px phone, 112px desktop).
- [ ] Only the hero is centred.
- [ ] Cards in a row are the same height; titles clamp at 2 lines, text at 3.
- [ ] Works at 375px with no sideways scroll; tap targets at least 44px.

## Words
- [ ] Buttons say what happens.
- [ ] Errors name the field and the fix, in black with the "!" disc, and a banner at the top lists them with links.
- [ ] Optional fields say "(optional)".
- [ ] Empty states say what will appear and give one next step.

## Access
- [ ] Visible black focus ring on everything you can tab to.
- [ ] Images have alt text, or `alt=""` if they repeat nearby text.
- [ ] Consent and marketing ticks start unticked, one per purpose.
- [ ] Motion is off under `prefers-reduced-motion`.
