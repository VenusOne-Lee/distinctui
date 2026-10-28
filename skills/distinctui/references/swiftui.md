# SwiftUI

distinctUI ships as a Swift package for iPhone and iPad apps (iOS 17 and later). It has the same tokens, type, pills and signature mark as the CSS, plus Bricolage Grotesque bundled under its Open Font Licence. The twelve rules in `SKILL.md` apply unchanged. This file says how they map to SwiftUI and what's different on a phone.

## Add the package

In Xcode: **File → Add Package Dependencies**, paste `https://github.com/VenusOne-Lee/distinctui`, add the `DistinctUI` library to the app target. `DistinctUIExamples` is optional: it holds the six example screens, useful to copy from, not to ship.

In `Package.swift`:

```swift
.package(url: "https://github.com/VenusOne-Lee/distinctui", branch: "main"),
// target dependency:
.product(name: "DistinctUI", package: "distinctui"),
```

If the project can't take a package, copy the files in `swift/Sources/DistinctUI/` and the font into the app, and list the font under `UIAppFonts`.

## Set it up once

```swift
import DistinctUI

@main
struct MakersApp: App {
    init() { DUIAppearance.apply() }   // Bricolage and black on the navigation and tab bars

    var body: some Scene {
        WindowGroup {
            RootView()
                .distinctUI(signature: Color(duiHex: 0x1F3BFF), palette: .signal)
        }
    }
}
```

`.distinctUI()` forces light mode, tints system controls black, sets a white background, registers the font and puts the signature colour and palette in the environment. Also set `UIUserInterfaceStyle` to `Light` in Info.plist so the launch screen and system sheets are light too.

## What maps to what

| Web | SwiftUI |
|---|---|
| `--dui-grey-600` and friends | `Color.duiGrey600`, or `.foregroundStyle(.duiGrey600)` |
| `--dui-signature` | `.distinctUI(signature:)`, read with `@Environment(\.duiSignature)` |
| `dui-palette-mzansi`, `data-ring="3"` | `.distinctUI(palette: .mzansi)`, `DUIMark(..., ring: 3)` |
| `dui-hero`, `dui-h1`, `dui-h2` with `<b>` | `DUITitle("Just", bold: "shipped", role: .h2)` |
| `dui-h3`, `dui-lede`, `dui-meta`, `dui-eyebrow` | `.duiText(.h3)`, `.duiText(.lede)`, `.duiText(.meta)`, `DUIEyebrow("Help wanted")` |
| any other size | `.duiFont(15, weight: .semibold, relativeTo: .subheadline)` |
| `dui-btn`, `dui-btn-ghost` | `.buttonStyle(.duiPrimary)`, `.buttonStyle(.duiSecondary)` |
| `dui-btn-sm`, `dui-btn-sm-ghost` | `.buttonStyle(.dui(size: .small))`, `.buttonStyle(.dui(.secondary, size: .small))` |
| `dui-link` | `.buttonStyle(.duiLink)` |
| `dui-count` | `.duiCount(3)` on the button |
| `dui-badge`, `-solid`, `-soft` | `DUIBadge("Live")`, `DUIBadge("Featured", style: .solid)`, `DUIBadge("v0.3", style: .soft)` |
| `dui-field` (pill) / `dui-input` | `DUITextField("Email", text: $email, shape: .pill)` / `shape: .soft` |
| `dui-textarea` | `DUITextField(..., multiline: true)` |
| `dui-label`, `dui-hint`, `dui-optional` | `DUIField("Name", hint: "…", optional: true) { … }` |
| `dui-field-error` | `DUIField(..., error: "…")` or `DUIFieldError("…")` |
| `dui-problems` | `DUIFormProblems(["Project name: add a name"]) { index in focus = … }` |
| `dui-check` | `Toggle(…).toggleStyle(.duiCheckbox)` |
| `dui-choice` | `DUIChoice("Beta", detail: "…", selected: stage == .beta) { … }` |
| `dui-card` | `.duiCard()`, or `.buttonStyle(.duiCard)` on a NavigationLink |
| `dui-feature` | `.duiFeature()` |
| `dui-note`, `dui-panel` | `.duiNote()`, `.duiPanel()` |
| `dui-card__media` | `DUIMedia(image)` |
| `dui-rows`, `dui-row` | `DUIRows { DUIRow { … } trailing: { … } }` |
| `dui-mark` | `DUIMark(initials: "ND", ring: 1, live: false, version: "v0.4", size: 64)` |
| photo mark | `DUIMark(photo: Image("naledi"), …)`, or `DUIMark(.remote(url, initials: "ND"))` |
| `dui-mark--empty` | `DUIMark.empty("+")` |
| `dui-mark--plain`, `--group` | `DUIMark(.initials("AK"), kind: .plain)`, `kind: .group` |
| `dui-mark__dot` | `DUIMark(..., dot: palette.ring(2))` |
| `dui-logo` | `DUILogo("makers")` |
| `dui-tabs` | `DUITabs(selection: $filter, options: [(value: "all", label: "All"), …])` |
| `dui-status` | `DUIStatus("You're signed in.")` |
| `dui-empty` | `DUIEmptyState("…", actionTitle: "Find makers") { … }` |
| `dui-container` | `.duiScreen()` (16pt gutter, white) |

Spacing, radii and borders are `DUISpace`, `DUIRadius` and `DUIBorder`. Colours set with `.duiText(role, colour:)` rather than a `.foregroundStyle` after it, which SwiftUI would ignore.

## Rules that change on a phone

1. **Light only, enforced.** `.distinctUI()` sets `.preferredColorScheme(.light)`. Don't add dark asset variants. Sheets, menus and alerts stay the system's, in light.
2. **System chrome stays native, in black.** Use `NavigationStack`, `TabView`, `.sheet`, `.confirmationDialog` and `Menu`. Don't rebuild them. `DUIAppearance.apply()` gives the bars Bricolage, a white background and a grey-200 hairline; selected tab icons are black, the rest grey-600. Don't tint bars or tab icons with the signature colour.
3. **Titles go in the content.** Use `.navigationBarTitleDisplayMode(.inline)` with a short title in the bar, and put the light-with-one-bold-word `DUITitle` at the top of the scroll view. Large system titles can't mix weights.
4. **Type sizes for a phone.** Hero 40, h1 34, h2 28, h3 24, h4 20, lede 18, body 17 (iOS's body size), rows 15, meta 14, eyebrow 13, badge 12. Every role scales with Dynamic Type. Test at the largest accessibility size: let rows wrap rather than truncate a name.
5. **Spacing for a phone.** 16pt side gutter (24 on iPad), 40pt between sections, 24pt inside a section, 20pt card padding, 16pt between cards. Respect the safe areas, never paint into them except the white background.
6. **Touch targets at least 44pt.** The pill styles have a 44pt minimum (38pt for small pills, which only go in rows of other controls). Rows are 44pt or taller.
7. **Switches are system switches tinted black.** Use `Toggle` with `.tint(.duiBlack)` for settings that apply straight away; use `.duiCheckbox` for ticks on a form you submit, like consent. Consent ticks start unticked.
8. **Icons are SF Symbols in black or grey-600,** at the weight of the text beside them. A symbol never carries the signature colour; only the mark does.
9. **Pressed, not hovered.** Buttons dim to 85% (primary) or fill grey-100 (outlined) while pressed. Cards that open something use `.buttonStyle(.duiCard)`: the edge goes black while pressed. No scale, no shadow, no spring.
10. **Motion.** 150ms ease-out on colour and opacity (`DUIMotion.change`). Check `@Environment(\.accessibilityReduceMotion)` before anything that moves. Haptics only for a real outcome (sent, saved), never on every tap.
11. **Marks scale by size.** Ring 6% of the size, gap 6%, version label 12% (10 to 20pt) over the bottom edge, earned dot 20%. Sizes: 20pt logo, 36 to 44pt in a card header, 48 to 64pt in a list, 120pt+ at the top of a profile. Photos load with `AsyncImage` through `.remote(url, initials:)`, showing the initials until the photo arrives.
12. **Errors are black and bold.** Never `Color.red`, never a red badge on a tab. The tab bar's own badge (`.badge(3)`) is red on iOS; use `.duiCount(3)` on a button instead, or leave the number off.

## Screens to copy

`swift/Sources/DistinctUIExamples/` has six screens. Run them with the demo app in `swift/Demo` (open `DistinctUIDemo.xcodeproj`, pick an iPhone simulator, Run).

| Screen | Use it for |
|---|---|
| `MakersExample` | A directory or feed: pill tabs, cards with marks, a note, an empty state |
| `ProfileExample` | A person: big mark with version, one black pill, build log rows, the help ask as the feature card, helpers with earned dots |
| `FormExample` | A long form after a failed submit: problems banner, field errors, choice cards, a consent tick |
| `SignInExample` | Sign in with an emailed link or a passkey, and the sent notice |
| `SettingsExample` | Profile rows, email switches, download your data, delete account |
| `ComponentsExample` | Every building block on one screen |

## Before you call it done

Run `review.md`, then these:

- [ ] Tried in the simulator with Dark Appearance on: still light.
- [ ] Tried at the largest Dynamic Type size: nothing clipped, rows wrap.
- [ ] VoiceOver reads each mark as a name and its state ("ND, not live yet, v0.4"), and each error as "Error: …".
- [ ] No `Color.red`, `.blue`, `.green` or `.accentColor` anywhere; system badges and swipe actions checked too (swipe actions default to red and blue: set `.tint(.duiBlack)`).
