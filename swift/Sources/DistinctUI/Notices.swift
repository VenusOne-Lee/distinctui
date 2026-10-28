import SwiftUI

/// Tabs are pills: the open one black, the rest outlined. Scrolls sideways when they don't fit.
public struct DUITabs<Value: Hashable>: View {
    @Binding var selection: Value
    let options: [(value: Value, label: String)]

    public init(selection: Binding<Value>, options: [(value: Value, label: String)]) {
        self._selection = selection
        self.options = options
    }

    public var body: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: DUISpace.x2) {
                ForEach(options.indices, id: \.self) { index in
                    let option = options[index]
                    let open = option.value == selection
                    Button {
                        selection = option.value
                    } label: {
                        Text(option.label)
                            .duiFont(14, weight: .semibold, relativeTo: .footnote)
                            .foregroundStyle(open ? Color.duiWhite : Color.duiBlack)
                            .padding(.horizontal, 16)
                            .frame(minHeight: 38)
                            .background(Capsule().fill(open ? Color.duiBlack : Color.duiWhite))
                            .overlay(Capsule().strokeBorder(open ? Color.duiBlack : Color.duiGrey200, lineWidth: 1))
                            .contentShape(Capsule())
                    }
                    .buttonStyle(.plain)
                    .accessibilityAddTraits(open ? [.isButton, .isSelected] : .isButton)
                }
            }
            .padding(.vertical, 3)
        }
        .scrollClipDisabled()
    }
}

/// Something just happened ("You're signed in"): a black outline and a tick.
public struct DUIStatus: View {
    let message: String

    public init(_ message: String) {
        self.message = message
    }

    public var body: some View {
        HStack(spacing: 12) {
            Image(systemName: "checkmark")
                .font(.system(size: 11, weight: .bold))
                .foregroundStyle(Color.duiWhite)
                .frame(width: 24, height: 24)
                .background(Circle().fill(Color.duiBlack))
                .accessibilityHidden(true)
            Text(message)
                .duiFont(15, relativeTo: .subheadline)
                .foregroundStyle(Color.duiBlack)
                .fixedSize(horizontal: false, vertical: true)
            Spacer(minLength: 0)
        }
        .padding(.horizontal, 20)
        .padding(.vertical, 12)
        .background(RoundedRectangle(cornerRadius: DUIRadius.menu, style: .continuous).fill(Color.duiWhite))
        .overlay(RoundedRectangle(cornerRadius: DUIRadius.menu, style: .continuous).strokeBorder(Color.duiBlack, lineWidth: DUIBorder.strong))
        .accessibilityElement(children: .combine)
    }
}

/// Nothing here yet: say what will show up, and give the one next step.
public struct DUIEmptyState: View {
    let message: String
    let actionTitle: String?
    let action: (() -> Void)?

    public init(_ message: String, actionTitle: String? = nil, action: (() -> Void)? = nil) {
        self.message = message
        self.actionTitle = actionTitle
        self.action = action
    }

    public var body: some View {
        let shape = RoundedRectangle(cornerRadius: DUIRadius.card, style: .continuous)
        VStack(spacing: DUISpace.x4) {
            DUIMark.empty("+", size: 56)
            Text(message)
                .duiText(.body, colour: .duiGrey600)
                .multilineTextAlignment(.center)
                .fixedSize(horizontal: false, vertical: true)
            if let actionTitle, let action {
                Button(actionTitle, action: action).buttonStyle(.dui(.secondary, size: .small))
            }
        }
        .padding(.horizontal, DUISpace.x6)
        .padding(.vertical, DUISpace.x10)
        .frame(maxWidth: .infinity)
        .overlay(shape.strokeBorder(Color.duiGrey400, style: StrokeStyle(lineWidth: 1, dash: [4, 4])))
    }
}

/// A section's eyebrow: small, upper case, grey.
public struct DUIEyebrow: View {
    let text: String

    public init(_ text: String) {
        self.text = text
    }

    public var body: some View {
        Text(text).duiText(.eyebrow)
    }
}

public extension View {
    /// Screen-level layout: the 16pt side gutter and a white background.
    func duiScreen() -> some View {
        self
            .padding(.horizontal, DUISpace.gutter)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(Color.duiWhite)
    }
}
