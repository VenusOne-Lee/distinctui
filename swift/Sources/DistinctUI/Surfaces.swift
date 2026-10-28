import SwiftUI

// Cards, notes, panels, rows and badges: soft corners for what you read.
// White space first, a grey-200 hairline second, never a shadow.

public extension View {
    /// A card: white, a grey-200 hairline, 22pt corners, 20pt padding.
    func duiCard(padding: CGFloat = DUISpace.x5) -> some View {
        modifier(DUISurface(radius: DUIRadius.card, edge: .duiGrey200, width: DUIBorder.hairline, fill: .duiWhite, padding: padding))
    }

    /// The one card on a screen that matters most: a black 1.5pt edge, 28pt corners.
    func duiFeature(padding: CGFloat = DUISpace.x6) -> some View {
        modifier(DUISurface(radius: DUIRadius.feature, edge: .duiBlack, width: DUIBorder.strong, fill: .duiWhite, padding: padding))
    }

    /// A grey panel for supporting content: tips, a summary.
    func duiPanel(padding: CGFloat = DUISpace.x5) -> some View {
        modifier(DUISurface(radius: DUIRadius.panel, edge: nil, width: 0, fill: .duiGrey50, padding: padding))
    }

    /// A grey note inside a card: "Needs: testers".
    func duiNote() -> some View {
        self
            .duiFont(14, relativeTo: .footnote)
            .foregroundStyle(Color.duiBlack)
            .padding(.horizontal, 12)
            .padding(.vertical, 10)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(RoundedRectangle(cornerRadius: DUIRadius.note, style: .continuous).fill(Color.duiGrey50))
    }
}

struct DUISurface: ViewModifier {
    let radius: CGFloat
    let edge: Color?
    let width: CGFloat
    let fill: Color
    let padding: CGFloat

    func body(content: Content) -> some View {
        let shape = RoundedRectangle(cornerRadius: radius, style: .continuous)
        content
            .padding(padding)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(shape.fill(fill))
            .overlay {
                if let edge { shape.strokeBorder(edge, lineWidth: width) }
            }
            .contentShape(shape)
    }
}

/// For a card that opens something (a NavigationLink or Button): the edge goes black while pressed.
/// No lift, no scale, no shadow.
public struct DUICardButtonStyle: ButtonStyle {
    public init() {}

    public func makeBody(configuration: Configuration) -> some View {
        let shape = RoundedRectangle(cornerRadius: DUIRadius.card, style: .continuous)
        configuration.label
            .padding(DUISpace.x5)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(shape.fill(Color.duiWhite))
            .overlay(shape.strokeBorder(configuration.isPressed ? Color.duiBlack : Color.duiGrey200, lineWidth: 1))
            .contentShape(shape)
            .animation(DUIMotion.change, value: configuration.isPressed)
    }
}

public extension ButtonStyle where Self == DUICardButtonStyle {
    static var duiCard: DUICardButtonStyle { DUICardButtonStyle() }
}

/// A picture inside a card or listing, grey until it arrives, 16:10 unless you say otherwise.
public struct DUIMedia: View {
    let image: Image?
    let aspect: CGFloat

    public init(_ image: Image?, aspect: CGFloat = 16 / 10) {
        self.image = image
        self.aspect = aspect
    }

    public var body: some View {
        let shape = RoundedRectangle(cornerRadius: DUIRadius.panel, style: .continuous)
        Color.duiGrey100
            .aspectRatio(aspect, contentMode: .fit)
            .overlay(alignment: .top) {
                if let image { image.resizable().scaledToFill() }
            }
            .clipShape(shape)
            .overlay(shape.strokeBorder(Color.duiGrey200, lineWidth: 1))
    }
}

/// A badge. Outline for a status (Live, Beta), solid for a highlight (Featured), soft for a version (v0.3).
public struct DUIBadge: View {
    public enum Style: Sendable { case outline, solid, soft }

    let text: String
    let style: Style

    public init(_ text: String, style: Style = .outline) {
        self.text = text
        self.style = style
    }

    public var body: some View {
        Text(text)
            .font(DUIFont.fixed(style == .soft ? 13 : 12, weight: .medium))
            .monospacedDigit()
            .lineLimit(1)
            .foregroundStyle(style == .solid ? Color.duiWhite : Color.duiBlack)
            .padding(.horizontal, 10)
            .padding(.vertical, style == .soft ? 4 : 2)
            .background(Capsule().fill(style == .solid ? Color.duiBlack : style == .soft ? Color.duiGrey100 : Color.duiWhite))
            .overlay {
                if style == .outline { Capsule().strokeBorder(Color.duiGrey400, lineWidth: 1) }
            }
            .fixedSize()
    }
}

/// A list of rows with hairlines between them, like `.dui-rows`.
public struct DUIRows<Content: View>: View {
    let content: Content

    public init(@ViewBuilder content: () -> Content) {
        self.content = content()
    }

    public var body: some View {
        VStack(spacing: 0) {
            Rectangle().fill(Color.duiGrey200).frame(height: 1)
            content
        }
    }
}

/// One row: what's on the left, what's on the right, a hairline under it.
public struct DUIRow<Leading: View, Trailing: View>: View {
    let leading: Leading
    let trailing: Trailing

    public init(@ViewBuilder leading: () -> Leading, @ViewBuilder trailing: () -> Trailing) {
        self.leading = leading()
        self.trailing = trailing()
    }

    public var body: some View {
        VStack(spacing: 0) {
            HStack(alignment: .center, spacing: DUISpace.x4) {
                leading
                Spacer(minLength: DUISpace.x2)
                trailing
            }
            .padding(.vertical, DUISpace.x4)
            .frame(minHeight: 44)
            Rectangle().fill(Color.duiGrey200).frame(height: 1)
        }
        .contentShape(Rectangle())
    }
}

public extension DUIRow where Trailing == EmptyView {
    init(@ViewBuilder leading: () -> Leading) {
        self.init(leading: leading, trailing: { EmptyView() })
    }
}

/// A grey chevron for rows that open something. The only icon colour is black or grey.
public struct DUIChevron: View {
    public init() {}

    public var body: some View {
        Image(systemName: "chevron.right")
            .font(.system(size: 13, weight: .semibold))
            .foregroundStyle(Color.duiGrey400)
            .accessibilityHidden(true)
    }
}
