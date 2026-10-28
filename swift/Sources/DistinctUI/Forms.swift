import SwiftUI

// Fields, ticks and choices. Errors are black and bold with a "!" disc, never red.

/// A field you type into. `.pill` for one line on its own (email, search); `.soft` for fields
/// on a longer form, and for anything multi-line. `wraps` lets a one-line answer grow to three lines.
/// The edge goes black when focused or invalid.
public struct DUITextField: View {
    public enum Shape: Sendable { case pill, soft }

    let title: String
    @Binding var text: String
    let prompt: String?
    let shape: Shape
    let invalid: Bool
    let multiline: Bool
    let wraps: Bool

    @FocusState private var focused: Bool

    public init(_ title: String, text: Binding<String>, prompt: String? = nil, shape: Shape = .soft, invalid: Bool = false, multiline: Bool = false, wraps: Bool = false) {
        self.title = title
        self._text = text
        self.prompt = prompt
        self.shape = multiline ? .soft : shape
        self.invalid = invalid
        self.multiline = multiline
        self.wraps = wraps
    }

    public var body: some View {
        field
            .duiFont(17, relativeTo: .body)
            .foregroundStyle(Color.duiBlack)
            .tint(.duiBlack)
            .focused($focused)
            .padding(.horizontal, shape == .pill ? 20 : 16)
            .padding(.vertical, 13)
            .frame(minHeight: multiline ? 112 : 48, alignment: multiline ? .topLeading : .leading)
            .duiFieldEdge(shape: shape, focused: focused, invalid: invalid)
            .contentShape(Rectangle())
            .onTapGesture { focused = true }
            .accessibilityValue(invalid ? "Needs fixing" : "")
    }

    @ViewBuilder
    private var field: some View {
        let placeholder = prompt.map { Text($0).foregroundStyle(Color.duiGrey400) }
        if multiline {
            TextField(title, text: $text, prompt: placeholder, axis: .vertical)
                .lineLimit(4...)
        } else if wraps {
            // One line of meaning that may run long, like a tagline: grows to three lines.
            TextField(title, text: $text, prompt: placeholder, axis: .vertical)
                .lineLimit(1...3)
        } else {
            TextField(title, text: $text, prompt: placeholder)
        }
    }
}

public extension View {
    /// The field edge for your own controls: grey hairline, black and 1.5pt when focused or invalid.
    func duiFieldEdge(shape: DUITextField.Shape = .soft, focused: Bool = false, invalid: Bool = false) -> some View {
        let strong = focused || invalid
        return background(
            RoundedRectangle(cornerRadius: shape == .pill ? 999 : DUIRadius.input, style: .continuous)
                .fill(Color.duiWhite)
        )
        .overlay(
            RoundedRectangle(cornerRadius: shape == .pill ? 999 : DUIRadius.input, style: .continuous)
                .strokeBorder(strong ? Color.duiBlack : Color.duiGrey200, lineWidth: strong ? DUIBorder.strong : DUIBorder.hairline)
        )
        .animation(DUIMotion.change, value: strong)
    }
}

/// A labelled field: label, "optional" if it is, a grey hint, the control, then the error.
public struct DUIField<Control: View>: View {
    let label: String
    let hint: String?
    let optional: Bool
    let error: String?
    let control: Control

    public init(_ label: String, hint: String? = nil, optional: Bool = false, error: String? = nil, @ViewBuilder control: () -> Control) {
        self.label = label
        self.hint = hint
        self.optional = optional
        self.error = error
        self.control = control()
    }

    public var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(spacing: 4) {
                Text(label).duiFont(15, weight: .medium, relativeTo: .subheadline)
                if optional {
                    Text("optional").duiFont(15, relativeTo: .subheadline).foregroundStyle(Color.duiGrey600)
                }
            }
            .accessibilityElement(children: .combine)
            if let hint {
                Text(hint).duiText(.meta).fixedSize(horizontal: false, vertical: true)
            }
            control
            if let error {
                DUIFieldError(error)
            }
        }
    }
}

/// A field's error: black, bold, with a small "!" disc. Say which field and how to fix it.
public struct DUIFieldError: View {
    let message: String

    public init(_ message: String) {
        self.message = message
    }

    public var body: some View {
        HStack(alignment: .firstTextBaseline, spacing: 8) {
            DUIAlertMark()
            Text(message)
                .duiFont(14, weight: .semibold, relativeTo: .footnote)
                .foregroundStyle(Color.duiBlack)
                .fixedSize(horizontal: false, vertical: true)
        }
        .accessibilityElement(children: .combine)
        .accessibilityLabel("Error: \(message)")
    }
}

/// The small black "!" disc.
public struct DUIAlertMark: View {
    public init() {}

    public var body: some View {
        Text("!")
            .font(DUIFont.fixed(10, weight: .bold))
            .foregroundStyle(Color.duiWhite)
            .frame(width: 16, height: 16)
            .background(Circle().fill(Color.duiBlack))
            .alignmentGuide(.firstTextBaseline) { $0[VerticalAlignment.center] + 4 }
            .accessibilityHidden(true)
    }
}

/// At the top of a form after a failed submit: how many things to fix, then each field by name.
public struct DUIFormProblems: View {
    let problems: [String]
    let select: ((Int) -> Void)?

    /// - Parameter select: called with the problem's index when someone taps it, to focus that field.
    public init(_ problems: [String], select: ((Int) -> Void)? = nil) {
        self.problems = problems
        self.select = select
    }

    public var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                DUIAlertMark()
                Text(problems.count == 1 ? "Fix 1 thing to carry on" : "Fix \(problems.count) things to carry on")
                    .duiFont(16, weight: .semibold, relativeTo: .callout)
            }
            ForEach(Array(problems.enumerated()), id: \.offset) { index, problem in
                if let select {
                    Button(problem) { select(index) }
                        .buttonStyle(.plain)
                        .duiFont(14, weight: .semibold, relativeTo: .footnote)
                        .underline()
                        .multilineTextAlignment(.leading)
                } else {
                    Text(problem).duiFont(14, relativeTo: .footnote)
                }
            }
            .padding(.leading, 24)
        }
        .foregroundStyle(Color.duiBlack)
        .padding(.horizontal, 20)
        .padding(.vertical, 16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(RoundedRectangle(cornerRadius: DUIRadius.panel, style: .continuous).fill(Color.duiGrey100))
        .accessibilityElement(children: .contain)
    }
}

/// A tick box in black. Consent boxes always start unticked, one per purpose.
public struct DUICheckboxStyle: ToggleStyle {
    public init() {}

    public func makeBody(configuration: Configuration) -> some View {
        Button {
            configuration.isOn.toggle()
        } label: {
            HStack(alignment: .top, spacing: 12) {
                RoundedRectangle(cornerRadius: 5, style: .continuous)
                    .fill(configuration.isOn ? Color.duiBlack : Color.duiWhite)
                    .overlay(
                        RoundedRectangle(cornerRadius: 5, style: .continuous)
                            .strokeBorder(configuration.isOn ? Color.duiBlack : Color.duiGrey400, lineWidth: 1.5)
                    )
                    .overlay {
                        if configuration.isOn {
                            Image(systemName: "checkmark")
                                .font(.system(size: 12, weight: .bold))
                                .foregroundStyle(Color.duiWhite)
                        }
                    }
                    .frame(width: 22, height: 22)
                    .padding(.top, 1)
                configuration.label
                    .duiFont(16, relativeTo: .callout)
                    .foregroundStyle(Color.duiBlack)
                    .multilineTextAlignment(.leading)
                    .fixedSize(horizontal: false, vertical: true)
                Spacer(minLength: 0)
            }
            .frame(minHeight: 44, alignment: .topLeading)
            .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .accessibilityRepresentation {
            Toggle(isOn: configuration.$isOn) { configuration.label }
        }
    }
}

public extension ToggleStyle where Self == DUICheckboxStyle {
    static var duiCheckbox: DUICheckboxStyle { DUICheckboxStyle() }
}

/// A choice shown as a card, for picking one of two to four. The picked one has a black edge.
public struct DUIChoice: View {
    let title: String
    let detail: String?
    let selected: Bool
    let action: () -> Void

    public init(_ title: String, detail: String? = nil, selected: Bool, action: @escaping () -> Void) {
        self.title = title
        self.detail = detail
        self.selected = selected
        self.action = action
    }

    public var body: some View {
        let shape = RoundedRectangle(cornerRadius: DUIRadius.panel, style: .continuous)
        Button(action: action) {
            HStack(alignment: .top, spacing: 12) {
                Circle()
                    .strokeBorder(selected ? Color.duiBlack : Color.duiGrey400, lineWidth: selected ? 6 : 1.5)
                    .frame(width: 20, height: 20)
                    .padding(.top, 2)
                VStack(alignment: .leading, spacing: 2) {
                    Text(title).duiFont(16, weight: .semibold, relativeTo: .callout)
                    if let detail {
                        Text(detail).duiText(.meta).fixedSize(horizontal: false, vertical: true)
                    }
                }
                Spacer(minLength: 0)
            }
            .foregroundStyle(Color.duiBlack)
            .padding(.horizontal, 18)
            .padding(.vertical, 16)
            .background(shape.fill(Color.duiWhite))
            .overlay(shape.strokeBorder(selected ? Color.duiBlack : Color.duiGrey200, lineWidth: selected ? 2 : 1))
            .contentShape(shape)
        }
        .buttonStyle(.plain)
        .accessibilityAddTraits(selected ? [.isButton, .isSelected] : .isButton)
    }
}
