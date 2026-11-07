# Review before you call it done

Go through every line. Fix what fails, then tell the user in one line what you checked.

Most of this is automated in `assets/check.js` (`checkHTML(html)`) and in `check_page` on the MCP server at `https://distinctui.com/mcp`. The checker finds the mechanical breaks: dark mode, colour off the mark, other fonts, shadows, radii, hover lifts, two black pills, red errors, vague buttons, missing labels and alt text. It can't judge white space, photos or tone, so still read this list.

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
- [ ] No shadows except on a floating menu or the date picker that opens under a field.
- [ ] Hover changes a border or opacity, never lifts or scales.

## Motion
- [ ] Anything that moves is saying what changed. Nothing loops or moves for attention.
- [ ] Durations come from the motion tokens (120 to 480ms; 700ms only for going live).
- [ ] With reduced motion turned on, the page still works and everything is in its final state.

## Mark
- [ ] The mark is dashed for anything not live yet, solid once live.
- [ ] Version labels read like `v0.3` or `v1.0`.
- [ ] A person without a signature of their own gets the plain black ring.

## Space and layout
- [ ] Sections have real breathing room (80px phone, 112px desktop).
- [ ] Only the hero is centred.
- [ ] Cards in a row are the same height; titles clamp at 2 lines, text at 3.
- [ ] Works at 375px with no sideways scroll; tap targets at least 44px.

## Photos, icons and charts
- [ ] No text on top of a photo, and no colour wash or gradient over one.
- [ ] Faces sit inside a mark, not as loose rectangles.
- [ ] Icons are Lucide outlines in the text colour, next to a word, with `aria-hidden="true"`.
- [ ] Charts are black and greys, two series at most, with the key number written out.

## Words
- [ ] Buttons say what happens.
- [ ] Errors name the field and the fix, in black with the "!" disc, and a banner at the top lists them with links.
- [ ] Optional fields say "(optional)".
- [ ] Empty states say what will appear and give one next step.
- [ ] Testimonials name a real-sounding person and place, in the plain black ring. No gold stars, no colour logos.

## Access
- [ ] Visible black focus ring on everything you can tab to.
- [ ] Images have alt text, or `alt=""` if they repeat nearby text.
- [ ] Consent and marketing ticks start unticked, one per purpose.
- [ ] Motion is off under `prefers-reduced-motion`.
- [ ] Nothing covers the page on arrival: no pop-up, no cookie wall. The cookie notice's main choice shares least, and nothing optional loads before a yes.
- [ ] Date fields keep the native input underneath; closed days say why.

## At work (apps with distinctui-work.css)
- [ ] Every list and table has loading, empty, no-results, error, no-access and read-only states.
- [ ] Each person has one ring colour everywhere; marks under 28px show photos, not initials.
- [ ] Single-key shortcuts have an off switch (`data-keys-toggle`), and every one is also a button or a command.
- [ ] IP addresses and places are `.dui-private`; dates are full, with the time zone.
- [ ] Bulk actions end in a notice with Undo, not a confirm dialog.
- [ ] Charts compare in black on one scale, and each can turn into its table.
