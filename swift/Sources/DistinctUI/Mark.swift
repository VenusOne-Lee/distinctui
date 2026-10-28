import SwiftUI

/// The signature mark: the one place colour lives. A ring round a face, initials, or nothing yet.
/// Dashed until the thing it stands for is live, solid once it ships, with a small black version label.
///
///     DUIMark(initials: "NM", ring: 2, live: false, version: "v0.1")
///     DUIMark(photo: Image("naledi"), ring: 1, version: "v0.4", size: 120)
///     DUIMark.empty(size: 64)
public struct DUIMark: View {
    public enum Face {
        /// Nobody here yet: a grey disc, with an optional symbol or letter in grey.
        case empty(String?)
        case initials(String)
        case photo(Image)
        /// A photo from the web. Shows the initials while it loads and if it fails.
        case remote(URL?, initials: String)
    }

    public enum Kind: Sendable {
        /// A person or project with their own colour.
        case signature
        /// No signature of their own: a thin black ring.
        case plain
        /// A group, not a person: a black double ring.
        case group
    }

    let face: Face
    let kind: Kind
    let ring: Int?
    let colour: Color?
    let live: Bool
    let version: String?
    let dot: Color?
    let size: CGFloat

    @Environment(\.duiSignature) private var signature
    @Environment(\.duiPalette) private var palette

    /// - Parameters:
    ///   - ring: 1 to 5 from the palette set with `.distinctUI(palette:)`. Leave it out for the product's signature colour.
    ///   - colour: an exact colour instead, for a product with one signature.
    ///   - dot: an earned dot in someone else's colour, bottom right.
    public init(
        _ face: Face,
        kind: Kind = .signature,
        ring: Int? = nil,
        colour: Color? = nil,
        live: Bool = true,
        version: String? = nil,
        dot: Color? = nil,
        size: CGFloat = 64
    ) {
        self.face = face
        self.kind = kind
        self.ring = ring
        self.colour = colour
        self.live = live
        self.version = version
        self.dot = dot
        self.size = size
    }

    public init(initials: String, ring: Int? = nil, live: Bool = true, version: String? = nil, dot: Color? = nil, size: CGFloat = 64) {
        self.init(.initials(initials), ring: ring, live: live, version: version, dot: dot, size: size)
    }

    public init(photo: Image, ring: Int? = nil, live: Bool = true, version: String? = nil, dot: Color? = nil, size: CGFloat = 64) {
        self.init(.photo(photo), ring: ring, live: live, version: version, dot: dot, size: size)
    }

    /// An empty spot in a row of faces: a grey disc in a dashed grey ring.
    public static func empty(_ symbol: String? = nil, size: CGFloat = 64) -> DUIMark {
        DUIMark(.empty(symbol), size: size)
    }

    private var isEmpty: Bool {
        if case .empty = face { return true }
        return false
    }

    private var ringColour: Color {
        if isEmpty { return .duiGrey400 }
        switch kind {
        case .plain, .group: return .duiBlack
        case .signature: return colour ?? ring.map { palette.ring($0) } ?? signature
        }
    }

    private var lineWidth: CGFloat {
        switch kind {
        case .signature: size * 0.06
        case .plain: max(1.5, size * 0.035)
        case .group: max(4, size * 0.08)
        }
    }

    /// The white gap between the ring and the face.
    private var gap: CGFloat { size * 0.06 }

    private var dashed: Bool { isEmpty || (!live && kind == .signature) }

    /// Even dashes all the way round, more on bigger marks.
    private var dash: [CGFloat] {
        guard dashed else { return [] }
        let around = CGFloat.pi * (size - lineWidth)
        let count = max(8, Int(size / 5))
        return [around / CGFloat(count * 2)]
    }

    public var body: some View {
        ZStack {
            Circle().fill(Color.duiWhite)
            faceView
                .frame(width: inner, height: inner)
                .background(Circle().fill(Color.duiGrey100))
                .clipShape(Circle())
            ringView
        }
        .frame(width: size, height: size)
        .overlay(alignment: .bottomTrailing) {
            if let dot {
                let d = max(10, size * 0.2)
                Circle()
                    .fill(dot)
                    .frame(width: d, height: d)
                    .overlay(Circle().strokeBorder(Color.duiWhite, lineWidth: max(2, size * 0.025)))
            }
        }
        .overlay(alignment: .bottom) {
            if let version, !version.isEmpty {
                versionLabel(version)
                    .offset(y: size * 0.08)
            }
        }
        .accessibilityElement()
        .accessibilityLabel(accessibilityText)
    }

    private var inner: CGFloat { size - (lineWidth + gap) * 2 }

    @ViewBuilder
    private var ringView: some View {
        if kind == .group {
            // A double ring: two thin lines with a gap the same width between them.
            let third = lineWidth / 3
            ZStack {
                Circle().strokeBorder(ringColour, lineWidth: third)
                Circle().strokeBorder(ringColour, lineWidth: third).padding(third * 2)
            }
        } else {
            Circle().strokeBorder(ringColour, style: StrokeStyle(lineWidth: lineWidth, lineCap: .butt, dash: dash))
        }
    }

    @ViewBuilder
    private var faceView: some View {
        switch face {
        case .empty(let symbol):
            if let symbol { letters(symbol, colour: .duiGrey600) } else { Color.clear }
        case .initials(let text):
            letters(text, colour: .duiBlack)
        case .photo(let image):
            image.resizable().scaledToFill()
        case .remote(let url, let initials):
            AsyncImage(url: url) { phase in
                if let image = phase.image {
                    image.resizable().scaledToFill()
                } else {
                    letters(initials, colour: .duiBlack)
                }
            }
        }
    }

    private func letters(_ text: String, colour: Color) -> some View {
        Text(text)
            .font(DUIFont.fixed(size * 0.32, weight: .semibold))
            .tracking(-0.02 * size * 0.32)
            .foregroundStyle(colour)
            .lineLimit(1)
            .minimumScaleFactor(0.5)
            .padding(.horizontal, size * 0.04)
            .frame(maxWidth: .infinity, maxHeight: .infinity)
    }

    private func versionLabel(_ text: String) -> some View {
        let font = min(20, max(10, size * 0.12))
        return Text(text)
            .font(DUIFont.fixed(font, weight: .bold))
            .monospacedDigit()
            .foregroundStyle(Color.duiWhite)
            .lineLimit(1)
            .padding(.horizontal, font * 0.55)
            .padding(.vertical, font * 0.15)
            .background(Capsule().fill(Color.duiBlack))
            .overlay(Capsule().strokeBorder(Color.duiWhite, lineWidth: min(3, max(1.5, size * 0.03))))
            .fixedSize()
    }

    private var accessibilityText: String {
        var parts: [String] = []
        switch face {
        case .empty: parts.append("Empty spot")
        case .initials(let text), .remote(_, let text): parts.append(text)
        case .photo: break
        }
        if !isEmpty && kind == .signature { parts.append(live ? "live" : "not live yet") }
        if let version { parts.append(version) }
        return parts.joined(separator: ", ")
    }
}

/// The logo: a small ring in the signature colour, then the name in lower case.
public struct DUILogo: View {
    let name: String

    @Environment(\.duiSignature) private var signature

    public init(_ name: String) {
        self.name = name
    }

    public var body: some View {
        HStack(spacing: 8) {
            Circle()
                .strokeBorder(signature, lineWidth: 3.5)
                .frame(width: 20, height: 20)
            Text(name.lowercased())
                .font(DUIFont.fixed(20, weight: .bold))
                .tracking(-0.6)
                .foregroundStyle(Color.duiBlack)
        }
        .accessibilityElement(children: .combine)
    }
}
