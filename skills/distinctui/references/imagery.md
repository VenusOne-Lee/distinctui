# Photos, icons and charts

The page is black, white and grey, so anything with colour in it gets noticed. These rules keep photos, icons and charts from competing with the signature mark.

## Photos

- **Photos can be in colour.** They're content, not decoration. Natural light and real places work best. Avoid stock-photo poses and heavy filters.
- **No colour washes.** Don't tint photos with the signature colour, and don't add duotones, gradients or overlays.
- **Text never sits on a photo.** Put the words beside the photo or under it. If a hero needs a photo, use a split layout, not a background image.
- **Corners match what holds them.** In a card the photo takes the card's radius. On its own it gets 22px, or 28px for a big one at the top of a page. One-line thumbnails (in a table or order summary) get 12px.
- **Fixed shapes.** 3:2 for scenes, 16:10 for card media, 1:1 for products and faces, 21:9 for a wide band on desktop. Crop with `object-fit: cover`.
- **Products on light grey.** Shoot or generate products on a seamless `#f2f2f2` background, so they sit on the page's grey-100 without a box.
- **Faces go in the mark.** A person's photo is cropped square around the face and placed inside their ring. Never show a face as a loose rectangle where a mark would do.
- **Screenshots get a hairline.** Give a screenshot a 1px grey-200 border so its white edges don't merge into the page.
- **Alt text says what's in it.** If a nearby heading already says it, use `alt=""`.
- **Real people need consent.** In examples, demos and seed data, use invented people and AI-generated photos, never someone's real face without their say-so.

## Icons

- **Lucide outlines only.** `assets/icons.svg` holds 25 of them as one sprite. Need another? Copy it from [lucide.dev](https://lucide.dev) into the sprite as a `<symbol>` (ISC licence).
- **Black, 1.75 stroke, at text size.** Use `class="dui-icon"`. It draws in `currentColor`, so it takes the text colour. Never fill an icon, and never colour one.
- **Next to a word.** An icon helps a label, it doesn't replace it. The exceptions are a close ×, a menu, plus and minus in a quantity stepper, and a search button. Those get `aria-label` and use the round `dui-icon-btn`.
- **Decorative icons are hidden.** Add `aria-hidden="true"` to every icon that sits beside a word.
- **One per thing.** Don't put icons on every row of a list or every card title. Use them where they help someone scan: delivery options, contact methods, file actions.

```html
<svg class="dui-icon" aria-hidden="true"><use href="icons.svg#truck"/></svg> Courier anywhere in SA

<button class="dui-icon-btn" type="button" aria-label="Close">
  <svg class="dui-icon" aria-hidden="true"><use href="icons.svg#x"/></svg>
</button>
```

The set: search, arrow-right, arrow-left, check, x, plus, minus, mail, message, map-pin, clock, lock, bag, truck, copy, external, download, refresh, home, bell, settings, user, calendar, phone, play.

## Charts

- **Black and greys only.** The series you care about is black. Comparison series are grey-400 and grey-200. Never use the signature colour or ring colours in a chart, unless a series stands for one person's ring.
- **Two series at most.** More than two? Use small multiples, one small chart per series, or a table.
- **Quiet grid.** Horizontal grid lines in grey-200, no vertical lines, no box around the chart. Axis labels 12 to 14px in grey-600, numbers in tabular figures.
- **Label the line, not a legend.** Put the series name at the end of its line.
- **Bars with soft tops.** Bars get an 8px radius at the top and sit on the baseline. Leave a gap between bars of at least half a bar's width.
- **Say the number too.** Write the key number above the chart in words ("1,204 counts this week, up 12%"), so nobody has to read it off an axis.
- **A table is often better.** When people need exact values, use `dui-table` with numbers right-aligned.
- **No 3D, gradients or pie charts.** For parts of a whole, use one stacked bar or a list with percentages.
