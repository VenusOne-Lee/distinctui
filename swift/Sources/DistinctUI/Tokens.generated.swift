// Generated from skills/distinctui/assets/tokens.json by scripts/swift-tokens.mjs.
// Don't edit by hand: change tokens.json, then run `npm run tokens`.
// The build fails if this file and tokens.json disagree.

import CoreGraphics
import SwiftUI

public extension Color {
    /// Page background, text on black.
    static let duiWhite = Color(duiHex: 0xFFFFFF)
    /// Text, primary buttons, focus ring, important borders.
    static let duiBlack = Color(duiHex: 0x000000)
    /// Notes and panels inside white.
    static let duiGrey50 = Color(duiHex: 0xFAFAFA)
    /// Image placeholders, hover and pressed fills, the form problems banner, soft badges.
    static let duiGrey100 = Color(duiHex: 0xF2F2F2)
    /// Hairlines, card and input borders.
    static let duiGrey200 = Color(duiHex: 0xE5E5E5)
    /// Placeholders and decoration only (2.5:1), never words someone must read.
    static let duiGrey400 = Color(duiHex: 0xA3A3A3)
    /// Secondary text: meta, hints, lede (5.3:1 on white).
    static let duiGrey600 = Color(duiHex: 0x6B6B6B)
    /// Long body text inside cards.
    static let duiGrey800 = Color(duiHex: 0x262626)
}

public extension ShapeStyle where Self == Color {
    static var duiWhite: Color { .duiWhite }
    static var duiBlack: Color { .duiBlack }
    static var duiGrey50: Color { .duiGrey50 }
    static var duiGrey100: Color { .duiGrey100 }
    static var duiGrey200: Color { .duiGrey200 }
    static var duiGrey400: Color { .duiGrey400 }
    static var duiGrey600: Color { .duiGrey600 }
    static var duiGrey800: Color { .duiGrey800 }
}

/// Five ring colours for when each person or project gets its own mark. The fifth is always black.
/// Pick one palette per product and keep it. These colours go on marks and earned dots, nowhere else.
public enum DUIPalette: String, CaseIterable, Sendable {
    case signal, mzansi, earth, coast, jacaranda

    /// Rings 1 to 5, in order.
    public var hex: [UInt32] {
        switch self {
        case .signal: [0x1F3BFF, 0xFF4F1F, 0x7A2CFF, 0xE8A800, 0x000000]
        case .mzansi: [0xE03C31, 0x001489, 0xFFB81C, 0x007749, 0x000000]
        case .earth: [0xB5452B, 0xC8860A, 0x6B3E26, 0x2E3A87, 0x000000]
        case .coast: [0x0067A5, 0xFF5A4E, 0x00706B, 0xD9A400, 0x000000]
        case .jacaranda: [0x6F3FC7, 0xC2185B, 0x2F80ED, 0xF26B1D, 0x000000]
        }
    }
}

/// Corner radii. Pills for what you press or type one line into, soft corners for what you read.
/// Pills use `Capsule()`.
public enum DUIRadius {
    /// Inputs on longer forms.
    public static let input: CGFloat = 14
    /// Grey note inside a card.
    public static let note: CGFloat = 12
    /// Panels, choice cards, problems banner.
    public static let panel: CGFloat = 16
    /// Cards.
    public static let card: CGFloat = 22
    /// Floating menu, status notice.
    public static let menu: CGFloat = 24
    /// The one feature card.
    public static let feature: CGFloat = 28
}

/// The 4pt spacing scale.
public enum DUISpace {
    public static let x1: CGFloat = 4
    public static let x2: CGFloat = 8
    public static let x3: CGFloat = 12
    public static let x4: CGFloat = 16
    public static let x5: CGFloat = 20
    public static let x6: CGFloat = 24
    public static let x8: CGFloat = 32
    public static let x10: CGFloat = 40
    public static let x14: CGFloat = 56
    public static let x20: CGFloat = 80
    public static let x28: CGFloat = 112
}

/// Hairlines and the one black edge.
public enum DUIBorder {
    /// Hairlines, in grey-200.
    public static let hairline: CGFloat = 1
    /// The black edge: feature card, status, invalid field.
    public static let strong: CGFloat = 1.5
}

/// Motion durations in seconds.
public enum DUIDuration {
    /// Press, hover.
    public static let fast: Double = 0.12
    /// Tabs, menus, fields.
    public static let base: Double = 0.2
    /// Reveals, rings drawing in.
    public static let slow: Double = 0.48
    /// Dashed to solid, going live.
    public static let live: Double = 0.7
}

/// Easing curves as cubic-bezier control points, the same as the CSS.
public enum DUIEasing {
    /// Default: settle.
    public static let settle: (x1: Double, y1: Double, x2: Double, y2: Double) = (0.2, 0, 0, 1)
    /// Going live only.
    public static let pop: (x1: Double, y1: Double, x2: Double, y2: Double) = (0.3, 1.5, 0.5, 1)
}
