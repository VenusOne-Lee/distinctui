import SwiftUI

/// Pill buttons. One black pill per group; the second action is outlined; the third is a text link.
public struct DUIButtonStyle: ButtonStyle {
    public enum Kind: Sendable { case primary, secondary, link }
    public enum Size: Sendable { case regular, small }

    var kind: Kind
    var size: Size
    var fullWidth: Bool

    @Environment(\.isEnabled) private var isEnabled

    public init(_ kind: Kind = .primary, size: Size = .regular, fullWidth: Bool = false) {
        self.kind = kind
        self.size = size
        self.fullWidth = fullWidth
    }

    public func makeBody(configuration: Configuration) -> some View {
        Group {
            if kind == .link {
                configuration.label
                    .duiFont(size == .small ? 14 : 16, weight: .medium, relativeTo: .subheadline)
                    .underline(true, pattern: .solid)
                    .foregroundStyle(Color.duiBlack)
                    .frame(minHeight: 44)
            } else {
                configuration.label
                    .duiFont(size == .small ? 14 : 16, weight: .semibold, relativeTo: size == .small ? .footnote : .callout)
                    .lineLimit(1)
                    .padding(.horizontal, size == .small ? 16 : 24)
                    .padding(.vertical, size == .small ? 9 : 13)
                    .frame(maxWidth: fullWidth ? .infinity : nil, minHeight: size == .small ? 38 : 48)
                    .foregroundStyle(kind == .primary ? Color.duiWhite : Color.duiBlack)
                    .background(Capsule().fill(fill(pressed: configuration.isPressed)))
                    .overlay(Capsule().strokeBorder(edge, lineWidth: 1))
                    .contentShape(Capsule())
            }
        }
        .opacity(isEnabled ? (kind == .primary && configuration.isPressed ? 0.85 : 1) : 0.4)
        .animation(DUIMotion.change, value: configuration.isPressed)
    }

    private func fill(pressed: Bool) -> Color {
        switch kind {
        case .primary: .duiBlack
        default: pressed ? .duiGrey100 : .duiWhite
        }
    }

    /// The big outlined pill has a black edge; the small one a grey hairline, like the CSS.
    private var edge: Color {
        switch (kind, size) {
        case (.primary, _), (.secondary, .regular): .duiBlack
        default: .duiGrey200
        }
    }
}

public extension ButtonStyle where Self == DUIButtonStyle {
    /// The black pill. One per group.
    static var duiPrimary: DUIButtonStyle { DUIButtonStyle(.primary) }
    /// The outlined pill, for the second action.
    static var duiSecondary: DUIButtonStyle { DUIButtonStyle(.secondary) }
    /// An underlined text link, for the third.
    static var duiLink: DUIButtonStyle { DUIButtonStyle(.link) }

    static func dui(_ kind: DUIButtonStyle.Kind = .primary, size: DUIButtonStyle.Size = .regular, fullWidth: Bool = false) -> DUIButtonStyle {
        DUIButtonStyle(kind, size: size, fullWidth: fullWidth)
    }
}

/// A number on a button or tab, only for things waiting on this person.
public struct DUICount: View {
    let count: Int

    public init(_ count: Int) {
        self.count = count
    }

    public var body: some View {
        Text(count > 99 ? "99+" : "\(count)")
            .font(DUIFont.fixed(11, weight: .bold))
            .monospacedDigit()
            .foregroundStyle(Color.duiWhite)
            .padding(.horizontal, 5)
            .frame(minWidth: 20, minHeight: 20)
            .background(Capsule().fill(Color.duiBlack))
            .overlay(Capsule().strokeBorder(Color.duiWhite, lineWidth: 2))
            .fixedSize()
            .accessibilityLabel("\(count) waiting")
    }
}

public extension View {
    /// Puts a `DUICount` on the top right corner, like `.dui-has-count`.
    func duiCount(_ count: Int) -> some View {
        overlay(alignment: .topTrailing) {
            if count > 0 { DUICount(count).offset(x: 6, y: -6) }
        }
    }
}
