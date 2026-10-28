import DistinctUI
import SwiftUI

/// A person's page: their mark big, one black pill, a build log, the help ask as the feature card.
public struct ProfileExample: View {
    @State private var following = false

    public init() {}

    private let maker = Maker.naledi
    private let log = [
        LogEntry(version: "v0.4", title: "Counts work offline", detail: "Answers the ask from Sibusiso, who tested it in Imbali.", when: "12 Oct"),
        LogEntry(version: "v0.3", title: "isiZulu voice input", detail: "Three shop owners helped with the word list.", when: "28 Sep"),
        LogEntry(version: "v0.2", title: "Low stock list", detail: nil, when: "9 Sep"),
        LogEntry(version: "v0.1", title: "First version", detail: nil, when: "21 Aug"),
    ]

    public var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: DUISpace.section) {
                top
                buildLog
                helpAsk
                backers
            }
            .duiScreen()
            .padding(.top, DUISpace.x4)
            .padding(.bottom, DUISpace.section)
        }
        .navigationTitle(maker.name)
        .navigationBarTitleDisplayMode(.inline)
    }

    private var top: some View {
        VStack(alignment: .leading, spacing: DUISpace.x5) {
            maker.mark(size: 128, live: false, version: "v0.4")
                .padding(.bottom, DUISpace.x2)
            DUITitle("Naledi is building", bold: "Spaza Stock")
            Text("Stock counts for spaza shops, by voice, in isiZulu and English.")
                .duiText(.lede)
                .fixedSize(horizontal: false, vertical: true)
            HStack(spacing: DUISpace.x4) {
                fact(nil, maker.city)
                fact("2", "projects")
                fact("14", "people tried it")
            }
            VStack(alignment: .leading, spacing: DUISpace.x3) {
                Button("Try Spaza Stock") {}
                    .buttonStyle(.dui(fullWidth: true))
                HStack(spacing: DUISpace.x3) {
                    Button(following ? "Following" : "Follow") { following.toggle() }
                        .buttonStyle(.dui(.secondary, fullWidth: true))
                    Button("Get in touch") {}
                        .buttonStyle(.duiLink)
                        .padding(.horizontal, DUISpace.x2)
                }
            }
            .padding(.top, DUISpace.x1)
        }
    }

    private func fact(_ number: String?, _ label: String) -> some View {
        HStack(spacing: 4) {
            if let number { Text(number).duiFont(15, weight: .semibold, relativeTo: .subheadline) }
            Text(label).duiFont(15, relativeTo: .subheadline).foregroundStyle(Color.duiGrey600)
        }
        .lineLimit(1)
    }

    private var buildLog: some View {
        VStack(alignment: .leading, spacing: DUISpace.x4) {
            DUITitle("Build", bold: "log", role: .h2)
            DUIRows {
                ForEach(log, id: \.version) { entry in
                    DUIRow {
                        HStack(alignment: .firstTextBaseline, spacing: DUISpace.x3) {
                            DUIBadge(entry.version, style: .soft)
                            VStack(alignment: .leading, spacing: 2) {
                                Text(entry.title).duiFont(16, weight: .semibold, relativeTo: .callout)
                                if let detail = entry.detail {
                                    Text(detail).duiText(.meta).fixedSize(horizontal: false, vertical: true)
                                }
                            }
                        }
                    } trailing: {
                        Text(entry.when).duiText(.meta).monospacedDigit()
                    }
                }
            }
        }
    }

    private var helpAsk: some View {
        VStack(alignment: .leading, spacing: DUISpace.x4) {
            DUIEyebrow("Help wanted")
            Text("Test it in a shop near you").duiText(.h3)
            Text("Count one shelf by voice and tell Naledi which words it got wrong. Takes ten minutes.")
                .duiText(.body, colour: .duiGrey800)
                .fixedSize(horizontal: false, vertical: true)
            Text("**Needs:** testers in KZN").duiNote()
            Button("I can help") {}
                .buttonStyle(.dui(size: .small))
        }
        .duiFeature()
    }

    private var backers: some View {
        VStack(alignment: .leading, spacing: DUISpace.x4) {
            DUITitle("Helped by", bold: "4 people", role: .h2)
            HStack(spacing: DUISpace.x3) {
                Maker.kagiso.mark(size: 52)
                DUIMark(.photo(Photo.thando), ring: 4, dot: DUIPalette.signal.ring(1), size: 52)
                Maker.lerato.mark(size: 52, live: false)
                DUIMark(.photo(Photo.ayanda), kind: .plain, size: 52)
                DUIMark.empty("+", size: 52)
            }
            Text("The dot means they've helped Naledi before.").duiText(.meta)
        }
    }
}

private struct LogEntry {
    let version: String
    let title: String
    let detail: String?
    let when: String
}

#Preview {
    NavigationStack { ProfileExample() }.distinctUI()
}
