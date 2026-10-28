import CoreText
import SwiftUI
import UIKit

/// Bricolage Grotesque, bundled with the package (SIL Open Font Licence, see Resources/OFL.txt).
/// The font is variable: weight 200 to 800 and optical size 12 to 96, set as axes.
public enum DUIFont {
    /// The PostScript name of the font's default instance, found once it's registered.
    public private(set) static var postScriptName: String?

    private static let registration: Void = {
        guard let url = Bundle.module.url(forResource: "BricolageGrotesque", withExtension: "ttf") else { return }
        CTFontManagerRegisterFontsForURL(url as CFURL, .process, nil)
        if let descriptors = CTFontManagerCreateFontDescriptorsFromURL(url as CFURL) as? [CTFontDescriptor],
           let first = descriptors.first {
            postScriptName = CTFontDescriptorCopyAttribute(first, kCTFontNameAttribute) as? String
        }
    }()

    /// Registers the font. Safe to call more than once; `.distinctUI()` calls it for you.
    public static func register() {
        _ = registration
    }

    /// Bricolage at an exact size and weight, for text that has to fit a shape, like initials in a mark.
    public static func fixed(_ size: CGFloat, weight: Font.Weight = .regular) -> Font {
        guard let font = uiFont(size, weight: weight) else { return .system(size: size, weight: weight) }
        return Font(font as CTFont)
    }

    /// Bricolage as a UIFont, for UIKit pieces such as the navigation bar.
    public static func uiFont(_ size: CGFloat, weight: Font.Weight = .regular) -> UIFont? {
        register()
        guard let name = postScriptName, let base = UIFont(name: name, size: size) else { return nil }
        let axes: [NSNumber: NSNumber] = [
            NSNumber(value: weightAxis): NSNumber(value: Double(axisValue(weight))),
            NSNumber(value: opticalSizeAxis): NSNumber(value: Double(min(max(size, 12), 96))),
        ]
        let variation = UIFontDescriptor.AttributeName(rawValue: kCTFontVariationAttribute as String)
        return UIFont(descriptor: base.fontDescriptor.addingAttributes([variation: axes]), size: size)
    }

    private static let weightAxis: UInt32 = 0x7767_6874 // "wght"
    private static let opticalSizeAxis: UInt32 = 0x6F70_737A // "opsz"

    private static func axisValue(_ weight: Font.Weight) -> CGFloat {
        switch weight {
        case .ultraLight, .thin: 200
        case .light: 300
        case .regular: 400
        case .medium: 500
        case .semibold: 600
        case .bold: 700
        default: 800
        }
    }
}

/// The type scale on a phone. The web sizes, with body at iOS's 17pt.
public enum DUITextRole: CaseIterable, Sendable {
    case hero, h1, h2, h3, h4, lede, body, row, meta, eyebrow, badge

    public var size: CGFloat {
        switch self {
        case .hero: 40
        case .h1: 34
        case .h2: 28
        case .h3: 24
        case .h4: 20
        case .lede: 18
        case .body: 17
        case .row: 15
        case .meta: 14
        case .eyebrow: 13
        case .badge: 12
        }
    }

    /// Hero, h1 and h2 are light, with one bold word set through `DUITitle`.
    public var weight: Font.Weight {
        switch self {
        case .hero, .h1, .h2: .light
        case .h3, .h4: .bold
        case .eyebrow, .badge: .medium
        default: .regular
        }
    }

    /// Letter spacing as a share of the size.
    public var tracking: CGFloat {
        switch self {
        case .hero, .h1: -0.04
        case .h2: -0.035
        case .h3: -0.03
        case .h4: -0.02
        case .eyebrow: 0.08
        default: 0
        }
    }

    public var colour: Color {
        switch self {
        case .lede, .meta, .eyebrow: .duiGrey600
        default: .duiBlack
        }
    }

    /// The text style its size scales with under Dynamic Type.
    var textStyle: UIFont.TextStyle {
        switch self {
        case .hero, .h1: .largeTitle
        case .h2: .title1
        case .h3: .title2
        case .h4: .title3
        case .lede, .body: .body
        case .row: .subheadline
        case .meta, .eyebrow: .footnote
        case .badge: .caption1
        }
    }
}

public extension View {
    /// Sets text in a distinctUI role: Bricolage at the role's size, weight, tracking and colour,
    /// scaled with Dynamic Type.
    /// Pass `colour` to change it: a `.foregroundStyle` added after this one won't win.
    func duiText(_ role: DUITextRole, weight: Font.Weight? = nil, colour: Color? = nil) -> some View {
        modifier(DUITextModifier(role: role, weight: weight, colour: colour))
    }

    /// Bricolage at a size, scaled with Dynamic Type like the nearest text style.
    func duiFont(_ size: CGFloat, weight: Font.Weight = .regular, relativeTo style: UIFont.TextStyle = .body) -> some View {
        modifier(DUIFontModifier(size: size, weight: weight, style: style))
    }
}

struct DUITextModifier: ViewModifier {
    let role: DUITextRole
    let weight: Font.Weight?
    let colour: Color?
    @Environment(\.dynamicTypeSize) private var dynamicType

    func body(content: Content) -> some View {
        let size = DUIScale.scaled(role.size, style: role.textStyle, dynamicType: dynamicType)
        content
            .font(DUIFont.fixed(size, weight: weight ?? role.weight))
            .tracking(role.tracking * size)
            .foregroundStyle(colour ?? role.colour)
            .textCase(role == .eyebrow ? .uppercase : nil)
    }
}

struct DUIFontModifier: ViewModifier {
    let size: CGFloat
    let weight: Font.Weight
    let style: UIFont.TextStyle
    @Environment(\.dynamicTypeSize) private var dynamicType

    func body(content: Content) -> some View {
        content.font(DUIFont.fixed(DUIScale.scaled(size, style: style, dynamicType: dynamicType), weight: weight))
    }
}

enum DUIScale {
    static func scaled(_ size: CGFloat, style: UIFont.TextStyle, dynamicType: DynamicTypeSize) -> CGFloat {
        let traits = UITraitCollection(preferredContentSizeCategory: category(dynamicType))
        return UIFontMetrics(forTextStyle: style).scaledValue(for: size, compatibleWith: traits)
    }

    private static func category(_ size: DynamicTypeSize) -> UIContentSizeCategory {
        switch size {
        case .xSmall: .extraSmall
        case .small: .small
        case .medium: .medium
        case .large: .large
        case .xLarge: .extraLarge
        case .xxLarge: .extraExtraLarge
        case .xxxLarge: .extraExtraExtraLarge
        case .accessibility1: .accessibilityMedium
        case .accessibility2: .accessibilityLarge
        case .accessibility3: .accessibilityExtraLarge
        case .accessibility4: .accessibilityExtraExtraLarge
        case .accessibility5: .accessibilityExtraExtraExtraLarge
        @unknown default: .large
        }
    }
}

/// A heading in two weights: light for the line, bold for the word that carries the meaning.
///
///     DUITitle("Just", bold: "shipped")          // Just **shipped**
///     DUITitle("Naledi is building", bold: "Spaza Stock", role: .h1)
///     DUITitle("", bold: "Settings", after: " and data", role: .h2)
public struct DUITitle: View {
    let before: String
    let bold: String
    let after: String
    let role: DUITextRole

    @Environment(\.dynamicTypeSize) private var dynamicType

    public init(_ before: String, bold: String, after: String = "", role: DUITextRole = .h1) {
        self.before = before
        self.bold = bold
        self.after = after
        self.role = role
    }

    public var body: some View {
        let size = DUIScale.scaled(role.size, style: role.textStyle, dynamicType: dynamicType)
        let light = DUIFont.fixed(size, weight: .light)
        let heavy = DUIFont.fixed(size, weight: .bold)
        // Spaces go in the light parts so they're set at the title's size.
        let lead = before.isEmpty ? "" : before + " "
        return Text("\(Text(lead).font(light))\(Text(bold).font(heavy))\(Text(after).font(light))")
            .tracking(role.tracking * size)
            .lineSpacing(0)
            .foregroundStyle(Color.duiBlack)
            .fixedSize(horizontal: false, vertical: true)
            .accessibilityAddTraits(.isHeader)
    }
}
