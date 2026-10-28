import DistinctUI
import SwiftUI

/// Every building block on one screen: type, buttons, badges, marks, palettes, cards and notices.
public struct ComponentsExample: View {
    @State private var tab = 0
    @State private var ticked = false
    @State private var search = ""

    public init() {}

    public var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: DUISpace.section) {
                group("Type") {
                    DUITitle("Your product,", bold: "shipped", role: .hero)
                    DUITitle("Just", bold: "shipped", role: .h2)
                    Text("Card headline").duiText(.h3)
                    Text("A lede in grey-600, for the line under a title.").duiText(.lede)
                    Text("Body text in black, for reading.").duiText(.body)
                    Text("Meta: 12 Oct · Pietermaritzburg").duiText(.meta)
                }

                group("Buttons") {
                    HStack(spacing: DUISpace.x3) {
                        Button("Primary") {}.buttonStyle(.duiPrimary)
                        Button("Secondary") {}.buttonStyle(.duiSecondary)
                    }
                    HStack(spacing: DUISpace.x3) {
                        Button("Small") {}.buttonStyle(.dui(size: .small))
                        Button("Small outline") {}.buttonStyle(.dui(.secondary, size: .small))
                        Button("Link") {}.buttonStyle(.duiLink)
                    }
                    HStack(spacing: DUISpace.x3) {
                        Button("Inbox") {}.buttonStyle(.dui(.secondary, size: .small)).duiCount(3)
                        Button("Disabled") {}.buttonStyle(.duiPrimary).disabled(true)
                    }
                }

                group("Badges and tabs") {
                    HStack(spacing: DUISpace.x2) {
                        DUIBadge("Live")
                        DUIBadge("Featured", style: .solid)
                        DUIBadge("v0.3", style: .soft)
                    }
                    DUITabs(selection: $tab, options: [(value: 0, label: "All"), (value: 1, label: "Live"), (value: 2, label: "Beta")])
                }

                group("Signature mark") {
                    HStack(alignment: .top, spacing: DUISpace.x4) {
                        labelled("Empty") { DUIMark.empty(size: 64) }
                        labelled("Initials") { DUIMark(initials: "LJ", ring: 3, live: false, version: "v0.1") }
                        labelled("Photo") { DUIMark(photo: Photo.naledi, ring: 1, version: "v0.4") }
                        labelled("Dot") { DUIMark(photo: Photo.thando, ring: 4, dot: DUIPalette.signal.ring(2)) }
                    }
                    HStack(alignment: .top, spacing: DUISpace.x4) {
                        labelled("Plain") { DUIMark(.initials("AK"), kind: .plain) }
                        labelled("Group") { DUIMark(.initials("SA"), kind: .group) }
                        labelled("Dashed") { DUIMark(initials: "TM", live: false) }
                        labelled("Live") { DUIMark(initials: "TM", version: "v1.0") }
                    }
                    DUILogo("makers")
                }

                group("Palettes") {
                    ForEach(DUIPalette.allCases, id: \.self) { palette in
                        HStack(spacing: DUISpace.x3) {
                            ForEach(1...5, id: \.self) { ring in
                                DUIMark(.initials(""), colour: palette.ring(ring), size: 36)
                            }
                            Text(palette.name).duiText(.meta)
                        }
                    }
                }

                group("Cards") {
                    VStack(alignment: .leading, spacing: DUISpace.x2) {
                        Text("A card").duiText(.h4)
                        Text("White, a hairline, 22pt corners. Pressing turns the edge black.").duiText(.meta)
                    }
                    .duiCard()
                    VStack(alignment: .leading, spacing: DUISpace.x3) {
                        DUIEyebrow("This week")
                        Text("The feature card").duiText(.h3)
                        Text("**Needs:** testers in KZN").duiNote()
                    }
                    .duiFeature()
                    Text("A grey panel for tips and summaries.").duiText(.body).duiPanel()
                }

                group("Forms") {
                    DUIField("Search", hint: "One line on its own is a pill.") {
                        DUITextField("Search", text: $search, prompt: "Find a maker", shape: .pill)
                    }
                    DUIFieldError("Add your email address, like you@example.co.za.")
                    Toggle("Email me when someone offers to help", isOn: $ticked).toggleStyle(.duiCheckbox)
                }

                group("Notices") {
                    DUIStatus("You're signed in.")
                    DUIEmptyState("Nothing here yet. Your first version shows up here.", actionTitle: "Share a version") {}
                }
            }
            .duiScreen()
            .padding(.vertical, DUISpace.x6)
        }
        .navigationTitle("Components")
        .navigationBarTitleDisplayMode(.inline)
    }

    private func group<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: DUISpace.x4) {
            DUIEyebrow(title)
            content()
        }
    }

    private func labelled<Content: View>(_ label: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(spacing: DUISpace.x3) {
            content()
            Text(label).duiText(.meta)
        }
        .frame(minWidth: 64)
    }
}

#Preview {
    NavigationStack { ComponentsExample() }.distinctUI()
}
