import SwiftUI

// The token values (colours, ring palettes, radii, space, borders, durations, easing) are in
// Tokens.generated.swift, written from skills/distinctui/assets/tokens.json by
// scripts/swift-tokens.mjs. Change tokens.json, not that file. This file holds the Swift-only
// parts built on top of them. Colour lives only on marks: see `DUIPalette` and `DUIMark`.

public extension Color {
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

public extension DUIPalette {
    var name: String { rawValue.prefix(1).uppercased() + rawValue.dropFirst() }

    /// Ring 1 to 5. Anything outside that wraps round, so a stored number never fails a screen.
    func ring(_ number: Int) -> Color {
        Color(duiHex: hex[((number - 1) % 5 + 5) % 5])
    }
}

public extension DUISpace {
    /// Side gutter on a phone. Use `x6` on iPad.
    static let gutter: CGFloat = x4
    /// Between sections on a screen.
    static let section: CGFloat = x10
}

/// Motion tokens from references/motion.md. One easing curve for everything, a spring only
/// for going live. Check `accessibilityReduceMotion` and drop movement when it's on.
public enum DUIMotion {
    private static func settle(_ duration: Double) -> Animation {
        let e = DUIEasing.settle
        return .timingCurve(e.x1, e.y1, e.x2, e.y2, duration: duration)
    }

    /// 120ms: press and highlight.
    public static let fast = settle(DUIDuration.fast)
    /// 200ms: tabs, menus, fields.
    public static let base = settle(DUIDuration.base)
    /// 480ms: reveals and a ring drawing in.
    public static let slow = settle(DUIDuration.slow)
    /// 700ms: a mark going from dashed to solid, with a small pop. SwiftUI uses a spring
    /// for the web's `pop` curve, so it settles naturally if it's interrupted.
    public static let live = Animation.spring(duration: DUIDuration.live, bounce: 0.25)
    /// What a pressed button scales to. Cards never scale.
    public static let pressScale: CGFloat = 0.97

    /// Kept for state changes like a field edge; same as `base`.
    public static let change = base
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
