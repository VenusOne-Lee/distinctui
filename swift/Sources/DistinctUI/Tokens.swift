import SwiftUI

// The tokens from references/tokens.md, by value. Black, white and six greys.
// Colour lives only on marks: see `DUIPalette` and `DUIMark`.

public extension Color {
    static let duiWhite = Color(duiHex: 0xFFFFFF)
    static let duiBlack = Color(duiHex: 0x000000)
    /// Notes and panels inside white.
    static let duiGrey50 = Color(duiHex: 0xFAFAFA)
    /// Image placeholders, pressed fills, the form problems banner, soft badges.
    static let duiGrey100 = Color(duiHex: 0xF2F2F2)
    /// Hairlines, card and field borders.
    static let duiGrey200 = Color(duiHex: 0xE5E5E5)
    /// Placeholders and decoration only (2.5:1), never words someone must read.
    static let duiGrey400 = Color(duiHex: 0xA3A3A3)
    /// Secondary text: meta, hints, lede (5.3:1 on white).
    static let duiGrey600 = Color(duiHex: 0x6B6B6B)
    /// Long body text inside cards.
    static let duiGrey800 = Color(duiHex: 0x262626)

    /// A colour from a hex value like the CSS, 0xRRGGBB.
    init(duiHex rgb: UInt32) {
        self.init(
            .sRGB,
            red: Double((rgb >> 16) & 0xFF) / 255,
            green: Double((rgb >> 8) & 0xFF) / 255,
            blue: Double(rgb & 0xFF) / 255,
            opacity: 1
        )
    }
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

    public var name: String { rawValue.prefix(1).uppercased() + rawValue.dropFirst() }

    /// Ring 1 to 5. Anything outside that wraps round, so a stored number never fails a screen.
    public func ring(_ number: Int) -> Color {
        Color(duiHex: hex[((number - 1) % 5 + 5) % 5])
    }
}

/// Corner radii. Pills for what you press or type one line into, soft corners for what you read.
public enum DUIRadius {
    public static let note: CGFloat = 12
    public static let input: CGFloat = 14
    public static let panel: CGFloat = 16
    public static let card: CGFloat = 22
    public static let menu: CGFloat = 24
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
    /// Side gutter on a phone. Use `x6` on iPad.
    public static let gutter: CGFloat = 16
    /// Between sections on a screen.
    public static let section: CGFloat = 40
}

/// Hairlines and the one black edge.
public enum DUIBorder {
    public static let hairline: CGFloat = 1
    /// Selected or important: feature card, status notice, focused or invalid field.
    public static let strong: CGFloat = 1.5
}

/// 150ms, opacity or colour only. Off when Reduce Motion is on.
public enum DUIMotion {
    public static let change = Animation.easeOut(duration: 0.15)
}

// MARK: - Environment

private struct SignatureKey: EnvironmentKey {
    static let defaultValue = Color.duiBlack
}

private struct PaletteKey: EnvironmentKey {
    static let defaultValue = DUIPalette.signal
}

public extension EnvironmentValues {
    /// The product's one colour. It only ever appears on the signature mark.
    var duiSignature: Color {
        get { self[SignatureKey.self] }
        set { self[SignatureKey.self] = newValue }
    }

    /// The palette marks pick their ring colours from when they're given a number.
    var duiPalette: DUIPalette {
        get { self[PaletteKey.self] }
        set { self[PaletteKey.self] = newValue }
    }
}

public extension View {
    /// Set once at the root: the light-only look, black tint for system controls,
    /// the signature colour and the ring palette.
    func distinctUI(signature: Color = .duiBlack, palette: DUIPalette = .signal) -> some View {
        modifier(DistinctUIRoot(signature: signature, palette: palette))
    }
}

private struct DistinctUIRoot: ViewModifier {
    let signature: Color
    let palette: DUIPalette

    func body(content: Content) -> some View {
        content
            .environment(\.duiSignature, signature)
            .environment(\.duiPalette, palette)
            .preferredColorScheme(.light)
            .tint(.duiBlack)
            .foregroundStyle(Color.duiBlack)
            .background(Color.duiWhite.ignoresSafeArea())
            .onAppear { DUIFont.register() }
    }
}
